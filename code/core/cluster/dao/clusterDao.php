<?php
include_once dirname ( __FILE__ ) . '/../pgconnect/PGConnect.php';
include_once dirname ( __FILE__ ) . '/../cluster.php';
class ClusterDao {
	private static $cluster;
	private static $instance;
	
	private function __construct() {
		
		$cluster = array ();
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
	function getCluster($text) {
		
		$dbConn = PGConnect::getInstance ()->getCurrentConnection ();
		$ids = $this->getObjetRelated ( $dbConn, $text );
	
	}
	function getObjetRelated($dbConn, $text) {
		//getIdFromLiteral($dbConn,$text);
	//getIdFromAccessPoint($dbConn,$text);
	

	}
	function getIdFromLiteral($dbConn, $text) 
	{
			
	}
	function getPropertyRelatedToAccessPoint($dbConn, $text) {
		//seleccionar los id de las cadenas donde aparece el texto buscado
		$query = "select id from access_point where SP_ASCII(text) ilike '%$text%'";
		$result = pg_query ( $dbConn, $query );
		
		$ids = array ();
		$count = pg_num_rows ( $result );
		for($i = 0; $i < $count; $i ++) {
			$row = pg_fetch_array ( $result );
			$ids [$i] = $row ['id'];
		}
		$conjunto = implode ( ",", $ids );
		
		//seleccionar las propiedades a partir de property_as_relation donde aparecen los ids anteriores
		$query = "select pr.object as object, pgc.relname as tablename, pt.label as label, pr.id as id from property_as_relation pr inner join pg_class pgc on pr.tableoid = pgc.oid inner join property_type pt on pt.table_name = pgc.relname where subject in ($conjunto)";
		$result = pg_query ( $dbConn, $query );
		
		$property = array ();
		$count = pg_num_rows ( $result );
		for($i = 0; $i < $count; $i ++) {
			$row = pg_fetch_array ( $result );
			$property [$i] [0] = $row ['object'];
			$property [$i] [1] = $row ['tablename'];
			$property [$i] [2] = $row ['label'];
			$property [$i] [3] = $row ['id'];
		}
		
		/*for($i = 0; $i < sizeof ( $property ); $i ++) {
			$query = "select pt.range_table as rangetable from property_type pt where table_name = '$property[$i][1]'";
			$result = pg_query ( $dbConn, $query );
			
			$row = pg_fetch_array ( $result );
			$rangeTable = $row ['rangetable'];
			
			$query = "select table_name from property_type pt where pt.table_name = '$rangeTable'";
			$count = pg_num_rows ( $result );
			if ($count > 0) //es una subpropiedad
			{
				
			} 
			else //rango es una entidad 
			{*/
				$conceptSql = "select hmf.object from has_manifestation_form hmf inner join has_exemplifies he on he.object = hmf.subject where he.subject = '$property[$i][0]'";
				$query = "select cpa.text as text from controlled_access_point cpa inner join has_pref_term hpt on cpa.id = hpt.object inner join concept co on co.id = hpt.subject where co.id = ($conceptSql)";
				$result = pg_query ( $dbConn, $query );
				$row = pg_fetch_array ( $result );
				
				$entity = array ();
				$entity ['label'] = $row ['text'];
				$entity ['id'] = $property [$i] [0];
				
				$proper = array ();
				$proper ['label'] = $property [$i] [2];
				$proper ['id'] = $property [$i] [3];
				
			//	Cluster::getInstance ()->addNodo ( $entity, $proper );
				$this->findPropertyOfEntity($entity, $dbConn);
		/*	}
		}*/
	
	}
	public function findPropertyOfEntity($entity,$dbConn)
	{
		$propiedad = array ();
		$idEntity = $entity['id'];
		$query = "select pr.id as id, pt.label as label, pt.table_name as tablename from property pr inner join pg_class pgc on pgc.oid = pr.tableoid inner join property_type pt on pt.table_name = pgc.relname where pr.subject = '$idEntity'";
		$result = pg_query ( $dbConn, $query );
		$count = pg_num_rows ( $result );
		for ($i = 0; $i < $count; $i++) {
			$row = pg_fetch_array ( $result );
			$propiedad[$i]['id'] = $row['id'];
			$propiedad[$i]['label'] = $row['label'];
			$tablename = $row['tablename'];
			
			Cluster::getInstance ()->addNodo ( $entity, $propiedad[$i] );
			
			$idProperty = $propiedad[$i]['id'];
			$query1 = "select pt.id  from property_type pt where pt.domain_table = '$tablename' or pt.parent = '$idProperty'";
			$result1 = pg_query ( $dbConn, $query1 );
			$count1 = pg_num_rows($result1);
			
			if($count1 > 0)
			{
				$this->findPropertyOfEntity($propiedad[$i],$dbConn);
			}
			
		}
	}
}

?>