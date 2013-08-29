<?php
include_once dirname ( __FILE__ ) . '/../core/pgconnect/PGConnect.php';
/*function bibliographic_item($entity,$collection) {
	global $dbConn;
	$search  = array('Ã¡', 'Ã©', 'Ã­', 'Ã³', 'Ãº', 'Ã±', ' ', '.');
	$replace = array('a', 'e', 'i', 'o', 'u', 'n', '_', '_');
	$entity = "pangea:item_".strtolower(str_ireplace($search, $replace, $entity));
	$cmd="SELECT tablename FROM pg_tables WHERE tablename = '{$entity}'";
	$result = pg_query($dbConn, $cmd);
	if (pg_num_rows($result)==0) {
	  //echo "La tabla no estÃ¡ </br>";
		$cmd="CREATE TABLE \"$entity\" (CONSTRAINT \"{$entity}_pkey\" PRIMARY KEY (id)) INHERITS (\"frbr:Item\") WITH (OIDS=TRUE); ALTER TABLE \"$entity\" OWNER TO pangea;";
		$result = pg_query($dbConn, $cmd);
	  if (!$result) echo "No se pudo crear la tabla </br>";
	}fgff
	if (empty($collection)) {
		$IdItem=create_object($entity);
	} else {
    $collection = $entity."_".strtolower(str_ireplace($search, $replace, $collection));
		//echo $collection."<br/>";
		$result = pg_query($dbConn, "SELECT tablename FROM pg_tables WHERE tablename = '{$collection}'");	
		if (pg_num_rows($result)==0) {
			//echo "La tabla no estÃ¡ </br>";
			$cmd="CREATE TABLE \"$collection\" (CONSTRAINT \"{$collection}_pkey\" PRIMARY KEY (id)) INHERITS (\"$entity\") WITH (OIDS=TRUE); ALTER TABLE \"$collection\" OWNER TO pangea;";
			$result = pg_query($dbConn, $cmd);
			if (!$result) echo "No se pudo crear la tabla </br>";
		}
		$IdItem=create_object($collection);
  }
	return $IdItem;
}*/

class DataImport {
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
	
	private function readInheritsTable($id) { //se utiliza para sacar todas las tablas a las que hay que arreglarle el constraint
		$query = "select pgcl.oid, pgcl.relname from pg_class pgcl inner join pg_inherits pgin on pgcl.oid = pgin.inhrelid where pgin.inhparent = $id";
		$result = pg_query ( $this->dbConn, $query );
		$count = pg_num_rows ( $result );
		
		$resultado = array ();
		for($j = 0; $j < $count; $j ++) {
			$row = pg_fetch_array ( $result );
			
			$resultado [] = $row ["relname"];
			
			$resultado = array_merge ( $resultado, $this->readInheritsTable ( $row ["oid"] ) );
		}
		
		return $resultado;
	}
	
	
	function getHineritFrom($parent) {
		$query = "select oid as oid from pg_class where relname = '$parent'";
		$result = pg_query ( $this->dbConn, $query );
		$row = pg_fetch_array ( $result );
		$id = $row ['oid'];
		$Inherits = $this->readInheritsTable ( $id );
		
		return $Inherits;
	}
	
	public function fixConstraint($table){ //primary key entidades
		$constraintName = $table.'_pkey';
		//primero quito el constraint que está
		$query ="alter table \"$table\" drop constraint \"$constraintName\"";
		$result = pg_query ( $this->dbConn, $query );
		//ahora pongo el nuevo
		$query = "ALTER TABLE \"$table\" ADD CONSTRAINT \"$constraintName\" PRIMARY KEY (id)";
		$result = pg_query ( $this->dbConn, $query );
	}

	public function fixConstraint2($table){// unique properties
		$constraintName = $table.'_idkey';
		//primero quito el constraint que está
		$query ="alter table \"$table\" drop constraint \"$constraintName\"";
		$result = pg_query ( $this->dbConn, $query );
		//ahora pongo el nuevo
		$query = "ALTER TABLE \"$table\" ADD CONSTRAINT \"$constraintName\" unique (id)";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	public function fixConstraint3($table){ // primary key properties
		$constraintName = $table.'_pkey';
		//primero quito el constraint que está
		$query ="alter table \"$table\" drop constraint \"$constraintName\"";
		$result = pg_query ( $this->dbConn, $query );
		//ahora pongo el nuevo
		$query = "ALTER TABLE \"$table\" ADD CONSTRAINT \"$constraintName\" PRIMARY KEY (subject, object1)";
		$result = pg_query ( $this->dbConn, $query );
		echo "pasé";
	}
	
	public function deleteTriggers($table){//son dos triggers a quitar
		//set_numeric_fields
		$query ="drop trigger set_numeric_id on \"$table\"";
		$result = pg_query ( $this->dbConn, $query );
	}

	
	public function getYears() { //esta funcion es solo para mover los años que estaban en pangea:year para strDate
		$query = "select id, subject, object from \"pangea:year\"";
		$result = pg_query ( $this->dbConn, $query );
		
		$query2 = "delete from \"pangea:year\"";
		$result2 = pg_query ( $this->dbConn, $query2 );
		
		return $result;
	}
	
	public function insertYears($id, $subject, $object) {
		$query = "insert into \"pangea:strDate\" (id, subject, object) values ($id, $subject, '$object')";
		pg_query ( $this->dbConn, $query );
	}
	
	public function arreglarSegundasFechas($segundasFechas) {
		foreach ( $segundasFechas as $fecha ) {
			$subject = $fecha [0];
			$object = $fecha [1];
			$query = "insert into \"pangea:date\" (subject, object, datatype, lang) values ($subject,'$object','xsd:date','no')";
			pg_query ( $this->dbConn, $query );
		}
	}
	
	public function arreglarFechaCompuesta() { //esta funcion es solo para arreglar las fechas compuestas que se estaban guardando mal
		$query = "select * from \"pangea:date\" where object ilike '%-%'";
		$result = pg_query ( $this->dbConn, $query );
		$total = pg_num_rows ( $result );
		for($i = 0; $i < $total; $i ++) {
			$row = pg_fetch_array ( $result );
			$object = $row ['object'];
			$subject = $row ['subject'];
			echo $object . '</br>';
			$aux = explode ( '-', $object );
			$secondDate = '00/00/' . trim ( $aux [1] );
			echo $secondDate . '</br>';
			$query2 = "insert into \"pangea:date\" (subject, object, datatype, lang) values ($subject,'$secondDate','xsd:date','no')";
			pg_query ( $this->dbConn, $query2 );
			$firstDate = trim ( $aux [0] );
			echo $firstDate . '</br>';
			$query3 = "update \"pangea:date\" set object = '$firstDate' where object = '$object' and subject = $subject";
			pg_query ( $this->dbConn, $query3 );
		}
	}
	public function updateProducer() {
		/*esta funcion la use solo para relacionar los productores que estaban relacionados con expresiones con las respectivas manifestaciones*/
		$query = "select fp.subject as expression, fe.object as manifestation from \"frbr:producer\" fp left outer join \"frbr:embodiment\" fe
              on fe.subject = fp.subject where fp.subject in (select id from \"frbr:Expression\")";
		$result = pg_query ( $this->dbConn, $query );
		$total = pg_num_rows ( $result );
		for($i = 0; $i < $total; $i ++) {
			$row = pg_fetch_array ( $result );
			$expression = $row ['expression'];
			$manifestation = $row ['manifestation'];
			$update = "update \"frbr:producer\" set subject = $manifestation where subject = $expression";
			$result1 = pg_query ( $this->dbConn, $update );
			echo "manifestation " . $manifestation . " expression " . $expression . '<br/>';
		}
	}
	public function getItems($idConcept) { //esta funcion solo la use para arreglar los tipos de documentos que salian vacios en la vista
		$query1 = "select ex.subject as manift, fc.itemid, fc.itemtype, fc.ownername as biblio from \"frbr:exemplar\" ex 
		          inner join \"itemsForCollection\" fc on ex.object = cast(fc.itemid as text)";
		$result = pg_query ( $this->dbConn, $query1 );
		$total = pg_num_rows ( $result );
		for($i = 0; $i < $total; $i ++) {
			$row = pg_fetch_array ( $result );
			$itemtype = $row ['itemtype'];
			if ($itemtype == null) {
				$manifestation = $row ['manift'];
				echo $row ['biblio'];
				$query2 = "insert into \"pangea:hasForm\" (subject, object) values ($manifestation , $idConcept)";
				$result2 = pg_query ( $this->dbConn, $query2 );
			}
		}
	}
	
	public function getRelationWork($tableRelation) {
		$query = "select tr.subject as newwork, tr.object as oldwork, fr.object as expression from \"$tableRelation\" tr 
		         left outer join \"frbr:realization\" fr on fr.subject = tr.subject";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	}
	
	public function updateRelationWorkWithExpression($oldWork, $newWork, $oldexpression) {
		$query = "update \"frbr:realization\" set subject = $oldWork where subject = $newWork and object =$oldexpression";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	}
	
	public function updateRelationExpressionWithManifestation($idNewExpression, $oldexpression) {
		$query = "update \"frbr:embodiment\" set subject = $idNewExpression where subject = $oldexpression";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	}
	
	public function updateRelationExpressionWithManifestation2($idNewExpression, $oldexpression, $manifestationOld) {
		$query = "update \"frbr:embodiment\" set subject = $idNewExpression where subject = $oldexpression and object = $manifestationOld";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	}
	
	public function deleteFromTableRelation($tableRelation) {
		$query = "delete from \"$tableRelation\"";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	public function getNotesFromCreator() {
		$query = "select * from \"frbr:creator\" where object = 'Esta obra es una compilación de varias obras.'";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	}
	
	public function deleteNotes() { //este metodo se usa solo en el fichero arreglarRolCompilador
		$query1 = "delete from \"frbr:creator\" where object = 'Esta obra es una compilación de varias obras.'";
		$result1 = pg_query ( $this->dbConn, $query1 );
	}
	
	public function reubicarNotes($id, $subject, $object) {//este metodo se usa solo en el fichero arreglarRolCompilador
		$query = "insert into \"pangea:warning\" (id, subject, object) values ($id, $subject, '$object')";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	}
	
	public function guetRoles($table) { //esta funcion es solo para arreglar algunos roles en la BD
		$query = "select pr.subject as expression, pr.object as person, fe.object as manifestation from \"$table\" pr
		          left outer join \"frbr:embodiment\" fe on pr.subject = fe.subject 
		          group by pr.subject, pr.object, fe.object order by  pr.subject";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	}
	
	public function guetRoles2($table) { //esta funccion es para arreglar el rol compilador y sirve para arreglar los roles tipo 3 tambien
		$query = "select pr.subject as expression, pr.object as person, fr.subject as work from \"$table\" pr
		          left outer join \"frbr:realization\" fr on cast(pr.subject as text) = fr.object 
		          group by pr.subject, pr.object, fr.subject order by  pr.subject";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	
	}
	
	public function guetRoles3($table) {
		$query = "select id, subject as expression, object as person from \"$table\" 
		          group by subject, object, id order by subject";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	}
	
	public function deleteRolesFromOldTable($table) {
		$query1 = "delete from \"$table\"";
		$result1 = pg_query ( $this->dbConn, $query1 );
	}
	
	public function insertIntoRealizer($id, $subject, $object) {
		$query = "insert into \"frbr:realizer\" (id, subject, object) values ($id, $subject, $object)";
		$result = pg_query ( $this->dbConn, $query );
		echo "este fue el resultado d ela insercion " . $result . '<br/>';
		return $result;
	
	}
	public function getAllFromRealizerOf() {
		$query = "select subject, object from \"frbr:realizerOf\"";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	}
	
	public function deleteOldRelation($idWorkOld, $expression) {
		$query = "delete from \"frbr:realization\" where subject = $idWorkOld and object = $expression";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	}
	
	public function guetExpressionsFromCreator() {
		$query = "select fc.subject as expression, fc.object as person, fr.subject as work from \"frbr:creator\" fc 
                 left outer join \"frbr:realization\" fr on fr.object = cast(fc.subject as text)
                 where fc.subject in (select id from \"frbr:Expression\")";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	}
	
	public function updateCreator($idExpression, $idwork, $idPerson) {
		$query = "update \"frbr:creator\" set subject = $idwork where subject = $idExpression and object = $idPerson";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	}
	
	public function getNomenclator($form, $nomenclatorTable, $nomenclatorParent) {
		$id = false;
		$query = "select pl.subject as id from \"skosxl:prefLabel\" pl left outer join \"skosxl:literalForm\" lf on pl.object = cast(lf.subject as text)
	 	          where lf.object = '$form' and pl.subject in (select id from \"$nomenclatorTable\")";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$id = $row ['id'];
		
		//echo "estaba el nomenclador y el id es " . $id . '<br/>';
		} else {
			$idLabel = $this->setLabel ( $form, $lang = 'sp' );
			$id = $this->createObject ( $nomenclatorTable );
			$this->setRelation ( $id, 'skosxl:prefLabel', $idLabel );
			//lo relaciono con el nomenclador padre
			$query = "select pc.id from \"$nomenclatorTable\" pc left outer join \"skosxl:prefLabel\" pl on pc.id = pl.subject left outer join \"skosxl:literalForm\" lf on pl.object = cast(lf.subject as text) where lf.object = '$nomenclatorParent'";
			$result = pg_query ( $this->dbConn, $query );
			$row = pg_fetch_array ( $result );
			$idParent = $row ['id'];
			$this->setRelation ( $id, "skos:broader", $idParent );
		
		//echo "cree el nomenclador y el id es " . $id . '<br/>';
		}
		return $id;
	}
	
	public function importAlias($name, $newName, $alias) {
		$query = "select subject, object from \"skos:prefLabel\" where object = '$name'";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) { //si encuentro una biblioteca con ese nombre
			$row = pg_fetch_array ( $result );
			$subject = $row ['subject'];
			$object = $row ['object'];
			//actualizo el nombre de la biblioteca
			$query1 = "update \"skos:prefLabel\" set subject = $subject, object = '$newName' where subject = $subject and object = '$object' ";
			$result1 = pg_query ( $this->dbConn, $query1 );
			//inserto el alias
			$query2 = "insert into \"skos:altLabel\" (subject, object) values ($subject, '$alias')";
			$result2 = pg_query ( $this->dbConn, $query2 );
		
		}
	
	}
	private function verifyRelationBetweenManifAndExpress($oldManifestation, $typeExpression) {
		$idWork = false;
		$query = "select fr.subject as work from \"frbr:embodiment\" fe left outer join \"frbr:realization\" fr on fr.object = cast(fe.subject as text)
		         where fe.object = '$oldManifestation' and fe.subject in (select id from \"$typeExpression\")";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$idWork = $row ['work'];
		}
		return $idWork;
	}
	public function fixRolesType1and2($typeExpression, $nomenclator, $parentNomenclator, $tableNomenclator, $oldManifestation, $author_personal) {
		
		/*primero tengo que verificar que la manifestacion no esté relacionada ya,
		 * si la manifestacion ya esta relacionada con una expression de tipo $typeExpression, cojo la obra a la que
		 * esta asociada dicha expression y la relaciono con la persona */
		
		$idNewWork = $this->verifyRelationBetweenManifAndExpress ( $oldManifestation, $typeExpression );
		
		if ($idNewWork == false) {
			
			//obtengo el id del nomenclador
			$idNomenclator = $this->getNomenclator ( $nomenclator, $tableNomenclator, $parentNomenclator );
			
			/* creo una expression de tipo $typeExpression, le relleno la propiedad pangea:hasForm 
	y la relaciono con la manifestation vieja y con una nueva obra y la obra la relaciono con la persona en frbr:creator*/
			
			//levanto la expresion nueva
			$idNewExpression = $this->createObject ( $typeExpression );
			echo 'la nueva expression levantada es ' . $idNewExpression . '<br/>';
			
			//le pongo su tipo a la expression
			$this->setRelation ( $idNewExpression, "pangea:hasForm", $idNomenclator );
			
			//la relaciono con la manifestation vieja
			$this->setRelation ( $idNewExpression, "frbr:embodiment", $oldManifestation );
			
			//levanto la obra nueva
			$idNewWork = $this->createObject ( "frbr:Work" );
			echo 'la nueva obra levantada es ' . $idNewWork . '<br/>';
			
			//la relaciono con la nueva expression 
			$this->setRelation ( $idNewWork, "frbr:realization", $idNewExpression );
		
		}
		//en todos los casos relaciono la obra nueva con la persona
		$this->creator_personal ( $author_personal, $idNewWork, "frbr:creator" );
	}
	
	private function verifyRelationBetweenManifAndExpress1($IdManif, $propertyRelation) {
		$idWork = false;
		$query = "select fr.subject as work from \"frbr:realization\" fr left outer join \"$propertyRelation\" pr on cast(pr.subject as text) = fr.object
		          left outer join \"frbr:embodiment\" fe on fe.subject = pr.subject where fe.object = '$IdManif'";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$idWork = $row ['work'];
		}
		return $idWork;
	}
	
	public function fixRolesType3($IdManif, $IdExpression, $typeExpression, $propertyRelation, $author_personal) {
		/*primero hay que verificar si la manifestacion ya esta relacionada con una expression 
		 * que se encuentre relacionada con otra expression en la tabla $propertyRelation, si ya esta relacionada, cojo la obra con la cual
		 *  esta relaciona la expression y esa obra la relaciono con la persona*/
		$idNewWork = $this->verifyRelationBetweenManifAndExpress1 ( $IdManif, $propertyRelation );
		if ($idNewWork == false) {
			/*creo una obra nueva y una expression nueva, relaciono la expression nueva con la manifestation vieja y con la expression vieja
		 * y relaciono la obra con la persona*/
			
			//creo la obra nueva
			$idNewWork = $this->createObject ( "frbr:Work" );
			echo 'la nueva obra levantada es ' . $idNewWork . '<br/>';
			
			//creo una nueva expression
			$idNewExpression = $this->createObject ( $typeExpression );
			echo 'la nueva expression levantada es ' . $idNewExpression . '<br/>';
			
			//relaciono la expression con la obra nueva, la manifestacion vieja y con la expression vieja
			$this->setRelation ( $idNewWork, "frbr:realization", $idNewExpression );
			$this->setRelation ( $idNewExpression, "frbr:embodiment", $IdManif );
			$this->setRelation ( $idNewExpression, $propertyRelation, $IdExpression );
		}
		//en todos los casos relaciono la obra nueva con la persona
		$this->creator_personal ( $author_personal, $idNewWork, "frbr:creator" );
	}
	
	private function verifyNoteWork($IdWork) {
		$found = false;
		$query = "select subject from \"pangea:warning\" where object = 'Esta obra es una compilación de varias obras.'";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$found = true;
		}
		return $found;
	}
	
	public function fixRolesType4($IdWork, $author_personal) {
		
		/*primero tengo que analizar si ya la obra tiene puesta la nota, 
		 * si la tiene es solo relacionarla con la persona*/
		$found = $this->verifyNoteWork ( $IdWork );
		if (! $found) {
			
			/* le pongo una nota a la obra y relaciono a la persona con la obra*/
			$nota = "Esta obra es una compilación de varias obras.";
			
			//le pongo la nota a la obra vieja
			$this->setRelation ( $IdWork, "pangea:warning", $nota );
			
			echo "se le puso la nota a la obra " . $IdWork . '<br/>';
		
		}
		// siempre relaciono la obra con la persona
		$this->creator_personal ( $author_personal, $IdWork, "frbr:creator" );
	}
	
	private function verifyRelationBetweenManifAndExpressWithNote($IdManif, $note) {
		
		$idWork = false;
		
		$query = "select fr.subject as work from \"frbr:embodiment\" fe left outer join \"frbr:realization\" fr 
	           on fr.object = cast(fe.subject as text) where fe.object= '$IdManif' and
	           fe.subject in (select pw.subject from \"pangea:warning\" pw where pw.object = '$note' )";
		$result = pg_query ( $this->dbConn, $query );
		
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$idWork = $row ['work'];
		}
		return $idWork;
	}
	
	public function fixRolesType5($IdManif, $author_personal) {
		
		/* primero hay que verificar que la manifestacion no este relacionada ya,
		 * si ya esta relacionada con una expression que tenga esa nota, cojo la obra con la que 
		 * está relacionada dicha expression y la relaciono con la persona*/
		$note = 'Se debe revisar que fue lo que compuso la persona.';
		
		$idNewWork = $this->verifyRelationBetweenManifAndExpressWithNote ( $IdManif, $note );
		if ($idNewWork == false) {
			
			/*creo una nueva expression y una nueva obra y relaciono la nueva expression con la manifestation 
		 * y le pongo una nota a la expression*/
			
			//levanto la expresion nueva
			$idNewExpression = $this->createObject ( 'frbr:Expression' );
			echo 'la nueva expression levantada es ' . $idNewExpression . '<br/>';
			
			//le pongo la nota a la espression nueva
			$this->setRelation ( $idNewExpression, "pangea:warning", $note );
			
			//la relaciono con la manifestacion vieja
			$this->setRelation ( $idNewExpression, "frbr:embodiment", $IdManif );
			
			//levanto la nueva obra
			$idNewWork = $this->createObject ( "frbr:Work" );
			echo 'la nueva obra levantada es ' . $idNewWork . '<br/>';
			
			//la relaciono con la expression nueva
			$this->setRelation ( $idNewWork, "frbr:realization", $idNewExpression );
		}
		//en todos los casos relaciono la obra con la persona
		$this->creator_personal ( $author_personal, $idNewWork, "frbr:creator" );
	}
	
	private function verifyRelationBetweenManifAndExpress2($IdManif, $typeExpression) {
		$idExpression = false;
		$query = "select subject as expression from \"frbr:embodiment\" where object = '$IdManif' and subject in (select id from \"$typeExpression\")";
		$result = pg_query ( $this->dbConn, $query );
		
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$idExpression = $row ['expression'];
		}
		return $idExpression;
	}
	public function fixRolesType6($IdManif, $typeExpression, $author_personal, $nomenclator, $parentNomenclator, $tableNomenclator) {
		/*primero hay que verificar que la manifestacion no esté relacionada ya,
		 * si ya está relacionada con una expression de tipo $typeExpression, 
		 * cojo la expression y la relaciono con la persona*/
		$idNewExpression = $this->verifyRelationBetweenManifAndExpress2 ( $IdManif, $typeExpression );
		if ($idNewExpression == false) {
			/* creo una nueva expression, le pongo forma y la relaciono con la manifestation vieja y con la persona*/
			
			//obtengo el id del nomenclador
			$idNomenclator = $this->getNomenclator ( $nomenclator, $tableNomenclator, $parentNomenclator );
			
			//creo la nueva expression
			$idNewExpression = $this->createObject ( $typeExpression );
			echo 'la nueva expression levantada es ' . $idNewExpression . '<br/>';
			
			//le pongo la forma
			$this->setRelation ( $idNewExpression, "pangea:hasForm", $idNomenclator );
			
			//la relaciono con la manifestation vieja
			$this->setRelation ( $idNewExpression, "frbr:embodiment", $IdManif );
		
		}
		//siempre relaciono la expression con la persona
		$this->relateEntityWithEntity ( $author_personal, $idNewExpression, 'frbr:realizer', 'skos:prefLabel', 'frbr:Person' );
	}
	private function foundExpressionWithNote($IdExpression) {
		$expression = false;
		$query = "select subject from \"pangea:warning\" where subject = $IdExpression and 
		          object ilike 'La persona que está como realizador tenía puesto %como rol, hay que averiguar si es realmente un rol o es el cargo.'";
		$result = pg_query ( $this->dbConn, $query );
		
		if (pg_num_rows ( $result ) > 0) {
			return $IdExpression;
		}
		return $expression;
	}
	
	public function fixRolesType7($IdExpression, $author_personal, $rol) {
		/*primero hay que verificar si la expression tiene ya la nota puesta, 
		 * si tiene la nota solo la relaciono con la persona*/
		$IdExpression = $this->foundExpressionWithNote ( $IdExpression );
		if ($IdExpression == false) {
			/* relaciono la expression con la persona en la propiedad realizer y le agrego una nota*/
			
			//le pongo la nota a la expression
			$note = "La persona que está como realizador tenía puesto " . $rol . " como rol, hay que averiguar si es realmente un rol o es el cargo.";
			$this->setRelation ( $IdExpression, "pangea:warning", $note );
		}
		//siempre relaciono la expression con la persona
		$this->relateEntityWithEntity ( $author_personal, $IdExpression, 'frbr:realizer', 'skos:prefLabel', 'frbr:Person' );
	}
	
	private function verifyRelationBetweenManifAndExpress3($IdManif, $typeExpression, $note) {
		$idExpression = false;
		$query = "select pw.subject as expression from \"pangea:warning\" pw left outer join \"frbr:embodiment\" fe on pw.subject = fe.subject 
		where fe.object = '$IdManif' and pw.object = '$note' and pw.subject in (select id from \"$typeExpression\")";
		$result = pg_query ( $this->dbConn, $query );
		
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$idExpression = $row ['expression'];
		}
		return $idExpression;
	}
	
	public function fixRolesType8($IdManif, $typeExpression, $author_personal) {
		/*primero hay que verificar si la manifestacion ya esta relacionada con una expression de tipo $typeExpression,
		 * si ya esta relacionada, cojo la expression y la relaciono con la persona */
		$note = 'Se debe verificar qué interpretó la persona que está como realizador.';
		$idNewExpression = $this->verifyRelationBetweenManifAndExpress3 ( $IdManif, $typeExpression, $note );
		
		if ($idNewExpression == false) {
			/* creo una nueva expression y la relaciono con la manifestation vieja y con la persona*/
			//creo la nueva expression
			$idNewExpression = $this->createObject ( $typeExpression );
			echo 'la nueva expression levantada es ' . $idNewExpression . '<br/>';
			
			//le pongo la nota
			$this->setRelation ( $idNewExpression, "pangea:warning", $note );
			
			//la relaciono con la manifestation vieja
			$this->setRelation ( $idNewExpression, "frbr:embodiment", $IdManif );
		
		}
		//siempre relaciono la expression con la persona
		$this->relateEntityWithEntity ( $author_personal, $idNewExpression, 'frbr:realizer', 'skos:prefLabel', 'frbr:Person' );
	}
	
	public function getItemIdByNUmberStockAndBiblio($number_stock, $entity) {
		//select ns.* from "pangea:numberStock" ns left join "frbr:owner" oo on oo.subject = ns.subject where ns.object = '2331' and oo.object = '99189'
		$subject = false;
		$query = "select ns.subject from \"pangea:numberStock\" ns 
	           left join \"frbr:owner\" ow on ow.subject = ns.subject 
	           left join \"skos:prefLabel\" pl on cast(pl.subject as text) = ow.object where ns.object = '$number_stock' and pl.object = '$entity'";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$subject = $row ['subject'];
		}
		return $subject;
	}
	
	public function allAmbiguous() {
		$query = "select * from ambiguous";
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	}
	
	public function saveAmbiguous($id, $id_concept, $concept, $parent) {
		$query = "insert into ambiguous (id, id_concept, concept, parent) values ($id, $id_concept, '$concept', '$parent')";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	public function separatingDates() { //esta funcion solo la use para separar las fechas malas de las buenas
		$query1 = "select id, subject, object from \"pangea:date\" where object not ilike '%/%'"; //cojo las fechas malas
		$result1 = pg_query ( $this->dbConn, $query1 );
		
		//borro las fechas malas de la tabla de fechas buenas
		$query2 = "delete from \"pangea:date\" where object not ilike '%/%'";
		$result2 = pg_query ( $this->dbConn, $query2 );
		
		//meto las fechas malas en la nueva tabla
		$total = pg_num_rows ( $result1 );
		for($i = 0; $i < $total; $i ++) {
			$row = pg_fetch_array ( $result1 );
			$id = $row ['id'];
			$subject = $row ['subject'];
			$object = $row ['object'];
			$query3 = "INSERT INTO \"pangea:strDate\" (id, subject, object, datatype, lang) VALUES ($id, $subject, '$object', 'xsd:string', 'sp')";
			$result3 = pg_query ( $this->dbConn, $query3 );
		}
	}
	
	public function insertEntity($table, $id) {
		$query = "INSERT INTO \"$table\" (id) VALUES ($id);";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	private function getNextValue() {
		
		$query = "SELECT nextval('system_object_id_seq')";
		$result = pg_query ( $this->dbConn, $query );
		$row = pg_fetch_array ( $result, 0, PGSQL_NUM );
		return $row [0];
	}
	
	function updateSequence($value) {
		$query = "SELECT setval ('system_object_id_seq', $value)";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	public function createObject($table) {
		
		$idObject = $this->getNextValue ();
		$query = "INSERT INTO \"$table\" (id) VALUES ($idObject)";
		$result = pg_query ( $this->dbConn, $query );
		return $idObject;
	}
	
	public function create_relation($relation, $object, $subject) {
		//$part_object= (!is_string($part)) ? '{$part}' : $part;
		//echo 'relacion ' . $relation . '<br/>';
		$query = "select subject from \"$relation\"  where subject = $subject and object = $object";
		$result = pg_query ( $this->dbConn, $query );
		if (! (pg_num_rows ( $result ) > 0)) {
			$cmd = "insert into \"$relation\" (subject, object) values ($subject, $object)";
			$result = pg_Exec ( $this->dbConn, $cmd );
		}
	}
	public function setRelation($subject, $tableRelation, $object) {
		$query = "INSERT INTO \"$tableRelation\" (subject, object) VALUES ($subject, $object)";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	private function setIdLiteralForm($label, $idLabel, $lang) {
		$IdObject = $this->getNextValue ();
		echo 'literalForm ' . $label . "<br/>";
		$query = "INSERT INTO \"skosxl:literalForm\" (id, subject, object, lang) VALUES ($IdObject, $idLabel, '$label','$lang')";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	public function setRelationWithIdRow($subject, $tableRelation, $object, $rowId) {//se utiliza en el fichero readRdfFinalFichero
		$query = "INSERT INTO \"$tableRelation\" (id, subject, object) VALUES ($rowId, $subject, $object)";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	public function setRelationLiteral($subject, $tableRelation, $object, $dataType, $lang) {
		echo 'relac literal ' . $tableRelation . ' ' . $object . "<br/>";
		$query = "INSERT INTO \"$tableRelation\" (subject, object, datatype, lang) VALUES ($subject, '$object', '$dataType', '$lang')";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	public function setRelationLiteralWithIdRow($subject, $tableRelation, $object, $dataType, $lang, $rowId) {
		echo 'relac literal ' . $tableRelation . ' ' . $object . "<br/>";
		$query = "INSERT INTO \"$tableRelation\" (id, subject, object, datatype, lang) VALUES ($rowId, $subject, '$object', '$dataType', '$lang')";
		$result = pg_query ( $this->dbConn, $query );
	}
	
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
	
	private function setLabel1($label, $lang) {
		$result = array ();
		$result [1] = 'old';
		$idLabel = $this->getIdLiteralForm ( $label );
		if (! $idLabel) {
			$idLabel = $this->createObject ( 'skosxl:Label' );
			$this->setIdLiteralForm ( $label, $idLabel, $lang );
			$result [1] = 'new';
		}
		$result [0] = $idLabel;
		return $result;
	}
	public function setLabel($label, $lang = 'sp') {
		$idLabel = $this->getIdLiteralForm ( $label );
		if (! $idLabel) {
			$idLabel = $this->createObject ( "skosxl:Label" );
			$this->setIdLiteralForm ( $label, $idLabel, $lang );
		}
		return $idLabel;
	}
	
	/*private function createObjectProposed($table) {
		$idObject = $this->getNextValue ();
		$query = "INSERT INTO \"$table\" (id) VALUES ('$idObject');";
		$result = pg_query ( $this->dbConn, $query );
		$idNomenclator = $this->verifyConcept ( 'propuesto', 'skosxl:prefLabel', 'pangea:State' ); //por si el nomenclador no esta meterlo
		if (! $idNomenclator) {
			$idNomenclator = $this->createObject ( 'pangea:State' );
			$idLabel = $this->setLabel ( 'propuesto' );
			$this->setRelation ( $idNomenclator, 'skosxl:prefLabel', $idLabel );
		}
		
		$this->setRelation ( $idObject, 'pangea:warning', $idNomenclator );
		return $idObject;
	}*/
	
	public function createObjectProposed($table) {
		$idObject = $this->getNextValue ();
		$query = "INSERT INTO \"$table\" (id) VALUES ($idObject);";
		$result = pg_query ( $this->dbConn, $query );
		$this->setRelationLiteral ( $idObject, 'pangea:warning', 'propuesto', 'xsd:string', 'sp' );
		return $idObject;
	}
	
	private function relateEntityWithConcept($label, $idEntity, $tableRelation, $verificationTable, $tableEntity) {
		$lang = 'sp';
		//echo 'entity - concept ' . $tableRelation . '<br/>';
		//echo 'verification table ' . $verificationTable . '<br/>';
		$Label = $this->setLabel1 ( $label, $lang );
		if ($Label [1] == 'old') { //es un label viejo
			$idLabel = $Label [0];
			
			$query = "select DISTINCT subject from \"$verificationTable\" vt inner join \"$tableEntity\" te on vt.subject = te.id  where object = '$idLabel'";
			$result = pg_query ( $this->dbConn, $query );
			if (pg_num_rows ( $result ) > 0) {
				
				$row = pg_fetch_row ( $result );
				//$row = pg_fetch_array ( $result );
				$id = $row [0];
			
			} else {
				$id = $this->createObjectProposed ( $tableEntity );
				$this->create_relation ( $verificationTable, $idLabel, $id );
			}
		} else { //es un label nuevo
			$id = $this->createObjectProposed ( $tableEntity );
			$this->create_relation ( $verificationTable, $Label [0], $id );
		}
		
		$this->create_relation ( $tableRelation, $id, $idEntity );
	}
	/*public function relateEntityWithConcept($label, $idEntity, $tableRelation, $verificationTable, $tableEntity) {
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
	}*/
	
	public function relateEntityWithEntity($label, $idEntity, $tableRelation, $verificationTable, $tableEntity) {
		//echo 'entity - entity ' . $tableRelation . '<br/>';
		//echo 'verification table ' . $verificationTable . '<br/>';
		/*$labelM = str_replace ( ' ', '', $label );
		$labelM = mb_strtolower ( $labelM, 'UTF-8' );
		$query = "select subject from \"$verificationTable\" vt inner join \"$tableEntity\" te on vt.subject = te.id  where SP_ASCII(replace(lower(vt.object),' ', '')) ilike SP_ASCII('$labelM')";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0) {
			$row = pg_fetch_array ( $result );
			$id = $row ['subject'];
		
		}*/
		$id = $this->verifyEntity ( $label, $verificationTable, $tableEntity );
		if (! $id) {
			$id = $this->createObjectProposed ( $tableEntity );
			$this->setRelationLiteral ( $id, $verificationTable, $label, 'xsd:string', 'sp' );
		
		//$this->setRelation ( $id, $verificationTable, $label );
		}
		$this->create_relation ( $tableRelation, $id, $idEntity );
	}
	
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
	
	public function verifyConcept($label, $table, $typeEntity) {
		$idLabel = $this->getIdLiteralForm ( $label );
		if ($idLabel != NULL) {
			$query = "select subject from \"$table\" t inner join \"$typeEntity\" te on t.subject = te.id  where t.object = '$idLabel'";
			$result = pg_query ( $this->dbConn, $query );
			if (pg_num_rows ( $result ) > 0) {
				$row = pg_fetch_array ( $result );
				$id = $row ['subject'];
				return $id;
			}
		}
		return false;
	}
	
	public function verifyItem($biblio, $number_stock, $location) {
		if (($number_stock != "**VACIO**") && ($number_stock != '')) //busco por el numero de inventario
			$query = "select ns.subject from \"pangea:numberStock\" ns 
		         where ns.object = '$number_stock' and ns.subject in (select fo.subject from \"frbr:owner\" fo
		         where cast(fo.object as integer) in (select pl.subject from \"skos:prefLabel\" pl 
		         where SP_ASCII(pl.object) ilike SP_ASCII('$biblio')))";
		else //busco por la localización
			$query = "select pfl.subject from \"skos:prefLabel\" pfl 
		         where SP_ASCII(pfl.object) ilike SP_ASCII('$location') and pfl.subject in (select fo.subject from \"frbr:owner\" fo
		         where cast(fo.object as integer) in (select pl.subject from \"skos:prefLabel\" pl 
		         where SP_ASCII(pl.object) ilike SP_ASCII('$biblio')))";
		$result = pg_query ( $this->dbConn, $query );
		if (pg_num_rows ( $result ) > 0)
			return true;
			/*{
			$row = pg_fetch_array ( $result );
			$id = $row ['subject'];
			return $id;
		}*/
		else
			return false;
	
	}
	
	public function selectManifestation($isbn, $title) {
		$isbn = str_replace ( ' ', '', $isbn );
		$isbn = mb_strtolower ( $isbn, 'UTF-8' );
		
		$title = str_replace ( ' ', '', $title );
		$title = mb_strtolower ( $title, 'UTF-8' );
		
		$select = "select id from \"frbr:Manifestation\" fm 
		           where fm.id in (select subject from \"skos:prefLabel\" pl where SP_ASCII(replace(lower(pl.object),' ', '')) ilike SP_ASCII('$title'))
	               and fm.id in (select subject from \"pangea:isbn\" pis where SP_ASCII(replace(lower(pis.object),' ', '')) ilike SP_ASCII('$isbn'))";
		$result = pg_query ( $this->dbConn, $select );
		return $result;
	}
	
	public function selectManifestation2($title, $printer, $printer_date, $printer_place) {
		$title = str_replace ( ' ', '', $title );
		$title = mb_strtolower ( $title, 'UTF-8' );
		
		$printer = str_replace ( ' ', '', $printer );
		$printer = mb_strtolower ( $printer, 'UTF-8' );
		
		$printer_place = str_replace ( ' ', '', $printer_place );
		$printer_place = mb_strtolower ( $printer_place, 'UTF-8' );
		
		$cmd = "select id from \"frbr:Manifestation\" fm 
		        where fm.id in (select subject from \"skos:prefLabel\" pl where SP_ASCII(replace(lower(pl.object),' ', '')) ilike SP_ASCII('$title'))
		        and fm.id in (select subject from \"frbr:producer\" pd where cast(pd.object as integer) in (select subject from \"skos:prefLabel\" pf where SP_ASCII(replace(lower( pf.object),' ', '')) ilike SP_ASCII('$printer')) 
		        and cast(pd.object as integer) in (select id from \"frbr:ResponsibleEntity\"))
		        and fm.id in (select subject from \"pangea:place\" pp where cast(pp.object as integer) in (select subject from \"skos:prefLabel\" spf where SP_ASCII(replace(lower(spf.object),' ', '')) ilike SP_ASCII('$printer_place')) 
		        and cast(pp.object as integer) in (select id from \"frbr:Place\"))
		        and fm.id in (select subject from \"pangea:date\" pd where pd.object = '$printer_date')
		        ";
		//and fm.id in (select subject from \"pangea:editionDate\" where \"pangea:editionDate\".object='{$edition_date}') Lo quite porque esa fecha se le pone a la edicion y no a la manifestacion
		$result = pg_query ( $this->dbConn, $cmd );
		return $result;
	}
	
	public function selectManifestation2P($title, $printer, $printer_date, $printer_place) {
		$query = "select id from \"frbr:Manifestation\" fm where";
		$aux = 0;
		if ($title != '') {
			$title = str_replace ( ' ', '', $title );
			$title = mb_strtolower ( $title, 'UTF-8' );
			$query .= " fm.id in (select subject from \"skos:prefLabel\" pl where SP_ASCII(replace(lower(pl.object),' ', '')) ilike SP_ASCII('$title'))";
			$aux ++;
		}
		if ($printer != '') {
			$printer = str_replace ( ' ', '', $printer );
			$printer = mb_strtolower ( $printer, 'UTF-8' );
			if ($aux > 0)
				$query .= " and";
			$query .= " fm.id in (select subject from \"frbr:producer\" pd where cast(pd.object as integer) in (select subject from \"skos:prefLabel\" pf where SP_ASCII(replace(lower( pf.object),' ', '')) ilike SP_ASCII('$printer'))
			            and cast(pd.object as integer) in (select id from \"frbr:ResponsibleEntity\"))";
			$aux ++;
		}
		if ($printer_place != '') {
			$printer_place = str_replace ( ' ', '', $printer_place );
			$printer_place = mb_strtolower ( $printer_place, 'UTF-8' );
			if ($aux > 0)
				$query .= " and";
			$query .= " and fm.id in (select subject from \"pangea:place\" pp where cast(pp.object as integer) in (select subject from \"skos:prefLabel\" spf where SP_ASCII(replace(lower(spf.object),' ', '')) ilike SP_ASCII('$printer_place')) 
		                and cast(pp.object as integer) in (select id from \"frbr:Place\"))";
		}
		if ($printer_date != '') {
			if ($aux > 0)
				$query .= " and";
			$query .= " fm.id in (select subject from \"pangea:date\" pd where pd.object = '$printer_date')";
		}
		if (($aux > 0)) {
			$result = pg_query ( $this->dbConn, $query );
			return $result;
		} else
			return false;
	}
	
	public function collection($collection, $IdItem) {
		//$idLabel = $this->setLabel ( $collection, $lang_suf );
		//$this->create_relation ( 'pangea:collection', $idLabel [0], $IdItem );
		$this->relateEntityWithConcept ( $collection, $IdItem, 'pangea:hasCollection', 'skosxl:prefLabel', 'pangea:Collection' );
	}
	
	public function owner($entidad, $IdItem) {
		$this->relateEntityWithEntity ( $entidad, $IdItem, 'frbr:owner', 'skos:prefLabel', 'frbr:ResponsibleEntity' );
	}
	
	public function doc_type($document_type, $IdManif) {
		$position = strpos ( $document_type, "(" );
		if ($position) {
			$position_2 = strpos ( $document_type, ")" );
			$label = trim ( substr ( $document_type, 0, $position - 1 ) );
			if ($position_2) {
				$valueParent = trim ( substr ( $document_type, $position + 1, $position_2 - 1 ) );
				//chequeo en ambiguous
				$query = "select id_concept from ambiguous where concept= '$label' and parent = '$valueParent'";
				$result = pg_query ( $this->dbConn, $query );
				if (pg_num_rows ( $result ) > 0) {
					$row = pg_fetch_array ( $result );
					$id = $row ['id_concept'];
					$this->create_relation ( 'pangea:hasForm', $id, $IdManif );
				} else { //el concepto no esta metido
					$this->insertAmbiguousTerm ( $label, $IdManif, 'pangea:hasForm', 'skosxl:prefLabel', 'pangea:DocumentType', $valueParent );
				}
			
			} else
				$this->relateEntityWithConcept ( $label, $IdManif, 'pangea:hasForm', 'skosxl:prefLabel', 'pangea:DocumentType' );
		
		} else
			$this->relateEntityWithConcept ( $document_type, $IdManif, 'pangea:hasForm', 'skosxl:prefLabel', 'pangea:DocumentType' );
	}
	
	public function title($title, $IdManif) {
		echo "titulo ";
		$this->setRelationLiteral ( $IdManif, 'skos:prefLabel', $title, 'xsd:string', 'sp' );
	}
	
	public function labelExpression($label, $IdExpression) {
		
		$this->setRelationLiteral ( $IdExpression, 'skos:prefLabel', $label, 'xsd:string', 'sp' );
	}
	
	public function title_uniform($title_uniform, $lang_suf, $IdWork) {
		$this->setRelationLiteral ( $IdWork, 'skos:altLabel', $title_uniform, 'xsd:string', $lang_suf );
	}
	
	/*public function title_parallel($title_parallel, $lang_suf, $IdManif) {
		$idLabel = $this->setLabel ( $title_parallel, $lang_suf );
		$this->create_relation ( "pangea:titleParallel", $idLabel [0], $IdManif );
	}
	
	public function title_notitle($title_notitle, $lang_suf, $IdManif) {
		$idLabel = $this->setLabel ( $title_notitle, $lang_suf );
		$this->create_relation ( "pangea:titleWithout", $idLabel [0], $IdManif );
	}
	
	public function title_translated($title_translated, $lang_suf, $IdManif) {
		$idLabel = $this->setLabel ( $title_translated, $lang_suf );
		$this->create_relation ( "pangea:titleTranslated", $idLabel [0], $IdManif );
	}
	
	public function title_uniform($title_uniform, $lang_suf, $IdManif) {
		$idLabel = $this->setLabel ( $title_uniform, $lang_suf );
		$this->create_relation ( "pangea:titleUniform", $idLabel [0], $IdManif );
	}
	
	public function title_variant($title_variant, $lang_suf, $IdManif) {
		$idLabel = $this->setLabel ( $title_variant, $lang_suf );
		$this->create_relation ( "pangea:titleVariant", $idLabel [0], $IdManif );
	}
	
	public function title_contributedInf($title_contributedInf, $lang_suf, $IdManif) {
		$idLabel = $this->setLabel ( $title_contributedInf, $lang_suf );
		$this->create_relation ( "pangea:titleContributed", $idLabel [0], $IdManif );
	}
	
	public function title_oit($title_oit, $lang_suf, $IdManif) {
		$idLabel = $this->setLabel ( $title_oit, $lang_suf );
		$this->create_relation ( "pangea:titleOit", $idLabel [0], $IdManif );
	}
	
	public function title_oit_parallel($title_oit_parallel, $lang_suf, $IdManif) {
		$idLabel = $this->setLabel ( $title_oit_parallel, $lang_suf );
		$this->create_relation ( "pangea:titleOitParallel", $idLabel [0], $IdManif );
	}
	*/
	public function prefLabel($title, $lang_suf, $IdManif) {
		$idLabel = $this->setLabel ( $title, $lang_suf );
		$this->create_relation ( "skosxl:prefLabel", $idLabel, $IdManif );
	}
	
	public function creator_personal($creator_personal, $IdEntity, $tableRelation) {
		
		$this->relateEntityWithEntity ( $creator_personal, $IdEntity, $tableRelation, 'skos:prefLabel', 'frbr:Person' );
	}
	
	public function creator_corporate($creator_corporate, $IdWork) {
		
		$this->relateEntityWithEntity ( $creator_corporate, $IdWork, 'frbr:creator', 'skos:prefLabel', 'frbr:CorporateBody' );
	}
	
	public function creator_event($creator_event, $IdWork) {
		
		$this->relateEntityWithEntity ( $creator_event, $IdWork, 'frbr:creator', 'skos:prefLabel', 'frbr:Event' );
	}
	
	public function language_expression($lang, $IdExpression) {
		//$idLabel = $this->setLabel ( $lang, $lang_suf );
		//$this->create_relation ( "pangea:hasLanguage", $idLabel [0], $IdExpression );
		$languages = array ($lang );
		$position = strpos ( $lang, "-" );
		if ($position)
			$languages = explode ( '-', $lang );
		else {
			$position = strpos ( $lang, "/" );
			if ($position)
				$languages = explode ( '/', $lang );
			else {
				$position = strpos ( $lang, "," );
				if ($position)
					$languages = explode ( ',', $lang );
			}
		}
		foreach ( $languages as $lang )
			$this->relateEntityWithConcept ( trim ( $lang ), $IdExpression, 'pangea:hasLanguage', 'skosxl:prefLabel', 'pangea:Language' );
	}
	private function insertAmbiguousTerm($label, $idEntity, $tableRelation, $verificationTable, $tableEntity, $parent) {
		
		$lang = 'sp';
		$Label = $this->setLabel1 ( $label, $lang );
		if ($Label [1] == 'old') { //es un label viejo
			$idLabel = $Label [0];
			$query = "select subject from \"$verificationTable\" vt inner join \"$tableEntity\" te on vt.subject = te.id  where object = '$idLabel'";
			$result = pg_query ( $this->dbConn, $query );
			if (pg_num_rows ( $result ) > 0) {
				$row = pg_fetch_array ( $result );
				$id = $row ['subject'];
			
			} else {
				$id = $this->createObjectProposed ( $tableEntity );
				$this->create_relation ( $verificationTable, $idLabel, $id );
			}
		} else { //es un label nuevo
			$id = $this->createObjectProposed ( $tableEntity );
			$this->create_relation ( $verificationTable, $Label [0], $id );
		}
		
		$this->create_relation ( $tableRelation, $id, $idEntity );
		$query = "insert into ambiguous (id_concept, concept, parent) values ($id, '$label', '$parent')";
		$result = pg_query ( $this->dbConn, $query );
	}
	
	public function subject_concept($subject_concept, $IdWork) {
		$position = strpos ( $subject_concept, "(" );
		if ($position) {
			$position2 = strpos ( $subject_concept, ")" );
			//$label = trim ( substr ( $subject_concept, 0, $position - 1 ) );
			$label = trim ( substr ( $subject_concept, 0, $position ) );
			if ($position2) {
				$position3 = $position2 - $position;
				$valueParent = trim ( substr ( $subject_concept, $position + 1, $position3 - 1 ) );
				//chequeo en ambiguous
				$query = "select id_concept from ambiguous where concept= '$label' and parent = '$valueParent'";
				$result = pg_query ( $this->dbConn, $query );
				if (pg_num_rows ( $result ) > 0) {
					$row = pg_fetch_array ( $result );
					$id = $row ['id_concept'];
					$this->create_relation ( 'frbr:relatedSubject', $id, $IdWork );
				} else { //el concepto no esta metido
					$this->insertAmbiguousTerm ( $label, $IdWork, 'frbr:relatedSubject', 'skosxl:prefLabel', 'frbr:Concept', $valueParent );
				}
			
			} else
				$this->relateEntityWithConcept ( $label, $IdWork, 'frbr:relatedSubject', 'skosxl:prefLabel', 'frbr:Concept' );
		
		} else
			$this->relateEntityWithConcept ( $subject_concept, $IdWork, 'frbr:relatedSubject', 'skosxl:prefLabel', 'frbr:Concept' );
	}
	
	public function subject_event($subject_event, $IdWork) {
		
		$this->relateEntityWithEntity ( $subject_event, $IdWork, 'frbr:relatedSubject', 'skos:prefLabel', 'frbr:Event' );
	}
	
	public function subject_place($subject_place, $IdWork) {
		
		$this->relateEntityWithEntity ( $subject_place, $IdWork, 'frbr:relatedSubject', 'skos:prefLabel', 'frbr:Place' );
	}
	
	public function subject_personal($subject_personal, $IdWork) {
		
		$this->relateEntityWithEntity ( $subject_personal, $IdWork, 'frbr:relatedSubject', 'skos:prefLabel', 'frbr:Person' );
	}
	
	public function subject_corporate($subject_corporate, $IdWork) {
		
		$this->relateEntityWithEntity ( $subject_corporate, $IdWork, 'frbr:relatedSubject', 'skos:prefLabel', 'frbr:CorporateBody' );
	}
	
	public function subject_object($subject_object, $IdWork) {
		
		$this->relateEntityWithEntity ( $subject_object, $IdWork, 'frbr:relatedSubject', 'skos:prefLabel', 'frbr:Object' );
	}
	
	public function subject_title($subject_title, $IdWork) {
		
		$idLabel = $this->setLabel ( $subject_title );
		$this->create_relation ( "frbr:relatedSubject", $idLabel, $IdWork );
	
	}
	
	public function isbn($isbn, $IdManif) {
		//$idLabel = $this->setLabel ( $isbn, 'no' );
		//$this->create_relation ( "pangea:isbn", $idLabel [0], $IdManif );
		$this->setRelationLiteral ( $IdManif, 'pangea:isbn', $isbn, 'xsd:string', 'no' );
	}
	
	public function issn($issn, $IdExpression) {
		//$idLabel = $this->setLabel ( $issn, 'no' );
		//$this->create_relation ( "pangea:issn", $idLabel [0], $IdManif );
		$this->setRelationLiteral ( $IdExpression, 'pangea:issn', $issn, 'xsd:string', 'no' );
	
	}
	
	public function editionType($editionType, $IdExpression) {
		$this->relateEntityWithConcept ( $editionType, $IdExpression, 'pangea:hasSubject', 'skosxl:prefLabel', 'pangea:Subject' );
	}
	
	public function typology_doc($typology_doc, $IdExpression) {
		$position = strpos ( $typology_doc, "(" );
		if ($position) {
			$position_2 = strpos ( $typology_doc, ")" );
			$label = trim ( substr ( $typology_doc, 0, $position - 1 ) );
			if ($position_2) {
				$valueParent = trim ( substr ( $typology_doc, $position + 1, $position_2 - 1 ) );
				//chequeo en ambiguous
				$query = "select id_concept from ambiguous where concept= '$label' and parent = '$valueParent'";
				$result = pg_query ( $this->dbConn, $query );
				if (pg_num_rows ( $result ) > 0) {
					$row = pg_fetch_array ( $result );
					$id = $row ['id_concept'];
					$this->create_relation ( 'pangea:hasForm', $id, $IdExpression );
				} else { //el concepto no esta metido
					$this->insertAmbiguousTerm ( $label, $IdExpression, 'pangea:hasForm', 'skosxl:prefLabel', 'pangea:Typology', $valueParent );
				}
			
			} else
				$this->relateEntityWithConcept ( $label, $IdExpression, 'pangea:hasForm', 'skosxl:prefLabel', 'pangea:Typology' );
		
		} else
			$this->relateEntityWithConcept ( $typology_doc, $IdExpression, 'pangea:hasForm', 'skosxl:prefLabel', 'pangea:Typology' );
	}
	
	public function literary_form($literary_form, $IdWork) {
		
		$position = strpos ( $literary_form, "(" );
		if ($position) {
			$position_2 = strpos ( $literary_form, ")" );
			$label = trim ( substr ( $literary_form, 0, $position - 1 ) );
			if ($position_2) {
				$valueParent = trim ( substr ( $literary_form, $position + 1, $position_2 - 1 ) );
				//chequeo en ambiguous
				$query = "select id_concept from ambiguous where concept= '$label' and parent = '$valueParent'";
				$result = pg_query ( $this->dbConn, $query );
				if (pg_num_rows ( $result ) > 0) {
					$row = pg_fetch_array ( $result );
					$id = $row ['id_concept'];
					$this->create_relation ( 'pangea:hasForm', $id, $IdWork );
				} else { //el concepto no esta metido
					$this->insertAmbiguousTerm ( $label, $IdWork, 'pangea:hasForm', 'skosxl:prefLabel', 'pangea:Form', $valueParent );
				}
			
			} else
				$this->relateEntityWithConcept ( $label, $IdWork, 'pangea:hasForm', 'skosxl:prefLabel', 'pangea:Form' );
		
		} else
			$this->relateEntityWithConcept ( $literary_form, $IdWork, 'pangea:hasForm', 'skosxl:prefLabel', 'pangea:Form' );
	}
	
	public function printer_date($printer_date, $IdManif) {
		$this->setRelationLiteral ( $IdManif, 'pangea:date', $printer_date, 'xsd:date', 'no' );
	}
	
	public function printer_strDate($printer_date, $IdManif) {
		$this->setRelationLiteral ( $IdManif, 'pangea:strDate', $printer_date, 'xsd:string', 'sp' );
	}
	
	public function edition_date($edition_date, $IdExpression) {
		$this->setRelationLiteral ( $IdExpression, 'pangea:date', $edition_date, 'xsd:date', 'no' );
	}
	
	public function edition_strDate($edition_date, $IdExpression) {
		$this->setRelationLiteral ( $IdExpression, 'pangea:strDate', $edition_date, 'xsd:string', 'sp' );
	}
	
	public function volume($volume, $IdExpression) {
		$this->setRelationLiteral ( $IdExpression, 'pangea:serialOrd', $volume, 'xsd:string', 'sp' );
	}
	
	public function totalVolumeWithSerialId($idSerial, $totalVolume) {
		$this->setRelationLiteral ( $idSerial, 'pangea:extent', $totalVolume, 'xsd:string', 'sp' );
	}
	
	public function totalVolumeWithoutSerialId($totalVolume) {
		$id = $this->createObject ( "pangea:Serial" );
		$this->setRelationLiteral ( $id, 'pangea:extent', $totalVolume, 'xsd:string', 'sp' );
	}
	
	public function accompanyingMaterial($accompanyingMaterial) {
		
		$id = $this->verifyEntity ( $accompanyingMaterial, 'skos:prefLabel', "frbr:Expression" );
		if (! $id) {
			$id = $this->createObject ( "frbr:Expression" );
			$this->setRelationLiteral ( $id, 'skos:prefLabel', $accompanyingMaterial, 'xsd:string', 'sp' );
		}
		return $id;
	}
	
	public function group($matAcomp) {
		$id = $this->createObject ( "pangea:Group" );
		foreach ( $matAcomp as $idMaterial ) {
			$this->setRelation ( $id, 'frbr:part', $idMaterial );
		}
	}
	
	public function dimensions($dimensions, $IdManif) {
		$this->setRelationLiteral ( $IdManif, 'pangea:height', $dimensions, 'xsd:float', 'no' );
	}
	
	/*public function psYear($psyear, $IdManif) {
		$this->setRelationLiteral ( $IdManif, 'pangea:psYear', $psyear, 'xsd:string', 'sp' );
	}
	
	public function epoch($epoch, $IdManif) {
		$this->setRelationLiteral ( $IdManif, 'pangea:psEpoch', $epoch, 'xsd:string', 'sp' );
	}
	
	public function psVolume($psVolume, $IdManif) {
		$this->setRelationLiteral ( $IdManif, 'pangea:psVolume', $psVolume, 'xsd:string', 'sp' );
	}
	
	public function psNumber($psNumber, $IdManif) {
		$this->setRelationLiteral ( $IdManif, 'pangea:psNumber', $psNumber, 'xsd:string', 'sp' );
	}
		
	public function psMonth($month, $IdManif) {
		$this->setRelationLiteral ( $IdManif, 'pangea:psMonths', $month, 'xsd:string', 'sp' );
	}
	
	public function psDay($day, $IdManif) {
		$this->setRelationLiteral ( $IdManif, 'pangea:psDay', $day, 'xsd:string', 'sp' );
	}*/
	
	public function year($year, $IdManif) {
		//$this->setRelationLiteral ( $IdManif, 'pangea:year', $year, 'xsd:string', 'sp' );
		$this->setRelationLiteral ( $IdManif, 'pangea:strDate', $year, 'xsd:string', 'sp' );
	}
	
	public function illustration($illustration, $IdManif) {
		
		$IdExpression = $this->createObject ( "frbr:Image" );
		$this->setRelationLiteral ( $IdExpression, 'pangea:illustration', $illustration, 'xsd:string', 'sp' );
		
		$this->create_relation ( "frbr:embodiment", $IdManif, $IdExpression );
	}
	
	public function serialNumber($serialNumber, $IdExpression) {
		
		$this->setRelationLiteral ( $IdExpression, 'pangea:serialNumber', $serialNumber, 'xsd:string', 'sp' );
	}
	
	public function subSerialNumber($subSerialNumber, $IdExpression) {
		
		$this->setRelationLiteral ( $IdExpression, 'pangea:subSerialNumber', $subSerialNumber, 'xsd:string', 'sp' );
	}
	
	public function serial($serial, $IdExpression) {
		$result = array (false );
		$id = $this->verifyEntity ( $serial, 'skos:prefLabel', "pangea:Serial" );
		if (! $id) {
			$id = $this->createObject ( "pangea:Serial" );
			$this->setRelationLiteral ( $id, 'skos:prefLabel', $serial, 'xsd:string', 'sp' );
			$result [0] = true; //me dice que tuve que crear la serie nueva
		}
		$this->setRelation ( $id, 'frbr:part', $IdExpression ); //antes esta relacion estaba dentro del if
		$result [] = $id;
		return $result;
	}
	
	public function serialAndSubserial($serial, $subserial, $IdExpression) {
		$result = array (false );
		$idserial = $this->verifyEntity ( $serial, 'skos:prefLabel', "pangea:Serial" );
		if (! $idserial) {
			$idserial = $this->createObject ( "pangea:Serial" );
			$this->setRelationLiteral ( $idserial, 'skos:prefLabel', $serial, 'xsd:string', 'sp' );
			$result [0] = true; //me dice que tuve que crear la serie nueva
		}
		$idsub = $this->verifyEntity ( $subserial, 'skos:prefLabel', "pangea:Serial" );
		if (! $idsub) {
			$idsub = $this->createObject ( "pangea:Serial" );
			$this->setRelationLiteral ( $idsub, 'skos:prefLabel', $subserial, 'xsd:string', 'sp' );
			$this->setRelation ( $idserial, 'frbr:part', $idsub );
			$this->setRelation ( $idsub, 'frbr:part', $IdExpression );
		}
		$result [] = $idserial;
		return $result;
	}
	
	public function price_item($price, $currency, $IdItem) {
		if (($currency == 'Cup') || ($currency == 'N'))
			$currency = 'Mn';
		if ($currency == 'Desc')
			$currency = '';
		$this->setRelationLiteral ( $IdItem, "pangea:price" . $currency, $price, 'xsd:float', 'no' );
	}
	
	public function entry_date($entry_date, $IdItem) {
		$entry_date = str_replace ( '.', '/', $entry_date );
		$entry_date = str_replace ( '(', '/', $entry_date );
		$entry_date = str_replace ( '-', '/', $entry_date );
		$this->setRelationLiteral ( $IdItem, "pangea:date", $entry_date, 'xsd:date', 'no' );
	}
	
	public function number_stock($number_stock, $IdItem) {
		$this->setRelationLiteral ( $IdItem, "pangea:numberStock", $number_stock, 'xsd:string', 'sp' );
	}
	
	public function edition_place($edition_place, $IdExpression) {
		
		$this->relateEntityWithEntity ( $edition_place, $IdExpression, 'pangea:place', 'skos:prefLabel', 'frbr:Place' );
	}
	
	public function printer_place($printer_place, $IdManif) {
		
		$this->relateEntityWithEntity ( $printer_place, $IdManif, 'pangea:place', 'skos:prefLabel', 'frbr:Place' );
	}
	
	public function yelowNote($yelowNote, $IdEntity) {
		$this->setRelationLiteral ( $IdEntity, "pangea:warning", $yelowNote, 'xsd:string', 'sp' );
	}
	
	public function warningDate($warning, $IdEntity) {
		$warning = 'Se desconoce la fecha de impresión';
		$this->setRelationLiteral ( $IdEntity, "pangea:warning", $warning, 'xsd:string', 'sp' );
	}
	
	/*public function description($note_item, $idSubject) {
		$this->setRelationLiteral ( $idSubject, "rdfs:comment", $note_item, 'xsd:string', 'sp' );
	}*/
	
	public function item_note($note_item, $idSubject) {
		$this->setRelationLiteral ( $idSubject, "pangea:itemNote", $note_item, 'xsd:string', 'sp' );
	}
	
	public function boundwith_note($note_boundwith, $idSubject) {
		$this->setRelationLiteral ( $idSubject, "pangea:accompanyNote", $note_boundwith, 'xsd:string', 'sp' );
	}
	
	public function adquisition_note($note_adquisition, $idSubject) {
		$this->setRelationLiteral ( $idSubject, "pangea:adquisitionNote", $note_adquisition, 'xsd:string', 'sp' );
	}
	public function content_note($note_item, $idSubject) {
		$this->setRelationLiteral ( $idSubject, "pangea:contentNote", $note_item, 'xsd:string', 'sp' );
	}
	public function general_note($note_general, $idSubject) {
		$this->setRelationLiteral ( $idSubject, "pangea:generalNote", $note_general, 'xsd:string', 'sp' );
	}
	
	public function editorial($editorial, $IdExpression) {
		
		$this->relateEntityWithEntity ( $editorial, $IdExpression, 'frbr:realizer', 'skos:prefLabel', 'frbr:ResponsibleEntity' );
	}
	
	public function printer($printer, $IdManif) {
		
		$this->relateEntityWithEntity ( $printer, $IdManif, 'frbr:producer', 'skos:prefLabel', 'frbr:ResponsibleEntity' );
	}
	
	public function classif_dewey($classif_dewey, $IdWork) {
		
		//$idLabel = $this->setLabel ( $classif_dewey, 'no' );
		//$this->create_relation ( "pangea:classifDewey", $idLabel [0], $IdManif );
		$this->setRelationLiteral ( $IdWork, "pangea:classifDewey", $classif_dewey, 'xsd:string', 'sp' );
	
	}
	
	/*public function classif_cdu($classif_cdu, $IdManif) {
		
		//$idLabel = $this->setLabel ( $classif_cdu, 'no' );
		//$this->create_relation ( "pangea:classifCdu", $idLabel [0], $IdManif );
		$this->setRelationLiteral ( $IdManif, "pangea:classifCdu", $classif_cdu, 'xsd:string', 'sp' );
	}*/
	
	/*public function edition_country($edition_country, $IdManif) {
		
		$this->relateEntityWithEntity ( $edition_country, $IdManif, 'pangea:editionCountry', 'skos:prefLabel', 'frbr:Place' );
	}*/
	
	public function location($location, $IdItem) {
		$this->setRelationLiteral ( $IdItem, "skos:prefLabel", $location, 'xsd:string', 'sp' );
	}
	
	public function availability($availability, $IdItem) {
		//$this->setRelationLiteral ( $IdItem, "pangea:availability", $availability, 'xsd:string', 'sp' );
		$this->relateEntityWithConcept ( $availability, $IdItem, 'pangea:hasAvailability', 'skosxl:prefLabel', 'pangea:Availability' );
	}
	
	public function adquisition_way($adquisition_way, $IdItem) {
		//$this->setRelationLiteral ( $IdItem, "pangea:adquisitionWay", $adquisition_way, 'xsd:string', 'sp' );
		$this->relateEntityWithConcept ( $adquisition_way, $IdItem, 'pangea:hasAdquisitionWay', 'skosxl:prefLabel', 'pangea:AdquisitionWay' );
	}
	
	public function comission_act($comission_act, $IdItem) {
		$this->setRelationLiteral ( $IdItem, "pangea:comissionAct", $comission_act, 'xsd:string', 'sp' );
	}
	
	public function buy_act($buy_act, $IdItem) {
		$this->setRelationLiteral ( $IdItem, "pangea:buyAct", $buy_act, 'xsd:string', 'sp' );
	}
	
	public function pages($pages, $IdManif) {
		$this->setRelationLiteral ( $IdManif, "pangea:pages", $pages, 'xsd:integer', 'no' );
	}
	
	public function GetNames($value) {
		$statement = array ("caricaturista", "diseÃ±ador", "editor", "director", "redactor", "compilador", "prologuista", "notas", "traductor", "presentaciÃ³n", "curador", "autor" );
		$titles = array ("Dra.", "Dr.", "Ing.", "Lic.", "MsC." );
		$patron_nombres = '/\b[\w\s\.\-' . utf8_encode ( '\Ã¡\Ã©\Ã­\Ã³\Ãº\Ã�\Ã‰\Ã�\Ã“\Ãš\Ã±\Ã‘\Ã¤\Ã«\Ã¶\Ã¼\Ã„\Ã‹\Ã�\Ã–\Ãœ\Ã§\Ã \Ã¨\Ã¬\Ã²\Ã¹\Ã¿\â€™\Â´' ) . "]+/";
		$patron_date_before = '/[\d{1,4}]+(?=\-)/';
		$patron_date_after = '/(?<=\-)[\d{1,4}]+/';
		// full_names (last_name,first_name,title,birth_date,death_date,statement)
		$full_names = array ();
		
		//echo $value."<br/>";
		preg_match_all ( $patron_nombres, $value, $matriz );
		$size = sizeof ( $matriz [0] );
		switch ($size) {
			case 1 :
				//echo "Nombre:  ".$matriz[0][0]."<br />";  //Jaly Vazquez
				$full_names ['first_name'] = $matriz [0] [0];
				break;
			case 2 :
				if (in_array ( $matriz [0] [1], $statement )) {
					//echo "<b>Nombre:  ".$matriz[0][0]."</b><br />";
					$full_names ['first_name'] = $matriz [0] [0];
					//echo "<b>Menci&oacute;n:  ".$matriz[0][1]."</b><br />";
					$full_names ['statement'] = $matriz [0] [1];
				} else {
					//echo "<b>Apellidos:  ".$matriz[0][0]."</b><br />";
					$full_names ['last_name'] = $matriz [0] [0];
					//echo "<b>Nombre:  ".$matriz[0][1]."</b><br />";
					$full_names ['first_name'] = $matriz [0] [1];
				} //if
				break;
			case 3 : //Divito, Ana Mar?a, 1946-
				preg_match_all ( $patron_date_before, $matriz [0] [1], $before );
				preg_match_all ( $patron_date_after, $matriz [0] [1], $after );
				preg_match_all ( $patron_date_before, $matriz [0] [2], $before2 );
				preg_match_all ( $patron_date_after, $matriz [0] [2], $after2 );
				if (is_numeric ( $before [0] [0] ) or is_numeric ( $after [0] [0] )) {
					//echo "<b>Nombre:  ".$matriz[0][0]."</b><br />";
					$full_names ['first_name'] = $matriz [0] [0];
					//echo "<b>Fecha Nac.:  ".$before[0][0]."</b><br />";
					$full_names ['birth_date'] = $before [0] [0];
					//echo "<b>Fecha Muerte:  ".$after[0][0]."</b><br />";
					$full_names ['death_date'] = $after [0] [0];
					//echo "<b>Menci&oacute;n:  ".$matriz[0][2]."</b><br />";
					$full_names ['statement'] = $matriz [0] [2];
				} elseif (is_numeric ( $before2 [0] [0] ) or is_numeric ( $after2 [0] [0] )) {
					//echo "<b>Apellidos:  ".$matriz[0][0]."</b><br />";
					$full_names ['last_name'] = $matriz [0] [0];
					//echo "<b>Nombre:  ".$matriz[0][1]."</b><br />";
					$full_names ['first_name'] = $matriz [0] [1];
					//echo "<b>Fecha Nac.:  ".$before2[0][0]."</b><br />";
					$full_names ['birth_date'] = $before2 [0] [0];
					//echo "<b>Fecha Muerte:  ".$after2[0][0]."</b><br />";
					$full_names ['death_date'] = $after2 [0] [0];
				} else {
					//echo "<b>Apellidos:  ".$matriz[0][0]."</b><br />";
					$full_names ['last_name'] = $matriz [0] [0];
					//echo "<b>Nombre:  ".$matriz[0][1]."</b><br />";
					$full_names ['first_name'] = $matriz [0] [1];
					//echo "<b>Menci&oacute;n:  ".$matriz[0][2]."</b><br />";
					$full_names ['statement'] = $matriz [0] [2];
				}
				
				break;
			default :
				preg_match_all ( $patron_date_before, $matriz [0] [1], $before );
				preg_match_all ( $patron_date_after, $matriz [0] [1], $after );
				preg_match_all ( $patron_date_before, $matriz [0] [2], $before2 );
				preg_match_all ( $patron_date_after, $matriz [0] [2], $after2 );
				if (is_numeric ( $before [0] [0] ) or is_numeric ( $after [0] [0] )) {
					//echo "<b>Nombre:  ".$matriz[0][0]."</b><br />";
					$full_names ['first_name'] = $matriz [0] [0];
					//echo "<b>Fecha Nac.:  ".$before[0][0]."</b><br />";
					$full_names ['birth_date'] = $before [0] [0];
					//echo "<b>Fecha Muerte:  ".$after[0][0]."</b><br />";
					$full_names ['death_date'] = $after [0] [0];
					//echo "<b>Menci&oacute;n:  ".$matriz[0][2]."</b><br />";
					$full_names ['statement'] = $matriz [0] [2];
					//echo "<b>Menci&oacute;n:  ".$matriz[0][3]."</b><br />";
					$full_names ['other_statement'] = $matriz [0] [3];
				} elseif (is_numeric ( $before2 [0] [0] ) or is_numeric ( $after2 [0] [0] )) {
					//echo "<b>Apellidos:  ".$matriz[0][0]."</b><br />";
					$full_names ['last_name'] = $matriz [0] [0];
					//echo "<b>Nombre:  ".$matriz[0][1]."</b><br />";
					$full_names ['first_name'] = $matriz [0] [1];
					//echo "<b>Fecha Nac.:  ".$before2[0][0]."</b><br />";
					$full_names ['birth_date'] = $before2 [0] [0];
					//echo "<b>Fecha Muerte:  ".$after2[0][0]."</b><br />";
					$full_names ['death_date'] = $after2 [0] [0];
					//echo "<b>Menci&oacute;n:  ".$matriz[0][3]."</b><br />";
					$full_names ['statement'] = $matriz [0] [3];
				} else {
					//echo "<b>Apellidos:  ".$matriz[0][0]."</b><br />";
					$full_names ['last_name'] = $matriz [0] [0];
					//echo "<b>Nombre:  ".$matriz[0][1]."</b><br />";
					$full_names ['first_name'] = $matriz [0] [1];
					if (in_array ( $matriz [0] [2], $titles )) {
						//echo "<b>T&iacute;tulo:  ".$matriz[0][2]."</b><br />";
						$full_names ['title'] = $matriz [0] [2];
					} else {
						//echo "<b>Menci&oacute;n:  ".$matriz[0][2]."</b><br />";
						$full_names ['statement'] = $matriz [0] [2];
					}
					//echo "<b>Menci&oacute;n:  ".$matriz[0][3]."</b><br />";
					$full_names ['other_statement'] = $matriz [0] [3];
				}
				
				break;
		} //switch
		return $full_names;
	}
	
	public function delete_tables() {
		
		//$result = pg_Exec($dbConn, "delete from property;");
		//$result = pg_Exec($dbConn, "delete from access_point;");
		//$result = pg_Exec($dbConn, "delete from describable_entity;");
		$result = pg_Exec ( $this->dbConn, "delete from pangea;" );
	
		//Eliminar las relaciones
	//$result = pg_Exec($dbConn, "delete from exemplifies;");
	//$result = pg_Exec($dbConn, "delete from materializes;");
	//$result = pg_Exec($dbConn, "delete from realizes;");
	//$result = pg_Exec($dbConn, "delete from document_type;");
	/*$result = pg_Exec($dbConn, "delete from is_inventory_of;");
  $result = pg_Exec($dbConn, "delete from is_collection_of;");
  $result = pg_Exec($dbConn, "delete from is_conservation_of;");
  $result = pg_Exec($dbConn, "delete from is_note;");
  $result = pg_Exec($dbConn, "delete from is_isbn_of;");
  $result = pg_Exec($dbConn, "delete from is_acquisition_way;");
  $result = pg_Exec($dbConn, "delete from is_title_proper;");
  $result = pg_Exec($dbConn, "delete from is_paralell_title;");
  $result = pg_Exec($dbConn, "delete from is_other_title_info;");
  $result = pg_Exec($dbConn, "delete from is_title;");
  $result = pg_Exec($dbConn, "delete from without_title;");
  $result = pg_Exec($dbConn, "delete from is_named_as;");
  $result = pg_Exec($dbConn, "delete from is_lastname;");
  $result = pg_Exec($dbConn, "delete from is_creator;");
  $result = pg_Exec($dbConn, "delete from is_creator;");
  
  $result = pg_Exec($dbConn, "delete from is_abstract_of;");
  $result = pg_Exec($dbConn, "delete from is_publication_place_of;");
  $result = pg_Exec($dbConn, "delete from is_impression_place_of;");
  $result = pg_Exec($dbConn, "delete from is_publishing_house_of;");
  $result = pg_Exec($dbConn, "delete from is_publication_date_of;");
  $result = pg_Exec($dbConn, "delete from is_part_of;");
  $result = pg_Exec($dbConn, "delete from is_subject;");
  $result = pg_Exec($dbConn, "delete from is_language_of;");*/
	
	//Eliminar los atributos  
	//$result = pg_Exec($dbConn, "delete from controlled_access_point;");
	//$result = pg_Exec($dbConn, "delete from prefTerm;");
	//$result = pg_Exec($dbConn, "delete from manifestation_form;");
	//$result = pg_Exec($dbConn, "delete from title_proper;");
	//$result = pg_Exec($dbConn, "delete from title_parallel;");
	

	/*$result = pg_Exec($dbConn, "delete from inventories;");
  $result = pg_Exec($dbConn, "delete from isbn;");
  $result = pg_Exec($dbConn, "delete from object_sp;");
  $result = pg_Exec($dbConn, "delete from object_en;");
  $result = pg_Exec($dbConn, "delete from object_ru;");
  $result = pg_Exec($dbConn, "delete from object_it;");
  $result = pg_Exec($dbConn, "delete from objects;");
  $result = pg_Exec($dbConn, "delete from titles_sp;");
  $result = pg_Exec($dbConn, "delete from titles_en;");
  $result = pg_Exec($dbConn, "delete from titles_ru;");
  $result = pg_Exec($dbConn, "delete from titles_it;");
  $result = pg_Exec($dbConn, "delete from titles;");
  $result = pg_Exec($dbConn, "delete from auth_persons;");
  $result = pg_Exec($dbConn, "delete from persons;");
  $result = pg_Exec($dbConn, "delete from nomens;");
  $result = pg_Exec($dbConn, "delete from auth_coorporate_bodies;");
  $result = pg_Exec($dbConn, "delete from coorporate_bodies;");
  $result = pg_Exec($dbConn, "delete from auth_places;");*/
	
	//Borra las tablas de entidades
	//$result = pg_Exec($dbConn, "delete from item;");
	//$result = pg_Exec($dbConn, "delete from manifestation;");
	//$result = pg_Exec($dbConn, "delete from expression;");
	//$result = pg_Exec($dbConn, "delete from work;");  
	/*$result = pg_Exec($dbConn, "delete from subjects;");
  $result = pg_Exec($dbConn, "delete from places;");
  $result = pg_Exec($dbConn, "delete from dates;");
  $result = pg_Exec($dbConn, "delete from languages;");*/
	}
}
?>