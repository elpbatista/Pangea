<?php
include_once dirname ( __FILE__ ) . '/../PangeaObject.php';
include_once dirname ( __FILE__ ) . '/../pgconnect/PGConnect.php';

abstract class GenericDAO extends PangeaObject {
	
	static $ENTITY = 0;
	static $PROPERTY = 1;
	static $ACCESS_POINT = 2;
	static $LITERAL = 3;
	
	static $LANG = array ('ES' => 'cpa_sp', 'FR' => 'cpa_fr', 'DE' => 'cpa_de', 'EN' => 'cpa_en', 'IT' => 'cpa_it', 'OT' => 'cpa_ot', 'CT' => 'cpa_ct' );
	
	protected $dbConn;
	
	protected $preparedStatements = array ();
	
	private function buildQueryAccessPoint($array) {
		$table = GenericDAO::$LANG [$array ['lang']];
		$table = isset ( $table ) ? $table : 'access_point';
		$query = "INSERT INTO $table";
		return $query .= " (text) VALUES ('" . $array ['value'] . "')";
	}
	
	private function buildSetForUpadate($array) {
		$set = "";
		$size = sizeof ( $array );
		for($i = 0; $i < $size; $i ++) {
			$set .= $array [$i] ['column'] . " = '" . $array [$i] ['value'] . "'";
			if ($i != $size - 1)
				$set .= ", ";
		}
		
		return $set;
	}
	
	private function buildWhereById($array) {
		$where = $array ['column'] . " = '" . $array ['value'] . "'";
		return $where;
	}
	
	protected function logQuery($stream) {
		$query = 'INSERT INTO "queryLogs"("log") VALUES($1)';
		
		$data = array ($stream );
		
		$result = $this->prepareAndExecuteQuery ( $query, $data );
		
		return $result;
	}
	
	protected function prepareAndExecuteQuery($query, $data) {
		$queryID = md5 ( $query );
		
		if (! isset ( $this->preparedStatements [$queryID] )) {
			$this->preparedStatements [$queryID] = $query;
			
			$result = pg_prepare ( $this->dbConn, $queryID, $query );
		}
		
		$result = pg_execute ( $this->dbConn, $queryID, $data );
		
		return $result;
	}
	
	protected function setUTF8() {
		$query = "SET NAMES 'UTF8';";
		
		$result = pg_query ( $this->dbConn, $query );
	}
	
	function GenericDAO() {
		
		try {
			parent::__construct ();
			
			$this->dbConn = PGConnect::getInstance ()->getCurrentConnection ();
			
			pg_set_client_encoding ( $this->dbConn, PG_CLIENT_CONNECTION_ENCODING );
		
		} catch ( PangeaDataAccessException $e ) {
			throw $e;
		}
	}
	
	public function __update($array) {
		
		$query = "UPDATE " . $array ['table'] . " SET " . $this->buildSetForUpadate ( $array ['column_value'] ) . " WHERE " . $this->buildWhereById ( $array ['where'] );
		$result = pg_query ( $this->dbConn, $query );
	}
	
	public function refreshConn() {
		
		try {
			$this->dbConn = PGConnect::getInstance ()->getCurrentConnection ();
		
		} catch ( PangeaDataAccessException $e ) {
			throw $e;
		}
	}
	
	public function saveAccessPoint($value, $lang) {
		$query = $this->buildQueryAccessPoint ( array ('lang' => $lang, 'value' => $value ) );
		$query .= " returning id";
		$result = pg_query ( $this->dbConn, $query );
		$row = pg_fetch_array ( $result );
		$id = $row ['id'];
		return $id;
	
	}
	
	public function saveRelation($idPropertyType, $subject, $object) {
		$query = "select t1.table_name as t1name, t2.table_name as t2name from property_type t1, property_type t2 where (t1.id = $idPropertyType AND t2.id = t1.inverse and t1.inverse is not null) OR (t1.id = $idPropertyType and t1.inverse is null) ";
		$result = pg_query ( $this->dbConn, $query );
		
		$row = pg_fetch_array ( $result );
		$direct = $row ['t1name'];
		$inverse = $row ['t2name'];
		
		$query = "INSERT INTO $direct (subject, object) VALUES ('$subject', '$object') returning id";
		$result = pg_query ( $this->dbConn, $query );
		$row = pg_fetch_array ( $result );
		$id = $row ['id'];
		
		if (isset ( $inverse )) {
			$query = "INSERT INTO $inverse (subject, object) VALUES ('$object', '$subject')";
			$result = pg_query ( $this->dbConn, $query );
		}
		
		return $id;
	}
	
	public function saveLiteral($idProperty, $subject, $object) {
		$query = "select table_name from property_type where id = '$idProperty'";
		$result = pg_query ( $this->dbConn, $query );
		$row = pg_fetch_array ( $result );
		$table_name = $row ['table_name'];
		
		$query = "INSERT INTO $table_name (subject, object, value) VALUES ('$subject', '$object', '$object') returning id";
		$result = pg_query ( $this->dbConn, $query );
		$row = pg_fetch_array ( $result );
		$id = $row ['id'];
		return $id;
	}
	
	public function saveEntity($label) {
		$query = "SELECT table_name FROM entity_type where sp_label = '$label'";
		$result = pg_query ( $this->dbConn, $query );
		$row = pg_fetch_array ( $result );
		$table_name = $row ['table_name'];
		
		$query = "INSERT INTO $table_name DEFAULT VALUES returning id";
		$result = pg_query ( $this->dbConn, $query );
		$row = pg_fetch_array ( $result );
		$id = $row ['id'];
		return $id;
	}
}
?>