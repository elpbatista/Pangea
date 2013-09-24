<?php

include_once dirname ( __FILE__ ).'/../PangeaObject.php';
include_once RDFAPI_INCLUDE_DIR . 'RdfAPI.php';

class ModelSimpleFactory{	
	
	
	private static $ns = array(
								'rdf' => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
								'rdfs' => "http://www.w3.org/2000/01/rdf-schema#",
								'owl' => "http://www.w3.org/2002/07/owl#",
								'frbr' =>"http://purl.org/vocab/frbr/core#",
								'skos' => "http://www.w3.org/2004/02/skos/core#",
								'vann' => "http://purl.org/vocab/vann/",
								'foaf' => "http://xmlns.com/foaf/0.1/",	 
  								'dct' => "http://purl.org/dc/terms/",
								'dc' => "http://purl.org/dc/elements/1.1/",
								'pangea' => "http://ohc.cu/2011/06/pangea#"
	);
	
	private static function convert($resource){
		
		$explode = explode ( ":", $resource );
		$prefix = $explode [0];
		$namespace = self::$ns[$prefix];
		
		return $namespace . $explode [1];
	}
	
	static public function createDomainRange($param, & $result = array()){
		
		//$model = new MemModel ();
			
		
		foreach ( $param as $resource => $value ) {
			
			$sizeDomain = sizeof ( $value ['domain'] );
			$sizeRange = sizeof ( $value ['range'] );
			$size = sizeof($result);
			
			$s = new Resource(self::convert($resource));
			$p = new ResResource(self::$ns['rdfs'] .'domain');			
			$o = ($sizeDomain == 1)? new Resource(self::convert($value ['domain'] [0])) : self::owlUnion ( $value ['domain'], $result, "Domain" );
			
			$size = sizeof($result);
			
			$array[$size]['s'] = $s;
			$array[$size]['p'] = $p;
			$array[$size]['o'] = self::createObject('uri', $o);			
			
			//$model->add ( new Statement ( $s, $p, $o) );
			
			$p = new ResResource(self::$ns['rdfs'] .'range');
			$o = ($sizeRange == 1)? new Resource(self::convert($value ['range'] [0])) : self::owlUnion ( $value ['range'], $result, "Range" );
			
			$size = sizeof($result);
			
			$array[$size]['s'] = $s;
			$array[$size]['p'] = $p;
			$array[$size]['o'] = self::createObject('uri', $o);
			
			//$model->add ( new Statement ( $s, $p, $o));
			
			$p = self::$ns['rdf'] .'type';
			$o = new ResResource(self::$ns['owl'] .'ObjectProperty');
			

			$size = sizeof($result);
			
			$array[$size]['s'] = $s;
			$array[$size]['p'] = $p;
			$array[$size]['o'] = self::createObject('uri', $o);
			
			//$model->add(self::createType($s, $o));
			
			
		}
		return $result;
		//return $model;
	}
	
	
	static public function createClassIdentifier($param, & $result = array()){
		
		//$model = new MemModel ();
		
		$size = sizeof($result);
		
		foreach ( $param as $class ){
			
			$s = new ResResource(self::convert($class));
			$p = self::$ns['rdf'] .'type';			
			$o = new ResResource(self::$ns['owl'] .'Class');	

			
			$array[$size]['s'] = $s;
			$array[$size]['p'] = $p;
			$array[$size]['o'] = self::createObject('uri', $o);
			
			$size++;
			
			//$model->add (self::createType($s, $o));
		}
		return $result;
		//return $model;
	}
	
	static public function createRestriction($param, & $result){
		
		//$model = new MemModel ();
		
		
		
		foreach ( $param as $classRestriction ) {
			
			$class = $classRestriction['subject'];
			$key = $classRestriction ['key'];
			$property = $classRestriction ['property'];
			$value = $classRestriction ['value'];
			
			$s = new Resource($class) ;	
			$p = self::$ns['rdf'] .'type';		
			$o = new ResResource(self::$ns['owl'] .'Class');
			
			$size = sizeof($result);
			
			$array[$size]['s'] = $s;
			$array[$size]['p'] = $p;
			$array[$size]['o'] = self::createObject('uri', $o);			
			
			//$model->add ( self::createType($s, $o) );			
			
			$p = new ResResource(self::$ns['rdfs'] .'subClassOf');			
			$o = self::owlRestrictionValue ( $key, $property, $value, $result );
			
			$size = sizeof($result);
			
			$array[$size]['s'] = $s;
			$array[$size]['p'] = $p;
			$array[$size]['o'] = self::createObject('uri', $o);			
			
			//$model->add ( new Statement( $s, $p, $o ));			
		}
		
		return $result;
	}
	
	static public function createSubClass($param, & $result = array()){
		
		//$model = new MemModel ();
		$size = sizeof($result);
		
		foreach ( $param as $class => $subClass ){
			
			$s = new Resource(self::convert($subClass));
			$p = new ResResource(self::$ns['rdfs'] .'subClassOf');	
			$o = new Resource($class);
			
			$array[$size]['s'] = $s;
			$array[$size]['p'] = $p;
			$array[$size]['o'] = self::createObject('uri', $o);
			
			$size++;
			
			//model->add (new Statement($s, $p, $o));
		}			
		return $result;	
		//return $model;
	}
	
	static public function createObjectProperty($param, & $result = array()){
		
		//$model = new MemModel ();
		
		$size = sizeof($result);
				
		foreach ( $param as $resource ){
			
			$s = new Resource(self::convert($resource));
			$p = self::$ns['rdf'] .'type';							
			$o = new ResResource(self::$ns['owl'] .'ObjectProperty');	

			$array[$size]['s'] = $s;
			$array[$size]['p'] = $p;
			$array[$size]['o'] = self::createObject('uri', $o);
			
			$size++;
			
			//$model->add (self::createType($s, $o));
		}
			
		
		return $result;	
		//return $model;
	}

	static public function createDataTypeProperty($param, & $result = array()){
		
		//$model = new MemModel ();
		$size = sizeof($result);
				
		foreach ( $param as $resource ){
			
			$s = new Resource(self::convert($resource));
			$p = self::$ns['rdf'] .'type';							
			$o = new ResResource(self::$ns['owl'] .'DatatypeProperty');	

			$array[$size]['s'] = $s;
			$array[$size]['p'] = $p;
			$array[$size]['o'] = self::createObject('uri', $o);
			
			$size++;
			
			//$model->add (self::createType($s, $o));
		}			
		
		return $result;	
		//return $model;		
	}
	
	static public function createSubProperty($param){}
	
	static public function createLabel($param, & $result = array()){
		
		//$model = new MemModel ();	
		$size = sizeof($result);
		
		foreach ( $param as $resource => $label ){
			
			//$s = new ResResource(self::convert($resource));	
			$s = new ResResource($resource);	
			$p = new ResResource(self::$ns['rdfs'] .'label');	
			$o = $label;
			
			$array[$size]['s'] = $s;
			$array[$size]['p'] = $p;
			$array[$size]['o'] = self::createObject('literal', $o, 'en');
			
			$size++;
			
			//$model->add (new Statement ($s, $p, $o));			
		}	
		
		return $result;	
		//return $model;
	}

	/*
	 * Este metodo devuelve un arreglo de tripletas.
	 * */
	static public function createLabelArray($param){
		
		$triples = array();
		
		foreach ( $param as $resource => $label )
			$triples [] = array("subject" => $resource, "predicate" => 'rdfs:label', "object" => array('type' => 'literal' , 'value' => $label, 'lang' => 'en'));	
		
		return $triples;
	}
	
	static public function createObject($type, $value, $lang = null, $dtype = null){
		
		return array(
						'type' => $type,
						'value' => $value,
						'lang' => $lang,
						'datatype' => $dtype			
		);
	}
	
	static public function createModel($param){
		
		$model = new MemModel();
		
		foreach ($param as $i => $triples) {
			
			$s = new ResResource($triples[0]);
			$p = new ResResource($triples[1]);
			$o = is_array($triples[2]) ? new Literal($triples[2][0], $triples[2][1], $triples[2][2]) : new ResResource($triples[2]);
			
			$model->add(new Statement($s, $p, $o));		
		}
		
		return $model;
	}
	
	static private function owlUnion($union, & $model, $bNodeName = 'bNode'){
		
		$s = new BlankNode ( "all$bNodeName" );
		$p = new ResResource(self::$ns['owl'] .'unionOf');
		$o = self::rdfList ( $union, $model, $bNodeName );
		
		$size = sizeof($model);
		
		$array[$size]['s'] = $s;
		$array[$size]['p'] = $p;
		$array[$size]['o'] = self::createObject('bnode', $o);
		
		//$model->add ( new Statement ( $s, $p, $o ) );
		
		return $s;		
	}
	
	static private function rdfList($list, $model, $bNodeName = 'bNode'){
		$cout = sizeof ( $list );
		
		$bNode1 = new BlankNode ( $bNodeName );
		
		for($i = 0; $i < $cout; $i ++) {
			
			$current = $i + 1;
			$nameCurrent = "bNode$current";
			$next = $current + 1;
			$nameNext = "bNode$next";
			
			$$nameNext = ($i == $cout - 1) ? new ResResource(self::$ns['rdf'].'nil') : new BlankNode ( $bNodeName );
			
			$s = $$nameCurrent;
			$p = new ResResource(self::$ns['rdf'].'first');
			$o = new Resource(self::convert($list[$i]));
			
			$size = sizeof($model);
			$array[$size]['s'] = $s;
			$array[$size]['p'] = $p;
			$array[$size]['o'] = self::createObject('uri', $o);;
			
			//$model->add ( new Statement ( $s, $p, $o) );
			
			$p = new ResResource(self::$ns['rdf'].'rest');;
			$o = $$nameNext;
			
			$size = sizeof($model);
			$array[$size]['s'] = $s;
			$array[$size]['p'] = $p;
			$array[$size]['o'] = self::createObject('bnode', $o);
			//$model->add ( new Statement ( $s, $p, $o) );
		}
		
		return $bNode1;
	}
	
	private static function owlRestrictionValue($type, $property, $value, & $model) {		
		
		$allType = array (
							'all' => self::$ns['owl']. 'allValuesFrom', 
							'some' => self::$ns['owl'] . 'someValuesFrom', 
							'hasValue' => self::$ns['owl'] . 'hasValue'
		);
		
		$s = new BlankNode ( 'rest' );	
		$p = self::$ns['rdf'] .'type';		
		$o = new ResResource(self::$ns['owl'] .'Restriction');
		
		$size = sizeof($model);
		$array[$size]['s'] = $s;
		$array[$size]['p'] = $p;
		$array[$size]['o'] = self::createObject('uri', $o);;
		
		//$model->add ( self::createType($s, $o));		
		
		$p = new ResResource(self::$ns['owl'] .'onProperty');
		$o = new Resource(self::convert($property));
		
		$size ++;
		
		$array[$size]['s'] = $s;
		$array[$size]['p'] = $p;
		$array[$size]['o'] = self::createObject('uri', $o);;
		
		//$model->add ( new Statement ( $s, $p, $o ) );
		
		$p = new ResResource($allType [$type]);
		$o = new Resource($value);
		
		$size ++;
		
		$array[$size]['s'] = $s;
		$array[$size]['p'] = $p;
		$array[$size]['o'] = self::createObject('uri', $o);;
		
		//$model->add ( new Statement ( $s, $p, $o ) );
		
		return $s;
	}
	
	private static function createType($subject, $object){
		return new Statement($subject,new ResResource(self::$ns['rdf'] .'type'), $object);
	}
}
?>