<?php
include_once dirname ( __FILE__ ) . '/../configs/config.php';
require_once ZENDAPI_INCLUDE_DIR . 'Log.php';
require_once ZENDAPI_INCLUDE_DIR . 'Log/Writer/Stream.php';
include_once dirname ( __FILE__ ) . '/PangeaTimer.php';

abstract class PangeaObject {
	private $log;
	protected $myself, $timer;
	
	public function __construct() {
		$this->myself = new ReflectionClass ( $this );
		
		try {
			$writers = array ();
			
			$timeZone = new DateTimeZone ( PG_DEFAULT_TIMEZONE );
			$date = new DateTime ( "now", $timeZone );
			
			$logFileName = 'application-' . $date->format ( 'Y-m-d' ) . '.log';
			
			$writers [] = new Zend_Log_Writer_Stream ( PG_LOG_PATH . $logFileName );
			
			$this->log = Zend_Log::factory ( $writers );
			
			$this->timer = new PangeaTimer ( false );
			
			// Test logging directory repository existens, will try to create it if no present
			if (! file_exists ( PG_LOG_PATH ))
				mkdir ( PG_LOG_PATH );
		
		} catch ( PangeaIOException $e ) {
			$this->logMessage ( $e->getMessage (), Zend_Log::ERR );
		}
	}
	
	protected function buildTriple($subject, $predicate, $object) {
		$triple = array ();
		
		if (! is_array ( $object )) {
			$newObject ['type'] = (is_numeric ( $object )) ? 'uri' : 'literal';
			$newObject ['value'] = $object;
			
			$object = $newObject;
		} else {
			$keys = array_keys ( $object );
			
			if (isset ( $keys [2] ) && (! isset ( $object [$keys [2]] ) || empty ( $object [$keys [2]] )))
				unset ( $object [$keys [2]] );
			
			if (isset ( $keys [3] ) && (! isset ( $object [$keys [3]] ) || empty ( $object [$keys [3]] )))
				unset ( $object [$keys [3]] );
		}
		
		$triple ['subject'] = $subject;
		$triple ['predicate'] = $predicate;
		$triple ['object'] = $object;
		
		return $triple;
	}
	
	/**
	 * calcDate
	 *
	 * @access protected
	 * @param  string $interval
	 * @return date
	 */
	protected function calcDate($interval) {
		date_default_timezone_set ( PG_DEFAULT_TIMEZONE );
		
		$calc = strtotime ( "+ " . $interval . " day" );
		
		$return = date ( PG_DEFAULT_DATETIME_FORMAT, $calc );
		
		return $return;
	}
	
	/**
	 * comparingDates
	 *
	 * @access protected
	 * @param  string $date1, string $date2
	 * @return float
	 */
	protected function comparingDates($date1, $date2) {
		return round ( (strtotime ( $date2 ) - strtotime ( $date1 )) / 60 / 60 / 24 );
	}
	
	protected function getINValueFromArray($valuesArray) {
		if (is_array ( $valuesArray )) {
			sort ( $valuesArray );
			
			$result = implode ( ",", $valuesArray );
		} else
			$result = $valuesArray;
		
		return $result;
	}
	
	protected function getListOffsetAndPgSize($page, $pageSize, $total) {
		$offsets = array ('offset' => 0, 'pageSize' => 0, 'pages' => 0 );
		
		$pages = ceil ( $total / $pageSize );
		
		if (($page > $pages) || ($total == 0))
			return $offsets;
		
		$offsets ['pageSize'] = $pageSize;
		
		if (($page < 1) or ($pageSize < 1)) {
			$page = 1;
			$pageSize = 1;
			$offsets ['pageSize'] = $total;
		} else
			$offsets ['offset'] = ($page - 1) * $pageSize;
		
		if ($page > $pages)
			$offsets ['offset'] = ($pages - 1) * $pageSize;
		
		$offsets ['pages'] = $pages;
		
		return $offsets;
	}
	
	/**
	 * getLowercaseStream
	 *
	 * @access protected
	 * @param  string $tream
	 * @return string
	 */
	protected function getLowercaseStream($stream) {
		$endoding = mb_detect_encoding ( $stream );
		
		$stream = mb_strtolower ( $stream, $endoding );
		
		return $stream;
	}
	
	protected function getSerializeDBObject($object) {
		/*		 
		$search = array ('&', '"', '\'', '<', '>', "\t", "\r", "\n", "\0" );
		$replace = array ('&amp;', '&quot;', '&#039;', '&lt;', '&gt;', '&nbsp;&nbsp;', '&#13;', '&#10;', "~~NULL_BYTE~~" ); 
		 * */
		$search = array ("\0" );
		$replace = array (PG_NULL_CHAR_STR_REPLACE );
		
		$stream = '';
		if (isset ( $object ))
			$stream = str_replace ( $search, $replace, serialize ( $object ) );
		
		return $stream;
	}
	
	protected function getSortedArray($unsortedArray, $asc = TRUE) {
		$iterator = new ArrayIterator ( $unsortedArray );
		
		$iterator->natsort ();
		
		$sortedArray = array_keys ( $iterator->getArrayCopy () );
		
		if (! $asc)
			$sortedArray = array_reverse ( $sortedArray );
		
		return $sortedArray;
	}
	
	protected function getUnserializeDBObject($stream) {
		/*
		$search = array ('&amp;', '&quot;', '&#039;', '&lt;', '&gt;', '&nbsp;&nbsp;', '&#13;', "~~NULL_BYTE~~" );
		$replace = array ('&', '"', '\'', '<', '>', "\t", "\r", "\0" );
		 * */
		
		$search = array (PG_NULL_CHAR_STR_REPLACE );
		$replace = array ("\0" );
		
		$object = null;
		if (isset ( $stream ) && (! empty ( $stream )))
			$object = unserialize ( str_replace ( $search, $replace, $stream ) );
		
		return $object;
	}
	
	/**
	 * objectToArray
	 *
	 * @access protected
	 * @param  object $obj
	 * @return array
	 */
	protected function objectToArray($obj) {
		if (is_object ( $obj ))
			$obj = get_object_vars ( $obj );
		else
			return $obj;
		
		foreach ( $obj as $key => $value )
			$obj [$key] = $this->objectToArray ( $value );
		
		return $obj;
	}
	
	/**
	 * log
	 *
	 * @access protected
	 * @param  string $message, int $priority
	 * @return void
	 */
	public function logMessage($message, $priority) {
		if (PG_LOG_ACTIVITY && isset ( $this->log )) {
			$callerObj = get_class ( $this );
			
			$callerInfo = debug_backtrace ();
			
			//$message = 'Generated by: ' . $callerObj . '::' . $callerInfo [1] ['function'] . '. ' . $message;
			

			$this->log->log ( $message, $priority );
		}
	}
	
	/**
	 * setFrom
	 *
	 * @access public
	 * @param mixed $data Array of variables to assign to instance
	 * @return void
	 */
	public function setFrom($data) {
		if (is_array ( $data ) && count ( $data )) {
			$valid = get_class_vars ( get_class ( $this ) );
			foreach ( $valid as $var => $val ) {
				if (isset ( $data [$var] )) {
					$this->$var = $data [$var];
				}
			}
		}
	}
	
	/**
	 * toArray
	 *
	 * @access public
	 * @return mixed Array of member variables keyed by variable name
	 */
	public function toArray() {
		$defaults = $this->myself->getDefaultProperties ();
		$return = array ();
		foreach ( $defaults as $var => $val ) {
			if ($this->$var instanceof PangeaObject) {
				$return [$var] = $this->$var->toArray ();
			} else {
				$return [$var] = $this->$var;
			}
		}
		
		return $return;
	}
	
	public function __destruct() {
		if ($this->log instanceof Zend_Log)
			$this->log->__destruct ();
	}
}
?>