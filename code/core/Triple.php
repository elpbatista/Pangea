<?php
class Triple {
	private $content = array ();
	
	public function __construct($subject, $predicate, $object) {
		$this->content [] = $subject;
		$this->content [] = $predicate;
		if (! is_array ( $object )){
			$this->content []["type"] = 'uri'; 
			$this->content []["value"] = $object;
		}else
			$this->content [] = $object;
	}
	
	public function toString() {
		$stream = '{"subject":"' . $this->content [0] . '","predicate":"' . $this->content [1] . '","object":';
		
		$literal = '';
		foreach ( $this->content [2] as $key => $value ) {
			$stream .= '"' . $key . '":"' . $value . '"';
			$literal = ($literal != '') ? ',' : '';
		}
		
		$stream .= '"{' . $literal . '}"}';
		
		return $stream;
	}
	
	public function toArray($labeled) {
		$tArray = array ();
		
		if ($labeled)
			$tArray = array_combine ( array ('subject', 'predicate', 'object' ), array_values ( $this->content ) );
		else
			$tArray = $this->content;
		
		return tArray;
	}
}
?>