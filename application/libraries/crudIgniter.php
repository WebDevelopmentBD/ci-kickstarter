<?php #defined('BASEPATH') or exit('No direct script access allowed'); 

/**
 * Class : crudIgniter
 * Database Create, Read, Update, Delete operator
 * @author : Abbas Uddin
 * @version : 1.1
 * @since : 10 November 2015
 */
class crudIgniter
{
	private $dblink, $cruds=array();

	function __construct( $dblink ){
		is_object($dblink) or die('invalid variable passed to class: crudIgniter');
		isset($dblink->dbdriver) or die('wrong reference passed to class: crudIgniter');
		$this->dblink = $dblink;

		//die('<pre>'.print_r($this->__get('`geo_countries`')->keyValue('iso, name'), 1));
	}

	//==Dynamically get an CRUD object
	function __get($tableName){
		#$this->dblink->table_exists($tableName) or die('You specified table '.$tableName.' that does not exist!');
		$tableName=str_replace('`','',$tableName);
		if(!is_numeric(strpos($tableName,'.')) && $this->dblink->database){
			$tableName=$this->dblink->database .'.'. $tableName;
		} 
		isset($this->cruds[ $tableName ]) or $this->cruds[ $tableName ] = new crudTable($tableName, $this->dblink);
		return $this->cruds[ $tableName ];
	}
}

//==Default operation of a Table
class crudTable
{
	private $table, $db, $schema, $cols=array();

	public function __construct($tablename, $dblink){
		$this->db   = $dblink;
        $this->table= $tablename;
		#$this->db->db_select($database2_name);
		#$this->cols = $this->db->list_fields($tablename);
		$this->schema = $this->colInfo( $tablename );
		$this->cols = & $this->schema['cols'];
		//die('<pre>'.print_r($this->schema, 1));
    }
	
	//==Fetch table metadata from DB Engine	
	private function colInfo( $t ){
		$shortname = substr($t, strrpos($t,'.')+1);
		$resp=array(
		  'total' => 0,
		  'legend'=> ucwords(str_replace('_',' ', $shortname)),
		  'cols'  => array(),
		  'types' => array(),
		  'size'  => array(),
		  'index' => array(),
		  'default'    => array(),
		  'attributes' => array()
		);
		
		//Find consistency
		if($q=$this->db->query('SHOW INDEX FROM '.$t.' WHERE `Non_unique`=0')) foreach($q->result_object() as $f)
		{
			if(!isset($resp['index'][ $k=strtolower($f->Key_name) ])) $resp['index'][ $k ]=$f->Column_name;
			else $resp['index'][ $k ] .= ','.$f->Column_name;
		}

		//Find entities
		if($q=$this->db->query('SHOW FULL FIELDS FROM '.$t)) foreach($q->result_object() as $f)
		{
			$resp['total']++;
			$resp['cols'][] = $f->Field;
			$resp['default'][]=$f->Default=='CURRENT_TIMESTAMP'? date('Y-m-d H:i:s'):$f->Default;
			if(isset($_GET['verbose'])) $resp['attributes'][ $f->Field ]=$f;
			list($etype, $limit) = $this->typeHandler( $f->Type );
			$resp['types'][ $f->Field ] = $etype;
			if( $limit ) $resp['size'][ $f->Field ] = $limit;
		}
		return $resp;
	}

	//@return DataType & Size allocation
	private function typeHandler( $entype ){
		$entype=explode(' ',$entype);
		$t = strpos($entype[ 0 ],'(');
		if(is_numeric( $t )) return array(
			substr($entype[ 0 ], 0, $t),
			substr($entype[ 0 ], $t+1, strlen($entype[ 0 ])-$t-2)
		);
		return array(trim($entype[ 0 ]), NULL);
	}

	
	private function keyfilter($data){
		return array_intersect_key($data, array_flip($this->cols));
	}
	
	public function getColumns(){ return $this->cols; }

	//HTML Table Heading
	public function getThead(){
		$th = array();
		foreach($this->cols as $col) $th[] = '  <th>'.ucwords(str_replace(array('-','_','.'),' ',$col)).'</th>';
		return "<tr>\n".join(PHP_EOL, $th)."\n</tr>";
	}

	/**
	* RUN SQL on database
	*/
	public function query($sql, $binds=FALSE, $return_object=NULL){
		return $this->db->query($sql, $binds, $return_object);
	}

	/**
	* Count available rows on a table
	*/
	public function count($sql_cond=1){
		$q=$this->db->select("COUNT(*) AS `total`")->from($this->table)->where($sql_cond)->get();
		return $q->num_rows()>0? $q->row()->total:0;
	}

	/**
	* Count available rows on a table
	*/
	public function countUnique($field, $sql_cond=1){
		$q=$this->db->select("COUNT(DISTINCT $field) AS `total`")->from($this->table)->where($sql_cond)->get();
		return $q->num_rows()>0? $q->row()->total:0;
	}

	/**
	* Sum a Field Value
	*/
	public function sum($field, $sql_cond=1){
		$q=$this->db->select("SUM($field) AS `total`")->from($this->table)->where($sql_cond)->get();
		return $q->num_rows()>0? $q->row()->total:0;
	}

	/**
	* Get a Field Value
	*/
	public function get($field, $sql_cond=1){
		$q=$this->db->select("$field AS `data`")->from($this->table)->where($sql_cond)->get();
		return $q->num_rows()>0? $q->row()->data:NULL;
	}

	/**
	* Fetch a single row from database
	*/
	public function record($sql_cond=1){
		$q=$this->db->from( $this->table )->where($sql_cond)->get();
		return $q->num_rows()>0? $q->row():new stdClass;
	}

	/**
	* Get some records from database
	*/
	public function records($sql_cond=1){
		$q=$this->db->query("SELECT * FROM $this->table WHERE ".$sql_cond);
		return $q->result_object();
	}

	/**
	* Get table records by pagination
	*
	* @param integer $no Page No
	* @param integer $limit Records per Page
	* @param string $cond Condition apply
	* @return array recordset
	*/
	function page($no=1, $limit=100, $cond=1){
		$index=$no>1? abs($no-1)*$limit:0;
		$q=$this->db->from( $this->table )->where( $sql_cond )->limit($index, $limit)->get();
		return $q->result_object();
	}


	/**
	* Get $key => $value PAIR from database.table
	*
	* @param array $select MySQL field names
	* @param string $cond MySQL WHERE condition.
	* @return array $key => $value
	*/
	public function keyValue($select=array(), $cond=1){
		if(is_string( $select )) $select=array_filter(explode(',',str_replace(' ','',$select)));
		if(empty( $select )) $select=array($this->cols[ 0 ]);

		$query = $this->db->query(sprintf('SELECT DISTINCT %s FROM %s WHERE %s', join(',',$select), $this->table, $cond));
		if($query->num_rows() < 1) return array();#response blank

		$output = array();
		if($query->num_fields() > 1) while($row=$query->unbuffered_row('array'))
		{
			$output[ reset($row) ] = next($row);
		}
		else while($row=$query->unbuffered_row('array'))
		{
			$output[]= reset($row);
		}
		$query->free_result();
		return $output;
	}


	/**
	* Put some value on database
	*/
	public function insert($rowset=array()){
		settype($rowset, 'array');
		return $this->db->insert($this->table, $this->keyfilter($rowset));
	}

	/**
	* Put some value on database
	*/
	public function replace($rowset=array()){
		settype($rowset, 'array');
		return $this->db->replace($this->table, $this->keyfilter($rowset));
	}
	
	/**
	* Update some rowset on database
	*/
	public function update($cond, $rowset=array()){
		settype($rowset, 'array');
		return $this->db->update($this->table, $this->keyfilter($rowset), $cond);
	}
	
	/**
	* Remove some rowset on database
	*/
	public function delete( $cond ){
		return $this->db->query(sprintf('DELETE FROM '.$this->table.PHP_EOL.'WHERE '.$cond));
		#return $this->db->delete('mytable', $cond);
	}
	
	/**
	* Lock table for READ/WRITE
	*/
	public function lock($rw=1){ return $this->db->query('LOCK TABLES '.$this->table.($rw? ' WRITE':' READ')); }
	public function unlock(){ return $this->db->query('UNLOCK TABLES'); }

}