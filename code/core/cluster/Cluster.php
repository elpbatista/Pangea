<?php
include_once dirname ( __FILE__ ) . '/../PangeaObject.php';

class Cluster {
	private $label, $prefValue, $id;
	private $entitiesCount, $entities, $entitiesWeights, $entitiesLabels, $entitiesClasses;
	private $clustersCount, $clusters;
	private $currEntityPage, $currClusterPage, $currPageSize;
	
	private function getListOffsetAndPgSize($page, $pageSize, $total) {
		$return = array ('offset' => 0, 'pageSize' => 0, 'pages' => 0 );
		
		$pages = ceil ( $total / $pageSize );
		
		if (($page > $pages) || ($total == 0))
			return $return;
		
		$return ['pageSize'] = $pageSize;
		
		if (($page < 1) or ($pageSize < 1)) {
			$page = 1;
			$pageSize = 1;
			$return ['pageSize'] = $total;
		} else
			$return ['offset'] = ($page - 1) * $pageSize;
		
		if ($page > $pages)
			$return ['offset'] = ($pages - 1) * $pageSize;
		
		$return ['pages'] = $pages;
		
		return $return;
	}
	
	/**
	 * @param field_type $entityCount
	 */
	private function setEntityCount($entityCount) {
		$this->entitiesCount = $entityCount;
	}
	
	/**
	 * @param field_type $clustersCount
	 */
	private function setClustersCount($clustersCount) {
		$this->clustersCount = $clustersCount;
	}
	
	/**
	 * Enter description here ...
	 * @param entitiesWithWeights
	 */
	private function sortEntitiesBy($sortReferenceValues, $asc = TRUE) {
		$iterator = new ArrayIterator ( $sortReferenceValues );
		
		$iterator->natsort ();
		
		$this->entities = array_keys ( $iterator->getArrayCopy () );
		
		if (! $asc)
			$this->entities = array_reverse ( $this->entities );
	}
	
	public function __construct($label) {
		$this->label = $label;
		$this->entitiesCount = 0;
		$this->clustersCount = 0;
		$this->id = 0;
		$this->currEntityPage = PG_DEFAULT_INITIAL_PAGE_INDEX;
		$this->currClusterPage = PG_DEFAULT_INITIAL_PAGE_INDEX;
		$this->currPageSize = PG_DEFAULT_PAGE_SIZE;
		
		// TODO..		
		$this->entities = array ();
		$this->entitiesWeights = array ();
		$this->entitiesLabels = array ();
		$this->entitiesClasses = array ();
		
		$this->clusters = array ();
	}
	
	public function addClusterChild($cluster) {
		if ($cluster instanceof Cluster) {
			//$key = md5 ( $cluster->getLabel () );
			$key = $cluster->getLabel ();
			
			$this->clusters [$key] = $cluster;
			
			$clustersCount = count ( $this->clusters );
			
			$cluster->setId ( $clustersCount );
			
			$this->setClustersCount ( $clustersCount );
		}
	}
	
	public function addClusterChildByLabel($clusterLabel) {
		//$key = md5 ( $clusterLabel );
		$key = $clusterLabel;
		
		if (! isset ( $this->clusters [$key] )) {
			$cluster = new Cluster ( $clusterLabel );
			
			$this->clusters [$key] = $cluster;
			
			$clustersCount = count ( $this->clusters );
			
			$cluster->setId ( $clustersCount );
			
			$this->setClustersCount ( $clustersCount );
		} else
			$cluster = $this->clusters [$key] = $cluster;
		
		return $cluster;
	}
	
	/**
	 * @param field_type $entitiesLabels
	 */
	public function addEntitiesLabels($entitiesLabels) {
		$this->entitiesLabels = $this->entitiesLabels + $entitiesLabels;
		
		asort ( $this->entitiesLabels );
	}
	
	public function isALeaf() {
		return empty ( $this->clusters );
	}
	
	/**
	 * @return the $prefValue
	 */
	public function getPrefValue() {
		return $this->prefValue;
	}
	
	/**
	 * @return the $label
	 */
	/**
	 * @return the $id
	 */
	public function getId() {
		return $this->id;
	}
	
	public function getLabel() {
		return $this->label;
	}
	
	/**
	 * @return the $entityCount
	 */
	public function getEntitiesCount() {
		return $this->entitiesCount;
	}
	
	/**
	 * @return the $entities
	 */
	public function getEntities() {
		return $this->entities;
	}
	
	public function getEntitiesInPage() {
		
		if ($this->entitiesCount == 0)
			$pagedEntities = $this->entities;
		else {
			$pageSize = $this->currPageSize;
			
			if ($pageSize == 0)
				$pageSize = $this->entitiesCount;
			
			$listInfo = $this->getListOffsetAndPgSize ( $this->currEntityPage, $pageSize, $this->entitiesCount );
			
			$pagedEntities = array_slice ( $this->entities, $listInfo ['offset'], $listInfo ['pageSize'] );
		}
		
		return $pagedEntities;
	}
	
	/**
	 * @return the Entities classes
	 */
	
	public function getEntitiesClass($entities) {
		$labels = array ();
		
		if (isset ( $this->entitiesClasses ))
			$labels = array_intersect_key ( $this->entitiesClasses, array_flip ( $entities ) );
		
		return $labels;
	}
		
	/**
	 * @return the Entities labels
	 */
	
	public function getEntitiesLabel($entities) {
		$labels = array ();
		
		if (isset ( $this->entitiesLabels ))
			$labels = array_intersect_key ( $this->entitiesLabels, array_flip ( $entities ) );
		
		return $labels;
	}
	
	/**
	 * @return the Entities weights
	 */
	
	public function getEntitiesWeight($entities) {
		$weights = array ();
		
		if (isset ( $this->entitiesWeights ))
			$weights = array_intersect_key ( $this->entitiesWeights, array_flip ( $entities ) );
		
		return $weights;
	}
	
	/**
	 * @return the $clusters
	 */
	public function getClusters() {
		return $this->clusters;
	}
	
	/**
	 * @return a $cluster child
	 */
	public function getClusterChild($childPath) {
		$subCluster = NULL;
		
		if (! isset ( $childPath ) || empty ( $childPath ))
			return $subCluster;
		
		$label_parts = explode ( PG_DELIMETER_CHAR, $childPath, 2 );
		
		$clusterID = array_shift ( $label_parts );
		
		if (! empty ( $label_parts ))
			$childPath = $label_parts [0];
		else
			unset ( $childPath );
		
		$subCluster = $this->getClusterChildByKey ( $clusterID );
		
		if (isset ( $subCluster ) && isset ( $childPath ) && ! empty ( $childPath ))
			$subCluster = $subCluster->getClusterChild ( $childPath );
		
		return $subCluster;
	}
	
	public function getClusterChildByKey($key) {
		//$key = md5 ( $key );
		

		$clusterChild = null;
		
		if (isset ( $this->clusters [$key] ))
			$clusterChild = $this->clusters [$key];
		
		return $clusterChild;
	}
	
	/**
	 * @return a $cluster child
	 */
	public function getClusterChildByEntity($entity) {
		$subCluster = NULL;
		
		if (array_key_exists ( $entity, $this->clustersByEntity ))
			$subCluster = $this->clustersByEntity [$entity];
		
		return $subCluster;
	}
	
	/**
	 * Enter description here ...
	 */
	public function getClustersInPage() {
		
		if ($this->clustersCount == 0)
			$pagedClusters = $this->clusters;
		else {
			$pageSize = $this->currPageSize;
			
			if ($pageSize == 0)
				$pageSize = $this->clustersCount;
			
			$listInfo = $this->getListOffsetAndPgSize ( $this->currClusterPage, $pageSize, $this->clustersCount );
			
			$pagedClusters = array_slice ( $this->clusters, $listInfo ['offset'], $listInfo ['pageSize'] );
		}
		
		return $pagedClusters;
	}
	
	/**
	 * @return the $currPageSize
	 */
	public function getCurrPageSize() {
		return $this->currPageSize;
	}
	
	/**
	 * @return the $clustersCount
	 */
	public function getClustersCount() {
		return $this->clustersCount;
	}
	
	static function loadFromArray($stream) {
		$cluster = new Cluster ( 'ROOT' );
		
		if (! is_array ( $stream ))
			return $cluster;
		
		$cluster->setLabel ( $stream ['label'] );
		$cluster->setPrefValue ( $stream ['prefValue'] );
		$cluster->setId ( $stream ['id'] );
		
		$cluster->setEntities ( $stream ['entities'] );
		$cluster->setEntitiesWeights ( $stream ['entitiesWeights'] );
		$cluster->setEntitiesLabels ( $stream ['entitiesLabels'] );
		
		$cluster->setCurrEntityPage ( $stream ['currEntityPage'] );
		$cluster->setCurrClusterPage ( $stream ['currClusterPage'] );
		$cluster->setCurrPageSize ( $stream ['currPageSize'] );
		
		if (! empty ( $stream ['clusters'] ))
			foreach ( $stream ['clusters'] as $key => $childClusterStream )
				$cluster->addClusterChild ( Cluster::loadFromArray ( $childClusterStream ) );
		
		return $cluster;
	}

	private function getStream() {
		$stream = array ();
		
		$stream ['label'] = $this->label;
		$stream ['prefValue'] = $this->prefValue;
		$stream ['id'] = $this->id;
		$stream ['entitiesCount'] = $this->entitiesCount;
		$stream ['entities'] = $this->entities;
		$stream ['entitiesWeights'] = $this->entitiesWeights;
		$stream ['entitiesLabels'] = $this->entitiesLabels;
		$stream ['clustersCount'] = $this->clustersCount;
		$stream ['currEntityPage'] = $this->currEntityPage;
		$stream ['currClusterPage'] = $this->currClusterPage;
		$stream ['currPageSize'] = $this->currPageSize;
		
		if (! empty ( $this->clusters ))
			foreach ( $this->clusters as $key => $cluster )
				$stream ['clusters'] [$key] = $cluster->getStream ();
		
		return $stream;
	}
	
	private function serialize() {
		return serialize ( $this->getStream () );
	} 
	
	/**
	 * @param field_type $label
	 */
	public function setLabel($label) {
		$this->label = $label;
	}
	
	/**
	 * @param field_type $entities
	 */
	public function setEntities($entities) {
		$this->entities = $entities;
		
		$this->setEntityCount ( count ( $this->entities ) );
	}
	
	/**
	 * @param field_type $entities
	 */
	public function setEntitiesClasses($entitiesClasses, $asc = TRUE) {
		$entitiesClasses = array_intersect_key ( $entitiesClasses, array_flip ( $this->entities ) );
		
		if ($asc == TRUE)
			asort ( $entitiesClasses );
		else
			arsort ( $entitiesClasses );
		
		$this->entitiesClasses = $entitiesClasses;
	}
		
	/**
	 * @param field_type $entities
	 */
	public function setEntitiesLabels($entitiesLabels, $asc = TRUE) {
		$entitiesLabels = array_intersect_key ( $entitiesLabels, array_flip ( $this->entities ) );
		
		if ($asc == TRUE)
			asort ( $entitiesLabels );
		else
			arsort ( $entitiesLabels );
		
		$this->entitiesLabels = $entitiesLabels;
	}
	
	/**
	 * @param field_type $entities
	 */
	public function setEntitiesWeights($entitiesWeights, $asc = FALSE) {
		$entitiesWeights = array_intersect_key ( $entitiesWeights, array_flip ( $this->entities ) );
		
		if ($asc == TRUE)
			asort ( $entitiesWeights );
		else
			arsort ( $entitiesWeights );
		
		$this->entitiesWeights = $entitiesWeights;
	}
	
	/**
	 * @param field_type $clusters
	 */
	public function setClusters($clusters) {
		$this->clusters = $clusters;
		
		$this->setClustersCount ( count ( $this->clusters ) );
	}
	
	/**
	 * @param field_type $id
	 */
	public function setId($id) {
		$this->id = $id;
	}
	/**
	 * @param field_type $prefValue
	 */
	public function setPrefValue($prefValue) {
		$this->prefValue = $prefValue;
	}
	
	/**
	 * @param field_type $currEntityPage
	 */
	public function setCurrEntityPage($currEntityPage) {
		$entitiesPagesCount = 0;
		
		if ($this->currPageSize > 0)
			$entitiesPagesCount = ceil ( $this->entitiesCount / $this->currPageSize );
			
		if (($currEntityPage == 0) || ($currEntityPage > $entitiesPagesCount))
			$currEntityPage = $entitiesPagesCount;
		
		$this->currEntityPage = $currEntityPage;
	}
	
	/**
	 * @param field_type $currClusterPage
	 */
	public function setCurrClusterPage($currClusterPage) {
		$clustersPagesCount = ceil ( $this->clustersCount / $this->currPageSize );
		
		if (($currClusterPage == 0) || ($currClusterPage > $clustersPagesCount))
			$currClusterPage = $clustersPagesCount;
		
		$this->currClusterPage = $currClusterPage;
	}
	
	/**
	 * @param field_type $currPageSize
	 */
	public function setCurrPageSize($currPageSize) {
		if (($currPageSize == 0) || ($currPageSize > $this->entitiesCount))
			$currPageSize = $this->entitiesCount;
		
		$this->currPageSize = $currPageSize;
	}
	
	public function sortEntitiesByLabels($asc = TRUE) {
		$this->sortEntitiesBy ( $this->entitiesLabels, $asc );
	}
	
	public function sortEntitiesByWeights($asc = FALSE) {
		$this->sortEntitiesBy ( $this->entitiesWeights, $asc );
	}
	
	public function sortClustersByEntitiesCount($direction, $recursive) {
		if ($this->clustersCount > 1) {
			$countsArray = array ();
			
			foreach ( $this->clusters as $key => $cluster ) {
				if ($recursive)
					$cluster->sortClustersByEntitiesCount ( $direction, $recursive );
				
				$entitiesCount = $cluster->getEntitiesCount ();
				
				$countsArray [$key] = $entitiesCount;
			}
			
			$orderedClusters = array ();
			
			array_multisort ( $countsArray, (($direction == '/d') ? SORT_DESC : SORT_ASC) );
			
			$clustersKeys = array_keys ( $countsArray );
			foreach ( $clustersKeys as $key )
				$orderedClusters [$key] = $this->clusters [$key];
			
			$this->setClusters ( $orderedClusters );
		}
	}
	
	public function toArray() {
		$clusterArray = array ();
		
		$clusterKey = $this->prefValue;
		if (! isset ( $clusterKey )) {
			$clusterKey = $this->label;
			
			$clusterArray [$clusterKey] = array ('count' => $this->entitiesCount );
		} else
			$clusterArray [$clusterKey] = array ('value' => $this->label, 'count' => $this->entitiesCount );
		
		if (! $this->isALeaf ()) {
			$childsArray = array ();
			
			$pagedClusters = $this->getClustersInPage ();
			
			foreach ( $pagedClusters as $cluster ) {
				$childValue = $cluster->toArray ();
				
				$childsArray = array_merge ( $childsArray, $childValue );
			}
			
			$clusterArray [$clusterKey] ['description'] = $childsArray;
		}
		
		return $clusterArray;
	}
}
?>