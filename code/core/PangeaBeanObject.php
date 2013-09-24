<?php
class PangeaBeanObject {
	private $data;
	
	public function __construct() {
		$this->data = array ();
	}
	
	public function __set($property, $value) {
		$this->data [$property] = $value;
	}
	
	public function __get($property) {
		if (isset ( $this->data [$property] ))
			return $this->data [$property];
	}
	
	public function getProperty($property){
		if (isset ( $this->data [$property] ))
			return $this->data [$property];		
	}
	
	public function setProperty($property, $value) {
		$this->data [$property] = $value;
	}
	
	public function __toString() {
		return $this->toArray ();
	}
	
	/**
	 * @return the $data
	 */
	public function getData() {
		return $this->data;
	}
	
	public function toArray() {
		$return = array ();
		foreach ( $this->data as $var => $val ) {
			if (is_object ( $this->data [$var] )) {
				$return [$var] = $this->data [$var]->toArray ();
			} elseif (is_array ( $this->data [$var] )) {
				$array = array ();
				foreach ( $this->data [$var] as $key => $v2 )
					if (is_object ( $v2 ))
						$array [$key] = $v2->toArray ();
					else
						$array [$key] = $v2;
				
				$return [$var] = $array;
			} else {
				$return [$var] = $this->data [$var];
			}
		}
		
		return $return;
	}
}
?>