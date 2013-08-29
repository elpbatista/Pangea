<?php
	include_once dirname(__FILE__).'/../pgconnect/PGConnect.php';
	
	class Meta 
	{
		private static $instance;
		
		private function __construct() { } 
		
   		public static function getInstance() 
   		{ 
      		if (!self::$instance instanceof self) 
      		{ 
         		self::$instance = new self; 
      		} 
      		return self::$instance; 
  	 	} 
 
		public function __clone() 
   		{ 
      		trigger_error("Operaci�n Invalida: No puedes clonar una instancia de ". get_class($this) ." class.", E_USER_ERROR ); 
   		}
   		
   		public function __wakeup() 
   		{ 
      		trigger_error("No puedes deserializar una instancia de ". get_class($this) ." class."); 
  	 	}  
		
		public function readType_element($dbConn)
		{
			$query = "SELECT et.id AS id,et.sp_label AS label FROM entity_type et";			
			$result = pg_query($dbConn,$query);
			
			$type_element = array();
			$count = pg_num_rows($result);
			for ($i = 0; $i < $count; $i++) 
			{
				$row = pg_fetch_array($result);
				$subElements = array(); 
				$subElements[0] = $row["id"];
				$subElements[1] = $row["label"];
				$type_element[$i][0] = $subElements;	
			}
			
			pg_free_result($result);
			
			return $type_element;
		}
		private function readType_elementProperty($id,$dbConn)
		{
			$query = "SELECT pt.id AS id, pt.sp_label AS label FROM property_type pt inner join entity_property ep on pt.id = ep.property_type where ep.entity_type = '$id' and pt.parent is NULL  and pt.is_visible = 'true'";			
			$result = pg_query($dbConn,$query);
			
			$count = pg_num_rows($result);
			$prop = array();
			for ($j = 0; $j < $count; $j++) 
			{
				
				$subElements = array(); 
					
				$row = pg_fetch_array($result);
					
				$subElements[0] = $row["id"];
				$subElements[1] = $row["label"];
		
				$prop[$j][0] = $subElements;
						
				$prop[$j][1] = $this->readSubPropertyOf($prop[$j][0][0],$dbConn);
				
			}
			return $prop;
		}
		private function readSubPropertyOf($id,$dbConn)
		{
			$query = "SELECT pt.id AS id, pt.sp_label AS label FROM property_type pt where (pt.parent = '$id'or pt.domain_table = (select ptl.table_name from property_type ptl where ptl.id = '$id')) and pt.is_visible = 'true' and pt.table_name not like 'is_%' ";
			$result = pg_query($dbConn,$query);
			
			$count = pg_num_rows($result);
			$prop = array();
			
			for ($j = 0; $j < $count; $j++) 
			{
				$subElements = array(); 
					
				$row = pg_fetch_array($result);
					
				$subElements[0] = $row["id"];
				$subElements[1] = $row["label"];
					
				$prop[$j][0] = $subElements;
					
				$prop[$j][1] = $this->readSubPropertyOf($prop[$j][0][0],$dbConn);	
			}
			pg_free_result($result);
			return $prop;
		}
		
		public function getStructure()
		{	
			$dbConn = PGConnect::getInstance()->getCurrentConnection();

			$elements = $this->readType_element($dbConn);
						
			$length = sizeof($elements);
			for ($i = 0; $i < $length; $i++) 
			{
				$id = $elements[$i][0][0];
				
				$elements[$i][1] = $this->readType_elementProperty($id,$dbConn);
			}
			
			pg_close($dbConn);
			
			return $elements;
		}
		
		
	}

?>