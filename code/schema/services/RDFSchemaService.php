<?php

ini_set("memory_limit","1024M");

include_once 'Array2xml.php';
include_once 'Ontology.php';
include_once 'SchemaService.php';

class RDFSchemaService{
	
	private $schemaService;
	
	public function __construct(){
		$this->schemaService = new SchemaService();
	}
	
	public function RDFDoc($rrr = 0, $nombre){
		
		$ontObject = new Ontology();
		$ontObject->Header(null);
		/*
		$params = array(
				'definition' =>array('class'=>'owl:class', 'name'=>"Wine"),
				'object_description'=>array('rdf:subClassOf'=>"#PotableLiquid"),
				'value_description'=>array('rdfs:label'=>array('lang' => 'en','value' => 'wine'),
											'owl:minCardinality' => array('datatype' => 'xsd:nonNegativeInteger', 'value' => 1))
		);*/
		/*
		$class_definitions = $this->schemaService->ClassDefinition();	
		$ontObject->Definition($class_definitions, "owl:Class");
		
		$property_definition = $this->schemaService->PropertyDefinition();
		$ontObject->Definition($property_definition, "rdf:property");
		*/
		if($rrr == 0){
			$individual_uri_definition = $this->schemaService->IndividualsURIDefinition();
			$ontObject->Definition($individual_uri_definition, "owl:Thing");
		}else{
			$individual_literal_definition = $this->schemaService->IndividualsLliteralDefinition();
			$ontObject->Definition($individual_literal_definition, "owl:Thing");
		}
		
		
		/*
		foreach ($class_definitions as $value) {
			
			$ontObject->ClassDefinition($value);
		}*/
		/*
		$property_definition = $this->schemaService->PropertyDefinition();
		
		foreach ($property_definition as $value) {
			
			$ontObject->ClassDefinition($value);
		}
		
		$individual_definition = $this->schemaService->IndividualsDefinition();
		
		foreach ($individual_definition as $value) {
			
			$ontObject->ClassDefinition($value);
		}
		*/
		$ontology = $ontObject->GetOntology();
		$ontology['owl:Ontology']['pangea:dbSequence']['@value'] = $this->schemaService->Sequence();			
		$xml = Array2XML::createXML('rdf:RDF', $ontology);
		$xmlWriter = new XMLWriter();
		//$xmlWriter->startElement($name);
		$a = new DOMDocument();
		//$xmlWriter->writeRaw($content);
		return file_put_contents('example.xml', $xmlWriter->flush(true), FILE_APPEND);
		//return $xml->save($nombre);
		//return $xml->saveXML();
	}
	public function JSONSchema($fileName){		
		$data = $this->GetSchema();
		$jsonData = json_encode($data);
		file_put_contents($fileName, $jsonData);
	}	
	
	private function GetSchema(){
		$result = array();
		$result[META_PROPERTY_TABLENAME] = array_merge($this->schemaService->getPropertyDomainRange(), $this->schemaService->PropertyDefinition());
		$result["owl:class"] = $this->schemaService->ClassDefinition();		
		
		//$restrictionCardinal = $this->schemaService->getClassRestrictionCardinal();
		return $result;
		
	}
}
$rdf = new RDFSchemaService();
$rdf->JSONSchema("schema.json");
echo 'ended';
/*
$rdf = new RDFSchemaService();
ini_set ( 'memory_limit', '4096M' );
$nombre = 'testProperty.xml';
echo $rdf->RDFDoc(0, $nombre);
*/
?>