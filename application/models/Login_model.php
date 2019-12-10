<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class : Login_model (Login Model)
 * Login model class to get to authenticate user credentials 
 * @author : Kishor Mali
 * @version : 1.1
 * @since : 15 November 2016
 */
class Login_model extends CI_Model
{

	public function __construct(){
		$this->load->database();
		//$this->cruds = new crudIgniter( $this->db );
		//print_r($this->cruds->{'codeigniter.tbl_users'}->records()) &exit;
	}

    
    /**
     * This function used to check the login credentials of the user
     * @param string $email : This is email of the user
     * @param string $password : This is encrypted password of the user
     */
    function loginMe($email, $password){
        $this->db->select('BaseTbl.uid, BaseTbl.password, BaseTbl.name, BaseTbl.roleid, Roles.role')->from('system_users AS BaseTbl');
        $this->db->join('system_roles as Roles','Roles.roleid = BaseTbl.roleid');
        $this->db->where('BaseTbl.email', $email)->where('BaseTbl.isDeleted', 0);
        $query = $this->db->get();
        
        $user = $query->row();
        
        if(!empty( $user ) && password_verify($password, $user->password)){
			return $user;
        }

        return array();
    }

    /**
     * This function used to check email exists or not
     * @param {string} $email : This is users email id
     * @return {boolean} $result : TRUE/FALSE
     */
    function checkEmailExist( $email ){
        $this->db->select('uid')->from('system_users');
        $this->db->where('email', $email)->where('isDeleted', 0);
        $query = $this->db->get();

        return ($query->num_rows() > 0);
    }


    /**
     * This function used to insert reset password data
     * @param {array} $data : This is reset password data
     * @return {boolean} $result : TRUE/FALSE
     */
    function resetPasswordUser( $data ){
        return $this->db->insert('system_passreset', $data);
    }

    /**
     * This function is used to get customer information by email-id for forget password email
     * @param string $email : Email id of customer
     * @return object $result : Information of customer
     */
    function getCustomerInfoByEmail( $email ){
        $this->db->select('uid, email, name')->from('system_users');
        $this->db->where('isDeleted', 0)->where('email', $email);
        $query = $this->db->get();

        return $query->row();
    }

    /**
     * This function used to check correct activation deatails for forget password.
     * @param string $email : Email id of user
     * @param string $activation_id : This is activation string
     */
    function checkActivationDetails($email, $activation_id){
        $this->db->select('id')->from('system_passreset');
        $this->db->where('email', $email)->where('activation_id', $activation_id);
        $query = $this->db->get();
        return $query->num_rows();
    }

    // This function used to create new password by reset link
    function createPasswordUser($email, $password){
        $this->db->where('email', $email)->where('isDeleted', 0);
        $this->db->update('system_users', array('password' => password_hash($password, PASSWORD_DEFAULT)));
        $this->db->delete('system_passreset', array('email' => $email));
    }

    /**
     * This function used to save login information of user
     * @param array $loginInfo : This is users login information
     */
    function lastLogin( $loginInfo ){
        $this->db->trans_start();
        $this->db->insert('system_loggedin', $loginInfo);
        $this->db->trans_complete();
    }

    /**
     * This function is used to get last login info by user id
     * @param number $uid : This is user id
     * @return number $result : This is query result
     */
    function lastLoginInfo( $uid ){
        $this->db->select('BaseTbl.createdDtm')->from('system_loggedin as BaseTbl');
        $this->db->where('BaseTbl.uid', $uid);
        $this->db->order_by('BaseTbl.id', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get();

        return $query->row();
    }
}?>