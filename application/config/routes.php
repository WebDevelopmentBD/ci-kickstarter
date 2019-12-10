<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

#$route['default_controller'] = 'welcome';
$route['default_controller'] = 'main'; ##{CONTRLLLER::METHOD/PARAMETER}
$route['page/(:any)'] = 'main/view/$1';


/*********** USER DEFINED ROUTES *******************/
$route['cp/dashboard']   = 'admin';
$route['cp/userListing'] = 'admin/userListing';
$route['cp/branchListing'] = 'admin/branchListing';
$route['cp/customerListing'] = 'admin/customerListing';
$route['cp/logout']      = 'admin/logout';

$route['cp/branchEdit/(:num)'] = 'admin/branchEdit/$1';
$route['cp/customerEdit/(:num)'] = 'admin/customerEdit/$1';

$route['cp/userListing/(:num)'] = "admin/userListing/$1";
$route['cp/addNew'] = "admin/addNew";
$route['cp/addNewUser'] = "admin/addNewUser";
$route['cp/editOld'] = "admin/editOld";
$route['cp/editOld/(:num)'] = "admin/editOld/$1";
$route['cp/editUser'] = "admin/editUser";
$route['cp/deleteUser'] = "admin/deleteUser";
$route['cp/profile'] = "admin/profile";
$route['cp/profile/(:any)'] = "admin/profile/$1";
$route['cp/profileUpdate'] = "admin/profileUpdate";
$route['cp/profileUpdate/(:any)'] = "admin/profileUpdate/$1";

$route['cp/loadChangePass'] = "admin/loadChangePass";
$route['cp/changePassword'] = "admin/changePassword";
$route['cp/changePassword/(:any)'] = "admin/changePassword/$1";
$route['cp/pageNotFound']			= "admin/pageNotFound";
$route['cp/checkEmailExists']  = "admin/checkEmailExists";
$route['cp/login-history']		= "admin/loginHistoy";
$route['cp/login-history/(:num)']		  = "admin/loginHistoy/$1";
$route['cp/login-history/(:num)/(:num)'] = "admin/loginHistoy/$1/$2";

$route['cp/login']   = 'login';
$route['cp/loginMe'] = 'login/loginMe';
$route['cp/forgotPassword'] = "login/forgotPassword";
$route['cp/resetPasswordUser'] = "login/resetPasswordUser";
$route['cp/resetPasswordConfirmUser'] = "login/resetPasswordConfirmUser";
$route['cp/resetPasswordConfirmUser/(:any)'] = "login/resetPasswordConfirmUser/$1";
$route['cp/resetPasswordConfirmUser/(:any)/(:any)'] = "login/resetPasswordConfirmUser/$1/$2";
$route['cp/createPasswordUser'] = "login/createPasswordUser";