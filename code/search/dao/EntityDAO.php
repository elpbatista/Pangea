<?php
include_once dirname ( __FILE__ ) . '/../../core/dao/GenericDAO.php';

class EntityDAO extends GenericDAO {
	
	function EntityDAO() {
		parent::GenericDAO ();
	}
	
	/** 
	 * INSERT, UPDATE y DELETE un lote de TRIPLETAS
	 * @param array $triples un arreglo pasado en esta forma:
	 * $triples = array(
	 * array("frbr:complement", array(5,6), array(1,2)),
	 * array("frbr:complement", array(3,4), null)
	 * );
	 */
	
	private function RDF_CREATE(&$idEntity, $entityType, $records) {
		
		$data = array ();
		
		if (isset ( $entityType ) && ! empty ( $entityType )) {
			// A potencial bNode...			
			

			$query = "Select * from insert_bnode('" . $idEntity . "', '" . $entityType . "') AS (subject TEXT, bnode TEXT);";
			
			$stat = pg_exec ( $this->dbConn, $query );
			
			$row = pg_fetch_all ( $stat );
			
			if (! empty ( $row ))
				$idEntity = $row [0] ['subject'];
		}
		
		if (! empty ( $records )) {
			$query = "Select * from rdf_insert(";
			$query .= $this->toCREATEArray ( $idEntity, $entityType, $records );
			$query .= ") AS (subject TEXT, property TEXT, object TEXT, err_code TEXT, err_msg TEXT);";
			
			if (PG_LOG_ACTIVITY)
				$this->logQuery ( "FROM RDF_CREATE function:: " . $query );
			
			$stat = pg_exec ( $this->dbConn, $query );
			
			$data = pg_fetch_all ( $stat );
		}
		
		return $data;
	}
	
	private function RDF_DELETE($idEntity, $records) {
		
		$data = array ();
		
		if (! empty ( $records )) {
			$query = "Select * from rdf_delete(";
			$query .= $this->toDELETEArray ( $idEntity, $records );
			$query .= ") AS (subject TEXT, property TEXT, object TEXT, err_code TEXT, err_msg TEXT);";
			
			if (PG_LOG_ACTIVITY)
				$this->logQuery ( "FROM RDF_DELETE function:: " . $query );
			
			$stat = pg_exec ( $this->dbConn, $query );
			
			$data = pg_fetch_all ( $stat );
		}
		
		return $data;
	}
	
	private function RDF_UPDATE($idEntity, $records) {
		
		$query = "Select * from rdf_update(";
		$query .= $this->toUPDATEArray ( $idEntity, $records );
		$query .= ") AS (subject NAME, property NAME, o1d TEXT, n3w TEXT, err_code TEXT, err_msg TEXT);";
		
		if (PG_LOG_ACTIVITY)
			$this->logQuery ( "FROM RDF_UPDATE function:: " . $query );
		
		$stat = pg_exec ( $this->dbConn, $query );
		
		$data = pg_fetch_all ( $stat );
		
		return $data;
	}
	
	private function toCREATEArray($idEntity, $entityType, $records) {
		/*
		 * 
		 	SELECT * FROM rdf_insert(
		 					ARRAY[('subject', 'class', 
		 						ARRAY[
		 								('property', ('type','value', NULL, NULL)::RDF_OBJECT)::DTIONARY, 
		 								('property1', ('type1','value1', NULL, NULL)::RDF_OBJECT)::DTIONARY
		 							  ])::TINSERT
		 						]) AS (subject text, property text, object TEXT, err_code TEXT, err_msg TEXT); 
		 * */
		$result = "";
		
		foreach ( $records as $property => $values )
			foreach ( $values as $value ) {
				if (! empty ( $result ))
					$result .= ", ";
				
				$result .= ($property == null) ? "(NULL, " : "('" . $property . "', ";
				
				if (! array_key_exists ( 'new', $value ))
					$result .= "NULL";
				else {
					$result .= "('" . $value ['new'] ['type'] . "', ";
					
					if (isset ( $value ['new'] ['class'] ))
						$result .= "'$value ['new'] ['class']', ";
					else
						$result .= "NULL, ";
					
					$result .= "'" . $value ['new'] ['value'] . "', ";
					
					if (isset ( $value ['new'] ['lang'] ))
						$result .= "'$value ['new'] ['lang']', ";
					else
						$result .= "NULL, ";
					
					if (isset ( $value ['new'] ['datatype'] ))
						$result .= "'$value ['new'] ['datatype']')";
					else
						$result .= "NULL)";
					
					$result .= "::RDF_OBJECT";
				}
				
				$result .= ")::DTIONARY";
			}
		
		$result = ", ARRAY[$result])::TINSERT]";
		
		if (isset ( $entityType ))
			$result = "'$entityType'" . $result;
		else
			$result = "NULL" . $result;
		
		$result = "ARRAY[('$idEntity', " . $result;
		
		return $result;
	}
	
	private function toDELETEArray($idEntity, $records) {
		/*
		 * 
			SELECT * FROM rdf_update(
				ARRAY[('subject', 'predicate', ('uri', 'value', NULL, NULL)::RDF_OBJECT)::TRIPLES]) 
				AS (subject TEXT, property TEXT, object TEXT, err_code TEXT, err_msg TEXT); 
		 * */
		$result = "";
		
		foreach ( $records as $property => $values ) {
			if (empty ( $values )) {
				if (! empty ( $result ))
					$result .= ", ";
				
				$result .= "('" . $idEntity . "', '" . $property . "', NULL)::TRIPLES";
			} else
				foreach ( $values as $value ) {
					if (! empty ( $result ))
						$result .= ", ";
					
					$result .= "('" . $idEntity . "', '" . $property . "', ('" . $value ['old'] ['type'] . "', NULL, '" . $value ['old'] ['value'] . "', NULL, NULL)::RDF_OBJECT)::TRIPLES";
				}
		}
		
		$result = "ARRAY[$result]";
		
		return $result;
	}
	
	private function toUPDATEArray($idEntity, $records) {
		/*
		 * 
			SELECT * FROM rdf_update(
				ARRAY[('subject', 'predicate', ('uri', 'value', NULL, NULL)::RDF_OBJECT)::TRIPLES]) 
				AS (subject TEXT, property TEXT, object TEXT, err_code TEXT, err_msg TEXT); 
		 * */
		$result = "";
		
		foreach ( $records as $property => $values )
			foreach ( $values as $value ) {
				if (array_key_exists ( 'new', $value ) && array_key_exists ( 'new', $value )) {
					if (! empty ( $result ))
						$result .= ", ";
					
					$result .= "('" . $idEntity . "', '" . $property . "'";
					
					$result .= ", ('" . $value ['old'] ['type'] . "', '" . $value ['old'] ['value'] . "'";
					
					if (array_key_exists ( 'lang', $value ['old'] ))
						$result .= ", '" . $value ['old'] ['lang'] . "'";
					else
						$result .= ", NULL";
					
					$result .= ", NULL)::RDF_OBJECT";
					
					$result .= ", ('" . $value ['new'] ['type'] . "', '" . $value ['new'] ['value'] . "'";
					
					if (array_key_exists ( 'lang', $value ['new'] ))
						$result .= ", '" . $value ['new'] ['lang'] . "'";
					else
						$result .= ", NULL";
					
					$result .= ", NULL)::RDF_OBJECT)::CRUD";
				}
			}
		
		if (! empty ( $result ))
			$result = "ARRAY[$result]";
		
		return $result;
	}
	
	function RDF_CRUD(&$ctgRecords) {
		
		$returnRows = array ();
		
		if (array_key_exists ( 'forDelete', $ctgRecords ['categories'] )) {
			$result = $this->RDF_DELETE ( $ctgRecords ['id'], $ctgRecords ['categories'] ['forDelete'] );
			
			if (is_array ( $result ))
				$returnRows = $result;
		}
		
		$records = array ();
		
		if (array_key_exists ( 'forInsert', $ctgRecords ['categories'] ))
			$records = $ctgRecords ['categories'] ['forInsert'];
		
		$result = $this->RDF_CREATE ( $ctgRecords ['id'], $ctgRecords ['class'], $records );
		
		if (is_array ( $result ))
			$returnRows = array_merge ( $returnRows, $result );
		
		return $returnRows;
	}
	
	public function URL_WEB_DOCS($id) {
		$arrayResult = array ('mime' => "text/html" );
		
		$query = "Select url, mime from \"MAP_WEB_DOCS\" where resource = '$id'";
		
		$result = pg_query ( $this->dbConn, $query );
		$count = pg_num_rows ( $result );
		
		for($i = 0; $i < $count; $i ++) {
			$row = pg_fetch_array ( $result );
			
			$arrayResult = array ('url' => $row ['url'], 'mime' => $row ['mime'] );
		}
		
		pg_free_result ( $result );
		
		return $arrayResult;
	}
}