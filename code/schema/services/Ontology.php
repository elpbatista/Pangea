<?php
class Ontology {	
	
	private $ontology;
	private $cant;
	private $dictionary;
	
	public function __construct(){
		
		$this->dictionary = array();		
		$this->ontology = array(
				
		'@attributes'=> 
			array(
				"xmlns"	=> "http://www.ohc.cu/2012/pangea-ontology/pangea#",
				"xmlns:pangea"	=> "http://www.ohc.cu/2012/pangea-ontology/pangea#",
				"xmlns:owl" => "http://www.w3.org/2002/07/owl#",
				"xmlns:rdf" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
				"xmlns:rdfs" => "http://www.w3.org/2000/01/rdf-schema#",
				"xmlns:skosxl"=> "http://www.w3.org/2008/05/skos-xl#",
				"xmlns:skos" => "http://www.w3.org/2004/02/skos/core#",
				"xmlns:frbr" => "http://purl.org/vocab/frbr/core#"
			)
		);
		$this->cant = -1;
	}
	
	public function GetOntology(){
		return $this->ontology;
	}
	
	public function Header($params){
		$this->ontology['owl:Ontology'] = array(
				'@attributes'=> array( 
										"rdf:about" => ""
				),
				'rdfs:comment' =>array(
										'@value'=>'Pangea Owl Ontology'
				),
				'owl:priorVersion' => array(
										'@attributes'=>array(
																"rdf:resource" => "http://pangea.ohc.cu/ontologyv2-0"
										)
				),
				'rdfs:label' =>array(
										'@value'=>'Pangea Ontology'
				)
			);
	}
	
	public function Definition($params, $defaultDefinition = "owl:Thing", $exportDB = true){
		
		
		
		foreach ($params as $subject => $pair) {
			
			$class = $defaultDefinition;
			
			if(array_key_exists("rdf:type", $pair)){
				$class = $pair["rdf:type"]['value'];
				unset($pair["rdf:type"]);
			}
			
			if($exportDB){
				
				if(array_key_exists("owl:inverseOf", $pair)){				
					unset($pair["owl:inverseOf"]);
				}
			}
			
			
			if(!array_key_exists($subject, $this->dictionary)){
				
				$pos = $this->cant++;
				$this->ontology[$class][$pos]['@attributes'] = array('rdf:ID' => $subject);
				$this->dictionary[$subject] = $pos;
			}else{
				$pos = $this->dictionary[$subject];
			}
			
			foreach ($pair as $predicated => $object) {
				
				if($exportDB){					
					$this->ontology[$class][$pos][$predicated]['@attributes']['test'] = $object['dbid'];					
				}
				
				if($object['type']=="uri"){
					$this->ontology[$class][$pos][$predicated]['@attributes']['rdf:resource'] = $object['value'];
					continue;
				}
				
				if(array_key_exists("lang", $object))
					$this->ontology[$class][$pos][$predicated]['@attributes']['xml:lang'] = $object['lang'];
					
				if(array_key_exists("datatype", $object))
					$this->ontology[$class][$pos][$predicated]['@attributes']['rdf:datatype'] = $object['datatype'];
					
					
				$this->ontology[$class][$pos][$predicated]['@value'] = $object['value'];
				
				
			}
		}		
	}
}

?>