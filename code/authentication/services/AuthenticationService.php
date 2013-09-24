<?php
include_once dirname ( __FILE__ ) . '/../dao/UserDAO.php';

class AuthenticationService {
	
	private $user_dao;
	
	function AuthenticationService() {
		$this->user_dao = new UserDAO ();
	}
	
	/******************************************************\
	 * Function Name :  role_assignment($username, $rol_name)
	 *
	 * Task : Asignar un rol a un usuario
	 *
	 * Arguments : string($username, $rol_name)
	 *
	 * Returns : true or false
	 *
	 ******************************************************/
	public function role_assignment($username, $rol_name) {
		
		$lowered_username = strtolower ( $username );
		
		//obtener el id del rol a agregar
		$rol_id = $this->user_dao->rol_id_by_name ( $rol_name );
		
		if ($rol_id != - 1 && $rol_id != null) {
			//obtener el id del usuario al que se le va a agregar el rol
			$user_id = $this->user_dao->user_id_by_username ( $lowered_username );
			
			if ($user_id != - 1 && $user_id != null) {
				return $this->user_dao->add_role_to_user ( $user_id, $rol_id );
			}
		}
		return false;
	} //end function
	

	/******************************************************\
	 * Function Name :  role_deallocation($username, $rol_name)
	 *
	 * Task : Desasignar un rol a un usuario
	 *
	 * Arguments : string($username, $rol_name)
	 *
	 * Returns : true or false
	 *
	 ******************************************************/
	public function role_deallocation($username, $rol_name) {
		
		$lowered_username = strtolower ( $username );
		
		//obtener el id del rol
		$rol_id = $this->user_dao->rol_id_by_name ( $rol_name );
		
		if ($rol_id != - 1 && $rol_id != null) {
			//obtener el id del usuario al que se le va a agregar el rol
			$user_id = $this->user_dao->user_id_by_username ( $lowered_username );
			
			if ($user_id != - 1 && $user_id != null) {
				return $this->user_dao->delete__role_to_user ( $user_id, $rol_id );
			}
		}
		return false;
	}
	
	/******************************************************\
	 * Function Name :  new_user_registration($user_name, $password)
	 *
	 * Task : registrar un nuevo usuario
	 *
	 * Arguments : string($username, $password)
	 *
	 * Returns : true or false
	 *
	 ******************************************************/
	public function new_user_registration($user_name, $password, $user_email) {
		
		if ($user_email != "") {
			//validar direcci�n de correo
			if (! ($this->user_dao->is_valid_email ( $user_email )))
				return false;
			
		//validar que ese email no existe ya
			$email_exists = $this->user_dao->verify_email_exists ( $user_email );
			if ($email_exists != - 1) //ya existe este correo
				return false;
		}
		
		//validar que ese nombre de usuario no existe ya
		$username_already_used = $this->user_dao->username_exists ( $user_name );
		
		if (! $username_already_used) {
			
			$hashed_password = md5 ( $password );
			//insertar en la tabla user los datos del nuevo usuario
			$this->user_dao->new_user ( $user_name, $hashed_password, $user_email );
			
			//ahora asignarle un rol por default
			$rol = "common_user";
			$rol_asignado = $this->role_assignment ( $user_name, $rol );
			if ($rol_asignado != - 1 && $rol_asignado != null)
				return true;
			else
				return false;
		} //en if(!$username_already_used)
		return false;
	} //en func
	

	/******************************************************\
	 * Function Name :  user_pass_valid()
	 *
	 * Task : Validar el par user-password
	 *
	 * Arguments : string($username,$password)
	 *
	 * Returns : true(valid)or false
	 *
	 ******************************************************/
	function user_pass_valid($username, $password) {
		
		$pwd_encrypted = md5($password);
		//if ($this->user_dao->username_exists ( $username ))
		return $this->user_dao->validate_user_pass ( $username, $pwd_encrypted );
			
		//return false;
	}
	
	/******************************************************\
	 * Function Name :  get_user_roles
	 *
	 * Task : Obtener los roles del usuario
	 *
	 * Arguments : string($username)
	 *
	 * Returns : los roles del usuario concatenados en un string
	 *
	 ******************************************************/
	function get_user_roles($username) {
		
		$roles_string = "";
		$this->user_dao = new UserDAO ();
		
		//obtener el id del user dado el username
		$user_id = $this->user_dao->user_id_by_username ( $username );
		
		//obtener los roles del user
		$roles_array = $this->user_dao->user_roles ( $user_id );
		$i = 0;
		foreach ( $roles_array as $id ) {
			$rol_name_add = $this->user_dao->rol_name_by_id ( $id ['rol_id'] );
			$roles_string = $roles_string . $rol_name_add;
			$i ++;
			$roles_string .= ",";
		
		}		
		$roles_string = substr ( $roles_string, 0, - 1 );
		return $roles_string;
	} //end function
	
    /******************************************************\
	 * Function Name :  get_user_roles_array
	 *
	 * Task : Obtener los roles del usuario en un array
	 *
	 * Arguments : string($username)
	 *
	 * Returns : los roles del usuario en un array de string
	 *
	 ******************************************************/
	function get_user_roles_array($username) {
		
		$this->user_dao = new UserDAO ();
		
		//obtener el id del user dado el username
		$user_id = $this->user_dao->user_id_by_username ( $username );
		
		//obtener los roles del user
		return $this->user_dao->user_roles ( $user_id );
		
	} //end function
	
	
	function modify_user_password($username, $new_pasword) {
		
		//obtener el id del usuario
		$user_id = $this->user_dao->user_id_by_username ( $username );
		
		if ($user_id != - 1) {
			$hashed_password = md5 ( $new_pasword );
			
			return $this->user_dao->change_password ( $user_id, $hashed_password );
		}
		return false;
	
	} //end function
	
    /******************************************************\
	 * Function Name :  user_has_permission
	 *
	 * Task : Saber si el user tiene todos los permisos por los q se pregunta
	 *
	 * Arguments : string($username); integer array($permission) 
	 *
	 * Returns : true or false
	 *
	 ******************************************************/
	function user_has_permission($username, $permission){
		
		//obtengo los roles de este user
		$roles = $this->get_user_roles_array($username); //los roles del user en un array.
				
		foreach ($roles as $rol ){ //por cada rol, obtengo el id de sus permisos.
			$all_permission = array_merge((array)$all_permission,(array)$this->user_dao->get_rol_permission($rol));			
		}
		
		foreach ($permission as $per){//por cada permiso q estoy chequeando pregunto si esta en la lista de permisos de los roles.
		  if(!in_array(strtolower($per), $all_permission)) //the comparison is done in a case-sensitive manner.
			 return false;
		}
		return true;
	}
	
	/******************************************************\
	 * Function Name :  fill_permissions_array
	 *
	 * Task : Llenar un array con el par: nombre permiso - 0 � 1 si lo tiene o no
	 *
	 * Arguments : string($username); 
	 *
	 * Returns : array
	 *
	 ******************************************************/
	function fill_permissions_array($username){
		
		//Obtengo todos los permisos de la tabla 
		// permission_id y permission_name en cada pos
		$all_permissions = $this->user_dao->get_all_permission(); 
		
	    //obtengo los roles de este user
		$roles = $this->get_user_roles_array($username); //los roles del user en un array.
				
		foreach ($roles as $rol ){ //por cada rol, obtengo el id de sus permisos.
			$user_permission = array_merge((array)$user_permission,(array)$this->user_dao->get_rol_permission($rol['rol_id']));			
		}
		
		//$pos = 0;
		foreach ($all_permissions as $per){
			//$final_array : el array a retornar			
			
			//tiene el permiso?
			/*if(in_array($per['permission_id'], $user_permission))
			  $final_array[$pos]['assigned'] = 1;
			else
			$final_array[$pos]['assigned'] = 0;*/
			if(in_array($per['permission_id'], $user_permission))
			  $final_array[] = $per['permission_name'];
              //$final_array[$pos]['permission_name'] = $per['permission_name'];
              
		 // $pos++;	
		}
		
		return $final_array;
	}
	
} //end class

//para probar
//echo(AuthenticationService::getInstance()->get_user_roles('user_new'));

?>