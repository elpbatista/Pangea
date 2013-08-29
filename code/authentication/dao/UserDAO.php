<?php

include_once dirname ( __FILE__ ) . '/../../core/dao/GenericDAO.php';

class UserDAO extends GenericDAO {
	
	private $auth_dbConn;
	
    function UserDAO() {
		
		try {
			//parent::__construct ();
			
			$pg_connect = PGConnect::getInstance();
			$this->auth_dbConn = $pg_connect->getNewConnection("laurasia.pangea.ohc.cu","PANGEA_AUTH","pangea", "p@Ng3A", "5432");
			
			pg_set_client_encoding ( $this->auth_dbConn, PG_CLIENT_CONNECTION_ENCODING );
		
		} catch ( PangeaDataAccessException $e ) {
			throw $e;
		}
	}
	
	/******************************************************\
	 * Function Name : new_user($login, $pass)
	 *
	 * Task : Create a new user entry in the users table
            based on args passed
	 *
	 * Arguments : string($login, $pass, $email)
	 *
	 * Returns : true or false
	 *
	 ******************************************************/
	function new_user($login, $password, $email) {
		/*
    Creating a New User Record in the DB:
    In this function we create a new user record in the db.

    We first build a query and save it into the $query variable.
    The query statement says:

    'Insert the value of $login, $password and $email into the 'username',
    'password' and 'user_email' columns in the 'user' table'
    */
		
		$query = "INSERT INTO \"user\" (username, password, user_email) VALUES('$login', '$password', '$email')";
		$result = pg_query ( $this->auth_dbConn, $query );
		
		return $result;
	} // end func newUser($login, $pass)
	

	/******************************************************\
	 * Function Name : username_exists($username)
	 *
	 * Task : Verificar si el nombre de usuario dado no existe ya en la BD
	 *
	 * Arguments : string($username)
	 *
	 * Returns : true(existe) or false(no existe)
	 *
	 ******************************************************/
	function username_exists($username) {
		
		$result = false;
		$lowered_username = strtolower ( $username );
		$query = "SELECT user_id FROM \"user\" WHERE (LOWER(username) = '$lowered_username')";
		$result = pg_query ( $this->auth_dbConn, $query );
		
		//return ($result !== false); //esto siempre da true

		$count = pg_num_rows ( $result ); 
		if ($count > 0)
			return true;
		return false; 		
		
	} // end func username_exists
	

	/******************************************************\
	 * Function Name : rol_id_by_name($rol_name)
	 *
	 * Task : Obtener el id de un rol dado su nombre
	 *
	 * Arguments : string($rol_name)
	 *
	 * Returns : integer (el id)
	 *
	 ******************************************************/
	function rol_id_by_name($rol_name) {
		
		//obtener el id del rol dado su nombre
		$query = "SELECT rol_id FROM \"roles\" WHERE (rol_name = '$rol_name')";
		$result = pg_query ( $this->auth_dbConn, $query );
		
		if ($result) {
			$row = pg_fetch_row ( $result );
			if ($row [0] != null)
				return $row [0];
			return - 1;
		}
		return - 1;
	} // end func
	

	/******************************************************\
	 * Function Name : rol_name_by_id($rol_id)
	 *
	 * Task : Obtener el nombre del rol dado su id
	 *
	 * Arguments : integer($rol_id)
	 *
	 * Returns : string (el nombre) o "" (cadena vac�a)
	 *
	 ******************************************************/
	function rol_name_by_id($rol_id) {
		
		//obtener el nombre del rol dado su id
		$query = "SELECT rol_name FROM \"roles\" WHERE (rol_id = '$rol_id')";
		$result = pg_query ( $this->auth_dbConn, $query );
		
		if ($result) {
			$row = pg_fetch_row ( $result );
			if ($row [0] != null)
				return $row [0];
			return "";
		}
		return "";
	} // end func
	

	/******************************************************\
	 * Function Name : user_id_by_username($username)
	 *
	 * Task : Obtener el id de un user dado su username
	 *
	 * Arguments : string($username)
	 *
	 * Returns : integer (el id)
	 *
	 ******************************************************/
	function user_id_by_username($user_name) {
		
		//obtener el id del usuario dado su username
		$query_user = "SELECT user_id FROM \"user\" WHERE (LOWER(username) = '{$user_name}')";
		$result_user = pg_query ( $this->auth_dbConn, $query_user );
		
		if ($result_user) {
		    $row = pg_fetch_result ( $result_user,0);
			return $row;			
		}
		return - 1;
	} // end func
	

	/******************************************************\
	 * Function Name : user_id_by_email
	 *
	 * Task : Obtener el id de un user dado su email
	 *
	 * Arguments : string($user_email)
	 *
	 * Returns : integer (el id) o -1
	 *
	 ******************************************************/
	function user_id_by_email($user_email) {
		
		//obtener el id del usuario dado su email
		$query_user = "SELECT user_id FROM \"user\" WHERE (user_email = '$user_email')";
		$result_user = pg_query ( $this->auth_dbConn, $query_user );
		
		if ($result_user) {
			$row = pg_fetch_row ( $result_user );
			if ($row [0] != null)
				return $row [0];
			return - 1;
		}
		return - 1;
	} // end func
	

	/******************************************************\
	 * Function Name : user_roles($user_id)
	 *
	 * Task : Obtener los roles de un usuario dado su id
	 *
	 * Arguments : string($user_id)
	 *
	 * Returns : un array con los roles// Each row is an array of field values indexed by field name. 
	 *
	 ******************************************************/
	function user_roles($user_id) {
		
		//obtener los roles del usuario dado su id
		$query = "SELECT rol_id FROM \"user_roles\" WHERE (user_id = '$user_id')";
		$result = pg_query ( $this->auth_dbConn, $query );
		
		if ($result) {
			$arr = pg_fetch_all ( $result );
			return $arr;
		}
		return - 1;
	} // end func
	

	/******************************************************\
	 * Function Name : verify_email_exists($email)
	 *
	 * Task : Verificar si un email ya existe.
	 *
	 * Arguments : string($email)
	 *
	 * Returns : el id del rol o -1 si no existe
	 *
	 ******************************************************/
	function verify_email_exists($email) {
		
		$query = "SELECT * FROM \"user\" WHERE (user_email = '$email')";
		$result = pg_query ( $this->auth_dbConn, $query );
		
		if ($result) {
			$row = pg_fetch_row ( $result );
			if ($row [0] != null)
				return $row [0];
			return - 1;
		}
		return - 1;
	} //end func
	

	/******************************************************\
	 * Function Name : verify_rol_exists($rol_id)
	 *
	 * Task : Verificar si un rol existe dado su id.
	 *
	 * Arguments : integer($rol_id)
	 *
	 * Returns : el id del rol o -1 si no existe
	 *
	 ******************************************************/
	function verify_rol_exists($rol_id) {
		
		$query = "SELECT * FROM \"roles\" WHERE (rol_id = $rol_id)";
		$result = pg_query ( $this->auth_dbConn, $query );
		
		if ($result) {
			$row = pg_fetch_row ( $result );
			if ($row [0] != null)
				return $row [0];
			return - 1;
		}
		return - 1;
	} //end func
	

	/******************************************************\
	 * Function Name : role_to_user($username, $rol)
	 *
	 * Task : Asignar un rol a un usuario(insertar en la tabla user_roles ambos ids)
	 *
	 * Arguments : integer($id_user, $id_rol)
	 *
	 * Returns : 
	 *
	 ******************************************************/
	function add_role_to_user($id_user, $id_rol) {
		
		if ($this->verify_rol_exists ( $id_rol ) != - 1) {
			$query_insert = "INSERT INTO \"user_roles\" (user_id, rol_id) VALUES('$id_user', '$id_rol')";
			$result_final = pg_query ( $this->auth_dbConn, $query_insert );
			return $result_final;
		}
		return - 1;
	} // end func
	

	/******************************************************\
	 * Function Name : delete_role_to_user($username, $rol)
	 *
	 * Task : Desasignar un rol a un usuario(eliminar en la tabla user_roles ambos ids)
	 *
	 * Arguments : integer($id_user, $id_rol)
	 *
	 * Returns : 
	 *
	 ******************************************************/
	function delete__role_to_user($id_user, $id_rol) {
		
		if ($this->verify_rol_exists ( $id_rol ) != - 1) {
			$query = "DELETE FROM \"user_roles\" WHERE(( user_id = '$id_user') AND (rol_id = '$id_rol'))";
			$result_final = pg_query ( $this->auth_dbConn, $query );
			return $result_final;
		}
		return - 1;
	} // end func	
	

	/******************************************************\
	 * Function Name : change_password($id_user, $new_password)
	 *
	 * Task : To change a user password
	 *
	 * Arguments : integer($id_user) string($new_password)
	 *
	 * Returns : true or false
	 *
	 ******************************************************/
	function change_password($id_user, $new_password) {
		
		$query = "UPDATE \"user\" SET password = '$new_password' WHERE( user_id = '$id_user')";
		$result = pg_query ( $this->auth_dbConn, $query );
		if ($result)
			return true;
		return false;
	
	} // end func			
	

	/******************************************************\
	 * Function Name : validate_user_pass
	 *
	 * Task : Validar el par user-password
	 *
	 * Arguments : string($username,$password)
	 *
	 * Returns : true or false
	 *
	 ******************************************************/
	function validate_user_pass($username, $password) {
		
		$query = "SELECT user_id FROM \"user\" WHERE (( LOWER(username) = '$username') AND (password = '$password'))";
		$result = pg_query ( $this->auth_dbConn, $query );
		
		if ($result) {
			$row = pg_fetch_all ( $result );
			if ($row [0] != null)
				return true;
		}
		return false;
	}
	
	
	/******************************************************\
	 * Function Name : is_valid_email($email)
	 *
	 * Task : Validate an email address.
	 *
	 * Arguments : string($email)
	 *
	 * Returns : true if the email address has the email 
             address format and the domain exists.
	 *
	 ******************************************************/
	function is_valid_email($email) {
		$isValid = true;
		$atIndex = strrpos ( $email, "@" );
		if (is_bool ( $atIndex ) && ! $atIndex) {
			$isValid = false;
		} else {
			$domain = substr ( $email, $atIndex + 1 );
			$local = substr ( $email, 0, $atIndex );
			$localLen = strlen ( $local );
			$domainLen = strlen ( $domain );
			if ($localLen < 1 || $localLen > 64) {
				// local part length exceeded
				$isValid = false;
			} else if ($domainLen < 1 || $domainLen > 255) {
				// domain part length exceeded
				$isValid = false;
			} else if ($local [0] == '.' || $local [$localLen - 1] == '.') {
				// local part starts or ends with '.'
				$isValid = false;
			} else if (preg_match ( '/\\.\\./', $local )) {
				// local part has two consecutive dots
				$isValid = false;
			} else if (! preg_match ( '/^[A-Za-z0-9\\-\\.]+$/', $domain )) {
				// character not valid in domain part
				$isValid = false;
			} else if (preg_match ( '/\\.\\./', $domain )) {
				// domain part has two consecutive dots
				$isValid = false;
			} else if (! preg_match ( '/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace ( "\\\\", "", $local ) )) {
				// character not valid in local part unless 
				// local part is quoted
				if (! preg_match ( '/^"(\\\\"|[^"])+"$/', str_replace ( "\\\\", "", $local ) )) {
					$isValid = false;
				}
			}
			/*if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      {
         // domain not found in DNS
         $isValid = false;
      }*/
		}
		return $isValid;
	} //end func	is_valid_email

	 /******************************************************\
	 * Function Name :  get_rol_permission
	 *
	 * Task : Devolver todos los permisos de un rol
	 *
	 * Arguments : integer($rol_id);
	 *
	 * Returns : array de integer con los id de los permisos
	 *
	 ******************************************************/
	function get_rol_permission($rol_id){
		
		$query = "SELECT permission_id FROM \"permission_roles\" WHERE (rol_id = '$rol_id')";
		$result = pg_query ( $this->auth_dbConn, $query );
		
		if ($result) {
			//return pg_fetch_all ( $result );
			$count = pg_num_rows ( $result );
		
			for($j = 0; $j < $count; $j ++){
			 $row = pg_fetch_array ( $result );
			 $final_array[] = $row['permission_id'];   
			}
			return  $final_array;
		}	
		return false;	
	}
	
	/******************************************************\
	 * Function Name :  get_all_permissions
	 *
	 * Task : Devolver todos los permisos que existen(id y nombre)
	 *
	 * Arguments : none
	 *
	 * Returns : array con los id y nombres de los permisos
	 *
	 ******************************************************/
	function get_all_permission(){
		$query = "SELECT permission_id,permission_name FROM \"permission\" ";
		$result = pg_query ( $this->auth_dbConn, $query );
		
		if ($result) 
			return pg_fetch_all ( $result );
		return false;
	}
	
	} //end class

	//UserDAO::getInstance()->user_id_by_username('user_new');
?>