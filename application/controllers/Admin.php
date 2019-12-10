<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Class : Admin (AdminController)
 * User Class to control all user related operations.
 * @author : Abbas Uddin
 * @version : 1.1
 */
class Admin extends CI_Controller
{

	const SLUG_PREFIX = 'cp/';##Sub-Folder VIEWS default: blank

	public $cruds, $user, $meta=array();

    /**
    * This is default constructor of the class
    */
    function __construct(){
        parent::__construct();
        $this->load->model('user_model');
		$this->load->model('db_model');
		
		#$this->load->model('login_model');
		$this->load->helper('url_helper');

		#$this->load->library('user_agent');
		$this->load->library('session');

        $this->session->userdata('isLoggedIn') or redirect('cp/login');
		
		$this->cruds = &$this->db_model->cruds;

		$this->user 	= (object)$this->session->userdata();
		$this->name		= $this->session->userdata( 'name' );

		$this->meta['user']= $this->user;
		$this->meta['uid'] = $this->user->uid;

		$this->meta['name'] = $this->session->userdata( 'name' );
		$this->meta['role'] = $this->session->userdata( 'role' );
		$this->meta['role_text'] = $this->session->userdata( 'roleText' );
		$this->meta['last_login']= $this->session->userdata( 'lastLogin' );
		$this->meta['pageTitle'] = SOFTWARE_NAME;
    }
    
    /**
     * This function used to load the first screen of the user
     */
    public function index(){
        $this->meta['pageTitle'] = SOFTWARE_NAME . ' : Dashboard';

		$this->load->view(self::SLUG_PREFIX . 'includes/header', $this->meta);
        $this->load->view(self::SLUG_PREFIX . 'dashboard');
        $this->load->view(self::SLUG_PREFIX . 'includes/footer');
    }

    /**
     * Page not found : error 404
     */
    public function pageNotFound(){
        $this->meta['pageTitle'] = SOFTWARE_NAME . ' : 404 - Page Not Found';

		$this->load->view(self::SLUG_PREFIX . 'includes/header', $this->meta);
        $this->load->view(self::SLUG_PREFIX . '404');
        $this->load->view(self::SLUG_PREFIX . 'includes/footer');

    }

    /**
     * Available users on the database
     */
    public function userListing(){
        $this->isAdmin() or $this->accessVoid();

		$searchText = $this->security->xss_clean($this->input->post('searchText'));
		$data['searchText'] = $searchText;
		
		$this->load->library('pagination');
		
		$count = $this->user_model->userListingCount($searchText);

		$returns = $this->paginationCompress ( "userListing/", $count, 10 );
		
		$data['userRecords'] = $this->user_model->userListing($searchText, $returns["page"], $returns["segment"]);
		
		$this->meta['pageTitle'] = SOFTWARE_NAME . ' : User Listing';
		
		//==Layout View			
		$this->load->view(self::SLUG_PREFIX . 'includes/header', $this->meta);
		$this->load->view(self::SLUG_PREFIX . 'users', $data);
		$this->load->view(self::SLUG_PREFIX . 'includes/footer');
    }

    /**
    * Available Company/Branches
    */
    public function branchListing(){
        $this->isAdmin() or $this->accessVoid();

		$searchText = $this->security->xss_clean($this->input->post('searchText'));
		$data['searchText'] = $searchText;

		$this->load->library('pagination');

		$count = $this->user_model->userListingCount( $searchText );

		$returns = $this->paginationCompress ( "branchListing/", $count, 10 );

		$data['thead']  = $this->cruds->{'sale_branch'}->getThead();
		$data['dataset']= $this->cruds->{'sale_branch'}->records();

		$this->meta['pageTitle'] = SOFTWARE_NAME.' : Branches';

		//==Layout View			
		$this->load->view(self::SLUG_PREFIX . 'includes/header', $this->meta);
		$this->load->view(self::SLUG_PREFIX . 'branchListing', $data);
		$this->load->view(self::SLUG_PREFIX . 'includes/footer');
    }

	/**
    * Edit a branch listing
    * @param number $i : This is branch index
    */
    function branchEdit( $i ){
        $this->isAdmin() or $this->accessVoid();
		is_numeric($i) or redirect('./branchListing');

		$data['record'] = $this->cruds->{'sale_branch'}->record('`i`='.$i);
		
		$this->meta['pageTitle'] = SOFTWARE_NAME . ' : Edit Branch';

		//==Layout View			
		$this->load->view(self::SLUG_PREFIX . 'includes/header', $this->meta);
		$this->load->view(self::SLUG_PREFIX . __FUNCTION__, $data);
		$this->load->view(self::SLUG_PREFIX . 'includes/footer');
    }
	
    /**
    * Available Customers
    */
    public function customerListing(){
        $this->isAdmin() or $this->accessVoid();

		$searchText = $this->security->xss_clean($this->input->post('searchText'));
		$data['searchText'] = $searchText;

		$this->load->library('pagination');

		$count = $this->user_model->userListingCount( $searchText );

		$returns = $this->paginationCompress( "customerListing/", $count, 10 );

		$data['thead']  = $this->cruds->{'sale_profiles'}->getThead();
		$data['dataset']= $this->cruds->{'sale_profiles'}->records();

		$this->meta['pageTitle'] = SOFTWARE_NAME.' : Customers';

		//==Layout View			
		$this->load->view(self::SLUG_PREFIX . 'includes/header', $this->meta);
		$this->load->view(self::SLUG_PREFIX . __FUNCTION__, $data);
		$this->load->view(self::SLUG_PREFIX . 'includes/footer');
    }
	
	/**
    * Edit a client profile
    * @param number $i : This is profile primary key
    */
    function customerEdit( $i ){
        $this->isAdmin() or $this->accessVoid();
		is_numeric($i) or redirect('./customerListing');

		$data['record'] = $this->cruds->{'sale_profiles'}->record('`id`='.$i);
		
		$this->meta['pageTitle'] = SOFTWARE_NAME . ' : Edit Profile';

		//==Layout View			
		$this->load->view(self::SLUG_PREFIX . 'includes/header', $this->meta);
		$this->load->view(self::SLUG_PREFIX . __FUNCTION__, $data);
		$this->load->view(self::SLUG_PREFIX . 'includes/footer');
    }




    /**
     * This function is used to load the add new form
     */
    function addNew(){
        $this->isAdmin() or $this->accessVoid();

		$this->load->model('user_model');
		$data['roles'] = $this->user_model->getUserRoles();
		
		$this->meta['pageTitle'] = SOFTWARE_NAME.' : Add New User';

		//==Layout View			
		$this->load->view(self::SLUG_PREFIX . 'includes/header', $this->meta);
		$this->load->view(self::SLUG_PREFIX . 'addNew', $data);
		$this->load->view(self::SLUG_PREFIX . 'includes/footer');
    }

    /**
     * This function is used to check whether email already exist or not
     */
    function checkEmailExists(){
        $userId = $this->input->post("userId");
        $email = $this->input->post("email");

        if(empty( $userId )){
            $result = $this->user_model->checkEmailExists($email);
        }else{
            $result = $this->user_model->checkEmailExists($email, $userId);
        }

        echo empty( $result )? "true":"false";
    }
    
    /**
     * This function is used to add new user to the system
     */
    function addNewUser(){
        $this->isAdmin() or $this->accessVoid();

		$this->load->library('form_validation');
		
		$this->form_validation->set_rules('fname','Full Name','trim|required|max_length[128]');
		$this->form_validation->set_rules('email','Email','trim|required|valid_email|max_length[128]');
		$this->form_validation->set_rules('password','Password','required|max_length[20]');
		$this->form_validation->set_rules('cpassword','Confirm Password','trim|required|matches[password]|max_length[20]');
		$this->form_validation->set_rules('role','Role','trim|required|numeric');
		$this->form_validation->set_rules('mobile','Mobile Number','required|min_length[10]');
		
		if($this->form_validation->run() == FALSE){
			$this->addNew();
		}
		else{
			$name     = ucwords(strtolower($this->security->xss_clean($this->input->post('fname'))));
			$email    = strtolower($this->security->xss_clean($this->input->post('email')));
			$password = $this->input->post('password');
			$roleId   = $this->input->post('role');
			$mobile   = $this->security->xss_clean($this->input->post('mobile'));
			
			$userInfo = array(
			  'email'     => $email,
			  'password'  => getHashedPassword($password),
			  'roleid'    => $roleId,
			  'name'      => $name,
			  'mobile'    => $mobile,
			  'createdBy' => $this->user->uid,
			  'createdDtm'=> date('Y-m-d H:i:s')
			);
			
			$this->load->model('user_model');
			$result = $this->user_model->addNewUser($userInfo);
			
			if($result > 0){
				$this->session->set_flashdata('success', 'New User created successfully');
			}
			else{
				$this->session->set_flashdata('error', 'User creation failed');
			}
			
			redirect('addNew');
		}
    }

    
    /**
     * This function is used load user edit information
     * @param number $userId : Optional : This is user id
     */
    function editOld($userId = NULL){
        if($this->isAdmin() == TRUE || $userId == 1){
            $this->accessVoid();
        }
        else{
            if($userId == NULL) redirect('userListing');

            $data['roles']    = $this->user_model->getUserRoles();
            $data['userInfo'] = $this->user_model->getUserInfo($userId);
            
            $this->meta['pageTitle'] = SOFTWARE_NAME . ' : Edit User';

			//==Layout View			
			$this->load->view(self::SLUG_PREFIX . 'includes/header', $this->meta);
			$this->load->view(self::SLUG_PREFIX . 'editOld', $data);
			$this->load->view(self::SLUG_PREFIX . 'includes/footer');
        }
    }
    
    
    /**
     * This function is used to edit the user information
     */
    function editUser(){
        $this->isAdmin() or $this->accessVoid();

		$this->load->library('form_validation');
		
		$userId = $this->input->post('userId');
		
		$this->form_validation->set_rules('fname','Full Name','trim|required|max_length[128]');
		$this->form_validation->set_rules('email','Email','trim|required|valid_email|max_length[128]');
		$this->form_validation->set_rules('password','Password','matches[cpassword]|max_length[20]');
		$this->form_validation->set_rules('cpassword','Confirm Password','matches[password]|max_length[20]');
		$this->form_validation->set_rules('role','Role','trim|required|numeric');
		$this->form_validation->set_rules('mobile','Mobile Number','required|min_length[10]');
		
		if($this->form_validation->run() == FALSE){
			$this->editOld($userId);
		}
		else{
			$name = ucwords(strtolower($this->security->xss_clean($this->input->post('fname'))));
			$email = strtolower($this->security->xss_clean($this->input->post('email')));
			$password = $this->input->post('password');
			$roleId = $this->input->post('role');
			$mobile = $this->security->xss_clean($this->input->post('mobile'));
			
			$userInfo = array();
			
			if(empty( $password )){
				$userInfo = array(
				  'email' => $email,
				  'roleid'=> $roleId,
				  'name'  => $name,
				  'mobile'=> $mobile,
				  'updatedBy' => $this->user->uid,
				  'updatedDtm'=> date('Y-m-d H:i:s')
				);
			}
			else{
				$userInfo = array(
				  'email'   => $email,
				  'password'=> getHashedPassword($password),
				  'roleid'  => $roleId,
				  'name'    => ucwords($name),
				  'mobile'  => $mobile,
				  'updatedBy' => $this->user->uid,
				  'updatedDtm'=> date('Y-m-d H:i:s')
				);
			}
			
			$result = $this->user_model->editUser($userInfo, $userId);
			
			if($result == true)
			{
				$this->session->set_flashdata('success', 'User updated successfully');
			}
			else
			{
				$this->session->set_flashdata('error', 'User updation failed');
			}
			
			redirect('userListing');
		}
    }


    /**
     * This function is used to delete the user using userId
     * @return boolean $result : TRUE / FALSE
     */
    function deleteUser(){
        if($this->isAdmin() == TRUE){
            echo(json_encode(array('status'=>'access')));
        }
        else{
            $userId = $this->input->post('userId');
            $userInfo = array('isDeleted'=>1,'updatedBy'=>$this->user->uid, 'updatedDtm'=>date('Y-m-d H:i:s'));
            
            $result = $this->user_model->deleteUser($userId, $userInfo);
            
            if($result > 0){ echo(json_encode(array('status'=>TRUE))); }
            else{ echo(json_encode(array('status'=>FALSE))); }
        }
    }
    
    /**
     * This function used to show login history
     * @param number $userId : This is user id
     */
    function loginHistoy($userId = NULL){
        if($this->isAdmin() == TRUE){
            $this->accessVoid();
        }
        else{
            $userId = ($userId == NULL ? 0 : $userId);

            $searchText = $this->input->post('searchText');
            $fromDate = $this->input->post('fromDate');
            $toDate = $this->input->post('toDate');

            $data["userInfo"]  = $this->user_model->getUserInfoById($userId);

            $data['searchText']= $searchText;
            $data['fromDate']  = $fromDate;
            $data['toDate']    = $toDate;
            
            $this->load->library('pagination');
            
            $count = $this->user_model->loginHistoryCount($userId, $searchText, $fromDate, $toDate);

            $returns = $this->paginationCompress( "login-history/".$userId."/", $count, 10, 3);

            $data['userRecords'] = $this->user_model->loginHistory($userId, $searchText, $fromDate, $toDate, $returns["page"], $returns["segment"]);
            
            $this->meta['pageTitle'] = SOFTWARE_NAME.' : User Login History';

			//==Layout View			
			$this->load->view(self::SLUG_PREFIX . 'includes/header', $this->meta);
			$this->load->view(self::SLUG_PREFIX . 'loginHistory', $data);
			$this->load->view(self::SLUG_PREFIX . 'includes/footer');
        }        
    }

    /**
     * This function is used to show users profile
     */
    function profile($active = "details"){
        $data["userInfo"] = $this->user_model->getUserInfoWithRole($this->user->uid);
        $data["active"] = $active;
        
        $this->meta['pageTitle'] = ($active == "details") ? SOFTWARE_NAME.' : My Profile' : SOFTWARE_NAME.' : Change Password';

		//==Layout View			
		$this->load->view(self::SLUG_PREFIX . 'includes/header', $this->meta);
		$this->load->view(self::SLUG_PREFIX . 'profile', $data);
		$this->load->view(self::SLUG_PREFIX . 'includes/footer');

    }

    /**
     * This function is used to update the user details
     * @param text $active : This is flag to set the active tab
     */
    function profileUpdate($active = "details"){
        $this->load->library('form_validation');
            
        $this->form_validation->set_rules('fname','Full Name','trim|required|max_length[128]');
        $this->form_validation->set_rules('mobile','Mobile Number','required|min_length[10]');
        $this->form_validation->set_rules('email','Email','trim|required|valid_email|max_length[128]|callback_emailExists');        
        
        if($this->form_validation->run() == FALSE){
            $this->profile($active);
        }
        else{
            $name = ucwords(strtolower($this->security->xss_clean($this->input->post('fname'))));
            $mobile = $this->security->xss_clean($this->input->post('mobile'));
            $email = strtolower($this->security->xss_clean($this->input->post('email')));
            
            $userInfo = array(
			  'name'      => $name,
			  'email'     => $email,
			  'mobile'    => $mobile,
			  'updatedBy' => $this->user->uid,
			  'updatedDtm'=> date('Y-m-d H:i:s')
			);
            
            $result = $this->user_model->editUser($userInfo, $this->user->uid);
            
            if($result == TRUE){
                $this->session->set_userdata('name', $name);
                $this->session->set_flashdata('success', 'Profile updated successfully');
            }
            else{
                $this->session->set_flashdata('error', 'Profile updation failed');
            }

            redirect('profile/'.$active);
        }
    }

    /**
     * This function is used to change the password of the user
     * @param text $active : This is flag to set the active tab
     */
    function changePassword($active = "changepass"){

        $this->load->library('form_validation');
        
        $this->form_validation->set_rules('oldPassword','Old password','required|max_length[20]');
        $this->form_validation->set_rules('newPassword','New password','required|max_length[20]');
        $this->form_validation->set_rules('cNewPassword','Confirm new password','required|matches[newPassword]|max_length[20]');
        
        if($this->form_validation->run() == FALSE){
            $this->profile($active);
        }
        else{
            $oldPassword = $this->input->post('oldPassword');
            $newPassword = $this->input->post('newPassword');
            
            $resultPas = $this->user_model->matchOldPassword($this->user->uid, $oldPassword);
            
            if(empty( $resultPas )){
                $this->session->set_flashdata('nomatch', 'Your old password is not correct');
                redirect('profile/'.$active);
            }
            else{
                $usersData = array(
				  'password'  => getHashedPassword($newPassword),
				  'updatedBy' => $this->user->uid,
				  'updatedDtm'=> date('Y-m-d H:i:s')
				);
                
                $result = $this->user_model->changePassword($this->user->uid, $usersData);
                
                if($result > 0){
					$this->session->set_flashdata('success', 'Password updation successful');
				}
                else {
					$this->session->set_flashdata('error', 'Password updation failed');
				}
                
                redirect('profile/'.$active);
            }
        }
    }

    /**
     * This function is used to check whether email already exist or not
     * @param {string} $email : This is users email
     */
    function emailExists( $email ){
        $userId = $this->user->uid;
        $return = false;

        if(empty( $userId )){
            $result = $this->user_model->checkEmailExists($email);
        }else{
            $result = $this->user_model->checkEmailExists($email, $userId);
        }

        if(empty( $result )) return TRUE;

		$this->form_validation->set_message('emailExists', 'The {field} already taken');
		return FALSE;
    }

	
	/**
	 * Takes mixed data and optionally a status code, then creates the response
	 *
	 * @access public
	 * @param array|NULL $data
	 *        	Data to output to the user
	 *        	running the script; otherwise, exit
	 */
	public function response($data = NULL) {
		$this->output->set_status_header( 200 )->set_content_type( 'application/json', 'utf-8' )->set_output(json_encode(
			$data,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		))->_display();
		exit();
	}
	
	/**
	* This function is used to check the access
	*/
	function isAdmin(){
		return ($this->user->role == ROLE_ADMIN);
	}
	
	/**
	 * This function is used to check the access
	 */
	function isTicketter(){
		return ($this->user->role != ROLE_ADMIN || $this->user->role != ROLE_MANAGER);
	}
	
	/**
	 * This function is used to load the set of views
	 */
	function accessVoid(){
		$this->meta['pageTitle'] = SOFTWARE_NAME.' : Access Denied';
		
		$this->load->view(self::SLUG_PREFIX .  '/includes/header', $this->meta);
		$this->load->view(self::SLUG_PREFIX .  '/access' );
		$this->load->view(self::SLUG_PREFIX .  '/includes/footer' );
		return NULL;
	}
	
	/**
	 * This function is used to logged out user from system
	 */
	function logout() {
		$this->session->sess_destroy();
		redirect( self::SLUG_PREFIX . 'login' );
	}
	
	/**
	 * This function used provide the pagination resources
	 * @param {string} $link : This is page link
	 * @param {number} $count : This is page count
	 * @param {number} $perPage : This is records per page limit
	 * @return {mixed} $result : This is array of records and pagination data
	 */
	function paginationCompress($link, $count, $perPage = 10, $segment = SEGMENT) {
		$this->load->library('pagination');

		$config['base_url']       = base_url().$link;
		$config['total_rows']     = $count;
		$config['uri_segment']    = $segment;
		$config['per_page']       = $perPage;
		$config['num_links']      = 5;
		$config['full_tag_open']  = '<nav><ul class="pagination">';
		$config['full_tag_close'] = '</ul></nav>';
		$config['first_tag_open'] = '<li class="arrow">';
		$config['first_link']     = 'First';
		$config['first_tag_close']= '</li>';
		$config['prev_link']      = 'Previous';
		$config['prev_tag_open']  = '<li class="arrow">';
		$config['prev_tag_close'] = '</li>';
		$config['next_link']      = 'Next';
		$config['next_tag_open']  = '<li class="arrow">';
		$config['next_tag_close'] = '</li>';
		$config['cur_tag_open']   = '<li class="active"><a href="#">';
		$config['cur_tag_close']  = '</a></li>';
		$config['num_tag_open']   = '<li>';
		$config['num_tag_close']  = '</li>';
		$config['last_tag_open']  = '<li class="arrow">';
		$config['last_link']	  = 'Last';
		$config['last_tag_close'] = '</li>';
	
		$this->pagination->initialize( $config );
		$page = $config['per_page'];
		$segment = $this->uri->segment( $segment );
	
		return array (
			"page" => $page,
			"segment" => $segment
		);
	}

}
?>