<?php

include_once dirname ( __FILE__ ) . '/../../../core/dao/GenericDAO.php';

class CatalogueEditDAO extends GenericDAO{	

	function loadDirectAndInverseProperty($id,$propertyId) {
		
		$tableNames = array();
		
		$query = "select t1.table_name as t1name, t2.table_name as t2name from property_type t1, property_type t2 where (t1.id = '$id' AND t2.id = t1.inverse and t1.inverse is not null) OR (t1.id = '$id' and t1.inverse is null) " ;
		$result = pg_query ( $this->dbConn, $query );
		
		$row = pg_fetch_array ( $result );
		$direct = $row['t1name'];
		$inverse = $row['t2name'];
		
		$returning[0]['idProperty'] = $propertyId;
		$returning[0]['table'] = $direct;
		
		if(isset($inverse)){
			
			$query = "select object,subject from $direct where id = '$propertyId'";
			$result = pg_query ( $this->dbConn, $query );			
			$row = pg_fetch_array ( $result );
			$object = $row['object'];
			$subject = $row['subject'];
			
		
			$query = "select id from $inverse where object = '$subject' and subject = '$object'";
			$result = pg_query ( $this->dbConn, $query );			
			$row = pg_fetch_array ( $result );			
			$returning[1]['idProperty'] = $row['id'];	
			$returning[1]['table'] = $inverse;
		}			
		
		return $returning;		
	}
	
	function updateDirectAndInverseProperty($array, $id ){
		
		$arrayCons = array(
					'table' => $array[0]['table'],
					'column_value' => array(
											array(
												'column' => 'object',
												'value' => $id
											)										
					),
					'where' => array(
								'column' => 'id',
								'value' => $array[0]['idProperty']
					)
		);
		
		
		$this->__update($arrayCons);
		
		if(isset($array[1])){
			
			$arrayCons = array(
						'table' => $array[1]['table'],
						'column_value' => array(
											array(
												'column' => 'subject',
												'value' => $id
											)
						),
						'where' => array(
									'column' => 'id',
									'value' => $array[1]['idProperty']
						)
			);
			$this->__update($arrayCons);	
		}
		
	}

	function updateLiteral($id, $idProperty, $object){
		$query = "select  table_name from property_type where id = $idProperty";	
		$result = pg_query ( $this->dbConn, $query );
		$row = pg_fetch_array ( $result );
		$table_name = $row['table_name'];
		$arrayConst = array(
						'table' => $table_name,
						'column_value' => array(
										array(
											'column' => 'object',
											'value'	=> $object											
										),
										array(
											'column' => 'value',
											'value'	=> $object											
										)
						),
						'where' => array(
									'column' => 'id',
									'value' => $id
						)
		);
		$this->__update($arrayConst);		
	}
}

?>