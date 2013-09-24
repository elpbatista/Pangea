<?php
include_once dirname ( __FILE__ ) . '/../core/pgconnect/PGConnect.php';
/** 

 */
class PGImport {
	
	private static $instance;
	protected $dbConn;
	
	private function __construct() {
		try {
			$this->dbConn = PGConnect::getInstance ()->getCurrentConnection ();
			pg_set_client_encoding ( $this->dbConn, "PG_CLIENT_CONNECTION_ENCODING" );
		} catch ( PangeaDataAccessException $e ) {
			throw $e;
		}
	}
	
	/**
	 * @example $var = PGImport::getInstance();
	 */
	public static function getInstance() {
		if (! self::$instance instanceof self) {
			self::$instance = new self ();
		}
		return self::$instance;
	}
	
	/**
	 * Metodo que lanza una excepcion, si se intenta clonar
	 * @access public
	 * @static
	 * @method __clone()
	 */
	public function __clone() {
		throw new PangeaRuntimeException ( "Operaci�n no Valida: No puedes clonar una instancia de " . get_class ( $this ) . " class.", E_USER_ERROR );
	}
	
	/**
	 * Metodo que lanza una excepcion, si se intenta deserializar un objeto de la clase
	 * @access public
	 * @static
	 * @method __wakeup()
	 */
	public function __wakeup() {
		throw new PangeaRuntimeException ( "No puedes deserializar una instancia de " . get_class ( $this ) . " class." );
	}
	
	/**
	 * Metodo para asignar un id a un objeto a traves de una secuencia
	 * @access public
	 * @static
	 * @method getNextValue()
	 */
	public function getNextValue() {
		$query = "SELECT nextval('system_object_id_seq')";
		$result = pg_query ( $this->dbConn, $query );
		$row = pg_fetch_array ( $result, 0, PGSQL_NUM );
		return $row [0];
	}
	
	public function createObject($table) {
		$idObject = $this->getNextValue ();
		$query = "INSERT INTO \"$table\" (id) VALUES ($idObject);";
		$result = pg_query ( $this->dbConn, $query );
		return $idObject;
	}
	/*public function createObjectProposed($table) {
		$idObject = $this->getNextValue ();
		$query = "INSERT INTO \"$table\" (id, newvalue) VALUES ('$idObject', TRUE);";
		$result = pg_query ( $this->dbConn, $query );
		return $idObject;
	}*/
	
	/*private function createObjectProposed($table) {
		$idObject = $this->getNextValue ();
		$query = "INSERT INTO \"$table\" (id) VALUES ('$idObject');";
		$result = pg_query ( $this->dbConn, $query );
		$idNomenclator = $this->verifyConcept ( 'pangea:State', 'propuesto' );
		if (! $idNomenclator) {
			$idNomenclator = $this->createObject ( 'pangea:State' );
			$idLabel = $this->setLabel ( 'propuesto' );
			$this->setRelation ( $idNomenclator, 'skosxl:prefLabel', $idLabel );
		}
		
		$this->setRelation ( $idObject, 'pangea:warning', $idNomenclator );
		return $idObject;
	}*/
	
	private function createObjectProposed($table) {
		$idObject = $this->getNextValue ();
		$query = "INSERT INTO \"$table\" (id) VALUES ($idObject);";
		$result = pg_query ( $this->dbConn, $query );
		$this->setRelationLiteral ( $idObject,  'pangea:warning', 'propuesto', 'xsd:string', 'sp' );
		return $idObject;
	}
	
	/*public function createTable($tableObject, $parent) {
		$parent = $this->replaceStr ( $parent );
		$resp = array ();
		$resp [0] = 'old';
		$tableObject = $this->replaceStr ( $tableObject );
		$query = "SELECT relname FROM pg_class WHERE relname = '$tableObject'";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) { //esa tabla ya existe
			$row = pg_fetch_row ( $result );
			$IdObject = $row [0];
			$query = "INSERT INTO \"$parent\" (id) VALUES ($IdObject)";
			$result = pg_query ( $this->dbConn, $query );
		} else {
			$key = $tableObject . '_pkey';
			$query = "CREATE TABLE \"$tableObject\" (CONSTRAINT \"$key\" PRIMARY KEY (id)) INHERITS (\"$parent\") WITH (OIDS=TRUE); ALTER TABLE \"$tableObject\" OWNER TO pangea;";
			$result = pg_query ( $this->dbConn, $query );
			$resp [0] = 'new';
			//escribo en el meta los datos de la tabla que acabo de crear
			$label = trim ( str_replace ( "pangea:Nomenclator_", "", $tableObject ) );
			$queryMeta = "insert into \"rdf:class\"(uri, description, label) values ('$tableObject','owl:Class','$label')";
			$result = pg_query ( $this->dbConn, $queryMeta );
			$queryMeta2 = "insert into \"rdf:subClassOf\" (subject,object) values ('$tableObject','$parent')";
			$result = pg_query ( $this->dbConn, $queryMeta2 );
		}
		$resp [1] = $tableObject;
		return $resp;
	}*/
	
	public function setRelation($subject, $tableRelation, $object) {
		$query = "INSERT INTO \"$tableRelation\" (subject, object) VALUES ($subject, $object)";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	public function setRelationLiteral($subject, $tableRelation, $object, $dataType, $lang) {
		$query = "INSERT INTO \"$tableRelation\" (subject, object, datatype, lang) VALUES ($subject, '$object', '$dataType', '$lang')";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	public function getRelation($subject, $tableRelation, $object) {
		$IdObject = NULL;
		$query = "SELECT id FROM \"$tableRelation\" WHERE \"$tableRelation\".object='{$object}' AND \"$tableRelation\".subject='{$subject}'";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_row ( $result );
			$IdObject = $row [0];
		}
		return $IdObject;
	}
	
	public function getRelationByLiteral($table, $tableRelation, $object) {
		$IdObject = NULL;
		$query = "SELECT id FROM \"$table\" WHERE \"$table\".id IN (SELECT DISTINCT \"$tableRelation\".subject FROM \"$tableRelation\" WHERE \"$tableRelation\".object = '$object')";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_row ( $result );
			$IdObject = $row [0];
		}
		return $IdObject;
	}
	
	/*public function getIdLiteralForm($label) {
		$idLabel = NULL;
		$query = "SELECT subject as subject FROM \"skosxl:literalForm\" WHERE SP_ASCII(object) ilike SP_ASCII('$label')";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$idLabel = $row ['subject'];
		}
		return $idLabel;
	}*/
	
	
	private function getIdLiteralForm($label) {
		$idLabel = NULL;
		$label = str_replace ( ' ', '', $label );
		$label = mb_strtolower ( $label, 'UTF-8' );
		
		$query = "SELECT subject as subject FROM \"skosxl:literalForm\" WHERE SP_ASCII(replace(lower(object),' ', '')) ilike SP_ASCII('$label')";
		
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$idLabel = $row ['subject'];
		
		}
		
		return $idLabel;
	}
	
	public function setIdLiteralForm($label, $idLabel, $lang) {
		$IdObject = $this->getNextValue ();
		$query = "INSERT INTO \"skosxl:literalForm\" (id, subject, object, lang) VALUES ($IdObject, $idLabel, '$label', '$lang');";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	/*public function setIdConcept() {
		$IdObject = $this->getNextValue ();
		$query = "INSERT INTO \"frbr:Concept\" (id) VALUES ($IdObject)";
		$result = pg_query ( $this->dbConn, $query );
		if ($result)
			return $IdObject;
		else
			return false;
	}*/
	
	public function verifyEntity($label, $table, $typeEntity) {
		$labelM = str_replace ( ' ', '', $label );
		$labelM = mb_strtolower ( $labelM, 'UTF-8' );
		$query = "select subject from \"$table\" vt inner join \"$typeEntity\" te on vt.subject = te.id  where SP_ASCII(replace(lower(vt.object),' ', '')) ilike SP_ASCII('$labelM')";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$id = $row ['subject'];
			return $id;
		}
		return false;
	}
	
	/*public function verifyEntity($entityType, $label) {
		$idEntity = NULL;
		$query = "select subject from \"skos:prefLabel\" pl 
		          inner join \"$entityType\" et on et.id = pl.subject 
		          where SP_ASCII(object) ilike SP_ASCII('$label')";
		$result = pg_query ( $this->dbConn, $query );
		$rows = pg_num_rows ( $result );
		if ($rows > 0) {
			$row = pg_fetch_row ( $result );
			$idEntity = $row [0];
		}
		return $idEntity;
	}*/
	
	/*public function verifyEntity($label, $table, $typeEntity) {
		$labelM = str_replace ( ' ', '', $label );
		$labelM = mb_strtolower ( $labelM, 'UTF-8' );
		$query = "select subject from \"$table\" vt inner join \"$typeEntity\" te on vt.subject = te.id  where SP_ASCII(replace(lower(vt.object),' ', '')) ilike SP_ASCII('$labelM')";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$id = $row ['subject'];
			return $id;
		}
		return false;
	}*/
	
		
	/*public function verifyConcept($entityType, $label) {
		$idConcept = false;
		$idLabel = $this->getIdLiteralForm ( $label );
		if ($idLabel != NULL) {
			$query = "select subject from \"skosxl:prefLabel\" pl 
		          inner join \"$entityType\" et on et.id = pl.subject 
		          where pl.object = '$idLabel'";
			$result = pg_query ( $this->dbConn, $query );
			$rows = pg_num_rows ( $result );
			if ($rows > 0) {
				$row = pg_fetch_row ( $result );
				$idConcept = $row [0];
			}
		}
		return $idConcept;
	}*/
	
	public function updateParent($labelId, $parent) {
		$query = "SELECT subject FROM \"skosxl:prefLabel\" WHERE object=$labelId";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_row ( $result );
			$idConcept = $row [0]; //concepto relacionado a la etiqueta
			if ($parent != 'frbr:Concept')
				$this->setRelation ( $idConcept, "skos:broaderTransitive", $parent );
		} else {
			echo "No se encontro subject de prefLabel";
		}
	
		//return $parent;
	}
	
	public function replaceStr($tableString) {
		$tableString = utf8_decode ( $tableString );
		$search = array ('á', 'é', 'í', 'ó', 'ú', 'ñ', ' ', '.', '(', ')' );
		$replace = array ('a', 'e', 'i', 'o', 'u', 'n', '_', '_', '_', '' );
		$tableString = utf8_encode ( str_ireplace ( $search, $replace, $tableString ) );
		return $tableString;
	}
	
	public function setIdTable($parent) {
		$parent = $this->replaceStr ( $parent );
		$position = strpos ( $parent, ":" );
		if ($position == false)
			$parent = "pangea:" . $parent;
		$IdObject = $this->getNextValue ();
		$query = "INSERT INTO \"$parent\" (id) VALUES ($IdObject)";
		$result = pg_query ( $this->dbConn, $query );
		return $IdObject;
	}
	
	public function setLabel($label) {
		$idLabel = $this->getIdLiteralForm ( $label );
		if (! $idLabel) {
			$idLabel = $this->createObject ( "skosxl:Label" );
			$this->setIdLiteralForm ( $label, $idLabel, "sp" );
		}
		return $idLabel;
	}
	
	public function hasLabel($valueNode) {
		$idValueNode = $this->getIdLiteralForm ( $valueNode );
		$result = array ();
		if (! $idValueNode) {
			$idValueNode = $this->createObject ( "skosxl:Label" );
			$this->setIdLiteralForm ( $valueNode, $idValueNode, "sp" );
			$result [0] = 'new';
		
		} else { //la etiqueta ya existe
			$result [0] = "hasLabel";
		
		}
		$result [1] = $idValueNode;
		return $result;
	}
	
	public function hasLabelNomenclator($valueNode) {
		$idValueNode = $this->getIdLiteralForm ( $valueNode );
		$result = '';
		if (! $idValueNode) {
			$idValueNode = $this->createObject ( "skosxl:Label" );
			$this->setIdLiteralForm ( $valueNode, $idValueNode, "sp" );
		}
		$result = $idValueNode;
		return $result;
	}
	
	/*public function isParent($valueNode, $idLabel, $parent, $idScheme) {
		//Es un padre, creo una tabla en el arbol de su padre
		$position = strpos ( $valueNode, ":" );
		if ($position == false)
			$valueNode = "pangea:" . $valueNode;
		$position = strpos ( $parent, ":" );
		if ($position == false)
			$parent = "pangea:" . $parent;
		$result = $this->createTable ( $valueNode, $parent );
		if ($result [0] == 'new') { //tuve que crear la tabla
			$this->setRelation ( $result [1], "skosxl:prefLabel", $idLabel );
			$this->setRelation ( $result [1], "skos:inScheme", $idScheme );
			$this->setRelation ( $result [1], "skos:hasTopConcept", $idScheme );
		}
		return $result [1];
	}*/
	
	public function createConcept($idLabel, $parent, $idScheme, $isParent = false) {
		$idConcept = $this->createObject ( "frbr:Concept" );
		if ($idConcept) {
			$this->setRelation ( $idConcept, "skosxl:prefLabel", $idLabel );
			$this->setRelation ( $idConcept, "skos:inScheme", $idScheme );
			if ($parent != 'frbr:Concept')
				$this->setRelation ( $idConcept, "skos:broader", $parent );
			if ($isParent)
				$this->setRelation ( $idConcept, "skos:hasTopConcept", $idScheme );
			return $idConcept;
		} else
			return false;
	}
	
	public function createNomenclator($idLabel, $nomenclatorTable, $parent) {
		$idNomenclator = $this->createObject ( $nomenclatorTable );
		if ($idNomenclator) {
			$this->setRelation ( $idNomenclator, "skosxl:prefLabel", $idLabel );
			if ($parent != $nomenclatorTable) //si son iguales es porque el concepto es tope.
				$this->setRelation ( $idNomenclator, "skos:broader", $parent );
			return $idNomenclator;
		} else
			return false;
	}
	
	/*public function isParentNomenclator($valueNode, $idLabel, $parent) {
		//Es un padre, creo una tabla en el arbol de su padre
		$position = strpos ( $valueNode, ":" );
		if ($position == false)
			$valueNode = "pangea:Nomenclator_" . $valueNode;
		$position = strpos ( $parent, ":" );
		if ($position == false)
			$parent = "pangea:Nomenclator_" . $parent;
		$result = $this->createTable ( $valueNode, $parent );
		if ($result [0] == 'new') { //tuve que crear la tabla
			$this->setRelation ( $result [1], "skosxl:prefLabel", $idLabel );
		}
		return $result [1];
	}*/
	/*public function isSon($idLabel, $parent, $idScheme) {
		//Es un hijo, inserto un id en su padre	
		//$firstValue = $valueNode;
		$parent = $this->replaceStr ( $parent );
		$position = strpos ( $parent, ":" );
		$idTable = '';
		if ($position == false)
			$parent = "pangea:" . $parent;
		$query = "select subject from \"skosxl:prefLabel\" pl inner join \"$parent\" pa on pl.subject = pa.id  where pl.object = '$idLabel'";
		$result = pg_query ( $this->dbConn, $query );
		if (! (pg_num_rows ( $result ) > 0)) {
			$idTable = $this->setIdTable ( $parent );
			$this->setRelation ( $idTable, "skosxl:prefLabel", $idLabel );
			$this->setRelation ( $idTable, "skos:inScheme", $idScheme );
		}
		return $idTable;
	}*/
	/*public function isSonNomenclator($idLabel, $parent) {
		//Es un hijo, inserto un id en su padre	
		//$firstValue = $valueNode;
		$parent = $this->replaceStr ( $parent );
		$position = strpos ( $parent, ":" );
		$idTable = '';
		if ($position == false)
			$parent = "pangea:Nomenclator_" . $parent;
		$query = "select subject from \"skosxl:prefLabel\" pl inner join \"$parent\" pa on pl.subject = pa.id  where pl.object = '$idLabel'";
		$result = pg_query ( $this->dbConn, $query );
		if (! (pg_num_rows ( $result ) > 0)) {
			$idTable = $this->setIdTable ( $parent );
			$this->setRelation ( $idTable, "skosxl:prefLabel", $idLabel );
		}
		return $idTable;
	}*/
	public function findAmbiguous($nodeValue) {
		//Chequear si es ambiguo
		$result = array ();
		$result [1] = '';
		$result [2] = '';
		$position = strpos ( $nodeValue, "(" );
		if ($position) {
			echo "- AMBIGUO -" . $nodeValue . "<br/>";
			$result [0] = true;
			$position_2 = strpos ( $nodeValue, ")" );
			//$result [1] = trim ( substr ( $nodeValue, 0, $position - 1 ) ); //Valor del concepto
			$result [1] = trim ( substr ( $nodeValue, 0, $position ) ); //lo cambié porque el último valor que es el lenght lo cuenta a partir de 1 y no de cero
			if ($position_2) {
				$position_3 = $position_2 - $position;
				$result [2] = trim ( substr ( $nodeValue, $position + 1, $position_3 - 1 ) ); //Valor del parent
			} else {
				echo 'Hay un error en el ambiguo, no encuentro el 2do parentesis';
				$result [0] = false;
			}
		} else {
			$result [0] = false;
		}
		return $result;
	}
	
	public function setAmbiguous($idConcept, $valueConcept, $valueParent) {
		$query = "INSERT INTO \"ambiguous\" (id_concept, concept, parent) VALUES ($idConcept, '$valueConcept', '$valueParent');";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	/*public function setEntity($nodeValue, $tableEntity, $tableRelation) {
		$idLabel = $this->setLabel ( $nodeValue );
		$idEntity = $this->getRelationByLiteral ( $tableEntity, $tableRelation, $idLabel );
		if (! $idEntity) {
			$idEntity = $this->createObject ( $tableEntity );
			$this->setRelation ( $idEntity, $tableRelation, $idLabel );
		}
		return $idEntity;
	}*/
	
	/*public function relateEntity($label, $idEntity, $tableRelation) {
		$idLabel = $this->setLabel ( $label );
		$this->setRelation ( $idEntity, $tableRelation, $idLabel );
	}*/
	
	public function relateEntityWithConcept($label, $idEntity, $tableRelation, $verificationTable, $tableEntity) {
		$idLabel = $this->setLabel ( $label );
		$query = "select subject from \"$verificationTable\" vt inner join \"$tableEntity\" te on vt.subject = te.id  where object = '$idLabel'";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$id = $row ['subject'];
		
		} else {
			$id = $this->createObjectProposed ( $tableEntity );
			$this->setRelation ( $id, $verificationTable, $idLabel );
		}
		$this->setRelation ( $idEntity, $tableRelation, $id );
	}
	
	public function relateEntityWithEntity($label, $idEntity, $tableRelation, $verificationTable, $tableEntity) {
		$labelM = str_replace ( ' ', '', $label );
		$labelM = mb_strtolower ( $labelM, 'UTF-8' );
		$query = "select subject from \"$verificationTable\" vt inner join \"$tableEntity\" te on vt.subject = te.id  where SP_ASCII(replace(lower(vt.object),' ', '')) ilike SP_ASCII('$labelM')";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$id = $row ['subject'];
		
		} else {
			$id = $this->createObjectProposed ( $tableEntity );
			$this->setRelation ( $id, $verificationTable, $label );
		}
		$this->setRelation ( $idEntity, $tableRelation, $id );
	}
	
	public function insertAndRelateEntity($label, $entityTable, $relationTable) {
		$idLabel = $this->setLabel ( $label );
		$idEntity = $this->createObject ( $entityTable );
		//$idEntity = $this->getRelationByLiteral ( $tableEntity, $tableRelation, $idLabel );
		$this->setRelation ( $idEntity, $relationTable, $idLabel );
		return $idEntity;
	}
	
	/*funcion temporal para eliminar alguna stablas y probar un script*/
	public function deleteTables() {
		/*
		$result = pg_query($this -> dbConn, "delete from \"skosxl:Label_sp\";");
		$result = pg_query($this -> dbConn, "delete from \"skosxl:hiddenLabel\";");
		$result = pg_query($this -> dbConn, "delete from \"skos:ConceptScheme\";");
		$result = pg_query($this -> dbConn, "delete from \"skos:inScheme\";");
		$result = pg_query($this -> dbConn, "delete from \"skos:hasTopConcept\";");
		$result = pg_query($this -> dbConn, "delete from \"skosxl:literalForm\";");
		$result = pg_query($this -> dbConn, "delete from \"skosxl:prefLabel\";");
		*/
		$result = pg_query ( $this->dbConn, "DELETE FROM \"pangea\" CASCADE;" );
	
	}
}
?>