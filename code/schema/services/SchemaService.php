<?php

include_once dirname ( __FILE__ ) . '/../../core/PangeaBeanObject.php';
include_once dirname ( __FILE__ ) . '/../../core/services/GenericService.php';
include_once dirname ( __FILE__ ) . '/../dao/SchemaDAO.php';
include_once dirname ( __FILE__ ) . '/../../search/services/SearchService.php';

class SchemaService extends GenericService {

	private $schemaDao;
	private $searchService;
	
	function SchemaService(){
	}
	
	private function getSearchService(){
		if($this->searchService == null)
			$this->searchService = new SearchService();
		return $this->searchService;
	}
	
	private function getSchemaDao(){
		if($this->schemaDao == null)
			$this->schemaDao = new SchemaDAO();
		return $this->schemaDao;
	}
	
	public function getSchemasNamespace(){
		return $this->getSchemaDao()->getSchemasNamespace();
	}
	
	public function getDefaultNamespace(){
		
		return $this->getSchemaDao()->getDefaultNamespace();		
	}
	
	public function getPropertyDomainRange(){

		return $this->getSchemaDao()->getPropertyDomainRange();
	}
	
	public function getClassIdentifier(){
		
		return $this->getSchemaDao()->getClassIdentifier();
	}
	
	public function getClassLabels(){
		
		return $this->getSchemaDao()->getClassLabels();
	}
		
	function getClassRestrictionValue (){
		
		return $this->getSchemaDao()->getClassRestrictionValue();
	}
	
	public function getSubClass(){
		
		return $this->getSchemaDao()->getSubClass();
	}
	
	public function getProperties(){
		
		return $this->getSchemaDao()->getProperties();
	}
	
	public function getSubProperties(){
		
		return $this->getSchemaDao()->getSubProperties();
	}
	
	public function getLabeling(){
		
		return $this->getSchemaDao()->getLabeling();
	}
	
	public function ClassDefinition(){
		
		return $this->getSchemaDao()->ClassDefinition();
		//return $this->definitionTemplate($this->getSchemaDao()->ClassDefinition(), "owl:class", "rdf:type");
		/*
		$array = $this->schemaDao->ClassDefinition();
		$result= array();
		$cant = 0;
		
		foreach ($array as $subject => $pair) {
			
			$class ="owl:class";
			
			if(array_key_exists('rdf:type', $pair)){
				$class = $pair['rdf:type'];
				unset($pair['rdf:type']);
			}
			
			$result[$cant]['definition'] = array('name'=>$subject, 'class'=>$class);
			
			foreach ($pair as $predicated => $object) {
				$result[$cant]['object_description'][$predicated] = $object;
			}
			$cant++;
		}		
		return $result;*/
	}
	
	public function PropertyDefinition(){
		
		return $this->getSchemaDao()->PropertyDefinition();
		
		//return $this->definitionTemplate($this->getSchemaDao()->PropertyDefinition(), "owl:ObjectProperty", "rdf:type");
		/*
		$array = $this->schemaDao->PropertyDefinition();
		$result= array();
		$cant = 0;
		
		foreach ($array as $subject => $pair) {
			
			$class ="owl:ObjectProperty";
			
			if(array_key_exists('rdf:type', $pair)){
				$class = $pair['rdf:type'];
				unset($pair['rdf:type']);
			}
			
			$result[$cant]['definition'] = array('name'=>$subject, 'class'=>$class);
			
			foreach ($pair as $predicated => $object) {
				$result[$cant]['object_description'][$predicated] = $object;
			}
			$cant++;
		}		
		
		return $result;*/
	}
	
	public function IndividualsURIDefinition(){
				
		return $this->getSchemaDao()->IndividualURIDefinition();		
	}
	
	public function IndividualsLliteralDefinition(){
		return $this->getSchemaDao()->IndividualLiteralDefinition();
	}
	
	public function Sequence(){
		return $this->getSchemaDao()->object_sequence();
	}
	
	private function definitionTemplate($dbArray, $defaultDefinition, $onProCostumDefinition){
		
		$result= array();
		$cant = 0;
		
		foreach ($dbArray as $subject => $pair) {

			$class = $defaultDefinition;
			
			if(array_key_exists($onProCostumDefinition, $pair)){
				$class = $pair[$onProCostumDefinition]['value'];
				unset($pair[$onProCostumDefinition]);
			}
			
			$result[$cant]['definition'] = array('name'=>$subject, 'class'=>$class);
			
			foreach ($pair as $predicated => $object) {
				$result[$cant]['description'][$predicated] = $object;
			}
			$cant++;
		}
		
		return $result;
	}
}
?>