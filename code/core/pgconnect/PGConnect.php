<?php
include_once dirname ( __FILE__ ) . '/../exceptions/PangeaException.php';

/** 
 * Manejo de conexiones a base de datos postgres, 
 * implementa el patron singleton.
 * @property mixed String  $host
 * @property mixed String  $dbName
 * @property mixed String  $user
 * @property mixed String  $password
 * @property mixed String  $port
 * @author Baromir, Anabel
 */
class PGConnect {
	/**
	 * Almacena el host para la conexiï¿½n a la Base de Datos.
	 * @property $host String
	 * @access private
	 */
	private $host = "laurasia.pangea.ohc.cu";
	//private $host = "homesh.nodo.ohc.cu";
	//private $host = "localhost";
	
	/**
	 * Almacena el nombre de la Base de Datos a la que se va a conectar.
	 * @property $dbName String
	 * @access private
	 */
	//private $dbName = "PANGEA_DESA";
	private $dbName = "PANGEA_DESA3";
	
	/**
	 * Almacena el usuario para la conexiï¿½n a la Base de Datos.
	 * @property $user String
	 * @access private
	 */
	private $user = "pangea";
	
	/**
	 * Almacena la contraseï¿½a para la conexiï¿½n a la Base de Datos.
	 * @property $password String
	 * @access private
	 */
	private $password = "p@Ng3A";
	
	/**
	 * Almacena el puerto de conexiï¿½n de la Base de Datos.
	 * @property $port String
	 * @access private
	 */
	private $port = "5432";
	
	/**
	 * Representa una instancia de la clase PGConnect.
	 * @property $instance PGConnect
	 * @static 
	 * @access private
	 */
	private static $instance;
	
	/**
	 * Constructor de la clase.
	 * @access private
	 * @method __construct()
	 */
	private function __construct() {
	}
	
	/**
	 * Mï¿½todo  que da cumplimiento al patron singleton, devolviendo simepre la
	 * misma instancia de la clase.
	 * @access public
	 * @static
	 * @method getInstance() 
	 * @return PGConnect
	 * @example $var = PGConnect::getInstance();
	 */
	public static function getInstance() {
		if (! self::$instance instanceof self) {
			self::$instance = new self ();
		}
		return self::$instance;
	}
	
	/**
	 * Mï¿½todo que lanza una exepciï¿½n, si se intenta clonar
	 * @access public
	 * @static
	 * @method __clone()
	 */
	public function __clone() {
		throw new PangeaRuntimeException ( "Operación no Valida: No puedes clonar una instancia de " . get_class ( $this ) . " class.", E_USER_ERROR );
	}
	
	/**
	 * Mï¿½todo que lanza una exepciï¿½n, si se intenta deserializar un objeto de la clase
	 * @access public
	 * @static
	 * @method __wakeup()
	 */
	public function __wakeup() {
		throw new PangeaRuntimeException ( "No puedes deserializar una instancia de " . get_class ( $this ) . " class." );
	}
	
	public function setHost($host) {
		$this->host = $host;
	}
	public function getHost() {
		return $this->host;
	}
	
	public function setDBName($dbName) {
		$this->dbName = $dbName;
	}
	public function getDBName() {
		return $this->dbName;
	}
	
	public function setUser($user) {
		$this->user = $user;
	}
	public function getUser() {
		return $this->user;
	}
	
	public function setPassword($password) {
		$this->password = $password;
	}
	public function getPassword() {
		return $this->password;
	}
	
	/**
	 * Mï¿½todo  que devuelve la conexiï¿½n que se encuentra abierta, 
	 * si no hay ninguna, la crea.
	 * @access public
	 * @method getCurrentConnection();
	 * @return Recurso de conexion de PostgreSQL si se pudo conectar, false si falla.
	 */
	public function getCurrentConnection() {
		$connection_string = "host=" . $this->host . " port=" . $this->port . " dbname=" . $this->dbName . " user=" . $this->user . " password=" . $this->password;
		if (! $dbConn = pg_connect ( $connection_string ))
			throw new PangeaConnectException ( "Conexion Fallida: ", null, pg_last_error () );
		return $dbConn;
	}
	/**
	 * Mï¿½todo  que crea una nueva conexiï¿½n.
	 * @access public
	 * @method getNewConnection();
	 * @return Recurso de conexion de PostgreSQL si se pudo conectar, false si falla.
	 */
	public function getNewConnection($host, $dbName, $user, $passw, $port) {
		$connection_string = "host=" . $host . " port=" . $port . " dbname=" . $dbName . " user=" . $user . " password=" . $passw;		
		$newDbConn = pg_connect ( $connection_string) ;
		return $newDbConn;
	}
}

?>
