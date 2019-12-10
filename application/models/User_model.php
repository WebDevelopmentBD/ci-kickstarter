<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class : User_model (User Model)
 * User model class to get to handle user related data 
 * @author : Kishor Mali
 * @version : 1.1
 * @since : 15 November 2016
 */
class User_model extends CI_Model
{

	public function __construct(){
		$this->load->database();
		//$this->cruds = new crudIgniter( $this->db );
		//print_r($this->cruds->{'codeigniter.system_users'}->records()) &exit;
	}

    /**
     * This function is used to get the user listing count
     * @param string $searchText : This is optional search text
     * @return number $count : This is row count
     */
    function userListingCount($searchText = '')
    {
        $this->db->select('BaseTbl.uid, BaseTbl.email, BaseTbl.name, BaseTbl.mobile, BaseTbl.createdDtm, Role.role');
        $this->db->from('system_users as BaseTbl');
        $this->db->join('system_roles as Role', 'Role.roleid = BaseTbl.roleid','left');
        if(!empty($searchText)) {
            $likeCriteria = "(BaseTbl.email  LIKE '%".$searchText."%'
                            OR  BaseTbl.name  LIKE '%".$searchText."%'
                            OR  BaseTbl.mobile  LIKE '%".$searchText."%')";
            $this->db->where($likeCriteria);
        }
        $this->db->where('BaseTbl.isDeleted', 0);
        $this->db->where('BaseTbl.roleid !=', 1);
        $query = $this->db->get();
        
        return $query->num_rows();
    }
    
    /**
     * This function is used to get the user listing count
     * @param string $searchText : This is optional search text
     * @param number $page : This is pagination offset
     * @param number $segment : This is pagination limit
     * @return array $result : This is result
     */
    function userListing($searchText = '', $page, $segment)
    {
        $this->db->select('BaseTbl.uid, BaseTbl.email, BaseTbl.name, BaseTbl.mobile, BaseTbl.createdDtm, Role.role');
        $this->db->from('system_users as BaseTbl');
        $this->db->join('system_roles as Role', 'Role.roleid = BaseTbl.roleid','left');
        if(!empty($searchText)) {
            $likeCriteria = "(BaseTbl.email  LIKE '%".$searchText."%'
                            OR  BaseTbl.name  LIKE '%".$searchText."%'
                            OR  BaseTbl.mobile  LIKE '%".$searchText."%')";
            $this->db->where($likeCriteria);
        }
        $this->db->where('BaseTbl.isDeleted', 0);
        $this->db->where('BaseTbl.roleid !=', 1);
        $this->db->order_by('BaseTbl.uid', 'DESC');
        $this->db->limit($page, $segment);
        $query = $this->db->get();
        
        $result = $query->result();        
        return $result;
    }
    
    /**
     * This function is used to get the user roles information
     * @return array $result : This is result of the query
     */
    function getUserRoles()
    {
        $this->db->select('roleid, role');
        $this->db->from('system_roles')->where('roleid !=', 1);
        $query = $this->db->get();
        
        return $query->result();
    }

    /**
     * This function is used to check whether email id is already exist or not
     * @param {string} $email : This is email id
     * @param {number} $uid : This is user id
     * @return {mixed} $result : This is searched result
     */
    function checkEmailExists($email, $uid = 0)
    {
        $this->db->select("email");
        $this->db->from("system_users");
        $this->db->where("email", $email);   
        $this->db->where("isDeleted", 0);
        if($uid != 0){
            $this->db->where("uid !=", $uid);
        }
        $query = $this->db->get();

        return $query->result();
    }
    
    
    /**
     * This function is used to add new user to system
     * @return number $insert_id : This is last inserted id
     */
    function addNewUser($userInfo)
    {
        $this->db->trans_start();
        $this->db->insert('system_users', $userInfo);
        
        $insert_id = $this->db->insert_id();
        
        $this->db->trans_complete();
        
        return $insert_id;
    }
    
    /**
     * This function used to get user information by id
     * @param number $uid : This is user id
     * @return array $result : This is user information
     */
    function getUserInfo($uid)
    {
        $this->db->select('uid, name, email, mobile, roleid')->from('system_users');
        $this->db->where('isDeleted', 0);
		$this->db->where('roleid !=', 1);
        $this->db->where('uid', $uid);
        $query = $this->db->get();
        
        return $query->row();
    }
    
    
    /**
     * This function is used to update the user information
     * @param array $userInfo : This is users updated information
     * @param number $uid : This is user id
     */
    function editUser($userInfo, $uid)
    {
        $this->db->where('uid', $uid);
        $this->db->update('system_users', $userInfo);
        
        return TRUE;
    }
    
    
    
    /**
     * This function is used to delete the user information
     * @param number $uid : This is user id
     * @return boolean $result : TRUE / FALSE
     */
    function deleteUser($uid, $userInfo)
    {
        $this->db->where('uid', $uid);
        $this->db->update('system_users', $userInfo);
        
        return $this->db->affected_rows();
    }


    /**
     * This function is used to match users password for change password
     * @param number $uid : This is user id
     */
    function matchOldPassword($uid, $oldPassword)
    {
        $this->db->select('uid, password');
        $this->db->where('uid', $uid);        
        $this->db->where('isDeleted', 0);
        $query = $this->db->get('system_users');
        
        $user = $query->result();

        if(!empty($user)){
            if(verifyHashedPassword($oldPassword, $user[0]->password)){
                return $user;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }
    
    /**
     * This function is used to change users password
     * @param number $uid : This is user id
     * @param array $userInfo : This is user updation info
     */
    function changePassword($uid, $userInfo)
    {
        $this->db->where('uid', $uid);
        $this->db->where('isDeleted', 0);
        $this->db->update('system_users', $userInfo);
        
        return $this->db->affected_rows();
    }


    /**
     * This function is used to get user login history
     * @param number $uid : This is user id
     */
    function loginHistoryCount($uid, $searchText, $fromDate, $toDate)
    {
        $this->db->select('BaseTbl.uid, BaseTbl.sessionData, BaseTbl.machineIp, BaseTbl.userAgent, BaseTbl.agentString, BaseTbl.platform, BaseTbl.createdDtm');
        if(!empty($searchText)) {
            $likeCriteria = "(BaseTbl.sessionData LIKE '%".$searchText."%')";
            $this->db->where($likeCriteria);
        }
        if(!empty($fromDate)) {
            $likeCriteria = "DATE_FORMAT(BaseTbl.createdDtm, '%Y-%m-%d' ) >= '".date('Y-m-d', strtotime($fromDate))."'";
            $this->db->where($likeCriteria);
        }
        if(!empty($toDate)) {
            $likeCriteria = "DATE_FORMAT(BaseTbl.createdDtm, '%Y-%m-%d' ) <= '".date('Y-m-d', strtotime($toDate))."'";
            $this->db->where($likeCriteria);
        }
        if($uid >= 1){
            $this->db->where('BaseTbl.uid', $uid);
        }
        $this->db->from('system_loggedin as BaseTbl');
        $query = $this->db->get();
        
        return $query->num_rows();
    }

    /**
     * This function is used to get user login history
     * @param number $uid : This is user id
     * @param number $page : This is pagination offset
     * @param number $segment : This is pagination limit
     * @return array $result : This is result
     */
    function loginHistory($uid, $searchText, $fromDate, $toDate, $page, $segment)
    {
        $this->db->select('BaseTbl.uid, BaseTbl.sessionData, BaseTbl.machineIp, BaseTbl.userAgent, BaseTbl.agentString, BaseTbl.platform, BaseTbl.createdDtm');
        $this->db->from('system_loggedin as BaseTbl');
        if(!empty($searchText)) {
            $likeCriteria = "(BaseTbl.sessionData  LIKE '%".$searchText."%')";
            $this->db->where($likeCriteria);
        }
        if(!empty($fromDate)) {
            $likeCriteria = "DATE_FORMAT(BaseTbl.createdDtm, '%Y-%m-%d' ) >= '".date('Y-m-d', strtotime($fromDate))."'";
            $this->db->where($likeCriteria);
        }
        if(!empty($toDate)) {
            $likeCriteria = "DATE_FORMAT(BaseTbl.createdDtm, '%Y-%m-%d' ) <= '".date('Y-m-d', strtotime($toDate))."'";
            $this->db->where($likeCriteria);
        }
        if($uid >= 1){
            $this->db->where('BaseTbl.uid', $uid);
        }
        $this->db->order_by('BaseTbl.id', 'DESC');
        $this->db->limit($page, $segment);
        $query = $this->db->get();
        
        $result = $query->result();        
        return $result;
    }

    /**
     * This function used to get user information by id
     * @param number $uid : This is user id
     * @return array $result : This is user information
     */
    function getUserInfoById($uid)
    {
        $this->db->select('uid, name, email, mobile, roleid');
        $this->db->from('system_users');
        $this->db->where('isDeleted', 0);
        $this->db->where('uid', $uid);
        $query = $this->db->get();
        
        return $query->row();
    }

    /**
     * This function used to get user information by id with role
     * @param number $uid : This is user id
     * @return aray $result : This is user information
     */
    function getUserInfoWithRole($uid)
    {
        $this->db->select('BaseTbl.uid, BaseTbl.email, BaseTbl.name, BaseTbl.mobile, BaseTbl.roleid, Roles.role');
        $this->db->from('system_users as BaseTbl');
        $this->db->join('system_roles as Roles','Roles.roleid = BaseTbl.roleid');
        $this->db->where('BaseTbl.uid', $uid);
        $this->db->where('BaseTbl.isDeleted', 0);
        $query = $this->db->get();
        
        return $query->row();
    }

}

  