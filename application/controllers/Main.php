<?php
/**
* Default web-page controller
*
*/
class Main extends CI_Controller
{
	public $meta=array();

	public function __construct(){
		parent::__construct();
		#$this->load->model( 'db_model' );
		$this->load->helper('url_helper');
		
		//Default page title
		$this->meta['title'] = 'Home';
	}

	/** Welcome Home **/
	public function index()	{
        $this->load->view('templates/header', $this->meta);//Load header template i.e. include('../views/templates/header.php')
		//Actual content of the requested URL i.e. include('../views/pages/home.php');
        $this->load->view('pages/home', $this->meta);
        $this->load->view('templates/footer', $this->meta);//Load footer template i.e. include('../views/templates/footer.php')
	}

	public function view($page = 'home'){

        file_exists(APPPATH.'views/pages/'.$page.'.php') or show_404();// Whoops, we don't have a page for that!

        $this->meta['title'] = ucfirst( $page ); // Capitalize the first letter

        $this->load->view('templates/header', $this->meta);//Load header template i.e. include('../views/templates/header.php')

		//Actual content of the requested URL i.e. include('../views/pages/home.php');
        $this->load->view('pages/'.$page, $this->meta);

        $this->load->view('templates/footer', $this->meta);//Load footer template i.e. include('../views/templates/footer.php')
	}
}