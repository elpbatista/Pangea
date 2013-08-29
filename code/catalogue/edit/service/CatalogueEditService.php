<?php

include_once dirname ( __FILE__ ) . '/../dao/CatalogueEditDAO.php';

class CatalogueEditService{
	private $cedao;
	
	function CatalogueEditService(){
		$this->cedao = new CatalogueEditDAO();
	}
	
	function saveAccessPoint($valueAP, $lang){
		
		return $this->cedao->saveAccessPoint($valueAP, $lang);
	}
	
	function saveLiteral($idProperty, $subject, $object){
		
		return $this->cedao->saveLiteral($idProperty, $subject, $object);
	}
	
	function saveEntity($labelEntity){
		$id = $this->cedao->saveEntity($labelEntity);
		return $id;
	}
	
	function saveRelation($idPropertyType, $subject, $object){
		
		return $this->cedao->saveRelation($idPropertyType, $subject, $object);
	}
	
	function updateRelations($idObj, $id, $idProperty){		
		
		$direct_inverse = $this->cedao->loadDirectAndInverseProperty($id, $idProperty);		
		$this->cedao->updateDirectAndInverseProperty($direct_inverse, $idObj);			
	}
	
	function updateLiteral($id, $idProperty, $object){
		
		return $this->cedao->updateLiteral($id, $idProperty, $object);
	}
	
	
}

?>