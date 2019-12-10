<?php
/**
* CRUD	special table(s) manipulation CLASS
* 
* 
* @package    TinyERP
* @subpackage Classesi
* @author     Abbas Uddin <abbasuddin@outlook.com>
*/
class schema extends db
{
	
	const SPACE='&nbsp; &nbsp; &nbsp; &nbsp;', Charset='utf8', GMToffset='+06:00', version=1.0;//=====version of each update

	static $verbose=FALSE, $dbselect=array();#some pre-config

	public $legend="Fieldset Info", $label=array(), $fetch_limit=1000;
	public $status=NULL, $dateReverse=TRUE, $ready=FALSE, $info=array(), $cols=array(), $fprefix=NULL;
	public $upCol, $setAttr=array();
	
	private $options = array(//pre-defined-configuration
		'html_table' => array('editor'=>NULL, 'sn'=>TRUE),
		'auto_form' => array('legends'=>array())
	);
	private $systemDB = array('sys', 'mysql', 'performance_schema', 'information_schema');

	private $dblist=array(), $tables=array(), $views=array(), $fullname, $joined=array(), $data=array(), $primary=array(), $unique=array(), $increment=array(), $focused=FALSE;
	private $fieldClass=array(), $fieldHide=array(), $fieldSkip=array(), $fieldRequired=array(), $fieldOvrride=array(), $fileBase;

	//__MAIN__ Entry Point
	function __construct($dblink, $charset='utf8', $GMToffset=NULL){ //can not be overriden
		if( !$dblink ) exit('Database connection is not valid');

		if(is_string( $dblink )){
			$this->dbName=$dblink;
			$dblink=iConnect( $dblink );
		}

		//Linkup to the SERVER
		parent::__construct($this->resLink=$dblink);
		try{
		  $this->SQLmode("");
		  $this->charset( $charset );
		  if(is_null( $GMToffset )){
			  $this->gmtOffset( defined('GMToffset')? GMToffset:self::GMToffset );
		  }
		  else { $this->gmtOffset($GMToffset); }
		}
		catch(Exception $e){ error_log($e->getMessage().' Violation'); }
		
		//List available database(s)
		$q=$this->query('SHOW SCHEMAS');
		if( $q ){
			while($row=$this->fetchRow( $q )) if(!in_array($row[0], $this->systemDB)) $this->dblist[]=$row[0];
			$this->free( $q );
			unset($q, $row);
		}

		//List available tables(s)
		foreach($this->dblist as $db)
		{
			if(!empty(self::$dbselect) && !in_array($db, self::$dbselect)) continue;
			foreach(parent::listTables($db) as $table)
			{
			  $this->tables[ $db .'.'. $table ] = $this->colInfo($db.'.'.$table);
			}
		}

		//List available view(s)
		foreach($this->dblist as $db)
		{
			if(!empty(self::$dbselect) && !in_array($db, self::$dbselect)) continue;
			foreach(parent::listViews($db) as $table)
			{
			  $this->views[ $db .'.'. $table ] = array();
			}
		}

		#$this->legend	= ucwords(str_replace('_',' ',$table));

		//Get the actual table name
		#if(!$this->fullname) $this->fullname=sprintf('`%s`.`%s`', $this->dbName, $table);

		/*foreach($this->info as $i=>$tf)
		{
			$this->data[$tf->Field]=strlen($tf->Default)<1? ($tf->Null=='YES')? NULL:'':$tf->Default;
			if($tf->Key=='PRI') $this->primary[]=$tf->Field;
			if( !empty($tf->Extra) ){
				if($tf->Extra=='auto_increment') $this->increment[]=$tf->Field;
				if(substr($tf->Extra,0,9)=='on update') $this->autoUP[]=$tf->Field;
			}
			//creating extra field to check DATA-TYPE
			$t=explode('(',$tf->Type);
			$this->info[$i]->{'dtype'}=strtoupper($t[0]);
			$this->cols[]=$tf->Field;//store-to-check
		}
		$this->fieldSkip=$this->autoUP;
		#error_log(print_r($this->fullname, 1));
		*/
	}
	
	private function colInfo( $t ){
		$shortname = substr($t, strrpos($t,'.')+1);
		$resp=array(
		  'total'=> 0,
		  'legend'=> ucwords(str_replace('_',' ', $shortname)),
		  'cols' => array(),
		  'types' => array(),
		  'size' => array(),
		  'index' => array(),
		  'default' => array(),
		  'attributes' => array()
		);
		
		//Find consistency
		if($q=$this->query('SHOW INDEX FROM '.$t.' WHERE `Non_unique`=0')){
			while($f=$this->fetch($q))
			{
				if(!isset($resp['index'][ $k=strtolower($f->Key_name) ])) $resp['index'][ $k ]=$f->Column_name;
				else $resp['index'][ $k ] .= ','.$f->Column_name;
			}
			$this->free( $q );
		}

		//Find entities
		if($q=$this->query('SHOW FULL FIELDS FROM '.$t)){
			while($f=$this->fetch($q))
			{
				$resp['total']++;
				$resp['cols'][] = $f->Field;
				$resp['default'][]=$f->Default=='CURRENT_TIMESTAMP'? date('Y-m-d H:i:s'):$f->Default;
				if(isset($_GET['verbose'])) $resp['attributes'][ $f->Field ]=$f;
				list($etype, $limit) = $this->typeHandler( $f->Type );
				$resp['types'][ $f->Field ] = $etype;
				if( $limit ) $resp['size'][ $f->Field ] = $limit;
			}
			$this->free( $q );
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
	
	public function output(){
		return array(
			'databases' => $this->dblist,
			'tables' => $this->tables,
			'views' => $this->views
		);
	}

	public function autoCast($ckey){
		foreach($this->casttypes as $cast=>$fname) if(in_array($ckey, $fname)){ return $cast; }
		return FALSE;
	}

	public function logdbError($label='DB Engine'){
		if($this->error()) error_log(sprintf('%s %u: %s', $label, $this->error(), $this->lastError()));
		return $this->error();
	}

	private function tableString( $str ){
		$target=explode('.',str_replace('`','',trim($str)));
		if(count($target)==2){
			list($this->dbName, $table)=$target;
			$this->fullname=sprintf('`%s`.`%s`', $this->dbName, $table);
			return $table;
		}return $target[0];
	}


/** End construct method ******************************************/

	//public function escape($str){ return parent::escape( $str ); }
	public function refresh(){ return parent::runSQL('FLUSH TABLES '.$this->fullname); }
	public function repair(){ return parent::repairTable( $this->fullname ); }
	public function showIndex(){ return parent::fetchQuery('SHOW INDEX FROM '.$this->fullname); }

	#Lock the table for READ/WRITE $rw=1 Write, $rw=0 Read;
	public function lock($rw=1){ return parent::runSQL('LOCK TABLES '.$this->fullname.($rw? ' WRITE':' READ')); }
	public function unlock(){ return parent::runSQL('UNLOCK TABLES'); }

	/*** Print text or return bool on table update***/
	public function onChange($showTxt=NULL){
		$changed = $this->_oninsert || $this->_onupdate;
		if($changed && $showTxt) echo $showTxt;
		return $changed;
	}

	private function URI(){
		$uri=array();
		foreach($this->primary as $i) $uri[]=isset($_REQUEST[$i])? $i.'='.urlencode($_REQUEST[$i]):NULL;
		return count($uri)>0? '?'.join('&', $uri):NULL;
	}
	private function identifyTYPE($i){
		foreach(CRUD_STATIC::$dataTypes as $type=>$intext) if(in_array($this->fieldClass[$i],$intext))
		{
			return $type;
		}
		return NULL;		
	}
	private function identifyINPUT($col, $v, $i=NULL){
		$type=$this->identifyTYPE($col);
		#isset(CRUD_STATIC::$classNames[$type]) or error_log(sprintf('Datatype of `%s`.`%s` undefined', $this->table, $col));
		$attr=array(
			'name'	=> $this->fprefix.$col,
			'value'	=> $v,
			'type'	=> 'text',
			'class'	=> CRUD_STATIC::$classNames[$type].' crud',
			'placeholder'=> $this->info[$col]->Comment
		);
		$reqr=(in_array($col, $this->fieldRequired) || $this->info[$col]->{'Null'}=='NO');
		if($i) $attr['tabindex']=$i;
		if($reqr) $attr['required']='required';
		if( $this->focused ){
			$attr['autofocus']='autofocus';
			$this->focused=FALSE;
		}
		$imp = $reqr? '<sup style="color:#F93034;">*</sup>':NULL;
		if(isset( $this->setAttr[$col] )){
			$attr=array_merge($attr,$this->setAttr[$col]);
			$this->setAttr[$col]=array();//reset
		}
		switch( $type )
		{
			case 'text'://single-line
				$attr['type']='text';
				return '<input '.$this->attr($attr).' />'.$imp;
			break;
			case 'date'://date-time
				$attr['type']='text';
				$attr['size']=11;
				$attr['maxlength']=10;
				if($this->dateReverse){ $attr['value']=reverseDate($v); }
				return '<input '.$this->attr($attr).' />'.$imp.
						' <font color="#565555" size="-1" title="Date Format"><em>dd-mm-YYYY</em></font>';
			break;
			case 'textarea'://multi-line
				unset($attr['value'],$attr['type']);
				$attr['rows']=3;
				return '<textarea '.$this->attr($attr).'>'.$v.'</textarea>'.$imp;
			break;
			case 'set'://enum can be null if not-null
				unset($attr['value'],$attr['type']);
				$options=array();
				$selection=eval(
					(string) 'return '.str_replace(array('enum','set'),'array',$this->info[$col]->Type).';'
				);
				if($this->info[$col]->dtype=='SET'){
					$allv=(array)self::keymap($v);
					foreach($selection as $opt){
					  $on=in_array($opt, $allv)? ' checked="checked"':NULL;
					  $options[]=sprintf('<label class="border" style="white-space:nowrap;"><input name="'.$col.'[]" value="%s" type="checkbox"%s><span class="label">%s</span></label>', $opt, $on, ucfirst($opt));
					}
					return join(PHP_EOL, $options);
				}else{
					if( count($selection)<3 ){
					 // if($this->info[$col]->{'Null'}=='YES') $options[]="\t\t".'<option value="">N/A</option>';
					 	$allv=(array)self::keymap( $v );
						foreach($selection as $opt){
						  $on=in_array($opt, $allv)? ' checked="checked"':NULL;
						  $options[]=sprintf('<label class="border" style="white-space:nowrap;"><input name="'.$col.'" value="%s" type="radio"%s><span class="label">%s</span></label>', $opt, $on, ucfirst($opt));
						}
						return join(PHP_EOL, $options);
					}else{
					  if($this->info[$col]->{'Null'}=='YES') $options[]="\t\t".'<option value="">N/A</option>';
					  foreach($selection as $opt){
						$on=($v==$opt)? ' selected="selected"':NULL;
						$options[]=sprintf("\t\t".'<option value="%s"%s>%s</option>', $opt, $on, ucfirst($opt));
					  }
					}
				}
				return '<select '.$this->attr($attr).'>'.PHP_EOL.join(PHP_EOL, $options)."</select>$imp\r\n\t  ";
			break;
			default: return '<input '.$this->attr($attr).' />'.$imp;
		}
		return NULL;
	}

  /**
   * Get the file name
   *@param $baseFile set new base file.
   *@return string current base file.
   */
	public function base($baseFile=NULL){
		if( is_null($baseFile) ) return $this->fileBase;
		else return $this->fileBase=$baseFile;
	}

  /**
   * Don't printed selected columns
   * @param array $fields
   */
	function hideFields($fields){
		if(is_string($fields)) $fields=self::keymap($fields);
		$this->fieldHide=$fields;
	}

  /**
   * Escape some Inputs
   * @return HTML_FORM
   */
	function skipFields($fields){
		if(is_string($fields)) $fields=self::keymap($fields);
		$this->fieldSkip=array_unique(array_merge($fields,$this->autoUP));
	}

  /**
   * Required Inputs
   * @return n/a
   */
	function required($fields){
		if(is_string($fields)) $fields=self::keymap($fields);
		$this->fieldRequired=$fields;
	}

  /**
   * Set some static Input values.
   * @return n/a
   */
	function override($fvalue){
		if(!is_array($fvalue)) exit('override() must have array arguments');
		$this->fieldOvrride=$fvalue;
	}

  /**
   * [Alias of override] Set some static Input values.
   * @return n/a
   */
	function staticValue($fvalue){ $this->override($fvalue); }

  /**
   * Show a form field and it's default value
   * @return string [HTML Input Object]
   */
	public function autoField($field, $attr=array(), $label=FALSE){

		settype($field, 'string');//confirm textual

		if( !isset($this->info[$field]) ){
			print('<!--[invalid]-->@'.$field);
			return NULL;//Not existed field
		}
		if(in_array($field,$this->fieldSkip)){
			print('<!-- Skipped@'.$field.' -->');
			return NULL;//Not needed field
		}

		$PK = $this->primaryKey();
		$upKy = $this->autoID() || $this->primaryKey();

		$_value = $this->data[$field];//default value
		#$options = $this->options['auto_form'];

		if( $this->hasValue() ){//While updating records
		  if(!empty($this->upCol) && isset($_REQUEST[$this->upCol])){
			$cond="`$this->upCol`=".$this->identify($_REQUEST[$this->upCol]);
		  }
		  else{ $cond=$PK.'='.$this->identify( $_REQUEST[$PK] ); }

		  $_value = $this->get($field, $cond);//existing value
		}
		
		if(//==replace value
		  array_key_exists($field, $this->fieldOvrride)
		){ $_value = $this->fieldOvrride[$field]; }

		in_array($field,$this->primary) && $attr['type']='hidden';
		array_key_exists($field,$this->fieldHide) && $attr['type']='hidden';
		//$attr['value']=$value;
		
		//==setup field class
		$this->fieldClass[$field] = $this->info[ $field ]->dtype;

		//$this->override($fvalue);
		$this->setAttr[$field]=$attr;
		$input=$this->identifyINPUT($field, $_value);

		if( $label ){
			$text = isset($this->label[$field])? $this->label[$field]:ucwords(str_replace('_',' ',$field));
			printf('<label for="%s">%s %s</label>', $field, strlen($label>0)? $text:$label, $input);
			return TRUE;
		}
		echo $input;
		return TRUE;
	}


  /**
   * Generate Input Elements using MySQL `Table`
   *
   * @return HTML_FORM
   */
	public function autoFORM(){
		//print_r($this->data);
		//print_r($this->info);
		$c = 1; $spliter = "\n\t  <td>:</td>\n\t  ";
		$PK= $this->primaryKey();
		$upKy = $this->autoID()? $this->autoID():$PK;
		$onUp = $this->hasValue();
		//__DO::dump($upKy);
		$formData= $this->data;
		$options = $this->options['auto_form'];

		if( $onUp ){//While updating the table
		  if(!empty($this->upCol) && isset($_REQUEST[$this->upCol])){
			$cond="`$this->upCol`=".$this->identify($this->escape($_REQUEST[$this->upCol]));
		  }else{
			$cond=array();
			foreach($this->primary as $k) $cond[]="`$k`=".$this->identify( $this->escape($_REQUEST[$k]) );
			$cond= empty($cond)? 1:join(' AND ', $cond);
		  }

		  $record=$this->record( $cond );
		  if($record) foreach($formData as $k=>$v) if(isset($record->{$k}))
		  {
			  $formData[$k]=$record->{$k};
		  }
		  foreach($this->primary as $PRI) if($_GET[$PRI] && isset($formData[$PRI]))
		  {
			  unset( $formData[$PRI] );
		  }
		}
		
		foreach($this->fieldOvrride as $i=>$w) if(array_key_exists($i,$formData))
		{
			$formData[$i]=$w;//when applicable
		}

		$txt = "\t".'<form action="'.$this->fileBase.($onUp? $this->URI():NULL).'" name="'.strtoupper($this->table).'" id="'.$this->table.'" method="post" enctype="multipart/form-data">'.PHP_EOL;
		
		if($upKy && $onUp) foreach($this->primary as $k){
			if( isset($_GET[$k]) ) continue;
			$txt .= "\t  ".'<input type="hidden" name="'.$k.'" value="" />'.PHP_EOL;
		}
		if(count($this->fieldHide)>0) foreach($this->fieldHide as $ff) 
		{
			if(isset( $formData[$ff] )) $txt .= "\t  ".'<input name="'.$ff.'" value="'.(
						isset($formData[$ff])? $formData[$ff]:NULL
					).'" type="hidden" />'.PHP_EOL;
			unset( $formData[$ff] );
		}
		$txt .= "\t  ".'<fieldset><legend>'.$this->legend.'</legend>'.PHP_EOL."\t".
			'<table cellpadding="2" class="aform '.$this->table.'" align="center" border="0"><tbody>'.PHP_EOL;
		//$txt.="\t".'<tr bgcolor="#E5E5E5"><th colspan="3">'.$this->legend.' Entry Form</th></tr>'.PHP_EOL;
		
		$this->focused=TRUE;//==Make it ready.

		foreach($this->info as $f){//Class Name Manipulate
			$this->fieldClass[$f->Field] = $f->dtype;
		}

		foreach($formData as $k=>$v)
		{
		  if(in_array($k,$this->fieldSkip)){ continue; }
		  if(!$onUp && $k==$this->autoID()){ continue; }
		  else{
			  if(in_array($k,$this->primary) || in_array($k,$this->increment)) continue;
		  }

		  if( isset($options['legends'][$k]) ){//show legend before
			  $txt.="\t<tr>\r\n\t  <td nowrap></td>\r\n\t</tr>".PHP_EOL;
			  $txt.="\t<tr>\r\n\t  <td class='legend' colspan='3' nowrap>".$options['legends'][$k]."</td>\r\n\t</tr>".PHP_EOL;
		  }
		  $label = isset($this->label[$k])? $this->label[$k]:ucwords(str_replace('_',' ',$k));
		  //======VISIBLE Items===============
		  $txt.="\t<tr>\r\n\t  ".'<td nowrap><label for="'.$this->fprefix.$k.'">'.$label.'</label></td>'.$spliter.'<td>'.
		  		$this->identifyINPUT($k, $v, $c++).
				"</td>\r\n\t</tr>".PHP_EOL;
		}

		$txt.="\t</tbody></table></fieldset>".PHP_EOL;
		$txt.="\t".'<p class="afinal" align="center">'.PHP_EOL.
			'<input name="do'.($onUp? 'Update':'Insert').'" value="'.($onUp? 'UPDATE':'Save').'" class="save" style="background-color:rgb(47,195,138);color:#FFF;text-shadow:1px 1px 1px #2C2C2C;cursor:pointer;" tabindex="'.$c++.'" type="submit" />'.
			PHP_EOL."\t".self::SPACE.PHP_EOL.
			'<input onClick="try{self.close();}catch(e){}" value="Cancel" style="background-color:#F0EFE9;cursor:pointer;" type="reset" />'.PHP_EOL."\t</p>".PHP_EOL."</form>".PHP_EOL;
		
		/***if( $onUp ){
		  $txt.= '<!--script language="javascript">'.PHP_EOL.
		  '  var '.$this->table.'_fields='.json_encode($record).';'.PHP_EOL.
		  '  var f=document.forms["'.strtoupper($this->table).'"];'.PHP_EOL.
		  '  for(var i=0; i<f.length; i++) if(f[i].name && f[i].name in '.$this->table.'_fields) try{'.PHP_EOL.
		  '	f[i].value='.$this->table.'_fields[ f[i].name ];'.PHP_EOL.
		  '  }catch(e){console.log(e);}'.PHP_EOL.
		  '</script-->'.PHP_EOL;
		}**/
		return $txt;
	}

	public function debugWrite($v, $prepend="Result "){
		printf('<pre style="color:#000;background:#FFF;">%s</pre>', $prepend.print_r($v, TRUE));
	}

  /**
   * Get COLUMN Data-Type e.g. VARCHAR, INTEGER, DATE, etc.
   * @return string
   */
	public function dataType($col){ return $this->info[$col]->{'dtype'}; }

  /**
   * Check for PRIMARY KEY(s) in $_GET/$_POST
   * If request has primary key return TRUE
   * @return boolean
   */
	function isOK(){
		if(!is_object($this->status)) return FALSE;
		return $this->status->{'Table'};
	}

  /**
   * Get the Table Name
   * @return string
   */
	function tableName(){ return $this->table; }

  /**
   * Print HTML form[ attributes ]
   * e.g. <form name="" action="" />
   * @return string
   */
	function formName($ovAttr=array()){
		echo '<form '. $this->attr(array_merge(array(
			'id'	=> $this->table,
			'name'	=> strtoupper($this->table),
			'action'=> $this->fileBase.($this->hasValue()? $this->URI():NULL),
			'method'=> 'post',
			'accept-charset' => 'utf-8',
			'enctype'=>'application/x-www-form-urlencoded'
		),$ovAttr)).'>'.PHP_EOL;
		return TRUE;
	}
	
  /**
   * Print HTML input[ submit ]
   * e.g. <input name="doInsert" value="Save" />
   * @return string
   */
	function submitField($ovAttr=array()){
		unset($ovAttr['name'], $ovAttr['type']); $up=$this->hasValue();
		$css='color:#FFF;background-color:rgb(47,195,138);border-style:solid;';
		$css .= 'cursor:pointer;text-align:center;text-shadow:1px 1px 1px #2C2C2C;';
		echo '<input '. $this->attr(array_merge(array(
			'name'	=> ($up? self::UPDATE:self::INSERT),
			'value'	=> ($up? 'UPDATE':'Save'),
			'type'	=> 'submit',
			'class'	=> 'save',
			'style'	=> $css
		),$ovAttr)).' />'.PHP_EOL;

		echo '<input onClick="try{self.close();}catch(e){}" value="Cancel" class="form-reset" style="background-color:#F0EFE9;cursor:pointer;" type="reset" />'.PHP_EOL;

		return TRUE;
	}


   /**
   * Check for PRIMARY KEY(s) in $_GET/$_POST
   * If request has primary key return TRUE
   * @return boolean
   */
	function hasValue(){
	  //$keys=$this->primaryKey();
	  foreach($this->primary as $k) if(!isset( $_REQUEST[$k] ) || strlen($_REQUEST[$k])<1) return FALSE;
	  return TRUE;
	}
	
	function lastPrimary(){
		$keys=array();
		foreach($this->primary as $k){ $keys[]='MAX('.wrapQ($k).')'; }
		$keys=join(",",$keys);
		return $this->quickRow($this->query("SELECT $keys FROM $this->fullname LIMIT 1"));
	}

	function autoID(){
		$k=(join('-',$this->primary)==join('-',$this->increment));
		return $k? join('-', $this->primary):FALSE;
	}

	/**
	* Create a new Instance of this CRUD
	*
	* @param string $table-II
	* @return CRUD object
	*/
	function instance( $atable ){
		return new self( $atable, $this->resLink );
	}


	/**
	* Join another table using LEFT JOIN
	*
	* @param string $table-II
	* @param mixed MySQL ON() String or Array
	* @return object Child-class for fetch record
	*/
	function leftJoin($table2, $using=array()){
		return new CRUD_JOINER($this, $this->primary, array($table2), $using, 'LEFT');
	}

	/**
	* Join another table using INNER JOIN
	*/
	function innerJoin($table2, $using=array()){
		return new CRUD_JOINER($this, $this->primary, array($table2), $using, 'INNER');
	}

	/**
	* Count records from the Table
	*
	* @param string $field Table field name.
	* @param string $cond  MySQL conditions.
	* @return integer
	*/
	function rowCount($field='*', $cond=1, $unique=FALSE){
	  return $this->quickVar("SELECT COUNT(".($unique && $field!='*' ? 'DISTINCT '.$field:$field).") FROM $this->fullname WHERE ".$cond);
	}

	/**
	* Count specific unique rows
	*
	* @param string $field  SQL field name.
	* @param string $cond  MySQL conditions.
	* @return BIGINT unsigned
	*/
	function countUnique($field='*', $cond=1){
	  return sprintf('%u', $this->quickVar(
	    'SELECT COUNT('.($field=='*' ? $field:'DISTINCT '.$field).") FROM $this->fullname WHERE ".$cond
	  ));
	}

   /**
   * Check a data for existing on Database
   *
   * @param string $cond  MySQL WHERE condition.
   * @return integer
   */
	function exist($cond=1){ return $this->check($this->fullname, $cond); }

  /**
   * PRIMARY_KEYS as URI format
   * return NULL on blank primary key.
   * @return string
   */
	function primaryURL(){
		$vars=array_map("self::bulid_uri",array_keys($this->primary),array_values($this->primary));
		return empty($vars)? NULL:'?'.join('&',$vars);
	}

  /**
   * Get PRIMARY KEY(s)
   *
   * @param boolean $arr TRUE then return joined keys.
   * @return string
   */
	function primaryKey($arr=FALSE){ return $arr? $this->primary:join('-',$this->primary); }
	function incKey($arr=FALSE){ return $arr? $this->increment:join('-',$this->increment); }

  /**
   * Get Full Table Name e.g. MyDB.MyTable
   * @return string
   */
	function fullName(){ return $this->fullname; }

  /**
   * Update Records
   *
   * @param $data array([key]=>value)
   * @param string $cond 'MySQL WHERE condition'.
   * @return integer Affected_Rows
   */
	public function set($data, $cond){
		$this->_onset=$this->_onupdate=TRUE;
		if( empty($data) || is_string($data) || empty($cond) ) return FALSE;
		$data=Arrays::array_accept(is_object($data)? get_object_vars($data):(array)$data, $this->cols);//verify array inputs

		foreach($data as $k => $v) if($ct=CRUD_STATIC::phpCast( $this->info[$k]->dtype ))
		{//reCasting verify
			if(is_array( $v )) $data[$k]= $v =join(',',$v);
			if((is_null($v) || strlen((string)$v)==0) && $this->info[$k]->Null=='YES'){
			  @settype($data[$k], 'null');//is nulable
			}//else{ @settype($data[$k], $ct); }
			//date field
			if(in_array($this->info[$k]->dtype, CRUD_STATIC::$dataTypes['date'])){
				$data[$k]=Times::parse( $v );
				if($data[$k]=='0000-00-00' && $this->info[$k]->Null=='YES'){
					$data[$k]=NULL;
					@settype($data[$k], 'null');//is nulable
				}
			}
		}
		$resp=$this->update($this->fullname, $data, $cond);
		if($resp >= 0){
			$this->_lastupdate = $cond;
			return $resp;
		}
		return $this->_lastupdate=FALSE;
	}


  /**
   * Create a new Record
   * @param array $_POST or Key=>Value
   * @return integer on success FALSE on failure.
   */
	public function add($data=NULL, $replace=FALSE){
		if(is_array( $data )){ $data=Arrays::array_accept($data, $this->cols); }
		elseif(is_object( $data )){ $data=Arrays::array_accept(get_object_vars($data), $this->cols); }
		if(is_null( $data )){
		  $data=array();
		  foreach(Arrays::array_accept($_POST,$this->cols) as $k=>$v)
		  {
			if(in_array($k,$this->increment)) continue;
			if(is_array($v)) $v=join(",", $v);
			$data[$k] = $v;
		  }
		}
		$this->_oninsert=$this->_onadd=TRUE;
		$fields=array();
		foreach($data as $k => $v) if($ct=CRUD_STATIC::phpCast( $this->info[$k]->dtype ))
		{
			$fields[$k]=CRUD_STATIC::castedTemplate( $ct );//reCasting verify template
			if(is_null($v) && $this->info[$k]->Null=='YES'){
			  @settype($data[$k], 'null');//is nulable
			}
			if(in_array($this->info[$k]->dtype, CRUD_STATIC::$dataTypes['date'])){ $data[$k]=Times::parse( $v ); }
		}
		//error_log(print_r( $data, 1 ));
		$this->insertBind($this->fullname, $fields, array_values($data), $replace);
		//$query=$this->insert($this->fullname, $data, $replace); #var_dump( $data ) ;
		$this->savemsg=$this->error()? 'Fail to Save '.$this->lasterror():'Record has been saved successfully';
		return is_numeric($this->_lastid=$this->lastid())? $this->_lastid:$query;
	}

  /**
   * Minus a column value
   * @param $col field name
   * @param $cond MySQL Where condition
   * @return boolean on success FALSE on failure.
   */
	public function decrement($col, $cond){
		if(array_key_exists($col,$this->info)) return $this->runSQL(
			"UPDATE SET `$col`=`$col`-1 WHERE ".$cond
		);
		return FALSE;
	}

  /**
   * Plus a column value
   * @param $col field name
   * @param $cond MySQL Where condition
   * @return boolean on success FALSE on failure.
   */
	public function increment($col, $cond){
		if(array_key_exists($col,$this->info)) return $this->runSQL(
			"UPDATE SET `$col`=`$col`+1 WHERE ".$cond
		);
		return FALSE;
	}

  /**
   * Auto Update the self record
   *
   * @param string $post boolean.
   * @return int MySQL LAST_ID
   */
	public function autoUpdate($post=TRUE){
		$cond=0;
		##$pk=$this->primaryKey();
		if($this->hasValue()){
			$cond=array();
			foreach($this->primary as $k) $cond[]=parent::wrapQ($k).'='.$this->identify($this->escape( $_REQUEST[$k] ));
			$cond=join(' AND ', $cond);
		} 
		if(!empty($this->upCol) && isset($_REQUEST[ $this->upCol ])){
			$cond="`$this->upCol`=".$this->identify($this->escape( $_REQUEST[$this->upCol] ));
		}
		//echo '<pre>'.print_r($post,1).'</pre>';exit;
		$this->_lastid=$this->set($post===TRUE? $_POST:$post, $cond);
		$this->_onset=$this->_onupdate=TRUE;

		$_e=$this->logdbError('UPDATE "'.$this->fullname.'" error');
		$this->savemsg=$_e? 'Fail to Save '.$this->lasterror():'Record has been updated';

		if($this->_lastid >= 0){
		  $this->_lastupdate=$cond;
		  return $this->_lastid;
		}
		$this->_lastupdate=FALSE;
		return $this->_lastid;
	}

  /**
   * Automatically save records by POST
   * @return boolean
   */
	public function autoCommit($yes=TRUE){
		if( empty($_POST) ) return FALSE;
		if($this->_beupdate) $this->autoUpdate();
		elseif($this->_beinsert) $this->add();
		return TRUE;
	}

  /**
   * Auto create/update records by ARRAY
   * @return boolean
   */
	public function addEdit( $fields=array() ){
		if(!is_array($fields) || empty($fields)) return FALSE;
		$cols=array_keys($fields);
		//PRIMARY or UNIQUE
		$pri=Arrays::array_accept($cols, $this->primary);
		$uni=Arrays::array_accept($cols, $this->unique);
		if(!empty($pri) || !empty($uni)){
			$cond=array();
			foreach($pri as $pk){
				if(strlen($fields[$pk])<1){	unset($fields[$pk]); continue; }
				$cond[] = parent::wrapQ($pk).'='.parent::escape($fields[$pk]);
				unset( $fields[$pk] );
			}
			foreach($uni as $uk){
				if(strlen($fields[$uk])<1){	continue; }
				$cond[] = parent::wrapQ($uk).'='.parent::escape($fields[$uk]);
			}
			if(count($cond) > 0){
				$this->set($fields, join(' AND ',$cond));
				return TRUE;
			}
		}
		elseif( !empty($cols) ){
			$this->add( $fields );
			return TRUE;
		}
		return FALSE;
	}

  /**
   * Get message on $_POST submitted
   * @return string and exit
   */
	public function getMessage($reloadJS=FALSE){
		if($this->_onupdate || $this->_oninsert){
		  $uri=$this->_onupdate? $this->URI():$this->fileBase;
		  printf(
			'<script type="text/javascript">'.PHP_EOL.'%s'.PHP_EOL.'</script>',
			'  var wait=1;'.PHP_EOL.
			'  setTimeout(function(){try{'.PHP_EOL.($reloadJS?
			'  if(window.opener) window.opener.location.reload();':NULL).PHP_EOL.
			"  window.location='$uri';".PHP_EOL.
		    '  }catch(e){console.log(e);}}, wait*1000);'
		  );
		  exit('<body><center>'. $this->savemsg .'</center></body></html>');
		}
		return NULL;
	}

	function sum($cols, $cond=1){
		$cols = $this->strToCols($cols);
		$cols = array_map('self::sum_wrap',$cols);
		if(count($cols)>1) return $this->quickRow($this->query(
		  "SELECT ".join(', ',$cols)." FROM $this->fullname WHERE $cond"
		));
		return $this->quickVar("SELECT ".$cols[0]." FROM $this->fullname WHERE $cond");
	}

	/**
	* Response a SELECT QUERY
	* @return array (DB Row);
	*/
	function quickSelect($fieldRAW, $cond=1){
		$q=$this->query(sprintf('SELECT %s FROM %s WHERE %s LIMIT 1', $fieldRAW, $this->fullname, $cond));
		if( $q ){
			list($c, $row)=array($this->totalCol($q), $this->fetchRow($q), $this->free( $q ));
			return ($c > 1)? $row:$row[0];
		}return FALSE;
	}

	function average($col, $cond=1){
		return parent::avgCol($this->fullname, $col, $cond);
	}

	/* Fillup the HTML FORM using JavaScript*/
	function formFill($cond=1, $form=0, $callbak=NULL){
		$form=$this->identify( $form );
		$source = array();
		if(is_array($cond) || is_object($cond)){
			$source = $cond;
		}
		elseif($cond==1 && $this->hasValue()){
			$pri=array_intersect_key($_REQUEST, array_flip($this->primary));
			$cond=$this->toCondition( $pri );
			$source = $this->record( $cond );
		}
		$data  = '  var tikBox=function(rd,val){
			if(rd.length) for(var i=0;i<rd.length;i++) try{rd[i].checked=(rd[i].value==val);}catch(e){console.log(e);}
			else rd.checked=(rd.value==val);
		};'.PHP_EOL; 
		$data .= '  var __RECORD__='.json_encode( $source ).';'.PHP_EOL;
		$data .= '	if(typeof callback=="function") callback(__RECORD__);'.PHP_EOL;
		$data .= '  for(var i=0; i<f.length; i++) if(f[i] && f[i].name) try{'.PHP_EOL;
		$data .= '	if(f[i].name in __RECORD__) switch( f[i].type ){'.PHP_EOL;
		$data .= '	case "radio": tikBox(f[i], __RECORD__[ f[i].name ]); break;'.PHP_EOL;
		$data .= '	case "checkbox": tikBox(f[i], __RECORD__[ f[i].name ]); break;'.PHP_EOL;
		$data .= '	default:f[i].value=__RECORD__[f[i].name];'.PHP_EOL;
		$data .= '  }}catch(e){console.log(e);}'.PHP_EOL;
		printf("<script language='javascript' defer='defer'>(function(f, callback){\n  'use strict';\n%s})(document.forms[$form], %s);</script>", $data, $callbak? $callbak:'null');
	}


  /**
   * Get A Field Value
   *
   * @param string $name Field Name of the Table.
   * @param string $cond MySQL WHERE condition.
   * @return string
   */
	function get($name, $cond=1){
		return $this->quickVar("SELECT `$name` FROM $this->fullname WHERE $cond LIMIT 1");
	}

  /**
   * Get $key => $value
   *
   * @param array $select MySQL field names
   * @param string $cond MySQL WHERE condition.
   * @return array $key => $value
   */
	function keyValue($select=array(), $cond=1){
		if(is_string($select)) $select=self::keymap($select);
		$plen=count($select); $out=array();
		if($plen<1){ $select=array($this->cols[0], $this->cols[count($this->cols)>1? 1:0]); }
		if($plen>2){ $select[]=array_slice($select,0,2); }
		$q="SELECT DISTINCT ".join(',',$select)." FROM $this->fullname WHERE ".$cond;
		if( $res=$this->query($q) ){
			if(count($select)>1) while(list($k,$v)=$this->fetchRow($res)) $out[$k]=$v;
			else while(list($v)=$this->fetchRow($res)) $out[]=$v;
		}
		return $out;
	}

  /**
   * Get Maximum Value of the field
   *
   * @param string $field Field Name of the Table.
   * @param string $cond MySQL WHERE condition.
   * @return string
   */
	function getMax($field, $cond=1){
		return $this->quickVar("SELECT MAX(`$field`) FROM $this->fullname WHERE $cond LIMIT 1");
	}

  /**
   * Get Minimum & Maximum both values of the field
   *
   * @param string $field Field Name of the Table.
   * @param string $cond MySQL WHERE condition.
   * @return array
   */
	function getMaxMin($field, $cond=1){
		return $this->quickRow($this->query("SELECT MAX(`$field`), MAX(`$field`) FROM $this->fullname WHERE $cond",TRUE));
	}

  /**
   * Fetch a Record
   *
   * @param string $cond MySQL WHERE condition.
   * @param array $fields MySQL SELECT fileds i.e `id`,`name`,`status`.
   * @return object
   */
	function record($cond=NULL, $fields=array()){
		if(is_null( $cond )){
			$pri=array_intersect_key($_REQUEST, array_flip($this->primary));
			$cond=$this->hasValue()? $this->toCondition( $pri ):1;
		}
		if(empty( $fields )) $fields='*';
		if(is_array( $fields )) $fields=join(', ', $fields);
		return $this->getRecord($this->fullname, $cond, $fields);
	}

  /**
   * Fetch multiple Records
   *
   * @param string $cond 'MySQL WHERE condition'.
   * @return array(object)
   */
	function records($cond=1, $limit=1000){
		$rows=array();
		$q=$this->query("SELECT * FROM $this->fullname WHERE ".$cond.(empty($limit)? NULL:' LIMIT '.$limit));
		if( $q ){
			while($r=$this->fetch($q)) $rows[]=$r;
			$this->free( $q );
		}return $rows;
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
		$rows=array();
		$index=$no>1? abs($no-1)*$limit:0;
		$q=$this->query("SELECT * FROM $this->fullname WHERE $cond LIMIT $index,$limit");
		if( $q ){
			while($r=$this->fetch($q)) $rows[]=$r;
			$this->free( $q );
		}return $rows;
	}

  /**
   * Fetch multiple Records with RAW query
   *
   * @param string $fields 'Properly quoted or wraped'.
   * @param string $cond 'MySQL WHERE condition'.
   * @return array(object)
   */
	function recordSelect($fields, $cond=1){
		$rows=array();
		$q=$this->query("SELECT $fields FROM $this->fullname WHERE $cond");
		if($q){
			while($r=$this->fetch($q)) $rows[]=$r;
			$this->free( $q );
		}return $rows;
	}
	
  /**
   * Comma-seperated multiple records
   *
   * @param string $cond 'MySQL WHERE condition'.
   * @return string values
   */
	function recordsByComma($field, $cond=1){
		return parent::getValuesByComma($this->fullname, $field, $cond);
	}

  /**
   * Fetch by resource Records
   *
   * @param object 'MySQL SELECT fields'
   * @return array(object)
   */
	function fetchResource($query){
		$rows=array(); if( $query ){
			while($r=$this->fetch($query)) $rows[]=$r;
			$this->free( $query );
		}return $rows;
	}

  /**
   * Select fields from Table
   *
   * @param object 'MySQL Table `fields`'
   * @return object(Resource)
   */
	function select($arr=array(), $cond=1, $unique=array()){
		if(is_null($arr) || $arr=='*' || empty($arr)) return $this->query("SELECT * FROM $this->fullname WHERE $cond");
		if( !is_array($arr) ) $arr=self::keymap( $arr );
		if(is_array($unique) && count($unique)>0){
			foreach($arr as $i=>$v) $arr[$i]=in_array($v, $unique)? 'DISTINCT '.parent::wrapQ($v):parent::wrapQ($v);
		}
		elseif(is_string($unique)){
			return $this->query(
			  'SELECT DISTINCT `'.str_replace('.', '`.`', str_replace('`','',$unique)).'`, '.join(',', $arr).PHP_EOL.
			  "FROM $this->fullname WHERE ".$cond
			);
		}else{
			foreach($arr as $i=>$v) $arr[$i]=parent::wrapQ($v);
		}
		//echo "SELECT ".join(',', $arr)." FROM `$this->fullname` WHERE $cond";
		return $this->query("SELECT ".join(',', $arr)." FROM $this->fullname WHERE $cond");
	}

  /**
   * Generate auto HTML Table and flush output buffer.
   *
   * @param mixed $fields=* 'e.g. `field`,`field2`'
   * @param string $cond SQL condition
   * @param array $exCol Extra Static column
   *
   * @return
   */
	function autoTable($fields=NULL, $cond=1, $exCol=array()){
		$att=$this->options['html_table'];
		isset($att['sn']) or $att['sn']=TRUE;
		$limit=NULL; $total=0;
		$id=str_replace(array('.','_','-'),'',$this->table).(parent::$_htables);
		if(isset( $att['paginate'] )){
			$limit=sprintf(' LIMIT %u, %u', $att['paginate']['index'], $att['paginate']['limit']);
			$total=$this->rowCount('*', $cond);
			echo '<div id="pgi'.$id.'1"></div>'.PHP_EOL.'<script type="application/javascript" defer>'.PHP_EOL;
			echo 'if(typeof(pagination) != "undefined"){'.PHP_EOL;
			printf(
				'new pagination(document.getElementById("pgi%s1"), %u, %u, "%s");'.PHP_EOL.
				'}else{window.alert("pagination.js is not loaded yet");}</script>'.PHP_EOL,
				$id, $total, $att['paginate']['limit'], $att['paginate']['__VAR__']
			);
		}
		$res = $this->select($fields, $cond . $limit);//Available field(s) to show
		$this->htmlTable($res, $att['sn'], 'edit_'.$this->tableName(), $exCol);
		//==Simple auto pagination
		if(isset( $att['paginate'] )){
			echo '<div id="pgi'.$id.'2"></div>'.PHP_EOL.'<script type="application/javascript" defer>'.PHP_EOL;
			echo 'if(typeof(pagination) != "undefined"){'.PHP_EOL;
			printf(
				'new pagination(document.getElementById("pgi%s2"), %u, %u, "%s");'.PHP_EOL.
				'}else{window.alert("pagination.js is not loaded yet");}</script>'.PHP_EOL,
				$id, $total, $att['paginate']['limit'], $att['paginate']['__VAR__']
			);
		}
		//bottom content is available on editor exists.
		if( $att['editor'] ){
			$size='460, 600';
			if(count($att['editor_size'])==2) $size=join(',',$att['editor_size']);
			$self=$att['editor'].'?'.$this->primaryKey().'="+tr.childNodes[1].innerHTML';
			echo '<script type="text/javascript">'.PHP_EOL.
			'  function edit_'.$this->table.'(tr){'.PHP_EOL.
			'	  newForm("'.$self.',"'.$this->table.'",'.$size.');'.PHP_EOL.
			'  }'.PHP_EOL.
			'</script>';
		}
	}

  /**
   * Generated auto HTML Table with pagination
   *
   * @param integer $no running page
   * @param integer $limit records count
   *
   * @return void
   */
	function paginate($no=NULL, $limit=100, $_VAR='page'){
		if(is_numeric( $no ) && $no=$no<1? 1:$no) return ($this->options['html_table']['paginate']=array(
			'index'=> $no>1? abs($no-1)*$limit:0,
			'limit'=> intval($limit),
			'__VAR__'=> (string)$_VAR
		));
		unset($this->options['html_table']['paginate']);
	}

  /**
   * Generate {source-code} for HTML Table
   *
   * @param mixed $fields=* 'e.g. `field`,`field2`'
   * @param string $cond SQL condition
   * @param array $exCol Extra Static column
   *
   * @return string (HTML/PHP)
   */
	function codeTable($fields='*', $cond=1){
		$cols=array(); $pk=array(); $i=1; $td=NULL; 
		if( !($res=$this->select($fields, $cond.' LIMIT 0, 1')) ){
			if(empty($fields) || $fields=='*'){
				$cols=$this->cols;
			}else{
				is_array($fields) or $fields=array_map('trim',array_filter(
				  explode(',', str_replace('`','',$fields))
				));
				foreach($fields as $_c) if(in_array($_c, $this->cols)) $cols[]=$_c;
			}
		}
		else{
			foreach($this->fetchCol($res) as $obj) $cols[]=$obj->name; //ucwords(str_replace('_',' ',$obj->name));
			$this->free( $res );
		}
		isset($att['sn']) or $att['sn']=TRUE;
		$att=$this->options['html_table'];
		$id=str_replace(array('.','_','-'),'',$this->table).(parent::$_htables);
		foreach($this->primary as $c) $pk[]=sprintf('data-%s="<?php echo $row->%s;?>"', $c, $c);

		echo '<table border="1" class="crud table" align="center" cellpadding="2" id="crudTable'.(parent::$_htables+1).'">'.PHP_EOL;
		echo '<caption><h3 align="left">'.ucfirst($this->table).'</h3></caption>'.PHP_EOL.'<!--colgroup>'.PHP_EOL;
		if($att['sn']) print('  <col class="c-snrow" />'.PHP_EOL);
		foreach($cols as $c) printf('  <col class="c-%s" />'.PHP_EOL, strtolower(str_replace(array(' ','_','-','.'),'',$c)));
		echo '</colgroup-->'.PHP_EOL;
		echo '<thead>'.PHP_EOL.'  <tr class="header">'.($att['sn']? "\n\t<th>S/N</th>\n":NULL)."\t<th>";
		echo join("</th>\n\t<th>",array_map('ucwords',$cols)).'</th>'.PHP_EOL.'  </tr>'.PHP_EOL.'</thead>'.PHP_EOL.'<tbody>'.PHP_EOL;
		echo '<?php '.PHP_EOL;
		echo '#$crud=new CRUD(\''.str_replace('`','',$this->fullname).'\');//Valid CRUD object'.PHP_EOL;
		echo '$i=0; $total=$crud->rowCount(\'*\', '.Strings::quote($cond).');//Available records'.PHP_EOL;
		echo '#$dataset=$crud->page($no=intval(isset($_GET[\'page\'])? $_GET[\'page\']:1), $limit=100, '.Strings::quote($cond).');//Alternative foreach($dataset as $i=>$row)'.PHP_EOL;
		echo '$res = $crud->select('.Strings::quote($fields).", ".Strings::doubleQuote($cond.' LIMIT 0, ').' . $limit=100);//Available field(s) to show'.PHP_EOL;
		echo 'if($res) while($row=$crud->fetch($res)){?>'.PHP_EOL;
		echo '  <tr onDblClick="'.$this->table.'_edit(this);"'.($pk? ' '.join(' ', $pk).'>':'>').($att['sn']? PHP_EOL."\t".'<td><?php printf(\'%02d\', ++$i);?></td>'.PHP_EOL:NULL);
		foreach($cols as $c) echo "\t<td>".'<?php echo $row->'. $c .';?></td>'.PHP_EOL;
		echo '  </tr>'.PHP_EOL.'<?php } $crud->free( $res );?>'.PHP_EOL;
        echo '<!--tr><td colspan="'. (count($cols)+($att['sn']? 1:0)) .'" align="center">No Records</td></tr-->'.PHP_EOL;
    	echo '</tbody></table>'.PHP_EOL;
		echo '<!--Simple auto pagination-->'.PHP_EOL;
		echo '<div id="pgi'.$id.'2"></div>'.PHP_EOL.'<script type="application/javascript" defer>'.PHP_EOL;
		echo 'if(typeof(pagination) != "undefined"){'.PHP_EOL;
		printf(
			'new pagination(document.getElementById("pgi%s2"), <?php echo $total;?>, <?php echo $limit;?>, "%s");'.PHP_EOL.
			'}else{window.alert("pagination.js is not loaded yet");}'.PHP_EOL.'</script>'.PHP_EOL,
			$id, 'page'
		);
	}


  /**
   * Table/Record editor-script name.
   * @param $scriptName 'myEditor.php'
   * @param $size '800x600'
   */
	function setEditor($scriptName, $size='460x600'){
		$this->options['html_table']['editor']=$scriptName;
		$this->options['html_table']['editor_size']=array_map('intval',explode('x',strtolower($size)));
	}

  /**
   * Auto Table S/N column.
   * @param bool TRUE/FALSE
   * @return void
   */
	function autoSerial($bool=TRUE){
		$this->options['html_table']['sn']=$bool;
	}

  /**
   * Add legend before a row
   * @param array $legend=array
   * @param boolean $merge=bool
   */
	function setLegends($legend=array(), $merge=TRUE){
		if(count($legend)>0)
		$this->options['auto_form']['legends']=$merge? array_merge($this->options['auto_form']['legends'], $legend):$legend;
	}

  /**
   * Delete multiple records from the Table.
   * @param string $cond 'MySQL WHERE condition'
   * @return integer
   */
	function delete($cond){ return $this->drop($this->fullname, $cond); }

  /**
   * TRUNCATE/EMPTY the whole Table.
   * @return boolean
   */
	function clear(){ return $this->emptyTable($this->fullname); }

  /**
   * Get TIMESTAMP by the DATE
   *
   * @param string $sqlDate 'MySQL DATETIME'.
   * @return integer
   */
	function UNIX_TIMESTAMP($sqlDate){
	  return $this->quickVar("SELECT UNIX_TIMESTAMP('$sqlDate')");
	}

  /**
   * RESET AutoIncrement value
   * @return boolean
   */
	function reIndex($autoId='id'){
		$this->query("LOCK TABLES $this->fullname WRITE",TRUE);
		$this->query('@ROW = 0',TRUE);
		$this->query("UPDATE $this->fullname SET `$autoId` = @ROW := @ROW+1",TRUE);// ORDER BY `fld_date` ASC;
		$this->query('UNLOCK TABLES',TRUE);
		return TRUE;
	}

	private function getIp(){
		$ip=$_SERVER['REMOTE_ADDR'];
		if($ip=='::1') $ip='127.0.0.1';
		return filter_var($ip,FILTER_VALIDATE_IP)? $ip:'127.0.0.1';
	}
	
	private function sql_wrap($v){ return join('.',array_map('parent::wrapQ',explode('.',$v))); }
	private function sum_wrap($c){ return "SUM($c)"; }
	private function bulid_uri($k, $v){ return $k.'='.urlencode($v); }
	private function bulid_query($k, $v){ return "`$k`=".$this->identify($v); }
	private function attr($arr){
		$att=array();
		foreach($arr as $i=>$v) if( strlen($v)>0 ){
			$att[] = $i.'="'.$v.'"';
		}return join(' ',$att);
	}
	private function keymap($fields){
		return is_string($fields)? self::arrayGen($fields):$fields;
	}
	private function arrayGen($string){
		return (strlen($string)<1)? array():array_map('trim',explode(',',$string));
	}
	private function strToCols($cols){
		if( empty($cols) ) return array();
		if( is_string($cols) ){
			$cols=str_replace('`','',$cols);
			$cols=array_map('trim',explode(',',$cols));
		}
		return array_map('self::sql_wrap', $cols);
	}

	/**Usage for SourceCode preview i.e. printf('<pre>%s</pre>', $curd->preview());****/
	public function preview($v=NULL){
		$_var='$'.(strlen($this->table)<9? $this->table:substr($this->table,0,1).substr($this->table,-8));
		$last = end($this->cols);
		$bigName = max(array_map('strlen',$this->cols))+1;
		$output = $_var.' = new '.get_class()."('$this->table', '".parent::dbName().
					"', 'utf8', '+06:00');//declearation of class".PHP_EOL.PHP_EOL;
		$output .= '$posted = new __POST;//$_POST data handler'.PHP_EOL.PHP_EOL;
		$fields = NULL;
		foreach($this->data as $col => $value)
		{
			$comment = in_array($col,$this->primary) || in_array($col,$this->increment) || in_array($col,$this->autoUP);
			$fields .= ($comment? "//\t":"\t")."'$col'".str_repeat(' ',$bigName-strlen($col)).
						'=> $posted->'. $col .($last==$col? NULL:",");
			if(strlen($value)>0 || strlen($this->info[$col]->Comment)>0)
				$fields .= '  #'.$this->info[$col]->Comment.(strlen($value)>0? ' i.e. '.$value:NULL);
			$fields .= PHP_EOL;
		}
		$output .= '//===Inserting new values'.PHP_EOL;
		$output .= $_var.'->add(array('.PHP_EOL.$fields.'));'.PHP_EOL.PHP_EOL;
		$output .= '//===Updating rows'.PHP_EOL;
		$output .= $_var.'->set(array('.PHP_EOL.$fields.'), "'.
				(empty($this->primary)? 1:join('-',$this->primary).'=\'$posted->'.join('-',$this->primary)).'\'");'.PHP_EOL.PHP_EOL;
		$output .= '//===Deleting rows'.PHP_EOL;
		$output .= $_var.'->delete( "'.(empty($this->primary)? 1:join('-',$this->primary).'=\'$posted->'.join('-',$this->primary)).'\'" );'.PHP_EOL.PHP_EOL;
		$output .= '//===Printing rows'.PHP_EOL;
		$output .= '$res='.$_var.'->select( "'.join(', ',$this->cols).'", "'.join('-',$this->primary).'>0 LIMIT 10" );'.PHP_EOL.PHP_EOL;
		$output .= 'if( $res ){'.PHP_EOL.'  while($row='.$_var.'->fetch($res))'.PHP_EOL.'  {'.PHP_EOL."\t".'print_r( $row );'.PHP_EOL.'  }'.PHP_EOL.
					'  '.$_var.'->free( $res );'.PHP_EOL.'}'.PHP_EOL.PHP_EOL;
		ob_start();
		echo '//==HTML Table generation'.PHP_EOL;
		$this->codeTable();
		
		return $output .= ob_get_clean();
	}

	/**Usage for array_map('self::posted_dates', $_POST)****/
	public function posted_dates($v){
		$formatted=array("/\d{4}\-\d{2}-\d{2}/", "/\d{2}\-\d{2}-\d{4}/");
		if(preg_match($formatted[0],$v)) return $v;
		elseif(preg_match($formatted[1],$v)) return Times::reverse($v);
		else return $v;
	}

	/** PHP5 MAGIC METHODS auto call when needed **/
	#public function __get( $var ){}
	public function __destruct(){
	  if( $this->resLink ) parent::close();
	  parent::__destruct();
	}
	/** Finish magic methods **/
}