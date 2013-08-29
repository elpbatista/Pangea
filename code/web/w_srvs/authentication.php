<?php
include_once dirname ( __FILE__ ) . 'authentication/services/AuthenticationService.php';

$user_name = $_POST['user_name'];
$pass = $_POST['pass'];

	if($user_name != "" && $pass !=""){
	 $auth_service = AuthenticationService::getInstance();
	 
	  if($auth_service->user_pass_valid($user_name,$pass)){ //user-pass válido
	    $roles = $auth_service->get_user_roles($user_name); //obtener sus roles
	    if(strpos( $roles, "editor_user" ) !== false) //si tiene rol de editor
	      $can_edit = true;                           //setear la var en true
	       //$_SERVER['HTTP_REFERER'] 
	  }
	  else  //user-pass no válido.
	  {}
	}
 

?>