<?php
	include_once dirname(__FILE__).'/../pgconnect/PGConnect.php'; 
	 /** 
	  * Clase que recorre la estructura de la Base de Datos,
	  * implementa el patron singleton.
	  * @author Baromir, Anabel
	  */
	class BDStructure
	{		
		/**
		 * Representa una instancia de la clase BDStructure.
		 * @property $instance BDStructure
		 * @access private
		 * @static 
		 */
		private static $instance;
		
		/**
		 * Constructor de la clase.
		 * @access private
		 * @method __construct()
		 */
		private function __construct() { } 
		
		/**
   		 * Metodo que da cumplimiento al patron singleton, devolviendo simepre la
   		 * misma instancia de la clase.
   		 * @access public
   		 * @static
   		 * @method getInstance() 
   		 * @return BDStructure
   		 * @example $var = BDStructure::getInstance();
   		 */
   		public static function getInstance() 
   		{ 
      		if (!self::$instance instanceof self) 
      		{ 
         		self::$instance = new self; 
      		} 
      		return self::$instance; 
  	 	}
  	 	/**
  	 	 * Método que lanza una exepción, si se intenta clonar
  	 	 * @access public
  	 	 * @static
  	 	 * @method __clone()
  	 	 */ 
		public function __clone() 
   		{ 
      		trigger_error("Operación Invalida: No puedes clonar una instancia de ". get_class($this) ." class.", E_USER_ERROR ); 
   		}
   		
   		/**
   		 * Método que lanza una exepción, si se intenta deserializar un objeto de la clase
  	 	 * @access public
  	 	 * @static
  	 	 * @method __wakeup()
   		 */
   		public function __wakeup() 
   		{ 
      		trigger_error("No puedes deserializar una instancia de ". get_class($this) ." class."); 
  	 	}  
		
  	 	/**
  	 	 * Método que devuelve las tablas de la Base de Datos que no heredan de otras tablas, 
  	 	 * la información se devuelve en una matriz donde las filas representan las tablas y 
  	 	 * las columnas la informacion de las mismas: columna #1 - id de objeto(oid), 
  	 	 * columna #2 - nombre de la tabla, columna #3 - cantidad de registros en la Tabla.
  	 	 * @access private
  	 	 * @method readAloneTable($dbConn)
  	 	 * @param Recurso de conexion de PostgreSQL $dbConn
  	 	 * @return array:
  	 	 */
		private function readAloneTable($dbConn)
		{				
			$query = "select pgcl.oid, pgcl.relname, pgcl.reltuples from pg_class pgcl WHERE pgcl.oid not in (select pgin.inhrelid from pg_inherits pgin) and pgcl.relname in (SELECT relname FROM pg_class WHERE relname !~ '^(pg_|sql_)' and relkind = 'r')";
			$result = pg_query($dbConn,$query);
			
			$elements = array();
			$count = pg_num_rows($result);
			
			for($i=0; $i< $count; $i++)
			{
				$row = pg_fetch_array($result);
				
				$subElements = array(); 
				$subElements[0] = $row["oid"];
				$subElements[1] = $row["relname"];
				$subElements[2] = $row["reltuples"];
				
				$elements[$i][0] =	$subElements;
			}
			
			pg_free_result($result);
			
			return $elements;
		}
		
		/**
		 * Método que devuelve las tablas hijas de la tabla cuyo oid se pasa como parametro, 
		 * la información se devuelve en una matriz donde las filas representan las tablas y 
  	 	 * las columnas la informacion de las mismas: columna #1 - id de objeto(oid), 
  	 	 * columna #2 - nombre de la tabla, columna #3 - cantidad de registros en la Tabla.
		 * @access private
  	 	 * @method readInheritsTable($id,$dbConn)
		 * @param $id
		 * @param $dbConn
		 * @return array:
		 */
		private function  readInheritsTable($id,$dbConn)
		{
				$query = "select pgcl.oid, pgcl.relname, pgcl.reltuples from pg_class pgcl inner join pg_inherits pgin on pgcl.oid = pgin.inhrelid where pgin.inhparent = '$id'";	
				$result = pg_query($dbConn,$query);
				$count = pg_num_rows($result);
				$childs = array();
				for ($j = 0; $j < $count; $j++) 
				{
					$subElements = array(); 
					
					$row = pg_fetch_array($result);
					
					$subElements[0] = $row["oid"];
					$subElements[1] = $row["relname"];
					$subElements[2] = $row["reltuples"];
					
					$childs[$j][0] = $subElements;
					
					
					$childs[$j][1] = $this->readInheritsTable($childs[$j][0][0],$dbConn);
					
					$temp = $childs[$j][1];
					for ($i = 0; $i < sizeof($temp); $i++) 
					{
						$childs[$j][0][2]+=$temp[$i][0][2];	
					}
				}
				pg_free_result($result);
				return $childs;
		}
		
		/**
		 * Método que conforma un arreglo multidimencional con la estructura alborea de la base 
		 * de datos, la informacion se devuelve en una matriz donde las filas representan las tablas y las columnas la informacion de las mismas: columna #1 - id de objeto(oid), columna #2 - nombre de la tabla,
		 * columna #3 - cantidad de registros en la Tabla, columna #4 - arreglo con 
		 * las informacion de las tablas hijas (recursividad).
		 * @access public
  	 	 * @method getStructure()
		 * @return array
		 */
		public function getStructure()
		{	
			$dbConn = PGConnect::getInstance()->getCurrentConnection();

			$vacuumQuery = "VACUUM ANALYZE";			
			pg_query($dbConn,$vacuumQuery);
			
			$elements = $this->readAloneTable($dbConn);
						
			$length = sizeof($elements);
			for ($i = 0; $i < $length; $i++) 
			{
				$id = $elements[$i][0][0];
				$elements[$i][1] = $this->readInheritsTable($id,$dbConn);
				
				$temp = $elements[$i][1];
				for ($j = 0; $j < sizeof($temp); $j++) 
				{
					$elements[$i][0][2]+=$temp[$j][0][2];	
				}
			}
			
			pg_close($dbConn);
			
			return $elements;
		}
	
	}

?>