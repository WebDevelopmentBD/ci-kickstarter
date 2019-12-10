<?php
include(APPPATH . '/libraries/crudIgniter.php');

class Db_model extends CI_Model
{
	public function __construct(){
		$this->load->database();
		$this->cruds = new crudIgniter( $this->db );
		//print_r($this->cruds->{'codeigniter.system_users'}->records()) &exit;
	}
	
	public function records($cond = 1){
		return $this->cruds->{'system_users'}->records( $cond );
	}
}