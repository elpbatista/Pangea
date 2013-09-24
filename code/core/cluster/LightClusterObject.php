<?php
include_once dirname ( __FILE__ ) . '/../../util/GenericUtilityObject.php';

/** 
 * @author roman
 * 
 * 
 */
class LightClusterObject {
	private $clusteredEntities, $entitiesCount, $entitiesWeights, $entitiesLabels, $entitiesType;
	private $clutersInfo;
	//TODO - Insert your code here
	

	private function setClustersInfo() {
		$clustersInfo = array ();
		
		if (! empty ( $this->clusteredEntities ))
			foreach ( $this->clusteredEntities as $key => $entities )
				$clustersInfo [$key] = sizeof ( $entities );
		
		$this->clutersInfo = $clustersInfo;
	}
	
	function __construct($clusteredEntities = NULL, $entitiesCount = NULL, $weights = NULL, $labels = NULL, $types = NULL) {
		if (isset ( $clusteredEntities ))
			$this->clusteredEntities = $clusteredEntities;
		else
			$this->clusteredEntities = array ();
		
		if (isset ( $clusteredEntities ))
			$this->entitiesCount = $entitiesCount;
		else
			$this->entitiesCount = 0;
		
		if (isset ( $clusteredEntities ))
			$this->entitiesWeights = $weights;
		else
			$this->entitiesWeights = array ();
		
		if (isset ( $clusteredEntities ))
			$this->entitiesLabels = $labels;
		else
			$this->entitiesLabels = array ();
		
		if (isset ( $clusteredEntities ))
			$this->entitiesType = $types;
		else
			$this->entitiesType = array ();
		
		$this->setClustersInfo ();
	}
	
	/**
	 * @return the $clusteredEntities
	 */
	public function getClusteredEntities() {
		return $this->clusteredEntities;
	}
	
	/**
	 * @return the $clutersInfo
	 */
	public function getClutersInfo() {
		return $this->clutersInfo;
	}
	
	public function getClustersCount() {
		return sizeof ( $this->clusteredEntities );
	}
	
	public function getClustersInPage($page, $pageSize) {
		$pagedClusters = array ();
		
		$utilityObj = new GenericUtilityObject ();
		
		$entitiesCount = sizeof ( $this->clutersInfo );
		
		if (($entitiesCount == 0) || ! isset ( $pageSize ))
			$pagedClusters = $this->clutersInfo;
		else
			$pagedClusters = $utilityObj->getListPage ( $this->clutersInfo, $page, $pageSize );
		
		return $pagedClusters;
	}
	
	/**
	 * @return the $entityCount
	 */
	public function getEntitiesCount($class = NULL) {
		if (isset ( $class ) && isset ( $this->clusteredEntities [$class] ))
			return sizeof ( $this->clusteredEntities [$class] );
		
		return $this->entitiesCount;
	}
	
	/**
	 * @return the $entities
	 */
	public function getEntities($class) {
		$entities = array ();
		
		if (isset ( $class ) && ! isset ( $this->clusteredEntities [$class] ))
			$entities = $this->clusteredEntities [$class];
		else
			foreach ( $this->clusteredEntities as $clusteredEntities )
				$entities = array_merge ( $entities, $clusteredEntities );
		
		return $entities;
	}
	
	public function getEntitiesInPage($class, $page, $pageSize) {
		$pagedEntities = array ();
		
		if (isset ( $class ) && ! isset ( $this->clusteredEntities [$class] ))
			return $pagedEntities;
		
		$utilityObj = new GenericUtilityObject ();
		
		$entities = $this->clusteredEntities [$class];
		
		$entitiesCount = sizeof ( $entities );
		
		if (($entitiesCount == 0) || ! isset ( $pageSize ))
			$pagedEntities = $entities;
		else
			$pagedEntities = $utilityObj->getListPage ( $entities, $page, $pageSize );
			/*
		{
			if (! isset ( $pageSize ))
				$pageSize = $entitiesCount;
			
			$listInfo = $utilityObj->getListOffsetAndPgSize ( $page, $pageSize, $entitiesCount );
			
			$pagedEntities = array_slice ( $entities, $listInfo ['offset'], $listInfo ['pageSize'] );
		} 
			 * */
		
		return $pagedEntities;
	}
	
	public function getEntitiesLabels($entities = NULL) {
		if (isset ( $entities ))
			return array_intersect_key ( $this->entitiesLabels, array_flip ( $entities ) );
		else
			return $this->entitiesLabels;
	}
	
	public function getEntitiesTypes($entities = NULL) {
		if (isset ( $entities ))
			return array_intersect_key ( $this->entitiesType, array_flip ( $entities ) );
		else
			return $this->entitiesType;
	}
	
	public function getEntitiesWeights($entities = NULL) {
		if (isset ( $entities ))
			return array_intersect_key ( $this->entitiesWeights, array_flip ( $entities ) );
		else
			return $this->entitiesWeights;
	}
	
	public function merge($cluster) {
		if (! is_a ( $cluster, 'LightClusterObject' ))
			return false;
		
		$clusteredEntities = $cluster->getClusteredEntities ();
		
		foreach ( $clusteredEntities as $clusterKey => $entities )
			if (isset ( $this->clusteredEntities [$clusterKey] ))
				$this->clusteredEntities [$clusterKey] = array_unique ( array_merge ( $this->clusteredEntities [$clusterKey], $entities ) );
			else
				$this->clusteredEntities [$clusterKey] = $clusteredEntities [$clusterKey];
		
		$this->entitiesLabels += $cluster->getEntitiesLabels ();
		$this->entitiesType += $cluster->getEntitiesTypes ();
		$this->entitiesWeights += $cluster->getEntitiesWeights ();
		
		$this->entitiesCount += $cluster->getEntitiesCount ();
		
		$this->setClustersInfo ();
	}
	
	public function intercept($cluster) {
		if (! is_a ( $cluster, 'LightClusterObject' ))
			return false;
		
		$clusteredEntities = $cluster->getClusteredEntities ();
		foreach ( $clusteredEntities as $clusterKey => $entities )
			if (isset ( $this->clusteredEntities [$clusterKey] ))
				$this->clusteredEntities [$clusterKey] = array_unique ( array_merge ( $this->clusteredEntities [$clusterKey], $entities ) );
		
		$this->entitiesLabels += $cluster->getEntitiesLabels ();
		$this->entitiesType += $cluster->getEntitiesTypes ();
		$this->entitiesWeights += $cluster->getEntitiesWeights ();
		
		$this->entitiesCount += $cluster->getEntitiesCount ();
		
		$this->setClustersInfo ();
	}
	
	/**
	 * @param field_type $clusteredEntities
	 */
	public function setClusteredEntities($clusteredEntities) {
		$this->clusteredEntities = $clusteredEntities;
	}
	
	/**
	 * @param field_type $entitiesCount
	 */
	public function setEntitiesCount($entitiesCount) {
		$this->entitiesCount = $entitiesCount;
	}
	
	/**
	 * @param field_type $entitiesWeights
	 */
	public function setEntitiesWeights($entitiesWeights) {
		$this->entitiesWeights = $entitiesWeights;
	}
	
	/**
	 * @param field_type $entitiesLabels
	 */
	public function setEntitiesLabels($entitiesLabels) {
		$this->entitiesLabels = $entitiesLabels;
	}
	
	/**
	 * @param field_type $entitiesType
	 */
	public function setEntitiesType($entitiesType) {
		$this->entitiesType = $entitiesType;
	}
	
	public function sort($level, $asc = TRUE) {
		$utilityObj = new GenericUtilityObject ();
		
		$this->setClustersInfo ();
		
		$this->clutersInfo = $utilityObj->getSortedArray ( $this->clutersInfo, $asc );
		
		if ($level > 0)
			foreach ( $this->clusteredEntities as $class => $entities ) {
				$entitiesWeights = array_intersect_key ( $this->entitiesWeights, array_flip ( $entities ) );
				
				$this->clusteredEntities [$class] = array_keys ( $utilityObj->getSortedArray ( $entitiesWeights ) );
			}
		
		return TRUE;
	}
	
	function __destruct() {
		
		unset ( $this->clusteredEntities );
	}
}
?>