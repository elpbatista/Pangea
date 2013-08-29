<?php

include_once dirname ( __FILE__ ) . '/../../core/dao/GenericDAO.php';

class SchemaDAO extends GenericDAO{
	


	function SearchDAO() {
		parent::GenericDAO ();
	}	
	
	/**
	 * @deprecated
	 * no existe la tabla schema
	 */
	public function getSchemasNamespace(){
		
		$arrayResult = array();

		$query = "select prefix, namespace from schema";
		
		$result = pg_query($this->dbConn,$query);
		$count = pg_num_rows($result);
		
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$arrayResult[$row['prefix']] = $row['namespace'];			
		}
			
		pg_free_result($result);
		
		
		return $arrayResult;
	}
	/**
	 * @deprecated
	 * no existe la tabla schema
	 */
	public function getDefaultNamespace(){		

		$query = "select namespace from schema where prefix='pangea'";
		
		$result = pg_query($this->dbConn,$query);
		$count = pg_num_rows($result);
		
		$row = pg_fetch_row($result);
			
		pg_free_result($result);
		
		return $row;
	}
	
	public function getPropertyDomainRange() {
		
		$arrayResult = array();	
		
		//$query = "select uri,domain,range,type,label from \"rdf:property\" order by domain, range";
		//$query = "select uri,domain,range,uri,label from \"" . META_PROPERTY_TABLENAME . "\" order by uri, domain, range";
		$query = "select owlp.uri,owlp.domain,owlp.range,owlp.uri,owlp.label,owlop.uri as \"isfromobject\" from \"" . META_PROPERTY_TABLENAME . "\" owlp left outer join \"" . META_OBJECTPROPERTY_TABLENAME . "\" owlop on(owlp.uri=owlop.uri) order by owlp.uri, owlp.domain, owlp.range";
		
		$result = pg_query($this->dbConn,$query);
			
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$arrayResult[$row['uri']]['domain'][] = $row['domain'];
			$arrayResult[$row['uri']]['range'][] = $row['range'];
			
			if (isset($row['isfromobject']))
				$arrayResult[$row['uri']]['type'] = META_OBJECTPROPERTY_TABLENAME;
			else
				$arrayResult[$row['uri']]['type'] = META_DATAPROPERTY_TABLENAME;

			$arrayResult[$row['uri']]['label'][] = $row['label'];
		}
			
		pg_free_result($result);
		
		return $arrayResult; 
	}
	
	public function getClassIdentifier() {
		
		$result = array();
		
		//$query = "select uri from \"rdf:class\" where description = 'uri'";			
		$query = "select uri from \"owl:class\" order by uri";
		$result = pg_query($this->dbConn,$query);
			
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$result[] = $row['uri'];	
		}
			
		pg_free_result($result);
			
		return $result;
	}
	
	public function getClassLabels() {
		
		$results = array();
				
		$query = "select uri, label from \"owl:class\" order by uri";
		$result = pg_query($this->dbConn,$query);
		
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$results[$row['uri']] = $row['label'];	
		}
			
		pg_free_result($result);
			
		return $results;
	}
		
	public function ClassDefinition(){
		
		$arrayResult = array();
		
		$query = "SELECT owl.uri, mdescrip.object, pgc.relname FROM \"owl:class\" as owl, \"meta:descriptionRelation\" mdescrip, pg_class as pgc where owl.uri = mdescrip.subject and pgc.oid =  mdescrip.tableoid";
		
		$result = pg_query($this->dbConn,$query);
		
		$count = pg_num_rows($result);
		
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$arrayResult[$row["uri"]][$row["relname"]] = array('type'=>"uri", 'value' => $row["object"]);			
		}
			
		pg_free_result($result);
			
		return $arrayResult;
	}
	
	public function PropertyDefinition(){
		$arrayResult = array();
		
		//$query = "SELECT prop.uri, prop.domain, prop.range, prop.type, pgc.relname, pdescrip.object FROM \"" . META_PROPERTY_TABLENAME . "\" as prop, \"meta:descriptionRelation\" pdescrip, pg_class as pgc where prop.uri = pdescrip.subject and pgc.oid =  pdescrip.tableoid";
		$query = "SELECT prop.uri, prop.domain, prop.range, pgc.relname, pdescrip.object FROM \"" . META_PROPERTY_TABLENAME . "\" as prop, \"meta:descriptionRelation\" pdescrip, pg_class as pgc where prop.uri = pdescrip.subject and pgc.oid =  pdescrip.tableoid";
		$result = pg_query($this->dbConn,$query);
		
		$count = pg_num_rows($result);
		
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			if(!array_key_exists($row["uri"], $arrayResult)){

				//$arrayResult[$row["uri"]]['rdf:type'] = array('type'=>"uri", 'value' => $row["type"]);
			
				$arrayResult[$row["uri"]]['rdfs:domain'] = array('type'=>"uri", 'value' => $row["domain"]);
				$arrayResult[$row["uri"]]['rdfs:range'] = array('type'=>"uri", 'value' => $row["range"]);
			}
			
			$arrayResult[$row["uri"]][$row["relname"]] = array('type'=>"uri", 'value' => $row["object"]);			
		}
			
		pg_free_result($result);
			
		return $arrayResult;
	}
	
	public function IndividualURIDefinition(){
		$arrayResult = array();
		
		//$query = "SELECT prop.subject, prop.tableoid, prop.object, prop.id from \"owl:class\" as owl, \"" . META_PROPERTY_TABLENAME . "\" as prop where owl.id = prop.subject";
		
		$query = "SELECT prop.subject, prop.id as dbid, pgc.relname, prop.object, pgc1.relname as type from \"" . PG_CLASS_TABLENAME . "\" as owl, \"pangea:ObjectProperty\" as prop, pg_class as pgc, pg_class as pgc1 where owl.id = prop.subject and pgc.oid =  prop.tableoid and pgc1.oid =  owl.tableoid";
		
		$result = pg_query($this->dbConn,$query);
		
		$count = pg_num_rows($result);
		
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			if(!array_key_exists($row["subject"], $arrayResult)){
				
				$arrayResult[$row["subject"]]['rdf:type'] = array('type'=>"uri", 'value' => $row["type"]);
			}
			
			$arrayResult[$row["subject"]][$row["relname"]] = array('type'=>"uri", 'value' => $row["object"], 'dbid' => $row["dbid"] );
			
			//$arrayResult[$row["subject"]]['pangea:dbid'] = array('type'=>"uri", 'value' => $row["dbid"]);
				
		}
			
		pg_free_result($result);
			
		return $arrayResult;
	}
	
	public function IndividualLiteralDefinition(){
		$arrayResult = array();		
		
		$query = "SELECT prop.subject, prop.id as dbid, pgc.relname, prop.object, pgc1.relname as type, prop.datatype, prop.lang from \"" . PG_CLASS_TABLENAME . "\" as owl, \"pangea:DatatypeProperty\" as prop, pg_class as pgc, pg_class as pgc1 where owl.id = prop.subject and pgc.oid =  prop.tableoid and pgc1.oid =  owl.tableoid";
		
		$result = pg_query($this->dbConn,$query);
		
		$count = pg_num_rows($result);
		
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			if(!array_key_exists($row["subject"], $arrayResult)){
				
				$arrayResult[$row["subject"]]['rdf:type'] = array('type'=>"uri", 'value' => $row["type"]);
			}
			
			$arrayResult[$row["subject"]][$row["relname"]] = array('type'=>"literal", 'value' => $row["object"], 'datatype'=>$row["datatype"], 'lang' => $row["lang"], 'dbid' =>$row["dbid"]);	
		}
			
		pg_free_result($result);
			
		return $arrayResult;
	}	
	
	public function AllIndividuals(){
		$arrayResult = array();
		
		$query = "SELECT id From \"" . PG_CLASS_TABLENAME . "\" as owl, \"\" ";
		
		$result = pg_query($this->dbConn,$query);
		
		$count = pg_num_rows($result);
		
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$arrayResult[] = $row["id"];			
		}
			
		pg_free_result($result);
			
		return $arrayResult;
	}
	
	public function object_sequence(){
		$query = "select last_value from system_object_id_seq";
		
		$result = pg_query($this->dbConn,$query);
		
		$count = pg_num_rows($result);
		
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$last = $row["last_value"];		
		}
			
		pg_free_result($result);
			
		return  $last;
	}
	
	function ClassEnumeration(){
		
		$result = array();
		
		$query = "select subject,object from \"owl:enumeration\"";			
		$result = pg_query($this->dbConn,$query);
			
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$result[$row['subject']] = $row['object']; 	
		}
			
		pg_free_result($result);
		
		return $result;
	}
	
	function getClassRestrictionValue (){
		
		$result_array = array();
		
		$query = "select subject, object, value, type from \"owl:restrictionProperty\"";			
		$result = pg_query($this->dbConn,$query);
			
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$result_array[$i]['subject'] = $row['subject'];
			$result_array[$i]['property'] = $row['object'];
			$result_array[$i]['key'] = $row['type'];
			$result_array[$i]['value'] = $row['value'];
			
		}
			
		pg_free_result($result);
		
		return $result_array;
	}
	
	function getClassRestrictionCardinal(){
		
		$result = array();
		
		$query = "select subject, object, value, type from \"owl:restrictionCardinality\"";			
		$result = pg_query($this->dbConn,$query);
			
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$result[$row['subject']]['property'][] = $row['object'];
			$result[$row['subject']]['key'][] = $row['type'];
			$result[$row['subject']]['value'][] = $row['value'];
		}
			
		pg_free_result($result);
		
		return $result;
	}
	
	function ModelClassIntersection(){
		
		$result = array();
		
		$query = "select subject,object from \"owl:intersectionOf\"";			
		$result = pg_query($this->dbConn,$query);
			
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$result[$row['subject']] = $row['object']; 	
		}
			
		pg_free_result($result);
		
		return $result;
	}
	
	function getModelClassUnion(){
		
		$result = array();
		
		$query = "select subject,object from \"owl:unionOf\"";			
		$result = pg_query($this->dbConn,$query);
			
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$result[$row['subject']] = $row['object']; 	
		}
			
		pg_free_result($result);
		
		return $result;
	}
	
	function getModelClassComplement(){
		
		$result = array();
		
		$query = "select subject,object from \"owl:complementOf\"";			
		$result = pg_query($this->dbConn,$query);
			
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$result[$row['subject']] = $row['object']; 	
		}
			
		pg_free_result($result);
		
		return $result;
	}
	
	public function getSubClass() {
		
		$result_array = array();
		
		$query = "select subject, object from \"rdf:subClassOf\"";			
		$result = pg_query($this->dbConn,$query);
			
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$result_array[$row['subject']] [] = $row['object']; 	
		}
			
		pg_free_result($result);
		
		return $result_array;
	}
		
	public function getProperties(){
		
		$result_array = array();
		
		//$query = "select uri from \"rdf:property\"";			
		$query = "select uri from \"owl:Property\"";
		$result = pg_query($this->dbConn,$query);
			
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$result_array[] = $row['uri'];	
		}
			
		pg_free_result($result);
		
		return $result_array;
	}
	
	public function getSubProperties(){
		
		$result_array = array();
		
		$query = "select subject, object from \"rdf:subPropertyOf\"";			
		$result = pg_query($this->dbConn,$query);
			
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$result_array[$row['subject']] [] = $row['object']; 	
		}
			
		pg_free_result($result);
		
		return $result_array;
	}
	
	public function getInverse(){
		
		$result_array = array();
		
		$query = "select subject,object from \"owl:inverseOf\"";			
		$result = pg_query($this->dbConn,$query);
			
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$result_array[$row['subject']] = $row['object']; 	
		}
		
		pg_free_result($result);
		
		return $result_array;
	}
	
	public function getLabeling(){
		
		$result_array = array();
		
		$query = "select uri,label from \"rdf:resource\"";			
		$result = pg_query($this->dbConn,$query);
			
		$count = pg_num_rows($result);
		for ($i = 0; $i < $count; $i++) 
		{
			$row = pg_fetch_array($result);
			
			$result_array[$row['uri']] = $row['label']; 	
		}
			
		pg_free_result($result);
		
		return $result_array;
	}
}

//PangeaRDFSchemaDAO::getInstance()->getSubClass();




/*
abstract class TemplateMethod{
	
	public abstract function template();

	public function doIt($query, $dbConn){
		$result = array();
		$result = pg_query($dbConn,$query);	
		$count = pg_num_rows($result);
		$this->template();
		pg_free_result($result);
		return $result;
	}
}*/
?>