<?php
include_once dirname ( __FILE__ ) . '/../pgconnect/PGConnect.php';

class ReadEntity {
	
	private static $instance;
	
	private function __construct() {
	}
	
	public static function getInstance() {
		if (! self::$instance instanceof self) {
			self::$instance = new self ();
		}
		return self::$instance;
	}
	
	public function __clone() {
		trigger_error ( "Operación Invalida: No puedes clonar una instancia de " . get_class ( $this ) . " class.", E_USER_ERROR );
	}
	
	public function __wakeup() {
		trigger_error ( "No puedes deserializar una instancia de " . get_class ( $this ) . " class." );
	}
	
	public function listEntities($typeElementID = 9,$typeElement="Libro") {
		$elementos = array ();
		
		$dbConn = PGConnect::getInstance ()->getCurrentConnection ();
		$obj =null;
		$tableName = $this->tableNameforTypeElementID ( $typeElementID, $dbConn );
		
		if($tableName == 'phisical_entity')
		{
			$idconcept = $this->getConceptIdByTypeelement($typeElement, $dbConn);
			$obj =$this->getObjectOfTypeElementByPhisicalEntity($idconcept,$dbConn);
		}
		else {
			$obj = $this->getObjectOfTypeElementByTableName ( $tableName, $dbConn );
		}
		
		
		for($i = 0; $i < sizeof ( $obj ); $i ++)
			$elementos [$i] = $this->getElement ( $obj [$i], $dbConn );
		
		return $elementos;
	}
	private function getObjectOfTypeElementByPhisicalEntity($conceptid,$dbConn)
	{
		$query = "select pe.id as id from phisical_entity pe INNER JOIN exemplifies ex on pe.id = ex.subject inner join manifestation_form mf on ex.object = mf.subject  where mf.object = '$conceptid'"; //inner join pref_term pt on mf.object = pt.subject inner join controlled_access_point ca on pt.object = ca.id
		$result = pg_query ( $dbConn, $query );
		
		$count = pg_num_rows ( $result );
		
		$arreglo = array ();
		for($i = 0; $i < $count; $i ++) {
			$row = pg_fetch_array ( $result );
			$arreglo [$i] = $row ['id'];
		}
		return $arreglo;
	}
	private function getConceptIdByTypeelement($typeElement,$dbConn)
	{
		$query = "select concept_id from entity_type where label = '$typeElement'";
		$result = pg_query ( $dbConn, $query );
		$conceptId = pg_fetch_row ( $result );
		return $conceptId [0];
	}
	private function tableNameforTypeElementID($id, $dbConn) {
		$query = "select et.table_name from entity_type et where et.id = '$id'";
		$result = pg_query ( $dbConn, $query );
		$tableName = pg_fetch_row ( $result );
		return $tableName [0];
	}
	private function getObjectOfTypeElementByTableName($tableName, $dbConn) {
		$query = "select id as id from $tableName";
		$result = pg_query ( $dbConn, $query );
		
		$count = pg_num_rows ( $result );
		$arreglo = array ();
		for($i = 0; $i < $count; $i ++) {
			$row = pg_fetch_array ( $result );
			$arreglo [$i] = $row ['id'];
		}
		
		return $arreglo;
	}
	private function getPropertyOIDByIdSubject($id, $dbConn) {
		
		$query = "select distinct tableoid as oid from property where subject = $id";
		$result = pg_query ( $dbConn, $query );
		
		$count = pg_num_rows ( $result );
		$arreglo = array ();
		for($i = 0; $i < $count; $i ++) {
			$row = pg_fetch_array ( $result );
			$arreglo [$i] = $row ['oid'];
		}
		return $arreglo;
	}
	private function getLabelRangeFromPropertyByOID($property_oid, $dbConn) {
		$query = "select pt.label as label, pt.range_table as rangetable, pt.is_visible as visible from property_type pt inner join pg_class pgcl on pt.table_name = pgcl.relname where pgcl.oid = '$property_oid'";
		$result = pg_query ( $dbConn, $query );
		
		$count = pg_num_rows ( $result );
		$arreglo = array ();
		
		$row = pg_fetch_array ( $result );
		
		$arreglo [0] = $row ['label'];
		$arreglo [1] = $row ['rangetable'];
		$arreglo [2] = $row['visible'];
		
		return $arreglo;
	}
	private function getRange($tableName, $subject, $dbConn) {
		$query = "select object from $tableName where subject = '$subject'";
		$result = pg_query ( $dbConn, $query );
		
		$count = pg_num_rows ( $result );
		$arreglo = array ();
		for($i = 0; $i < $count; $i ++) {
			$row = pg_fetch_array ( $result );
			$arreglo [$i] = $row ['object'];
		}
		return $arreglo;
	}
	
	private function getTableNameByOID($propertyOid, $dbConn) {
		$query = "select pgc.relname as tablename from pg_class pgc where pgc.oid = '$propertyOid'";
		$result = pg_query ( $dbConn, $query );
		
		$row = pg_fetch_array ( $result );
		return $row ['tablename'];
	}
	private function getValueObjectByTableNameAndObject($tableRangeName, $object, $dbConn) {
		$query = "select trn.text as value from $tableRangeName trn where trn.id = '$object'";
		$result = pg_query ( $dbConn, $query );
		
		$row = pg_fetch_array ( $result );
		
		return $row['value'];
	}
	
	private function getElement($id, $dbConn) {
		$propiedad = array ();
		
		/*sacar los oid de las propiedasdes y el id del objeto con las que la entidad esta relacionada*/
		$properties = $this->getPropertyOIDByIdSubject ( $id, $dbConn );
		
		for($i = 0; $i < sizeof ( $properties ); $i ++) {
			$property_oid = $properties [$i];
			
			
			$label_tablerange = $this->getLabelRangeFromPropertyByOID ( $property_oid, $dbConn );
			
			$label = $label_tablerange [0];
			$tablerange = $label_tablerange [1];
			$visible = $label_tablerange[2];
			$value = "";
			
			$tablePropertyName = $this->getTableNameByOID ( $property_oid, $dbConn );
			
			$range = $this->getRange ( $tablePropertyName, $id, $dbConn );
			
			$temporal = array ();
			
			
			for($j = 0; $j < sizeof ( $range ); $j ++) 
			{	
				//18935 punto acceso controlado   || $rangeOid == '18935'
				if ($tablerange == $tablePropertyName) 
				{
					$temporal [$j] = $range[$j];
				} 
				else if ($tablerange == 'controlled_access_point' || $tablerange == 'issn' || $tablerange == 'isbn') 
				{
					
					$value = $this->getValueObjectByTableNameAndObject ( $tablerange, $range[$j], $dbConn );
					$temporal [$j] = $value;		
				} 
				else 
				{
					$temporal [$j] = $this->getElement ( $range[$j], $dbConn ); 
				}
			}
			if($visible=='t')
				$propiedad [$i][0] = $label;
			$propiedad [$i][1] = $temporal;
		}
		  
		return $propiedad;
	}
}
?>