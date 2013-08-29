<?php
/**
 * TODO
 * 
 * IMPORTANTE!: Documentar esta clase
 * */
include_once dirname ( __FILE__ ) . '/../dao/SearchDAO.php';
include_once dirname ( __FILE__ ) . '/../dao/EntityDAO.php';
include_once dirname ( __FILE__ ) . '/../../core/services/GenericService.php';
include_once dirname ( __FILE__ ) . '/../../core/cluster/Cluster.php';
include_once dirname ( __FILE__ ) . '/../../core/cluster/LightClusterObject.php';
include_once dirname ( __FILE__ ) . '/../../schema/services/SchemaService.php';

//include_once dirname ( __FILE__ ) . '/../../core/meta/ModelSimpleFactory.php';


class SearchService extends GenericService {
	private static $classKey;
	private $sdao, $schema;
	
	// Actualmente aun estoy valorando el uso de esta propiedad
	private $data = array ();
	private $trunks = array ();
	
	function SearchService() {
		parent::__construct ();
		
		try {
			$this->sdao = new SearchDAO ();
			
			date_default_timezone_set ( PG_DEFAULT_TIMEZONE );
			
			// Hay que revisar la consulta de esta funcion y filtrar el conjunto resultado... 
			$this->data ['relnames'] = $this->sdao->getAllRelname ();
			$this->data ['tableoids'] = array_flip ( $this->data ['relnames'] );
			
			// Schema Service
			$schemaSvr = new SchemaService ();
			
			$propertyDomainRange = $schemaSvr->getPropertyDomainRange ();
			
			// Naming properties of a builded Entity
			$this->data ['namingRelnames'] = $this->getNamingPropertiesRelname ();
			
			// Basic properties of a builded Entity
			$this->data ['baseRelnames'] = $this->getBasicPropertiesRelname ( PG_BASIC_PROPERTY_TABLENAME );
			$this->data ['baseRelnames'] [] = 'rdf:type';
			
			/** 
			 * TODO..
			 * 
			 * Cargar el esquema fuera del constructor de esta clase 
			 */
			foreach ( $propertyDomainRange as $property => $values ) {
				foreach ( $values ['domain'] as $class )
					$this->schema ['classes'] [$class] [] = $property;
				
				foreach ( $values ['label'] as $label ) {
					$label = str_replace ( ' ', '', $label );
					
					$this->data ['propertiesName'] [$label] = $property;
					$this->data ['propertiesLabel'] [$property] = $label;
				}
			}
			
			$this->schema ['subclassOf'] = $schemaSvr->getSubClass ();
			$this->schema ['subclasses'] = $this->getSubClasses ( $this->schema ['subclassOf'] );
			$this->schema ['properties'] = $propertyDomainRange;
			$this->schema ['subpropertyOf'] = $schemaSvr->getSubProperties ();
			
			// Recuperando las palabras reservadas de la Base de Datos.
			$this->data ['reserved'] = array_unique ( $this->getReservedWords () );
			
			$this->data ['classLabel'] = $schemaSvr->getClassLabels ();
			
			$this->data ['classUri'] = array_flip ( $this->data ['classLabel'] );
			
			// Test the Resultsets repository existens, will try to create it if no present
			if (! file_exists ( PG_RESULTSETS_REPO_PATH ))
				mkdir ( PG_RESULTSETS_REPO_PATH );
		
		} catch ( PangeaIOException $e ) {
			$this->logMessage ( $e->getMessage (), Zend_Log::ERR );
		}
	}
	
	private function buildEntities($ids, $size = PG_DB_LARGE_BUILD_LEVEL, $propertiesFilter = NULL, $language = PG_DEFAULT_LANGUAGE, $allLevels = FALSE) {
		
		$resultObj = array ();
		
		$triplesOfBuilded = array ();
		$triplesOfUnbuilded = array ();
		
		if (isset ( $ids )) {
			
			if (! is_array ( $ids ))
				$ids = array ($ids );
			
			$propertyRelnames = array ();
			
			$this->timer->reset ();
			$this->timer->start ();
			
			if (PG_USE_PERSISTENS_QUERIES) {
				$from = 'from cache';
				
				$triplesOfBuilded = $this->getTriplesOfBuildedEntity ( $ids, $size );
				
				$ids = array_diff ( $ids, array_keys ( $triplesOfBuilded ) );
				
				if (empty ( $ids ))
					$executionTime = $this->timer->_getelapsed ();
			}
			
			if (! empty ( $ids )) {
				$from = 'from database';
				
				$filterCloud = array ();
				
				$triplesOfUnbuilded = $this->buildEntitiesFromDB ( $ids, $size, $allLevels, $filterCloud );
				
				if (! empty ( $triplesOfUnbuilded )) {
					$triplesOfUnbuilded = $this->getOptimizedTriplesContiner ( $triplesOfUnbuilded );
					
					if (PG_SAVE_INFO_RETRIEVED) {
						$executionTime = $this->timer->_getelapsed ();
						
						$data = array ();
						
						foreach ( $triplesOfUnbuilded as $entity => $entityTriples ) {
							$data ['entityid'] = $entity;
							$data ['build Size'] = $size;
							$data ['triples'] = $entityTriples;
							
							$resultObj ['2beCached'] ['persistenTriplesInfo'] [] = $data;
						}
					}
				}
			}
			
			if (PG_SHOW_PROCEDURE_TIMES) {
				$this->logMessage ( 'buildEntity:: Triples of entity ' . $ids . ' retrieved (' . $from . ') return time = ' . $executionTime . ' seconds.', Zend_Log::INFO );
				$this->logMessage ( '------------------------------------------------------------------------------------------', Zend_Log::INFO );
			}
		}
		
		$resultObj ['response'] = $triplesOfBuilded + $triplesOfUnbuilded;
		
		return $resultObj;
	}
	
	/**
	 * TODO..
	 * REFACTORIZARRR!!!!!
	 */
	private function buildEntitiesFromDB($idEntities, $size, $allLevels = TRUE, &$filterCloud) {
		$triples = array ();
		
		if (! is_array ( $idEntities ))
			$idEntities = array ($idEntities );
		else
			$idEntities = array_unique ( $idEntities );
		
		if ($size == PG_DB_SHORT_BUILD_LEVEL) { // short size
			$names = $this->buildEntityNames ( $idEntities );
			
			foreach ( $names as $idEntity => $labelsTriples )
				$triples = array_merge ( $triples, $labelsTriples );
		
		} else {
			if ($size == PG_DB_LARGE_BUILD_LEVEL) // large size
				$entityTriples = $this->getTriplesByEntityIds ( $idEntities, $allLevels );
			else // ($size == PG_MEDIUM_BUILD_LEVEL) medium size
				$entityTriples = $this->getBasicsTriplesByEntityIds ( $idEntities );
			
			$triples = $this->fullFillTriples ( $entityTriples, true );
		}
		
		if (isset ( $triples ) && count ( $triples ) > 0) {
			$classes = $this->getClassFromEntities ( $idEntities );
			
			$predicate = 'rdf:type';
			
			foreach ( $idEntities as $entity ) {
				$object = array ('type' => "uri", 'value' => $classes [$entity] );
				
				$triples [] = $this->buildTriple ( $entity, $predicate, $object );
			}
		}
		
		return $triples;
	}
	
	private function buildEntityNames($entityIds) {
		$entityTriples = array ();
		
		$result = $this->sdao->getNamesByEntityId ( $entityIds );
		
		$total = pg_numrows ( $result );
		
		$defaultPropertyLabels = explode ( ',', PG_DEFAULT_LABEL_PROPERTY );
		
		for($i = 0; $i < $total; $i ++) {
			$row = pg_fetch_array ( $result );
			
			if (in_array ( $this->data ['relnames'] [$row ['tableoid_v']], $defaultPropertyLabels ))
				$entityTriples [$row ['subject']] [] = $this->buildTriple ( $row ['subject'], $this->data ['relnames'] [$row ['tableoid_v']], array ('type' => 'literal', 'value' => $row ['object'] ) );
		}
		
		return $entityTriples;
	}
	
	private function deletePersistenQuery($pQuery) {
		return $this->sdao->deletePersistenQuery ( $pQuery );
	}
	
	private function findPropertyNamesInDB($propertyNames, $language) {
		$triples = array ();
		
		if (! isset ( $language ) || ($language == ""))
			$language = 'sp';
		
		if (! isset ( $propertyNames ))
			return $triples;
		
		$pQuery = $this->sdao->getPersistenQuery ( md5 ( RDF_GENERATED_SCHEMA_QUERY ) );
		if ($pQuery !== false) {
			$results = $this->sdao->getEntityPropertiesLabels ( $pQuery, $propertyNames );
			if ($results)
				foreach ( $results as $triple )
					$triples [] = array ($triple ['subject'], $triple ['predicate'], $triple ['object'] );
		}
		
		return $triples;
	}
	
	private function getEntitiesDatatypePropertyData($subject, $object) {
		$data = array ();
		
		$results = $this->sdao->getEntitiesDatatypePropertyData ( $subject, $object );
		
		$rows = pg_fetch_all ( $results );
		
		if ($rows !== FALSE)
			$data = $rows;
		
		return $data;
	}
	
	private function fullFillTriples($triples) {
		$filledTriples = array ();
		
		if (! isset ( $triples ) || empty ( $triples ) || ! is_array ( $triples ))
			return $filledTriples;
		
		$idEntity = 0;
		
		$entities = array ();
		$literals = array ();
		
		$iCount = sizeof ( $triples );
		
		for($i = 0; $i < $iCount; $i ++) {
			$predicate = $this->data ['relnames'] [$triples [$i] ['tableoid']];
			/**
			 * TODO..
			 * Eliminar el uso de la funcion "is_aPropertyWithLiteralRangeSupport ( $predicate )"...
			 * */
			if (isset ( $triples [$i] ['datatype'] )) {
				
				$object = array ('type' => "literal", 'value' => $triples [$i] ['object'], 'datatype' => $triples [$i] ['datatype'], 'lang' => $triples [$i] ['lang'] );
				
				$filledTriples [] = $this->buildTriple ( $triples [$i] ['subject'], $predicate, $object );
			
			} elseif ($this->is_aPropertyWithLiteralRangeSupport ( $predicate )) {
				
				$literal = $this->getEntitiesDatatypePropertyData ( $triples [$i] ['subject'], $triples [$i] ['object'] );
				
				$object = array ('type' => "literal", 'value' => $triples [$i] ['object'], 'datatype' => $literal [0] ['datatype'], 'lang' => $literal [0] ['lang'] );
				
				$filledTriples [] = $this->buildTriple ( $triples [$i] ['subject'], $predicate, $object );
			
			} else
				$objects [$triples [$i] ['object']] [] = array ('entity' => $triples [$i] ['subject'], 'predicate' => $predicate );
		}
		
		if (! empty ( $objects )) {
			$result = $this->sdao->getNamesByEntityId ( array_keys ( $objects ) );
			
			$names = pg_fetch_all ( $result );
			
			if ($names !== FALSE) {
				$iCount = sizeof ( $names );
				
				for($i = 0; $i < $iCount; $i ++) {
					$info = $names [$i];
					
					if (isset ( $objects [$info ['subject']] )) {
						$subjCount = sizeof ( $objects [$info ['subject']] );
						
						for($j = 0; $j < $subjCount; $j ++) {
							$idEntity = $objects [$info ['subject']] [$j] ['entity'];
							$predicate = $objects [$info ['subject']] [$j] ['predicate'];
							
							$object = array ('type' => "uri", 'value' => $info ['subject'], 'typeof' => $info ['typeof'], 'label' => $info ['object'] );
							
							$filledTriples [] = $this->buildTriple ( $idEntity, $predicate, $object );
						}
						
						unset ( $objects [$info ['subject']] );
					}
				}
			}
			
			if (! empty ( $objects )) {
				$typesOf = $this->getClassFromEntities ( array_keys ( $objects ) );
				
				foreach ( $objects as $objectUri => $info ) {
					$subjCount = sizeof ( $info );
					
					for($j = 0; $j < $subjCount; $j ++) {
						$idEntity = $info [$j] ['entity'];
						$predicate = $info [$j] ['predicate'];
						
						$object = array ('type' => "uri", 'value' => $objectUri, 'typeof' => $typesOf [$objectUri] );
						
						$filledTriples [] = $this->buildTriple ( $idEntity, $predicate, $object );
					}
				}
			}
		}
		
		return $filledTriples;
	}
	
	private function getNamingPropertiesRelname() {
		$properties = array ();
		
		$results = $this->sdao->getPropertiesRelname4ShortBuild ();
		
		$relnames = pg_fetch_all ( $results );
		
		if ($relnames !== FALSE) {
			
			$total = sizeof ( $relnames );
			
			for($i = 0; $i < $total; $i ++)
				$properties [] = $relnames [$i] ['relname'];
		}
		
		$properties [] = 'rdf:type';
		
		return $properties;
	}
	
	private function getBasicPropertiesRelname($tableName) {
		$properties = array ();
		
		$results = $this->sdao->getBasicPropertiesRelname ( $tableName );
		
		$relnames = pg_fetch_all_columns ( $results, 0 );
		
		if ($relnames !== FALSE) {
			$total = sizeof ( $relnames );
			
			$properties = $relnames;
			
			for($i = 0; $i < $total; $i ++)
				$properties = array_merge ( $properties, $this->getBasicPropertiesRelname ( $relnames [$i] ) );
		}
		
		return $properties;
	}
	
	private function getBNodeEntityId($bnode) {
		$entity = NULL;
		
		$results = $this->sdao->getBNodeEntityID ( $bnode );
		
		$rows = pg_fetch_all ( $results );
		
		if ($rows !== FALSE)
			$entity = $rows [0] ['id'];
		
		return $entity;
	}
	
	private function getClassProperties($class, $onlyDirects, $size = NULL) {
		$properties = array ();
		
		if (! isset ( $this->schema ['classes'] [$class] ))
			return $properties;
		
		$classes = array ($class );
		
		do {
			if (isset ( $this->schema ['classes'] [$class] )) {
				$properties = array_merge ( $properties, $this->schema ['classes'] [$class] );
				
				if (! $onlyDirects && isset ( $this->schema ['subclassOf'] [$class] ))
					$classes = array_merge ( $classes, $this->schema ['subclassOf'] [$class] );
			}
			
			$class = array_shift ( $classes );
		
		} while ( ! empty ( $classes ) );
		
		$properties = array_unique ( $properties );
		
		return $properties;
	}
	
	private function getClassFromEntities($entities) {
		$classes = array ();
		
		if (! is_array ( $entities ))
			$entities = array ($entities );
		else
			$entities = array_unique ( $entities );
		
		if (sizeof ( $entities ) > 0) {
			//ponemos una tripleta con la clase a la que pertenencen las entidades
			$results = $this->sdao->getClassFromEntity ( $entities );
			
			if ($results) {
				$total = pg_num_rows ( $results );
				
				for($i = 0; $i < $total; $i ++) {
					$row = pg_fetch_array ( $results );
					
					$classes [$row ['id']] = $this->data ['relnames'] [$row ['tableoid']];
				}
			}
		}
		
		return $classes;
	}
	
	private function getCluster($entities, $oneClass) {
		try {
			$total = sizeof ( $entities );
			
			$weights = array ();
			$labels = array ();
			$types = array ();
			
			$entitiesByClass = array ();
			
			$class = 'ROOT';
			
			for($i = 0; $i < $total; $i ++) {
				$entity = $entities [$i] ['subject'];
				
				if (! $oneClass)
					$class = $entities [$i] ['typeof'];
				
				$entitiesByClass [$class] [] = $entity;
				
				$types [$entity] = $entities [$i] ['typeof'];
				
				if (isset ( $entities [$i] ['label'] ) && ! empty ( $entities [$i] ['label'] ))
					$labels [$entity] = $entities [$i] ['label'];
				
				if (isset ( $entities [$i] ['weight'] ))
					if (isset ( $weights [$entity] ))
						$weights [$entity] = $weights [$entity] + $entities [$i] ['weight'];
					else
						$weights [$entity] = $entities [$i] ['weight'];
				
				else if (isset ( $weights [$entity] ))
					$weights [$entity] = $weights [$entity] + 1;
				else
					$weights [$entity] = 1;
			}
			
			if ($oneClass)
				$total = sizeof ( array_unique ( $entitiesByClass [$class] ) );
			
			else {
				// Asign valid class names to the entitiesByClass keys
				$keys = array_keys ( $entitiesByClass );
				
				$iCount = sizeof ( $keys );
				
				$total = 0;
				
				for($i = 0; $i < $iCount; $i ++) {
					if (isset ( $this->data ['relnames'] [$keys [$i]] )) {
						$class = $this->data ['relnames'] [$keys [$i]];
						
						$entitiesByClass [$class] = $entitiesByClass [$keys [$i]];
						
						$total += sizeof ( $entitiesByClass [$class] );
					}
					
					unset ( $entitiesByClass [$keys [$i]] );
				}
			}
			
			$cluster = new LightClusterObject ( $entitiesByClass, $total, $weights, $labels, $types );
		
		} catch ( Exception $e ) {
			$this->logMessage ( $e->getMessage () );
		}
		
		return $cluster;
	}
	
	private function getClusterFromClasses($classes, $literalsEntities, $weights, $onlyRoot = FALSE) {
		$cluster = new Cluster ( 'ROOT' );
		
		$cluster->setCurrPageSize ( 0 );
		
		try {
			$clusterEntities = array ();
			
			if (isset ( $classes ) && is_array ( $classes ) && ! empty ( $classes )) {
				$clusterEntities ['ROOT'] = array_keys ( $classes );
				
				if (! $onlyRoot) {
					foreach ( $classes as $entity => $class ) {
						if (! isset ( $clusterEntities [$class] )) {
							$subCluster = new Cluster ( $class );
							
							$cluster->addClusterChild ( $subCluster );
						}
						
						$clusterEntities [$class] [] = $entity;
					}
					
					$subClusters = $cluster->getClusters ();
					foreach ( $subClusters as $label => $subCluster ) {
						$clusterEntities [$label] = array_unique ( $clusterEntities [$label] );
						
						$subCluster->setEntities ( $clusterEntities [$label] );
						$subCluster->setEntitiesWeights ( $weights );
						$subCluster->sortEntitiesByWeights ();
					}
				}
			
			} else
				$clusterEntities ['ROOT'] = $literalsEntities;
			
			if (isset ( $clusterEntities ['ROOT'] )) {
				$clusterEntities ['ROOT'] = array_unique ( $clusterEntities ['ROOT'] );
				
				$cluster->setEntities ( $clusterEntities ['ROOT'] );
				$cluster->setEntitiesClasses ( $classes );
				$cluster->setEntitiesWeights ( $weights );
				$cluster->sortEntitiesByWeights ();
			}
		} catch ( Exception $e ) {
			$this->logMessage ( $e->getMessage () );
		}
		
		return $cluster;
	}
	
	private function getClusterFromEncodedQuery($md5SearchString) {
		$successLoading = false;
		
		$cluster = null;
		
		// Recupero resultados y metaresultados del ResultSet cache.
		$now = date ( PG_DEFAULT_DATETIME_FORMAT );
		
		// Valid time for DB cached search information.
		$pQuery = $this->sdao->getPersistenQuery ( $md5SearchString );
		
		if ($pQuery !== false)
			$cluster = $this->getClusterFromPersistenQuery ( $pQuery );
		
		return $cluster;
	}
	
	private function getClusterFromPersistenQuery($pQuery) {
		$successLoading = false;
		
		$cluster = null;
		
		if ($pQuery !== false) {
			$successLoading = (isset ( $pQuery ['cluster'] ) && ! empty ( $pQuery ['cluster'] ) && ($pQuery ['status'] == PG_PERSISTEN_QUERY_STATUS_ACTIVE));
			
			if ($successLoading)
				$cluster = $this->getUnserializeDBObject ( $pQuery ['cluster'] );
		}
		
		return $cluster;
	}
	
	private function getFilteredEntities($filters) {
		
		$filteredEntities = array ();
		
		foreach ( $filters as $property => $values ) {
			$isLiteralValue = $this->is_aPropertyWithLiteralRangeSupport ( $property );
			
			if ($property == "none")
				if ($isLiteralValue)
					$property = PG_DATAPROPERTY_TABLENAME;
				else
					$property = PG_OBJECTPROPERTY_TABLENAME;
			
			$filteredEntities = array_merge ( $filteredEntities, $this->getEntitiesFromPropertyAndValues ( $property, $values, $isLiteralValue ) );
		}
		
		return $filteredEntities;
	}
	
	private function getEntitiesFromRange($searchTokens, $property, $ranges, $onTheStart) {
		$entities = array ();
		
		if ($this->is_aPropertyWithLiteralRangeSupport ( $property ))
			$entities = $this->getEntitiesByObjectsFromDatatypeProperty ( $property, NULL, $searchTokens ['texts'], NULL, NULL, NULL, $onTheStart );
		
		else
			$entities = $this->getEntitiesFromRangeViewByProperty ( $searchTokens, $property, $ranges, $onTheStart );
		
		return $entities;
	}
	
	//private function getEntitiesFromRangeViewByProperty($searchTokens, $property, $range, &$classes, &$labels, $onTheStart) {
	private function getEntitiesFromRangeViewByProperty($searchTokens, $property, $ranges, $onTheStart) {
		$entities = array ();
		
		$results = $this->sdao->getEntitiesFromRangeViewByProperty ( $searchTokens, $property, $ranges, $onTheStart );
		
		$rows = pg_fetch_all ( $results );
		
		if ($rows !== FALSE) {
			$iCount = sizeof ( $rows );
			
			for($i = 0; $i < $iCount; $i ++) {
				if (isset ( $entities [$rows [$i] ['subject']] ))
					$entities [$rows [$i] ['subject']] ['weight'] ++;
				else
					$entities [$rows [$i] ['subject']] = array ('subject' => $rows [$i] ['subject'], 'typeof' => $rows [$i] ['typeof'], 'label' => $rows [$i] ['label'], 'weight' => 1 );
				
				if (isset ( $entities [$rows [$i] ['object']] ))
					$entities [$rows [$i] ['object']] ['weight'] ++;
				else
					$entities [$rows [$i] ['object']] = array ('subject' => $rows [$i] ['object'], 'typeof' => $rows [$i] ['object_typeof'], 'label' => $rows [$i] ['object_label'], 'weight' => 1 );
			}
		
		//$entities = $rows;
		}
		
		return $entities;
	}
	
	private function getEntitiesFromRangesView($searchTokens, $ranges, &$classes, &$labels, $onTheStart, $sortedByWeights) {
		$entities = array ();
		
		$results = $this->sdao->getEntitiesFromRangesView ( $searchTokens, $ranges, $onTheStart, $sortedByWeights );
		
		$rows = pg_fetch_all ( $results );
		
		if ($rows !== FALSE)
			$entities = $rows;
		
		return $entities;
	}
	
	private function getEntitiesFromPropertyAndValues($property, $values, $areLiteralValues) {
		$entities = array ();
		
		$results = $this->sdao->getEntitiesFromPropertyAndValues ( $property, $values, $areLiteralValues );
		
		$rows = pg_fetch_all ( $results );
		
		if ($rows !== FALSE)
			$entities = $rows;
		
		return $entities;
	}
	/**
	 * Enter description here ...
	 * @param entList
	 * @param entResultList
	 * @param classes
	 */
	private function getEntitiesLabel($entList) {
		$labels = array ();
		
		$idsStr = implode ( ',', $entList );
		
		$entityNames = $this->buildEntityNames ( $entList );
		
		foreach ( $entityNames as $entity => $values )
			$labels [$entity] = $values [0] ['object'] ['value'];
		
		return $labels;
	}
	
	private function getEntitiesWeights($entities) {
		return array_count_values ( $entities );
	}
	
	private function getEntitiesByObjectsFromDatatypeProperty($objectPropertyTable, $datatypePropertyTable, $textsToSearch, $textsToExclude, $selectedTermsIds, $justSelectedTermsIds, $onTheStart) {
		$entities = array ();
		
		$results = $this->sdao->getEntitiesFromDatatypeProperty ( $objectPropertyTable, $datatypePropertyTable, $textsToSearch, $textsToExclude, $selectedTermsIds, $justSelectedTermsIds, $onTheStart );
		
		$rows = pg_fetch_all ( $results );
		
		if ($rows !== FALSE) {
			$iCount = sizeof ( $rows );
			
			for($i = 0; $i < $iCount; $i ++)
				$entities [$rows [$i] ['subject']] = $rows [$i];
		
		//$entities = $rows;
		}
		
		return $entities;
	}
	
	private function getEntitiesByObjectsFromObjectProperty($objects) {
		$entities = array ();
		
		$results = $this->sdao->getEntitiesObjectPropertiesFromObject ( $objects );
		
		$rows = pg_fetch_all ( $results );
		
		if ($rows !== FALSE)
			$entities = $rows;
		
		return $entities;
	}
	
	private function getEntityPropertyValues($tripleValueArray) {
		$propertyType = $tripleValueArray [0] ['type'];
		
		$valuesStream = array ();
		
		$tripleValuesCount = count ( $tripleValueArray );
		
		for($i = 0; $i < $tripleValuesCount; $i ++)
			$valuesStream [] = $tripleValueArray [$i] ['value'];
		
		return $valuesStream;
	}
	
	private function getFiltrerCloudFromEncodedQuery($md5SearchString) {
		$filterCloud = null;
		
		// Valid time for DB cached search information.
		$pQuery = $this->sdao->getPersistenQuery ( $md5SearchString );
		
		if ($pQuery !== false)
			$filterCloud = unserialize ( $pQuery ['filterCloud'] );
		
		return $filterCloud;
	}
	
	/**
	 * TODO..
	 * Esta funcion debe devolver un label dependiente de idioma actualmente se almacena genericamente
	 * */
	private function getLabel4Class($class, $language = NULL) {
		if (isset ( $this->data ['classLabel'] [$class] ))
			$label = $this->data ['classLabel'] [$class];
		else
			$label = '';
		
		return $label;
	}
	
	private function getManifestationsFromEntities($class, $entities) {
		$manifestationEntities = array ();
		
		switch ($class) {
			case "frbr:Work" :
				// Getting frbr:Expressions
				$expressions = $this->sdao->getObjectsFromPropertyTableBySubject ( $entities, "frbr:realization" );
				
				// Getting frbr:Manifestations
				$manifestationEntities = $this->sdao->getObjectsFromPropertyTableBySubject ( $expressions, "frbr:embodiment" );
				
				break;
			
			case "frbr:Expression" :
				// Getting frbr:Manifestations
				$manifestationEntities = $this->sdao->getObjectsFromPropertyTableBySubject ( $entities, "frbr:embodiment" );
				
				break;
			
			case "frbr:Manifestation" :
				// Do Nothing
				$manifestationEntities = $entities;
				
				break;
			//
			case "frbr:Item" :
				// Getting frbr:Manifestations
				$manifestationEntities = $this->sdao->getObjectsFromPropertyTableBySubject ( $entities, "frbr:exemplarOf" );
				
				break;
		}
		
		return $manifestationEntities;
	}
	
	/**
	 * Enter description here ...
	 * @param triples
	 */
	private function getOptimizedTriplesContiner($triples) {
		// Optimizando el retorno de los datos		
		$opTriples = array ();
		
		foreach ( $triples as $triple )
			if (($triple ['predicate'] !== PG_BUILDED_PROPERTY) && ($triple ['predicate'] !== PG_FILTERED_PROPERTY))
				$opTriples [$triple ['subject']] [$triple ['predicate']] [] = $triple ['object'];
		
		return $opTriples;
	}
	
	private function getPlainTextsFromEncodedQuery($md5SearchString) {
		$returnValue = null;
		
		// Recupero resultados y metaresultados del ResultSet cache.
		$pQuery = $this->sdao->getPersistenQuery ( $md5SearchString );
		
		if ($pQuery !== false)
			$returnValue = unserialize ( $pQuery ['plainTexts'] );
		
		return $returnValue;
	}
	
	private function getSearchTokensFromEncodedQuery($md5SearchString) {
		$returnValue = null;
		
		// Recupero resultados y metaresultados del ResultSet cache.
		$pQuery = $this->sdao->getPersistenQuery ( $md5SearchString );
		
		if ($pQuery !== false)
			$returnValue = unserialize ( $pQuery ['tokens'] );
		
		return $returnValue;
	}
	
	private function getReservedWords() {
		$entities = array ();
		
		/*		 */
		$ranges = $this->getSchemaPropertyRanges ( PG_ENTITY_FORM_PROPERTY_NAME );
		
		//$results = $this->sdao->getConceptsRangeTriples ( array (), $ranges [0], TRUE );
		$results = $this->sdao->getEntitiesFromRangesView ( array (), $ranges [0], TRUE, FALSE );
		
		$rows = pg_fetch_all_columns ( $results, 1 );
		
		if ($rows !== FALSE)
			$entities = $rows;
		
		return $entities;
	}
	
	private function getSchemaPropertyRanges($property) {
		$ranges = array ();
		
		if (! isset ( $property ) || empty ( $property ))
			return $ranges;
		
		if (array_key_exists ( $property, $this->schema ['properties'] ))
			$ranges = $this->schema ['properties'] [$property] ['range'];
		
		return $ranges;
	}
	
	private function getSchemaPropertyRangeDescendants($ranges) {
		$rangesList = $ranges;
		
		do {
			$rangesChilds = array ();
			
			$count = sizeof ( $rangesList );
			
			for($i = 0; $i < $count; $i ++) {
				$class = $rangesList [$i];
				
				if (isset ( $this->schema ['subclasses'] [$class] ))
					$rangesChilds = array_merge ( $rangesChilds, $this->schema ['subclasses'] [$class] );
			}
			
			if (! empty ( $rangesChilds )) {
				$rangesChilds = array_unique ( $rangesChilds );
				
				$ranges = array_merge ( $ranges, $rangesChilds );
				
				$rangesList = $rangesChilds;
			}
		} while ( ! empty ( $rangesChilds ) );
		
		return $ranges;
	}
	
	private function getSearchReservedTermDBTables($term) {
		$tables = array ();
		
		/**
		 * TODO..
		 * 
		 * Esta estructura se modificara en un futuro cercano. 
		 * No tengo ahora un disenno claro para ella en estos momentos.
		 * */
		if (isset ( $this->data ['seudoProperties'] [$term] )) {
			if (! is_array ( $this->data ['seudoProperties'] [$term] ))
				$term = $this->data ['seudoProperties'] [$term];
			
			$tables = $this->data ['seudoProperties'] [$term];
		
		} elseif (array_key_exists ( $term, $this->data ['propertiesName'] )) {
			$term = $this->data ['propertiesName'] [$term];
			
			$tables [$term] ['type'] = "property";
		
		}
		
		return $tables;
	}
	
	/**
	 * Retorna el listado de cadenas con las cuales se debe recuperar informacion y 
	 * el punto de inicio dentro del árbol de busqueda
	 * @param $searchString
	 * * @param $language
	 */
	
	/**
	 * TODO..
	 * Esto lleva refactorizacion inmediata.....
	 * 
	 * Existe un error al determinar las cadenas exactas que contengan caracteres en UTF8 
	 * */
	private function getSearchTokensAndStartPoints($searchString) {
		$searchTokens = array ();
		
		if (! isset ( $searchString ) || empty ( $searchString ))
			return $searchTokens;
		
		// Loading stop words...
		$this->loadStopWords ();
		
		$allTerms = array ();
		
		//	$arr_reg_exp = array ('reserved' => '', 'key_value' => '(\w[:?])+\s*' . PG_SEARCH_QUERY_EQUAL_CHAR . '\s*((\"[\w+\s*]+\")|\w+)', 'negative' => '-\s*\w+', 'exact' => '\"[\w+\s*]+\"', 'text' => '\w{2,}|(\+\s*\w+)' );		
		//((\"[\w*\s*]+\")|\w*))
		$arrOfRegExp ['key_value'] = '\S+\s*' . PG_SEARCH_QUERY_EQUAL_CHAR . '\s*(((\"[\w*\s*]+\")|\w*)|(\(((\"[\w*\s*]+\")|\w*)(\|((\"[\w*\s*]+\")|\w*))+\)))';
		
		$arrOfRegExp ['exact'] = '\".+\"'; // Antes: '\"[\w+\s*]+\"'
		$arrOfRegExp ['negative'] = '-\s*\w*';
		$arrOfRegExp ['classes'] = '';
		$arrOfRegExp ['reserved'] = '';
		$arrOfRegExp ['text'] = '\S{2,}|(\+\s*\S+)';
		
		/**
		 * TODO..
		 * Deben definirse si se van a utilizar reserved words.
		 */
		if (isset ( $this->data ['reserved'] ) && ! empty ( $this->data ['reserved'] )) {
			$arrOfRegExp ['reserved'] = '\[(';
			$arrOfRegExp ['reserved'] .= implode ( '|', $this->data ['reserved'] );
			$arrOfRegExp ['reserved'] .= ')\]';
		}
		
		if (isset ( $this->data ['classUri'] ) && ! empty ( $this->data ['classUri'] )) {
			//$arr_reg_exp ['classes'] = '\[class' . PG_SEARCH_QUERY_EQUAL_CHAR . '\s*(';
			$arrOfRegExp ['classes'] = '\[(';
			$arrOfRegExp ['classes'] .= implode ( '|', array_keys ( $this->data ['classUri'] ) );
			$arrOfRegExp ['classes'] .= ')\]';
		}
		
		$arrOfSearchTerms = explode ( PG_SEARCH_QUERY_AND_CHAR, $searchString );
		$arrOfResults = array ();
		
		$iCount = sizeof ( $arrOfSearchTerms );
		
		for($i = 0, $plusChr = ''; $i < $iCount; $i ++, $plusChr = PG_SEARCH_QUERY_AND_CHAR) {
			$searchTerm = $plusChr . trim ( $arrOfSearchTerms [$i] );
			
			foreach ( $arrOfRegExp as $key => $reg_exp ) {
				if (! empty ( $reg_exp )) {
					preg_match_all ( "/" . $reg_exp . "/msxiu", $searchTerm, $matches, PREG_OFFSET_CAPTURE );
					
					foreach ( $matches [0] as $found ) {
						$searchTerm = str_replace ( $found [0], '', $searchTerm );
						
						$arrOfResults [$key] [] = trim ( str_replace ( '"', '', $found [0] ) );
					}
					
					$searchTerm = trim ( $searchTerm );
				}
				
				if (empty ( $searchTerm ))
					break;
			}
		}
		
		if (isset ( $arrOfResults ['reserved'] ) && ! empty ( $arrOfResults ['reserved'] )) {
			$arrOfResults ['reserved'] = array_unique ( $arrOfResults ['reserved'] );
			
			$count = count ( $arrOfResults ['reserved'] );
			for($i = 0; $i < $count; $i ++) {
				$arrOfResults ['reserved'] [$i] = $this->getLowercaseStream ( $arrOfResults ['reserved'] [$i] );
				
				$arrOfResults ['reserved'] [$i] = str_replace ( '[', '', str_replace ( ']', '', $arrOfResults ['reserved'] [$i] ) );
			}
			
			/**
			 * TODO..
			 * Esto esta fijo aun pero la idea es que cada palabra reservada (Concepto) este asociado a un conjunto de propiedades
			 * */
			$searchTokens ['trunks'] [PG_ENTITY_FORM_PROPERTY_NAME] ['findOnStart'] = FALSE;
			$searchTokens ['trunks'] [PG_ENTITY_FORM_PROPERTY_NAME] ['type'] = "property";
			$searchTokens ['trunks'] [PG_ENTITY_FORM_PROPERTY_NAME] ['tokens'] = $arrOfResults ['reserved'];
		}
		
		if (isset ( $arrOfResults ['classes'] ) && ! empty ( $arrOfResults ['classes'] )) {
			$arrOfResults ['classes'] = array_unique ( $arrOfResults ['classes'] );
			
			$count = count ( $arrOfResults ['classes'] );
			for($i = 0; $i < $count; $i ++) {
				$arrOfResults ['classes'] [$i] = $this->getLowercaseStream ( $arrOfResults ['classes'] [$i] );
				
				$arrOfResults ['classes'] [$i] = str_replace ( '[', '', str_replace ( ']', '', $arrOfResults ['classes'] [$i] ) );
				
				$table = $this->data ['classUri'] [$arrOfResults ['classes'] [$i]];
				
				$searchTokens ['trunks'] [$table] ['findOnStart'] = FALSE;
				$searchTokens ['trunks'] [$table] ['type'] = "range";
			}
		}
		
		if (isset ( $arrOfResults ['key_value'] ) && ! empty ( $arrOfResults ['key_value'] )) {
			// Loading seudo properties...
			$this->loadSeudoProperties ();
			
			$trunks = array ();
			
			foreach ( $arrOfResults ['key_value'] as $key_value ) {
				// $found [0] ::= key:value
				$key_value = explode ( PG_SEARCH_QUERY_EQUAL_CHAR, $key_value );
				
				$term = strtolower ( trim ( $key_value [0] ) );
				
				$includePlus = (strpos ( $term, PG_SEARCH_QUERY_AND_CHAR ) === 0);
				
				if ($includePlus)
					$term = str_replace ( PG_SEARCH_QUERY_AND_CHAR, "", $term );
				
				if ($this->is_aValidSearchReservedTerm ( $term )) {
					$tables = $this->getSearchReservedTermDBTables ( $term );
					
					if (isset ( $tables )) {
						$values = trim ( $this->getLowercaseStream ( $key_value [1] ) );
						
						if (preg_match ( "/" . PG_SEARCH_QUERY_ALTERNATIVE_PATTERN_REG . "/i", $values ) == 1)
							$values = explode ( PG_SEARCH_QUERY_OR_CHAR, str_replace ( '(', '', str_replace ( ')', '', $values ) ) );
						else
							$values = array ($values );
						
						$searchString = $this->removeStopWords ( $values, PG_DEFAULT_LANGUAGE );
						
						if (! empty ( $searchString )) {
							$allTerms = array_merge ( $allTerms, $searchString );
							
							foreach ( $tables as $table => $attributes ) {
								/**
								 * TODO..
								 * Este criterio debe ser revizado.
								 * */
								if (! isset ( $searchTokens ['trunks'] [$table] ['intercept'] ))
									$searchTokens ['trunks'] [$table] ['intercept'] = $includePlus;
								else
									$searchTokens ['trunks'] [$table] ['intercept'] = $includePlus || $searchTokens ['trunks'] [$table] ['intercept'];
								
								if (isset ( $attributes ['findOnStart'] ))
									$searchTokens ['trunks'] [$table] ['findOnStart'] = $attributes ['findOnStart'];
								else
									$searchTokens ['trunks'] [$table] ['findOnStart'] = $this->is_aValidPropertyName ( $term );
								
								$searchTokens ['trunks'] [$table] ['type'] = $attributes ['type'];
								
								$glue = PG_VALUES_DELIMETER_CHAR;
								
								if ($includePlus)
									$glue .= PG_SEARCH_QUERY_AND_CHAR;
								
								if (! empty ( $searchTokens ['trunks'] [$table] ['tokens'] ))
									$searchTokens ['trunks'] [$table] ['tokens'] .= $glue;
								else
									$searchTokens ['trunks'] [$table] ['tokens'] = "";
								
								if (isset ( $attributes ["values"] ) && ! empty ( $attributes ["values"] ))
									$tokens = $glue . $attributes ["values"];
								else
									$tokens = implode ( $glue, $searchString );
								
								$searchTokens ['trunks'] [$table] ['tokens'] .= $tokens;
							}
						}
					}
				}
			}
		}
		
		if ((isset ( $arrOfResults ['exact'] ) && ! empty ( $arrOfResults ['exact'] )) || (isset ( $arrOfResults ['text'] ) && ! empty ( $arrOfResults ['text'] ))) {
			$searchTokens ['texts'] = array ();
			$searchTokens ['texts'] ['tokens'] = array ();
			
			if (! empty ( $arrOfResults ['exact'] )) {
				//$searchTokens ['exacts'] = $arr_results ['exact'];
				$searchTokens ['texts'] ['tokens'] = $arrOfResults ['exact'];
				
				$allTerms = array_merge ( $allTerms, $arrOfResults ['exact'] );
			}
			
			if (! empty ( $arrOfResults ['text'] )) {
				$texts = array ();
				
				$includePlus = FALSE;
				
				foreach ( $arrOfResults ['text'] as $term ) {
					/**
					 * TODO..
					 * ESTO DEBE DESAPARECES UNA VEZ RESUELTO EL PROBLEMA CON LA EXPRESION REGULAR DE LOS TERMINOS RESERVADOS
					 * ()
					 * */
					$term = str_replace ( '[]', '', $term );
					
					$includePlus = $includePlus || (strpos ( $term, PG_SEARCH_QUERY_AND_CHAR ) === 0);
					
					if (! empty ( $term ) && ! $this->is_aValidPropertyName ( $term )) {
						$term = $this->getLowercaseStream ( $term );
						
						$texts = array_merge ( $texts, $this->getStreamTokens ( $term, PG_DEFAULT_LANGUAGE ) );
						
						$searchTokens ['texts'] ['tokens'] = array_merge ( $searchTokens ['texts'] ['tokens'], array_unique ( $texts ) );
						
						$allTerms = array_merge ( $allTerms, $searchTokens ['texts'] ['tokens'] );
					} else {
						$tables = $this->getSearchReservedTermDBTables ( $term );
						
						if (isset ( $tables )) {
							$searchTokens ['trunks'] [$tables] ['findOnStart'] = TRUE;
							$searchTokens ['trunks'] [$tables] ['tokens'] = '';
						}
					}
				}
			}
			
			if (! empty ( $searchTokens ['texts'] ['tokens'] )) {
				$searchTokens ['texts'] ['tokens'] = array_unique ( $searchTokens ['texts'] ['tokens'] );
				
				$searchTokens ['texts'] ['intercept'] = $includePlus;
			} else
				unset ( $searchTokens ['texts'] );
		}
		
		if (isset ( $arrOfResults ['negative'] ) && ! empty ( $arrOfResults ['negative'] )) {
			foreach ( $arrOfResults ['negative'] as $negativeToken ) {
				$negativeToken = str_replace ( '-', '', $negativeToken );
				
				if (! empty ( $negativeToken ))
					$searchTokens ['negative'] [] = $this->getLowercaseStream ( $negativeToken );
			
			}
			
			if (isset ( $searchTokens ['negative'] ))
				$searchTokens ['negative'] = array_unique ( $searchTokens ['negative'] );
		}
		
		//TODO..
		//Get ambiguos terms	
		$ambiguosTerms = $this->sdao->identifyAmbiguousTerms ( $allTerms );
		
		if (isset ( $ambiguosTerms ) && ! empty ( $ambiguosTerms ))
			sort ( $searchTokens ['ambiguos'] = array_unique ( $ambiguosTerms ) );
		
		return $searchTokens;
	}
	
	/**
	 * Enter description here ...
	 * @param searchTokens
	 */
	private function getStreamTokens($term, $language) {
		$tokens = array ();
		
		$termParts = $this->removeStopWords ( explode ( ' ', $term ), $language );
		
		$partialSearchString = '';
		
		foreach ( $termParts as $token ) {
			if ($partialSearchString !== '')
				$partialSearchString = $partialSearchString . ' ';
			$partialSearchString .= $token;
			
			$tokens [] = trim ( $partialSearchString );
		}
		
		$tokens = array_unique ( $tokens );
		
		return $tokens;
	}
	
	private function getSubClasses($subClassesOf) {
		$subClasses = array ();
		
		if (! empty ( $subClassesOf ))
			foreach ( $subClassesOf as $subclass => $parentClasses ) {
				for($i = 0; $i < sizeof ( $parentClasses ); $i ++) {
					if (! isset ( $subClasses [$parentClasses [$i]] ))
						$subClasses [$parentClasses [$i]] = array ();
					
					$subClasses [$parentClasses [$i]] [] = $subclass;
				}
			}
		
		return $subClasses;
	}
	
	private function getTriplesByDirectEntityIds($entityForBuild, &$objects) {
		$triples = array ();
		
		$results = $this->sdao->getEntitiesDatatypeProperties ( $entityForBuild );
		
		$rows = pg_fetch_all ( $results );
		
		if ($rows !== FALSE)
			$triples = $rows;
		
		$results = $this->sdao->getEntitiesObjectProperties ( $entityForBuild );
		
		$rows = pg_fetch_all ( $results );
		
		if ($rows !== FALSE)
			$triples = array_merge ( $triples, $rows );
		
		return $triples;
	}
	
	private function getTriplesByEntityIdsRecursive($entitiesForBuild, &$arrayskip, $propertyFilter = NULL) {
		$triples = array ();
		
		$results = $this->sdao->getPropertyByEntityIds ( $entitiesForBuild );
		
		$rows = pg_fetch_all ( $results );
		
		if ($rows !== FALSE) {
			$total = sizeof ( $rows );
			
			$bibliographicObjects = array ();
			
			for($i = 0; $i < $total; $i ++) {
				$triples [] = array ($rows [$i] ['subject'], $rows [$i] ['tableoid'], $rows [$i] ['object'] );
				
				if ($this->is_aBibliographicObjectByTableOid ( $rows [$i] ['tableoid'] ))
					$bibliographicObjects [] = $rows [$i] ['object'];
			}
			
			$bibliographicObjects = array_diff ( $bibliographicObjects, $arrayskip );
			
			$arrayskip = array_merge ( $bibliographicObjects, $arrayskip );
			
			if (sizeof ( $bibliographicObjects ) > 0)
				$triples = array_merge ( $triples, $this->getTriplesByEntityIdsRecursive ( $bibliographicObjects, $arrayskip, $propertyFilter ) );
		}
		
		return $triples;
	}
	
	/*
	 * Verifica si una entidad ha sido previamente construida o no 
	 * Si se encuentra construida y no ha "caducado" devuelve las tripletas que la forman
	 * de lo contrario elimina todas las tripletas de BD.
	 * */
	private function getTriplesOfNamedEntity($entityId) {
		$triples = array ();
		
		if (isset ( $entityId )) {
			$plainQueryCode = str_replace ( '$', $entityId, RDF_GENERATED_ENTITYNAME_QUERY );
			
			$pQuery = $this->sdao->getPersistenQuery ( md5 ( $plainQueryCode ) );
			
			if ($pQuery) {
				$days = is_null ( $pQuery ['timetolive'] ) ? 0 : $this->comparingDates ( date ( PG_DEFAULT_DATETIME_FORMAT ), $pQuery ['timetolive'] );
				
				if (($days >= 0) && ($pQuery ['status'] == PG_PERSISTEN_QUERY_STATUS_ACTIVE)) {
					
					$results = $this->sdao->getPersistenQueryTriples ( $pQuery ['id'] );
					
					if ($results)
						foreach ( $results as $triple )
							$triples [] = unserialize ( $triple ['triple'] );
				} else
					$this->sdao->deletePersistenQueryTriples ( $pQuery ['id'] );
			}
		}
		
		return $triples;
	}
	
	private function getTriplesOfBuildedEntity($entityIds, $size) {
		$triples = array ();
		
		if (isset ( $entityIds )) {
			$results = $this->sdao->getPersistenQueryTriples ( $entityIds, $size );
			
			if ($results) {
				$propertyFilter = array ();
				
				if ($size == PG_DB_SHORT_BUILD_LEVEL)
					$propertyFilter = array_flip ( $this->data ['namingRelnames'] );
				
				elseif ($size == PG_DB_MEDIUM_BUILD_LEVEL)
					$propertyFilter = array_flip ( $this->data ['baseRelnames'] );
				
				$total = count ( $results );
				
				if (! empty ( $propertyFilter ))
					for($i = 0; $i < $total; $i ++) {
						$properties = unserialize ( $results [$i] ['triples'] );
						
						$triples [$results [$i] ['entityid']] = array_intersect_key ( $properties, $propertyFilter );
					}
				
				else
					for($i = 0; $i < $total; $i ++)
						$triples [$results [$i] ['entityid']] = unserialize ( $results [$i] ['triples'] );
			}
		}
		
		return $triples;
	}
	
	private function getBasicsTriplesByEntityIds($entityForBuild) { //siempre viene un solo id
		$triples = array ();
		
		$results = $this->sdao->getEntitiesBasicProperties ( $entityForBuild );
		
		$rows = pg_fetch_all ( $results );
		
		if ($rows !== FALSE)
			$triples = $rows;
		
		return $triples;
	}
	
	private function getTriplesByEntityIds($entityForBuild, $recursively = FALSE) { //siempre viene un solo id
		$triples = array ();
		
		//este metodo siempre recibe un arreglo
		$arrayskip = array ($entityForBuild );
		
		if ($recursively)
			$triples = $this->getTriplesByEntityIdsRecursive ( $entityForBuild, $arrayskip, NULL );
		else
			$triples = $this->getTriplesByDirectEntityIds ( $entityForBuild, $arrayskip );
		
		return $triples;
	}
	
	/**
	 * Enter description here ...
	 * @param relname
	 */
	private function is_aBibliographicClass($class) {
		return ($class == 'frbr:Work') || ($class == 'frbr:Expression') || ($class == 'frbr:Manifestation') || ($class == 'frbr:Item');
	}
	
	private function is_aBibliographicObject($relname) {
		return ($relname == 'frbr:exemplar') || ($relname == 'frbr:embodimentOf') || ($relname == 'frbr:realizationOf') || ($relname == 'frbr:exemplarOf');
	}
	
	private function is_aBibliographicObjectByTableOid($oid) {
		return ($oid == $this->data ['tableoid'] ['frbr:exemplar']) || ($oid == $this->data ['tableoid'] ['frbr:embodimentOf']) || ($oid == $this->data ['tableoid'] ['frbr:realizationOf']) || ($oid == $this->data ['tableoid'] ['frbr:exemplarOf']);
	}
	
	private function is_aBNode($entity) {
		$results = $this->sdao->is_aBNode ( $entity );
		
		$rows = pg_fetch_all ( $results );
		
		return ($rows [0] ['is_bnode'] == 't');
	}
	
	private function is_aLiteralDbValue($object) {
		return ((strpos ( $object, '{' ) === 0) && (strpos ( strrev ( $object ), '}' ) === 0));
	}
	
	private function is_aLiteralRange($range) {
		return (($range == 'skosxl:Label') || ($range == 'xsd:date') || ($range == 'xsd:integer') || ($range == 'xsd:string') || ($range == 'xsd:float'));
	}
	
	private function is_aPropertyWithLiteralRangeSupport($property) {
		$ranges = $this->getSchemaPropertyRanges ( $property );
		
		$supported = FALSE;
		
		/* 
		 * 
		 *foreach ( $ranges as $range ) 
		 *	$supported = ($supported || $this->is_aLiteralRange ( $range ));
		 *
		 * Solo uso el 1er rango
		 * */
		if (! empty ( $ranges ))
			$supported = $this->is_aLiteralRange ( $ranges [0] );
		
		return $supported;
	}
	
	private function is_aPropertyTableOidWithLiteralRangeSupport($tableOid) {
		if (! isset ( $this->data ['realnames'] [$tableOid] ))
			return FALSE;
		
		$property = $this->data ['realnames'] [$tableOid];
		
		return $this->is_aPropertyWithLiteralRangeSupport ( $property );
	}
	
	private function is_aStopWord($stream, $language) {
		if (isset ( $this->data ['stopWords'] ) && isset ( $this->data ['stopWords'] [$language] ))
			return in_array ( trim ( $stream ), $this->data ['stopWords'] [$language] );
		
		return false;
	}
	
	private function is_aSubClassOf($subClass, $class) {
		if ($subClass == $class)
			return true;
		
		$classes = array ();
		if (isset ( $this->schema ['subclassOf'] [$subClass] ))
			$classes = array_unique ( $this->schema ['subclassOf'] [$subClass] );
		
		while ( ! empty ( $classes ) ) {
			$tclass = array_shift ( $classes );
			
			if ($class == $tclass)
				return true;
			
			if (isset ( $this->schema ['subclassOf'] [$tclass] ))
				$classes = array_merge ( $classes, $this->schema ['subclassOf'] [$tclass] );
		}
		
		return false;
	}
	
	private function is_aSubPropertyOf($subProperty, $property) {
		$properties = array ();
		
		$propertyArr = explode ( '|', $property );
		
		if (isset ( $this->schema ['subpropertyOf'] [$subProperty] ))
			$parentProperties = array_unique ( $this->schema ['subpropertyOf'] [$subProperty] );
		
		while ( ! empty ( $parentProperties ) ) {
			$firstProperty = array_shift ( $parentProperties );
			
			if (in_array ( $firstProperty, $propertyArr ))
				return true;
			
			if (isset ( $this->schema ['subpropertyOf'] [$firstProperty] ))
				$parentProperties = array_merge ( $parentProperties, $this->schema ['subpropertyOf'] [$firstProperty] );
		}
		return false;
	}
	
	private function is_aValidPropertyName($term) {
		return (preg_match ( "/" . PG_PROPERTY_NAME_PATTERN_REG . "/i", $term ) == 1);
	}
	
	private function is_aValidSearchReservedTerm($term) {
		/*
		 * Es válida si el termino $term esta presente en el grupo de propiedades definidas en la BD o 
		 * se incluyo en el grupo de las "seudo" propiedades definidas en /resources/seudo_properties.json.
		 * */
		$is_valid = (array_key_exists ( $term, $this->data ['propertiesName'] ) || array_key_exists ( $term, $this->data ['seudoProperties'] ));
		
		return $is_valid;
	}
	
	private function loadSeudoProperties() {
		$seudoProperties = array ();
		
		if (empty ( $this->data ['seudoProperties'] )) {
			$resourcesDirPath = dirname ( __FILE__ ) . '/../../resources';
			
			if (file_exists ( $resourcesDirPath . '/seudo_properties.json' )) {
				// Loading external properties data..
				$seudoPropJSON = file_get_contents ( $resourcesDirPath . '/seudo_properties.json', FILE_TEXT | FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
				
				// Removing the "readable tabs"
				/*
			 * La listas de propiedades e informacion asociada se encuentran "amigablemente" formateadas 
			 * para que sea mucho mas facil su comprencion y modificacion.
			 * */
				$seudoPropJSON = preg_replace ( '/\t/i', '', $seudoPropJSON );
				
				$seudoPropObject = $this->objectToArray ( json_decode ( $seudoPropJSON ) );
				
				$seudoPropertiesMapping = array ();
				/**
				 * TODO...
				 * Me quede aqui!!!!! ;)
				 * */
				foreach ( $seudoPropObject as $term => $components ) {
					if (isset ( $components ['tables'] ))
						$seudoPropertiesMapping [$term] = $components ['tables'];
					
					if (isset ( $components ['aliases'] ) && ! empty ( $components ['aliases'] ))
						foreach ( $components ['aliases'] as $alias )
						/* 
						 *  Aqui se pudiera considerar la asignacion de la llave del termino base 
						 *  en lugar del contenido de dicho elemento
						 *  
						 *  $seudoPropertiesMapping [$alias] = $components ['propertyTables'];
						 */
						$seudoPropertiesMapping [$alias] = $term;
				}
				
				$seudoProperties = $seudoPropertiesMapping;
			}
			
			$this->data ['seudoProperties'] = $seudoProperties;
		}
	}
	
	private function loadStopWords() {
		if (empty ( $this->data ['stopWords'] )) {
			// Stopwords...
			$stopWords = array ();
			
			$resourcesDirPath = dirname ( __FILE__ ) . '/../../resources';
			
			$dirObject = dir ( $resourcesDirPath );
			
			while ( false !== ($entry = $dirObject->read ()) ) {
				$parts = explode ( '.', $entry );
				
				$fileName = $parts [0];
				$filePath = $resourcesDirPath . '/' . $entry;
				
				switch ($fileName) {
					case "stopwords" :
						$language = $parts [1];
						
						// Load language dependent stopwords...
						$stopWords [$language] = file ( $filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
						
						break;
				}
			}
			
			$dirObject->close ();
			
			$this->data ['stopWords'] = $stopWords;
		}
	}
	
	/**
	 * Enter description here ...
	 * @param triples
	 */
	private function removeDuplicatedTriples($triples) {
		// Removing duplicates...
		$uniqueTriples = array ();
		
		$keys = array_keys ( $triples [0] );
		
		$oKey = $keys [2];
		
		foreach ( $triples as $triple ) {
			$tripleCopy = $triple;
			
			if (is_array ( $tripleCopy [$oKey] ))
				$tripleCopy [$oKey] = implode ( '|', $tripleCopy [$oKey] );
			
			$key = strtolower ( str_replace ( ' ', '', implode ( '|', $tripleCopy ) ) );
			
			$uniqueTriples [$key] = $triple;
		}
		
		$uniqueTriples = array_values ( $uniqueTriples );
		
		return $uniqueTriples;
	}
	
	private function removeStopWords($termsArray, $language) {
		$nonStopWords = array ();
		
		if (! is_array ( $termsArray ) || empty ( $termsArray ))
			return $nonStopWords;
		
		foreach ( $termsArray as $stream )
			if (! $this->is_aStopWord ( $stream, $language ))
				$nonStopWords [] = $stream;
		
		return $nonStopWords;
	}
	
	/**
	 * Enter description here ...
	 * @param md5SearchString
	 * @param ttl
	 * @param pQueryID
	 */
	//$this->savePersistenQuery ( $plainText, $md5SearchString, NULL, $cluster, PG_PERSISTEN_QUERY_STATUS_ACTIVE, $filterCloud );
	//private function savePersistenQuery($plainQueries, $md5SearchString, $tokens, $cluster, $status, $filterCloud, $executionTime) {
	private function savePersistenQuery($data) {
		if (PG_SAVE_INFO_RETRIEVED) {
			if (isset ( $data ['plainTexts'] ))
				$data ['plainTexts'] = serialize ( $data ['plainTexts'] );
			
			if (isset ( $data ['cluster'] ))
				$data ['cluster'] = $this->getSerializeDBObject ( $data ['cluster'] );
			
			if (isset ( $data ['tokens'] ))
				$data ['tokens'] = utf8_encode ( serialize ( $data ['tokens'] ) );
			
			if (isset ( $data ['filterCloud'] ))
				$data ['filterCloud'] = serialize ( $data ['filterCloud'] );
			
			$pQueryID = $this->sdao->insertPersistenQuery ( $data );
			
			if ($pQueryID == 0)
				throw new PangeaDataAccessException ( 'Can not save the current Query (' . $data ['text'] . ') in DB' );
		}
		
		return $pQueryID;
	}
	
	private function savePersistenTriples($data) {
		if (PG_SAVE_INFO_RETRIEVED) {
			if (isset ( $data ['triples'] ))
				$data ['triples'] = serialize ( $data ['triples'] );
			
			$this->sdao->deletePersistenQueryTriples ( $data ['entityid'] );
			
			$result = $this->sdao->insertPersistenTriples ( $data );
			
			if (! $result)
				throw new PangeaDataAccessException ( 'Can not save associated triples of entity ' . $data ['entity'] . ' in DB' );
		}
	}
	
	private function saveTriples($pQuery, $triples, $deleteBefore, $checkExistence) {
		try {
			if (isset ( $pQuery )) {
				$this->timer->start ();
				
				$this->sdao->insertPersistenQueryTriples ( $pQuery, $triples, $deleteBefore, $checkExistence );
				
				$this->timer->stop ();
				
				if (PG_SHOW_PROCEDURE_TIMES)
					$this->logMessage ( 'Triples DB insertion time = ' . $this->timer->_getelapsed () . ' seconds.', Zend_Log::INFO );
			}
		} catch ( PangeaIOException $e ) {
			$this->logMessage ( $e->getMessage (), Zend_Log::ALERT );
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Enter description here ...
	 * @param plainQuery
	 * @param encodedQuery
	 * @param selectedTermsIds
	 * @param justSelectedTermsIds
	 * @param inferenceLevel, 
	 * @param page
	 * @param pageSize
	 */
	private function searchCore($plainQuery, $encodedQuery = NULL, $selectedTermsIds = NULL, $justSelectedTermsIds = FALSE, $inferenceLevel = PG_DEFAULT_INFERENCE_LEVEL, $page = PG_DEFAULT_INITIAL_PAGE_INDEX, $pageSize = PG_DEFAULT_PAGE_SIZE, $language = PG_DEFAULT_LANGUAGE) {
		$responseObj = array ();
		
		/** TODO.. 
		 * Los tokens de la Cadena de Busqueda (CdB) son lo que identifican a un Conjunto Resultado (CR)
		 * + alla de la CdB en si misma, debido a que la premutacion de los tokens no altera el conjunto
		 * de tonkens a tratar.
		 * */
		
		$this->timer->reset ();
		$this->timer->start ();
		
		$searchTokens = array ();
		
		if (isset ( $plainQuery ) && ! empty ( $plainQuery ))
			$searchTokens = $this->getSearchTokensAndStartPoints ( $plainQuery );
		
		if (isset ( $selectedTermsIds ) && ! empty ( $selectedTermsIds )) {
			$searchTokens ['selectedTermsIds'] = $selectedTermsIds;
			$searchTokens ['justSelectedTerms'] = $justSelectedTermsIds;
			
			sort ( $searchTokens ['selectedTermsIds'] );
		}
		
		if (! isset ( $encodedQuery ))
			$encodedQuery = md5 ( serialize ( $searchTokens ) );
		
		if (PG_USE_PERSISTENS_QUERIES) {
			$pQuery = $this->sdao->getPersistenQuery ( $encodedQuery );
			
			if ($pQuery !== FALSE) {
				$cluster = $this->getClusterFromPersistenQuery ( $pQuery );
				
				if (! isset ( $cluster ) || ($cluster->getEntitiesCount () == 0)) {
					$cluster = null;
				}
			}
		}
		
		if (isset ( $cluster )) {
			$from = 'from cache';
			
			$executionTime = $this->timer->_getelapsed ();
		
		} elseif (! empty ( $searchTokens )) {
			$from = 'from database';
			
			$textsToExclude = array ();
			
			if (isset ( $searchTokens ['negative'] ))
				$textsToExclude = $searchTokens ['negative'];
				
			/*
			 * Entities IDs
			 * */
			$entities = array ();
			/*
			 * Entities Classes
			 * */
			$classes = array ();
			
			/*
			 * Entities Labels: this info is retrieved from the labels getter's functions
			 * */
			$labels = array ();
			
			/*
			 * Entities Weights: this info is retrieved from the triples getter's functions
			 * */
			$weights = array ();
			
			/*
			 * Esta seccion sera modificada. Cada uno de los grupos de tokens producira un conjunto
			 * de tripletas que se uniran para formar el cluster.
			 * 
			 * $searchTokens ('texts'=>(...), 'ambiguos'=>(...), 'trunks'=>(...))
			 * */
			
			$idsEntity = array ();
			
			$literalIds = array ();
			
			/*
			 *  TRUE = Search the searchString in the start position of the field value.
			 *
			 *  FALSE = Search the stringSearch in any position of the field value. 
			 * */
			
			$onTheStart = FALSE;
			
			// Terminos "MARCADOs"
			

			$interceptEntities = FALSE;
			
			$trunkEntities = array ();
			
			if (isset ( $searchTokens ['trunks'] ) && ! empty ( $searchTokens ['trunks'] )) {
				$arrKeys = array_keys ( $searchTokens ['trunks'] );
				
				$indexCount = sizeof ( $arrKeys );
				
				for($i = 0; $i < $indexCount; $i ++) {
					$trunk = $arrKeys [$i];
					$info = $searchTokens ['trunks'] [$trunk];
					
					$isPropertyTable = ($info ['type'] == 'property');
					
					if ($info ['type'] == 'range') {
						$ranges = array ($trunk );
						
						$trunk = NULL;
					} else
						$ranges = $this->getSchemaPropertyRanges ( $trunk );
					
					$onTheStart = $info ['findOnStart'];
					/**
					 * TODO..
					 * 
					 * Aqui solo se esta implementando la "union" de conjuntos resultados (array_merge) se debe buscar solucion a la "intercepcion"
					 * de conjuntos (array_intersect) usando la estructura del conjunto que agrupa a las entidades recuperadas
					 * */
					$ranges = $this->getSchemaPropertyRangeDescendants ( $ranges );
					
					$partialTrunkEntities = $this->getEntitiesFromRange ( $info ['tokens'], $trunk, $ranges, $onTheStart );
					
					$interceptEntities = $interceptEntities || $searchTokens ['trunks'] [$arrKeys [$i]] ['intercept'];
					
					if (! empty ( $trunkEntities ))
						$trunkEntities = $this->getMergedArraysOfEntities ( $trunkEntities, $partialTrunkEntities, $searchTokens ['trunks'] [$arrKeys [$i]] ['intercept'] );
					else
						$trunkEntities = $partialTrunkEntities;
				}
				
				/**
				 * TODO..
				 * 
				 * Revizar esto...
				 * 
				 * */
				if (! empty ( $trunkEntities )) {
					//Aqui debo obtener las manifestaciones a partir de las entidades bibliograficas que no pertenezcan a dicha clase
					

					/*
					 * 
					 * $trunkEntities [i] ::= subject, subject-tableoid, subject-label, object, object-tableoid, object-label
					 * 
					 * */
					$entitiesByClass = array ();
					
					$subjects = array_keys ( $trunkEntities );
					
					foreach ( $trunkEntities as $info )
						$entitiesByClass [$info ['typeof']] [] = $info ['subject'];
					
					$manifestationsEntities = array ();
					
					foreach ( $entitiesByClass as $tableoid => $classEntities ) {
						$class = $this->data ['relnames'] [$tableoid];
						
						if ($this->is_aBibliographicClass ( $class ))
							$manifestationsEntities = array_merge ( $manifestationsEntities, $this->getManifestationsFromEntities ( $class, $classEntities ) );
					}
					
					$manifestationsEntities = array_diff ( $manifestationsEntities, $subjects );
					
					/*
					 * TODO..
					 * Refactorizar: pudieramos obtener esto ya en este formato????
					 * */
					if (! empty ( $manifestationsEntities )) {
						$iCount = sizeof ( $manifestationsEntities );
						
						for($i = 0; $i < $iCount; $i ++) {
							$triple = array ();
							
							$triple ['subject'] = $manifestationsEntities [$i];
							$triple ['typeof'] = $this->data ['tableoids'] ['frbr:Manifestation'];
							$triple ['weight'] = 1;
							
							$trunkEntities [$manifestationsEntities [$i]] = $triple;
						}
					}
				}
				
				$entities = $trunkEntities;
			}
			
			$texts = array ();
			
			if (isset ( $searchTokens ['texts'] )) {
				$texts = $searchTokens ['texts'] ['tokens'];
				
				$interceptEntities = $interceptEntities || $searchTokens ['texts'] ['intercept'];
			}
			
			if (! empty ( $texts ) || ! empty ( $selectedTermsIds )) {
				$entitiesFromPlainText = $this->getEntitiesByObjectsFromDatatypeProperty ( NULL, NULL, $texts, $textsToExclude, $selectedTermsIds, $justSelectedTermsIds, $onTheStart );
				
				$entities = $this->getMergedArraysOfEntities ( $entities, $entitiesFromPlainText, $interceptEntities );
			}
			
			if (! empty ( $entities ))
				//Return a cluster with the entities of the ROOT cluster and every subcluster sorted by weight
				$cluster = $this->getCluster ( array_values ( $entities ), FALSE );
			
			$executionTime = $this->timer->_getelapsed ();
			
			// Saving all the queries even if not produce any result...
			$data = array ();
			
			$ttl = $this->calcDate ( PG_PERSISTENS_TTL_INTERVAL );
			
			$data ['text'] = $encodedQuery;
			$data ['plainTexts'] = array ($plainQuery );
			$data ['timetolive'] = $ttl;
			$data ['cluster'] = $cluster;
			$data ['status'] = PG_PERSISTEN_QUERY_STATUS_ACTIVE;
			$data ['tokens'] = $searchTokens;
			$data ['executionTime'] = $executionTime;
			
			$responseObj ['2beCached'] ['persistenQueriesInfo'] [] = $data;
		}
		
		if (PG_SHOW_PROCEDURE_TIMES) {
			$this->logMessage ( 'search:: Triples retrieved (' . $from . ') return time = ' . $executionTime . ' seconds (search term: ' . $plainQuery . ')', Zend_Log::INFO );
			$this->logMessage ( '------------------------------------------------------------------------------------------', Zend_Log::INFO );
		}
		
		$responseObj ['encodedQuery'] = $encodedQuery;
		$responseObj ['cluster'] = $cluster;
		
		if (isset ( $searchTokens ) && isset ( $searchTokens ['ambiguos'] ))
			$responseObj ['ambiguos'] = $searchTokens ['ambiguos'];
		
		return $responseObj;
	}
	
	private function getMergedArraysOfEntities($entitiesList1, $entitiesList2, $interceptEntities) {
		$keysFrom = array_keys ( $entitiesList1 );
		$keysTo = array_keys ( $entitiesList2 );
		
		$resultingKeys = array ();
		
		if ($interceptEntities)
			$resultingKeys = array_intersect ( $keysFrom, $keysTo );
		else
			$resultingKeys = array_merge ( $keysFrom, $keysTo );
		
		$resultingEntities = array ();
		
		foreach ( $resultingKeys as $key ) {
			$resultingWeight = 0;
			
			if (isset ( $entitiesList1 [$key] )) {
				$resultingEntities [$key] = $entitiesList1 [$key];
				
				$resultingWeight += $entitiesList1 [$key] ['weight'];
			}
			
			if (isset ( $entitiesList2 [$key] )) {
				$resultingEntities [$key] = $entitiesList2 [$key];
				
				$resultingWeight += $entitiesList2 [$key] ['weight'];
			}
			
			$resultingEntities [$key] ['weight'] = $resultingWeight;
		}
		
		return $resultingEntities;
	}
	
	private function searchCore_before($plainQuery, $encodedQuery = NULL, $selectedTermsIds = NULL, $justSelectedTermsIds = FALSE, $inferenceLevel = PG_DEFAULT_INFERENCE_LEVEL, $page = PG_DEFAULT_INITIAL_PAGE_INDEX, $pageSize = PG_DEFAULT_PAGE_SIZE, $language = PG_DEFAULT_LANGUAGE) {
		$responseObj = array ();
		
		/** TODO.. 
		 * Los tokens de la Cadena de Busqueda (CdB) son lo que identifican a un Conjunto Resultado (CR)
		 * + alla de la CdB en si misma, debido a que la premutacion de los tokens no altera el conjunto
		 * de tonkens a tratar.
		 * */
		
		$this->timer->reset ();
		$this->timer->start ();
		
		$searchTokens = array ();
		
		if (isset ( $plainQuery ) && ! empty ( $plainQuery ))
			$searchTokens = $this->getSearchTokensAndStartPoints ( $plainQuery );
		
		if (isset ( $selectedTermsIds ) && ! empty ( $selectedTermsIds )) {
			$searchTokens ['selectedTermsIds'] = $selectedTermsIds;
			$searchTokens ['justSelectedTerms'] = $justSelectedTermsIds;
			
			sort ( $searchTokens ['selectedTermsIds'] );
		}
		
		if (! isset ( $encodedQuery ))
			$encodedQuery = md5 ( serialize ( $searchTokens ) );
		
		if (PG_USE_PERSISTENS_QUERIES) {
			$pQuery = $this->sdao->getPersistenQuery ( $encodedQuery );
			
			if ($pQuery !== FALSE) {
				$cluster = $this->getClusterFromPersistenQuery ( $pQuery );
				
				if (! isset ( $cluster ) || ($cluster->getEntitiesCount () == 0)) {
					$cluster = null;
				}
			}
		}
		
		if (isset ( $cluster )) {
			$from = 'from cache';
			
			$executionTime = $this->timer->_getelapsed ();
		
		} elseif (! empty ( $searchTokens )) {
			$from = 'from database';
			
			$textsToExclude = array ();
			
			if (isset ( $searchTokens ['negative'] ))
				$textsToExclude = $searchTokens ['negative'];
				
			/*
			 * Entities IDs
			 * */
			$entities = array ();
			/*
			 * Entities Classes
			 * */
			$classes = array ();
			
			/*
			 * Entities Labels: this info is retrieved from the labels getter's functions
			 * */
			$labels = array ();
			
			/*
			 * Entities Weights: this info is retrieved from the triples getter's functions
			 * */
			$weights = array ();
			
			/*
			 * Esta seccion sera modificada. Cada uno de los grupos de tokens producira un conjunto
			 * de tripletas que se uniran para formar el cluster.
			 * 
			 * $searchTokens ('texts'=>(...), 'ambiguos'=>(...), 'trunks'=>(...))
			 * */
			
			$idsEntity = array ();
			
			$literalIds = array ();
			
			/*
			 *  TRUE = Search the searchString in the start position of the field value.
			 *
			 *  FALSE = Search the stringSearch in any position of the field value. 
			 * */
			
			$onTheStart = FALSE;
			
			// Terminos "MARCADOs"
			

			$trunkEntities = array ();
			
			if (isset ( $searchTokens ['trunks'] ) && ! empty ( $searchTokens ['trunks'] )) {
				$arrKeys = array_keys ( $searchTokens ['trunks'] );
				
				$indexCount = sizeof ( $arrKeys );
				
				for($i = 0; $i < $indexCount; $i ++) {
					$trunk = $arrKeys [$i];
					$info = $searchTokens ['trunks'] [$trunk];
					
					$isPropertyTable = ($info ['type'] == 'property');
					
					if ($info ['type'] == 'range') {
						$ranges = array ($trunk );
						
						$trunk = NULL;
					} else
						$ranges = $this->getSchemaPropertyRanges ( $trunk );
					
					$onTheStart = $info ['findOnStart'];
					/**
					 * TODO..
					 * 
					 * Aqui solo se esta implementando la "union" de conjuntos resultados (array_merge) se debe buscar solucion a la "intercepcion"
					 * de conjuntos (array_intersect) usando la estructura del conjunto que agrupa a las entidades recuperadas
					 * */
					$ranges = $this->getSchemaPropertyRangeDescendants ( $ranges );
					
					$trunkEntities = array_merge ( $trunkEntities, $this->getEntitiesFromRange ( $info ['tokens'], $trunk, $ranges, $onTheStart ) );
				}
				
				/**
				 * TODO..
				 * 
				 * Revizar esto...
				 * 
				 * */
				if (! empty ( $trunkEntities )) {
					//Aqui debo obtener las manifestaciones a partir de las entidades bibliograficas que no pertenezcan a dicha clase
					

					/*
					 * 
					 * $trunkEntities [i] ::= subject, subject-tableoid, subject-label, object, object-tableoid, object-label
					 * 
					 * */
					$entitiesByClass = array ();
					
					$subjects = array ();
					
					$iCount = sizeof ( $trunkEntities );
					
					for($i = 0; $i < $iCount; $i ++) {
						$entitiesByClass [$trunkEntities [$i] ['typeof']] [] = $trunkEntities [$i] ['subject'];
						
						$subjects [] = $trunkEntities [$i] ['subject'];
						
						$triple = array ();
						
						$triple ['subject'] = $trunkEntities [$i] ['subject'];
						$triple ['typeof'] = $trunkEntities [$i] ['typeof'];
						
						if (isset ( $trunkEntities [$i] ['label'] ))
							$triple ['label'] = $trunkEntities [$i] ['label'];
						
						$triple ['weight'] = 1;
						
						$entities [] = $triple;
						
						if (isset ( $trunkEntities [$i] ['object'] )) {
							$triple ['subject'] = $trunkEntities [$i] ['object'];
							$triple ['typeof'] = $trunkEntities [$i] ['object_typeof'];
							$triple ['label'] = $trunkEntities [$i] ['object_label'];
							$triple ['weight'] = 1;
							
							$entities [] = $triple;
						}
					}
					
					$biblioEntities = array ();
					
					foreach ( $entitiesByClass as $tableoid => $classEntities ) {
						$class = $this->data ['relnames'] [$tableoid];
						
						if ($this->is_aBibliographicClass ( $class ))
							$biblioEntities = array_merge ( $biblioEntities, $this->getManifestationsFromEntities ( $class, $classEntities ) );
					}
					
					$biblioEntities = array_diff ( $biblioEntities, $subjects );
					
					/*
					 * TODO..
					 * Refectorizar: pudieramos obtener esto ya en este formato????
					 * */
					if (! empty ( $biblioEntities )) {
						$iCount = sizeof ( $biblioEntities );
						
						$classes = $this->getClassFromEntities ( $biblioEntities );
						
						for($i = 0; $i < $iCount; $i ++) {
							$triple = array ();
							
							$triple ['subject'] = $biblioEntities [$i];
							$triple ['typeof'] = $classes [$biblioEntities [$i]];
							$triple ['weight'] = 1;
							
							$entities [] = $triple;
						}
					}
				}
			}
			
			$texts = array ();
			
			if (isset ( $searchTokens ['texts'] ))
				$texts = $searchTokens ['texts'];
			
			if (! empty ( $texts ) || ! empty ( $selectedTermsIds ))
				$entities = array_merge ( $entities, $this->getEntitiesByObjectsFromDatatypeProperty ( NULL, NULL, $texts, $textsToExclude, $selectedTermsIds, $justSelectedTermsIds, $onTheStart ) );
			
			if (! empty ( $entities ))
				//Return a cluster with the entities of the ROOT cluster and every subcluster sorted by weight
				$cluster = $this->getCluster ( $entities, FALSE );
			
			$executionTime = $this->timer->_getelapsed ();
			
			// Saving all the queries even if not produce any result...
			$data = array ();
			
			$ttl = $this->calcDate ( PG_PERSISTENS_TTL_INTERVAL );
			
			$data ['text'] = $encodedQuery;
			$data ['plainTexts'] = array ($plainQuery );
			$data ['timetolive'] = $ttl;
			$data ['cluster'] = $cluster;
			$data ['status'] = PG_PERSISTEN_QUERY_STATUS_ACTIVE;
			$data ['tokens'] = $searchTokens;
			$data ['executionTime'] = $executionTime;
			
			$responseObj ['2beCached'] ['persistenQueriesInfo'] [] = $data;
		}
		
		if (PG_SHOW_PROCEDURE_TIMES) {
			$this->logMessage ( 'search:: Triples retrieved (' . $from . ') return time = ' . $executionTime . ' seconds (search term: ' . $plainQuery . ')', Zend_Log::INFO );
			$this->logMessage ( '------------------------------------------------------------------------------------------', Zend_Log::INFO );
		}
		
		$responseObj ['encodedQuery'] = $encodedQuery;
		$responseObj ['cluster'] = $cluster;
		
		if (isset ( $searchTokens ) && isset ( $searchTokens ['ambiguos'] ))
			$responseObj ['ambiguos'] = $searchTokens ['ambiguos'];
		
		return $responseObj;
	}
	
	private function touchEntitiesList($entList, $entLabels = NULL, $weights = NULL, $classes = NULL) {
		$entResultList = array ();
		
		if (! isset ( $classes ))
			$classes = $this->getClassFromEntities ( $entList );
		
		if (isset ( $entLabels ))
			/*
			 * Labels from the cache
			 * */
			$labels = $entLabels;
		else
			$labels = $this->getEntitiesLabel ( $entList );
		
		/**
		 * TODO...
		 * Esto hay que revizarlo
		 * 
		 * */
		if (! is_array ( $classes ))
			foreach ( $entList as $entity ) {
				if (isset ( $labels [$entity] ))
					$entResultList [$entity] ['label'] = $labels [$entity];
				
				if (isset ( $weights [$entity] ))
					$entResultList [$entity] ['count'] = $weights [$entity];
				
				$entResultList [$entity] ['typeof'] = $classes;
			}
		
		else
			foreach ( $entList as $entity ) {
				if (isset ( $labels [$entity] ))
					$entResultList [$entity] ['label'] = $labels [$entity];
				
				if (isset ( $weights [$entity] ))
					$entResultList [$entity] ['count'] = $weights [$entity];
				
				if (isset ( $classes ))
					$entResultList [$entity] ['typeof'] = $classes [$entity];
			}
		
		return $entResultList;
	}
	
	public function build($ids, $size = PG_DB_LARGE_BUILD_LEVEL, $language = PG_DEFAULT_LANGUAGE, $propertiesFilter = NULL, $allLevels = FALSE) {
		
		$resultObjects ['response'] = array ();
		
		if (! is_array ( $ids ))
			$ids = array ($ids );
		
		$resultObjects = $this->buildEntities ( $ids, $size, $language, $propertiesFilter, $allLevels );
		
		return $resultObjects;
	}
	
	public function documentsForCollection($startDate, $endDate, $biblio = 'todas') {
		$statistics = array ();
		$statistics ['consolidated'] = array ();
		if ($startDate == '')
			$startDate = '01/01/1960';
		if ($endDate == '')
			$endDate = date ( 'm/d/Y' );
		$result = $this->sdao->getDocumentsForCollection ( $biblio, $startDate, $endDate );
		if ($result) {
			$total = pg_num_rows ( $result );
			for($i = 0; $i < $total; $i ++) {
				$row = pg_fetch_array ( $result );
				
				$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] ['name'] = $row ['alias'];
				$collection = $row ['itemcollection'];
				if ($collection == '') //los agrupo bajo el titulo 'sin coleccion'
					$collection = 'sin colecciÃ³n';
				
				if (array_key_exists ( $collection, $statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] ))
					$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] [$collection] ['items'] += $row ['items'];
				
				else
					$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] [$collection] ['items'] = ( int ) $row ['items'];
				
		//consolidado
				if (array_key_exists ( $row ['ownertype'], $statistics ['consolidated'] )) {
					if (array_key_exists ( $collection, $statistics ['consolidated'] [$row ['ownertype']] ))
						$statistics ['consolidated'] [$row ['ownertype']] [$collection] ['items'] += $row ['items'];
					else
						$statistics ['consolidated'] [$row ['ownertype']] [$collection] ['items'] = ( int ) $row ['items'];
				} else
					$statistics ['consolidated'] [$row ['ownertype']] [$collection] ['items'] = ( int ) $row ['items'];
				
				if ($row ['currency'] != '') {
					$moneda = $this->data ['relnames'] [$row ['currency']];
					$moneda = str_replace ( 'pangea:price', '', $moneda );
					
					/*$price = str_replace ( 'e+', 'e +', $row ['price'] );
					$price = round ( $price, 2 ); //para redondear el resultado a 2 digitos despues de la coma
					*/
					$price = number_format ( $row ['price'], 2, '.', '' );
					
					if ($moneda == '') //este es el caso de que esten ubicados en la tabla padre pangea:price
						$moneda = 'sin_moneda';
					$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] [$collection] [$moneda] = $price;
					
					//consolidado
					if (array_key_exists ( $moneda, $statistics ['consolidated'] [$row ['ownertype']] [$collection] )) {
						$statistics ['consolidated'] [$row ['ownertype']] [$collection] [$moneda] += $price;
						$statistics ['consolidated'] [$row ['ownertype']] [$collection] [$moneda] = number_format ( $statistics ['consolidated'] [$row ['ownertype']] [$collection] [$moneda], 2, '.', '' );
					} else
						//$statistics ['consolidated'] [$row ['ownertype']] [$collection] [$moneda] = ( float ) $price;
						$statistics ['consolidated'] [$row ['ownertype']] [$collection] [$moneda] = $price;
				}
				
				if (array_key_exists ( 'total', $statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] )) {
					
					$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['total'] += $row ['items'];
					if ($row ['currency'] != '') {
						if (array_key_exists ( $moneda, $statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] )) {
							$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda] += $price;
							$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda] = number_format ( $statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda], 2, '.', '' );
						} else
							$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda] = $price;
					}
				} else {
					$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['total'] = ( int ) $row ['items'];
					if ($row ['currency'] != '')
						$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda] = $price;
				}
			
			}
		
		//recorro los precios del consolidado para redondearlos
		

		/*foreach ( $statistics ['consolidated'] [$row ['ownertype']] [$collection] as $collection ) {
				//$price = str_replace ( 'e+', 'e +', $price );
				foreach ( $collection as $moneda => $price ) {
					if ($moneda != 'items') {
						$price = number_format ( $price, 2, '.', '' );
						//$statistics ['consolidated'] [$row ['ownertype']] [$collection] [$moneda] = round ( $price, 2 ); //para redondear el resultado a 2 digitos despues de la coma
						$statistics ['consolidated'] [$row ['ownertype']] [$collection] [$moneda] = $price;
					}
				}
			}*/
		
		}
		return $statistics;
	}
	
	public function documentsForType($startDate, $endDate, $biblio = 'todas') {
		$statistics = array ();
		$statistics ['consolidated'] = array ();
		if ($startDate == '')
			$startDate = '01/01/1960';
		if ($endDate == '')
			$endDate = date ( 'm/d/Y' );
		$result = $this->sdao->getDocumentsForType ( $biblio, $startDate, $endDate );
		if ($result) {
			$total = pg_num_rows ( $result );
			for($i = 0; $i < $total; $i ++) {
				$row = pg_fetch_array ( $result );
				
				$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] ['name'] = $row ['alias'];
				//para los tipos que vienen vacios de momento le voy a poner sin tipo
				$itemType = $row ['itemtype'];
				if ($itemType == '')
					$itemType = 'Sin_tipo';
				
				if (array_key_exists ( $itemType, $statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] ))
					$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] [$itemType] ['items'] += $row ['items'];
				else
					$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] [$itemType] ['items'] = ( int ) $row ['items'];
				
		//consolidado
				if (array_key_exists ( $row ['ownertype'], $statistics ['consolidated'] )) {
					if (array_key_exists ( $itemType, $statistics ['consolidated'] [$row ['ownertype']] ))
						$statistics ['consolidated'] [$row ['ownertype']] [$itemType] ['items'] += $row ['items'];
					
					else
						$statistics ['consolidated'] [$row ['ownertype']] [$itemType] ['items'] = ( int ) $row ['items'];
				} else
					$statistics ['consolidated'] [$row ['ownertype']] [$itemType] ['items'] = ( int ) $row ['items'];
				if ($row ['currency'] != '') {
					$moneda = $this->data ['relnames'] [$row ['currency']];
					$moneda = str_replace ( 'pangea:price', '', $moneda );
					
					/*	$price = str_replace ( 'e+', 'e +', $row ['price'] );
					$price = round ( $price, 2 ); //para redondear el resultado a 2 digitos despues de la coma
					*/
					$price = number_format ( $row ['price'], 2, '.', '' );
					
					if ($moneda == '') //este es el caso de que esten ubicados en la tabla padre pangea:price
						$moneda = 'sin_moneda';
					$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] [$itemType] [$moneda] = $price;
					
					//consolidado
					if (array_key_exists ( $moneda, $statistics ['consolidated'] [$row ['ownertype']] [$itemType] )) {
						$statistics ['consolidated'] [$row ['ownertype']] [$itemType] [$moneda] += $price;
						$statistics ['consolidated'] [$row ['ownertype']] [$itemType] [$moneda] = number_format ( $statistics ['consolidated'] [$row ['ownertype']] [$itemType] [$moneda], 2, '.', '' );
					} else
						$statistics ['consolidated'] [$row ['ownertype']] [$itemType] [$moneda] = $price;
				
				}
				
				if (array_key_exists ( 'total', $statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] )) {
					$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['total'] += $row ['items'];
					if ($row ['currency'] != '') {
						if (array_key_exists ( $moneda, $statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] )) {
							$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda] += $price;
							$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda] = number_format ( $statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda], 2, '.', '' );
						} else
							$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda] = $price;
					}
				} else {
					$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['total'] = ( int ) $row ['items'];
					if ($row ['currency'] != '')
						$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda] = $price;
				}
			
			}
		
		//recorro los precios del consolidado para redondearlos
		/*foreach ( $statistics ['consolidated'] [$row ['ownertype']] [$itemType] as $moneda => $price ) {
				//$price = str_replace ( 'e+', 'e +', $price );
				$price = number_format ( $price, 2, '.', '' );
				//$statistics ['consolidated'] [$row ['ownertype']] [$itemType] [$moneda] = round ( $price, 2 ); //para redondear el resultado a 2 digitos despues de la coma
				$statistics ['consolidated'] [$row ['ownertype']] [$itemType] [$moneda] = $price;
			}*/
		
		}
		return $statistics;
	}
	
	public function documentsForEntryConcept($startDate, $endDate, $biblio = 'todas') {
		$statistics = array ();
		$statistics ['consolidated'] = array ();
		if ($startDate == '')
			$startDate = '01/01/1960';
		if ($endDate == '')
			$endDate = date ( 'm/d/Y' );
		$result = $this->sdao->getDocumentsForEntryConcept ( $biblio, $startDate, $endDate );
		if ($result) {
			$total = pg_num_rows ( $result );
			for($i = 0; $i < $total; $i ++) {
				$row = pg_fetch_array ( $result );
				
				$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] ['name'] = $row ['alias'];
				
				$found = false;
				if (array_key_exists ( 'ways', $statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] )) {
					foreach ( $statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] ['ways'] as $key => $ways ) {
						if ($ways ['way'] == $row ['adquisitionway']) {
							$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] ['ways'] [$key] ['count'] += $row ['items'];
							
							if ($row ['currency'] != '') { //lo que viene es el id de la tabla
								$moneda = $this->data ['relnames'] [$row ['currency']]; //aqui cojo el nombre de la tabla
								$moneda = str_replace ( 'pangea:price', '', $moneda ); //aqui me quedo solo con la parte del nombre que identifica a la moneda
								

								/*$price = str_replace ( 'e+', 'e +', $row ['price'] );
								$price = round ( $price, 2 ); //para redondear el resultado a 2 digitos despues de la coma
								*/
								$price = number_format ( $row ['price'], 2, '.', '' );
								
								if ($moneda == '') //este es el caso de que esten ubicados en la tabla padre pangea:price
									$moneda = 'sin_moneda';
								$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] ['ways'] [$key] [$moneda] = $price;
							}
							
							$found = true;
							break;
						}
					}
				}
				if (! $found) {
					
					if ($row ['currency'] != '') { //lo que viene es el id de la tabla
						$moneda = $this->data ['relnames'] [$row ['currency']]; //aqui cojo el nombre de la tabla
						$moneda = str_replace ( 'pangea:price', '', $moneda ); //aqui me quedo solo con la parte del nombre que identifica a la moneda
						

						/*$price = str_replace ( 'e+', 'e +', $row ['price'] );
						$price = round ( $price, 2 );*/
						$price = number_format ( $row ['price'], 2, '.', '' );
						
						if ($moneda == '') //este es el caso de que esten ubicados en la tabla padre pangea:price
							$moneda = 'sin_moneda';
						$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] ['ways'] [] = array ('way' => $row ['adquisitionway'], 'count' => ( int ) $row ['items'], $moneda => $price );
					} else
						$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['statistic'] ['ways'] [] = array ('way' => $row ['adquisitionway'], 'count' => ( int ) $row ['items'] );
				}
				
				//consolidado
				$foundConsolidate = false;
				
				if (array_key_exists ( $row ['ownertype'], $statistics ['consolidated'] )) {
					foreach ( $statistics ['consolidated'] [$row ['ownertype']] as $key => $ways ) {
						if ($ways ['way'] == $row ['adquisitionway']) {
							
							$statistics ['consolidated'] [$row ['ownertype']] [$key] ['count'] += $row ['items'];
							if ($row ['currency'] != '') {
								if (array_key_exists ( $moneda, $statistics ['consolidated'] [$row ['ownertype']] [$key] )) {
									
									$statistics ['consolidated'] [$row ['ownertype']] [$key] [$moneda] += $price;
									$statistics ['consolidated'] [$row ['ownertype']] [$key] [$moneda] = number_format ( $statistics ['consolidated'] [$row ['ownertype']] [$key] [$moneda], 2, '.', '' );
								} else
									
									$statistics ['consolidated'] [$row ['ownertype']] [$key] [$moneda] = $price;
							}
							
							$foundConsolidate = true;
							break;
						}
					}
				}
				
				if ($foundConsolidate == false) {
					if ($row ['currency'] != '')
						//$statistics ['consolidated'] ['ways'] [] = array ('way' => $row ['adquisitionway'], 'count' => ( int ) $row ['items'], $moneda => $price );
						$statistics ['consolidated'] [$row ['ownertype']] [] = array ('way' => $row ['adquisitionway'], 'count' => ( int ) $row ['items'], $moneda => $price );
					else
						//$statistics ['consolidated'] ['ways'] [] = array ('way' => $row ['adquisitionway'], 'count' => ( int ) $row ['items'] );
						$statistics ['consolidated'] [$row ['ownertype']] [] = array ('way' => $row ['adquisitionway'], 'count' => ( int ) $row ['items'] );
				}
				
				if (array_key_exists ( 'total', $statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] )) {
					
					$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['total'] += $row ['items'];
					if ($row ['currency'] != '') {
						if (array_key_exists ( $moneda, $statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] )) {
							$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda] += $price;
							$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda] = number_format ( $statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda], 2, '.', '' );
						} else
							$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda] = $price;
					}
				} else {
					$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] ['total'] = ( int ) $row ['items'];
					if ($row ['currency'] != '')
						$statistics ['values'] [$row ['ownertype']] [$row ['ownerid']] [$moneda] = $price;
				}
			
			}
		
		}
		return $statistics;
	}
	
	/**
	 * TODO..
	 * Este metodo Debe subir a la clase base de todos nuestros servicios (GenericService??) 
	 */
	public static function factory($classKey = __CLASS__) {
		date_default_timezone_set ( PG_DEFAULT_TIMEZONE );
		/*
		if (session_id () == '')
			session_start ();
		*/
		
		if ($classKey != '')
			self::$classKey = $classKey;
			
		/*
		 * Esto debemos revisarlo y tal vez refactorizarlo creo que durante
		 * el proceso de serializacion se corrompe la variable $this-> $sdao -> $dbConn
		 * lo cual genera ciertos warnings "invisibles" que no se generan en tiempo de
		 * depuracion (debugging time)
		 *
		*/
		return new SearchService ();
		if (isset ( $_SESSION [self::$classKey] ) === TRUE) {
			$_SESSION [self::$classKey] = unserialize ( $_SESSION [self::$classKey] );
			
			$_SESSION [self::$classKey]->sdao->refreshConn ();
			
			return $_SESSION [self::$classKey];
		} else
			
			return new SearchService ();
	}
	
	public function getSchema() {
		return $this->schema;
	}
	
	/**
	 * TODO..
	 * Debo darle un nombre mas descriptivo y preciso a esta funcion
	 * 
	 * 
	 * Debo modificar el flujo de acceso a la info de la Consultas Persistentes de modo 
	 * que para acceder se recupere afuera y se haga el procesamiento de cada componente
	 * en la funcion qu ecorresponda.
	 * */
	public function getClusterInPage($md5SearchString, $clusterID, $page) {
		$resultObj = array ();
		
		if (PG_SHOW_PROCEDURE_TIMES) {
			$this->timer->reset ();
			$this->timer->start ();
		}
		
		$pQuery = $this->sdao->getPersistenQuery ( $md5SearchString );
		
		if (($pQuery !== false) && isset ( $pQuery ['cluster'] )) {
			$cluster = $this->getClusterFromPersistenQuery ( $pQuery );
			
			if (isset ( $cluster ) && ! empty ( $cluster )) {
				$responseObj = array ();
				
				$clusterEntities = $cluster->getClusteredEntities ();
				
				$clustersDescription = array ();
				
				$clusterChilds = $cluster->getClustersInPage ( $page, PG_DEFAULT_PAGE_SIZE );
				foreach ( $clusterChilds as $clusterKey => $entitiesCount ) {
					$clustersDescription [$clusterKey] ['count'] = $entitiesCount;
					
					if (isset ( $this->data ['classLabel'] [$clusterKey] ))
						$clustersDescription [$clusterKey] ['label'] = $this->getLabel4Class ( $clusterKey );
					
					$clustersDescription [$clusterKey] ['typeof'] = "owl:Class";
				}
				
				$responseObj ['id'] = $md5SearchString;
				$responseObj ['count'] = $cluster->getClustersCount ();
				$responseObj ['description'] = $clustersDescription;
			}
		}
		
		$resultObj ['response'] = $responseObj;
		
		return $resultObj;
	}
	
	public function getEntitiesFilteredByCluster($md5SearchString, $clusterID, $page, $pageSize, $language) {
		$resultObj = array ();
		
		$responseObj ['count'] = 0;
		$responseObj ['description'] = array ();
		
		$clusterModified = FALSE;
		
		if (PG_SHOW_PROCEDURE_TIMES) {
			$this->timer->reset ();
			$this->timer->start ();
		}
		
		$pQuery = $this->sdao->getPersistenQuery ( $md5SearchString );
		
		if (($pQuery !== false) && isset ( $pQuery ['cluster'] )) {
			$cluster = $this->getClusterFromPersistenQuery ( $pQuery );
			
			$entities = $cluster->getEntitiesInPage ( $clusterID, $page, $pageSize );
			
			$responseObj ['count'] = $cluster->getEntitiesCount ( $clusterID );
			
			if (! empty ( $entities )) {
				$weights = $cluster->getEntitiesWeights ( $entities );
				
				$responseObj ['description'] = $this->touchEntitiesList ( $entities, NULL, $weights, $clusterID );
			}
		}
		
		if (PG_SHOW_PROCEDURE_TIMES) {
			$this->logMessage ( 'getEntitiesFilteredByCluster:: Entities retrieved return time = ' . $this->timer->_getelapsed () . ' seconds.', Zend_Log::INFO );
			$this->logMessage ( '------------------------------------------------------------------------------------------', Zend_Log::INFO );
		}
		
		$resultObj ['response'] = $responseObj;
		
		return $resultObj;
	}
	
	public function getEntityCRUDForSave($entity, $entityType, $propertyValues) {
		$crudRecords = array ();
		
		$crudArray = array ();
		
		$filterCloud = array ();
		
		$isBNode = $this->is_aBNode ( $entity );
		
		$crudRecords ['id'] = $entity;
		$crudRecords ['isbnode'] = $isBNode;
		$crudRecords ['class'] = $entityType;
		$crudRecords ['categories'] = array ();
		
		if (! $isBNode) {
			
			$entityTriples = $this->buildEntities ( $entity );
			
			if (isset ( $entityTriples ['response'] ) && isset ( $entityTriples ['response'] [$entity] )) {
				$filterCloud = $entityTriples ['response'] [$entity];
			}
		}
		
		foreach ( $propertyValues as $property => $valuesList ) {
			if (isset ( $this->data ['propertiesLabel'] [$property] )) {
				$valuesForInsert = array ();
				$valuesForDelete = array ();
				
				$propertyType = 'uri';
				
				if (! is_array ( $valuesList ))
					if (! empty ( $valuesList ))
						$valuesList = array ($valuesList );
					else
						$valuesList = array ();
				
				if (! empty ( $valuesList )) {
					if ($this->is_aPropertyWithLiteralRangeSupport ( $property )) {
						$propertyType = 'literal';
						
						$valuesCount = count ( $valuesList );
						for($i = 0; $i < $valuesCount; $i ++)
							$valuesList [$i] = utf8_encode ( $valuesList [$i] );
					}
				}
				
				if ($isBNode)
					$valuesForInsert = $valuesList;
				
				else {
					$currentValuesList = array ();
					
					if (isset ( $filterCloud [$property] ))
						$currentValuesList = $this->getEntityPropertyValues ( $filterCloud [$property] );
					
					$valuesForInsert = array_diff ( $valuesList, $currentValuesList );
					$valuesForDelete = array_diff ( $currentValuesList, $valuesList );
				}
				
				// Insertar...
				if (! empty ( $valuesForInsert ))
					foreach ( $valuesForInsert as $value ) {
						$crudArray = array ('new' => array ('type' => $propertyType, 'value' => $value ) );
						
						$crudRecords ['categories'] ['forInsert'] [$property] [] = $crudArray;
					}
				
		// Eliminar...
				if (! empty ( $valuesForDelete ))
					foreach ( $valuesForDelete as $value ) {
						$crudArray = array ('old' => array ('type' => $propertyType, 'value' => $value ) );
						
						$crudRecords ['categories'] ['forDelete'] [$property] [] = $crudArray;
					}
			}
		}
		
		return $crudRecords;
	}
	
	public function getNamedEntities($ids) {
		$resultObjects ['response'] = array ();
		
		$idsStr = $this->sdao->getINValueFromArray ( $ids );
		
		$resultObj = $this->buildEntities ( $ids, PG_DB_SHORT_BUILD_LEVEL );
		
		$resultObjects ['response'] = $resultObj;
		
		return $resultObjects;
	}
	
	public function searchMerge($searchsInfo, $plainQuery = NULL, $language = PG_DEFAULT_LANGUAGE) {
		$responseObj = array ();
		
		$mergedCluster = NULL;
		
		if (isset ( $plainQuery ))
			$encodedQuery = md5 ( $plainQuery );
		
		if (PG_USE_PERSISTENS_QUERIES) {
			$pQuery = $this->sdao->getPersistenQuery ( $encodedQuery );
			
			if (isset ( $pQuery ))
				$mergedCluster = $this->getClusterFromPersistenQuery ( $pQuery );
		}
		
		if (! isset ( $mergedCluster )) {
			$mergedCluster = new LightClusterObject ();
			
			$plainQuery = "";
			
			if (isset ( $searchsInfo ['_md5'] )) {
				if (! is_array ( $searchsInfo ['_md5'] ))
					$searchsInfo ['_md5'] = array ($searchsInfo ['_md5'] );
				
				$plainQuery = "md5::" . implode ( '+', $searchsInfo ['_md5'] );
				
				foreach ( $searchsInfo ['_md5'] as $encodedQuery ) {
					$searchInfoObj = $this->searchCore ( NULL, $encodedQuery );
					
					if (isset ( $searchInfoObj ['cluster'] ))
						$mergedCluster->merge ( $searchInfoObj ['cluster'] );
				}
			}
			
			if (isset ( $searchsInfo ['selectedIds'] )) {
				if (! empty ( $plainQuery ))
					$plainQuery .= "+";
				
				$plainQuery .= "selectedIds::" . $searchsInfo ['selectedIds'];
				
				$searchInfoObj = $this->searchCore ( NULL, NULL, $searchsInfo ['selectedIds'] );
				
				if (isset ( $searchInfoObj ['cluster'] ))
					$mergedCluster->merge ( $searchInfoObj ['cluster'] );
				
				if (isset ( $searchInfoObj ['2beCached'] ))
					foreach ( $searchInfoObj ['2beCached'] as $key => $values )
						if (isset ( $responseObj ['2beCached'] [$key] ))
							$responseObj ['2beCached'] [$key] = array_merge ( $responseObj ['2beCached'] [$key], $values );
						else
							$responseObj ['2beCached'] [$key] = $values;
			}
			
			if (isset ( $searchsInfo ['text'] )) {
				if (! empty ( $plainQuery ))
					$plainQuery .= "+";
				
				$plainQuery .= "text::(" . $searchsInfo ['text'] . ")";
				
				$searchInfoObj = $this->searchCore ( $searchsInfo ['text'] );
				
				if (isset ( $searchInfoObj ['cluster'] ))
					$mergedCluster->merge ( $searchInfoObj ['cluster'] );
				
				if (isset ( $searchInfoObj ['2beCached'] ))
					foreach ( $searchInfoObj ['2beCached'] as $key => $values )
						if (isset ( $responseObj ['2beCached'] [$key] ))
							$responseObj ['2beCached'] [$key] = array_merge ( $responseObj ['2beCached'] [$key], $values );
						else
							$responseObj ['2beCached'] [$key] = $values;
			}
			
			// Saving all the queries even if not produce any result...
			$encodedQuery = md5 ( $plainQuery );
			
			$data = array ();
			
			$data ['text'] = $encodedQuery;
			$data ['plainTexts'] = array ($plainQuery );
			$data ['cluster'] = $mergedCluster;
			$data ['status'] = PG_PERSISTEN_QUERY_STATUS_ACTIVE;
			
			$responseObj ['2beCached'] ['persistenQueriesInfo'] [] = $data;
		}
		
		$responseObj ['response'] ['id'] = $encodedQuery;
		$responseObj ['response'] ['count'] = $mergedCluster->getEntitiesCount ();
		$responseObj ['response'] ['description'] = array ();
		
		if ($responseObj ['response'] ['count'] > 0) {
			$clusteredEntities = $mergedCluster->getClusteredEntities ();
			
			$clusters = array ();
			
			foreach ( $clusteredEntities as $rdfType => $entities ) {
				$clusters [$rdfType] ['count'] = sizeof ( $entities );
				
				if (isset ( $this->data ['classLabel'] [$rdfType] ))
					$clusters [$rdfType] ['label'] = $this->getLabel4Class ( $rdfType, $language );
				
				$clusters [$rdfType] ['typeof'] = "owl:Class";
			}
			
			$responseObj ['response'] ['description'] = $clusters;
		}
		
		return $responseObj;
	}
	
	public function search($plainQuery = NULL, $encodedQuery = NULL, $selectedTermsIds = NULL, $justSelectedTermsIds = FALSE, $inferenceLevel = PG_DEFAULT_INFERENCE_LEVEL, $page = PG_DEFAULT_INITIAL_PAGE_INDEX, $pageSize = PG_DEFAULT_PAGE_SIZE, $language = PG_DEFAULT_LANGUAGE) {
		$responseObj = array ();
		
		$cluster = NULL;
		
		try {
			$searchInfoObj = $this->searchCore ( $plainQuery, $encodedQuery, $selectedTermsIds, $justSelectedTermsIds, $inferenceLevel, $page, $pageSize );
			
			if (isset ( $searchInfoObj ['2beCached'] ))
				$responseObj ['2beCached'] = $searchInfoObj ['2beCached'];
			
			$responseObj ['response'] ['id'] = $searchInfoObj ['encodedQuery'];
			$responseObj ['response'] ['count'] = 0;
			$responseObj ['response'] ['description'] = array ();
			
			if (isset ( $searchInfoObj ['cluster'] ) && is_object ( $searchInfoObj ['cluster'] )) {
				$responseObj ['response'] ['count'] = $searchInfoObj ['cluster']->getEntitiesCount ();
				
				$searchInfoObj ['cluster']->sort ( 0, FALSE );
				
				$clusteredEntities = $searchInfoObj ['cluster']->getClutersInfo ();
				
				$clusters = array ();
				
				foreach ( $clusteredEntities as $rdfType => $entitiesCount ) {
					$clusters [$rdfType] ['count'] = $entitiesCount;
					
					if (isset ( $this->data ['classLabel'] [$rdfType] ))
						$clusters [$rdfType] ['label'] = $this->getLabel4Class ( $rdfType, $language );
					
					$clusters [$rdfType] ['typeof'] = "owl:Class";
				}
				
				$responseObj ['response'] ['description'] = $clusters;
			}
		} catch ( PangeaException $e ) {
			$this->logMessage ( $e->getMessage (), Zend_Log::ERR );
		}
		
		return $responseObj;
	}
	
	public function searchListed($properties, $ranges, $searchToken, $page, $pageSize, $language) {
		$responseObj = array ();
		
		try {
			$cluster = NULL;
			
			// Entities IDs
			$entities = array ();
			
			// Entities Weights: this info is retrieved from the triples getter's functions
			$weights = array ();
			
			// Entities Labels: this info is retrieved from the triples getter's functions
			$labels = array ();
			
			// Literals IDs
			$literals = array ();
			
			if (! isset ( $ranges ))
				$ranges = array ();
			
			$inferenceLevel = (! isset ( $inferenceLevel )) ? PG_DEFAULT_INFERENCE_LEVEL : $inferenceLevel;
			$page = (! isset ( $page )) ? PG_DEFAULT_INITIAL_PAGE_INDEX : $page;
			$pageSize = (! isset ( $pageSize )) ? PG_DEFAULT_PAGE_SIZE : $pageSize;
			$language = (! isset ( $language )) ? 'sp' : $language;
			
			$plainQuery = '';
			try {
				$this->timer->reset ();
				$this->timer->start ();
				
				if (! isset ( $properties ))
					$plainQuery = $searchToken;
				else if (is_array ( $properties )) {
					foreach ( $properties as $property )
						if ($this->is_aValidPropertyName ( $property )) {
							if (! empty ( $plainQuery ))
								$plainQuery .= ',';
							
							$plainQuery .= $property;
							
							if (isset ( $searchToken ) && ! empty ( $searchToken ))
								$plainQuery .= '=' . $searchToken;
							
							$ranges = array_merge ( $ranges, $this->getSchemaPropertyRanges ( $property ) );
						}
					
					$ranges = array_unique ( $ranges );
				
				} else {
					$plainQuery = $properties . '=' . $searchToken;
					
					$ranges = array_merge ( $ranges, $this->getSchemaPropertyRanges ( $properties ) );
				}
				
				/**
				 * TODO..
				 * Revizar...
				 * */
				$ranges = $this->getSchemaPropertyRangeDescendants ( $ranges );
				
				$plainQuery = 'list:' . $plainQuery . ' in {' . implode ( ',', $ranges ) . '}';
				
				$searchTokens = $this->getSearchTokensAndStartPoints ( $searchToken );
				$searchTokens ['ranges'] = $ranges;
				
				$encodedQuery = md5 ( serialize ( $searchTokens ) );
				
				if (PG_USE_PERSISTENS_QUERIES) {
					$pQuery = $this->sdao->getPersistenQuery ( $encodedQuery );
					
					if ($pQuery)
						$cluster = $this->getClusterFromPersistenQuery ( $pQuery );
				}
				
				if (! isset ( $cluster )) {
					$from = 'from database';
					
					$cluster = NULL;
					
					$onTheStart = FALSE;
					
					$classes = array ();
					
					$entities = array_merge ( $entities, $this->getEntitiesFromRangesView ( $searchToken, $ranges, $classes, $labels, $onTheStart, FALSE ) );
					
					if (! empty ( $entities )) {
						$cluster = $this->getCluster ( $entities, TRUE );
						
						$executionTime = $this->timer->_getelapsed ();
						
						if (PG_SAVE_INFO_RETRIEVED) {
							$data = array ();
							
							$ttl = $this->calcDate ( PG_PERSISTENS_TTL_INTERVAL );
							
							$data ['text'] = $encodedQuery;
							$data ['plainTexts'] = array ($plainQuery );
							$data ['timetolive'] = $ttl;
							$data ['cluster'] = $cluster;
							$data ['status'] = PG_PERSISTEN_QUERY_STATUS_ACTIVE;
							$data ['tokens'] = $searchTokens;
							$data ['executionTime'] = $executionTime;
							
							$resultObj ['2beCached'] ['persistenQueriesInfo'] [] = $data;
						}
					}
				
				} else {
					$from = 'from cache';
					
					$executionTime = $this->timer->_getelapsed ();
				}
				
				if (PG_SHOW_PROCEDURE_TIMES) {
					$this->logMessage ( 'searchListed:: Triples retrieved (' . $from . ') return time = ' . $executionTime . ' seconds (search term: ' . $plainQuery . ')', Zend_Log::INFO );
					$this->logMessage ( '------------------------------------------------------------------------------------------', Zend_Log::INFO );
				}
			
			} catch ( PangeaException $e ) {
				$this->logMessage ( $e->getMessage (), Zend_Log::ERR );
			}
			
			$responseObj ['id'] = $encodedQuery;
			$responseObj ['count'] = 0;
			
			if (isset ( $cluster )) {
				$class = 'ROOT';
				
				$responseObj ['count'] = $cluster->getEntitiesCount ( $class );
				
				$entities = $cluster->getEntitiesInPage ( $class, $page, $pageSize );
				if (! empty ( $entities )) {
					$labels = $cluster->getEntitiesLabels ( $entities );
					$weights = $cluster->getEntitiesWeights ( $entities );
					$types = $cluster->getEntitiesTypes ( $entities );
					
					$responseObj ['description'] = $this->touchEntitiesList ( $entities, $labels, $weights, $types );
				}
			}
		} catch ( PangeaException $e ) {
			$this->logMessage ( $e->getMessage (), Zend_Log::ERR );
		}
		
		$resultObj ['response'] = $responseObj;
		
		return $resultObj;
	}
	
	public function searchFiltered($encodedQuery, $clusterID, $filters, $page, $pageSize, $language) {
		$cluster = NULL;
		
		$responseObj = array ();
		
		$responseObj = '';
		
		$page = (! isset ( $page )) ? PG_DEFAULT_INITIAL_PAGE_INDEX : $page;
		$pageSize = (! isset ( $pageSize )) ? PG_DEFAULT_PAGE_SIZE : $pageSize;
		$language = (! isset ( $language )) ? 'sp' : $language;
		
		if (! isset ( $clusterID ) || empty ( $clusterID ))
			// Solo se busca en la manifestaciones
			$clusterID = PG_DEFAULT_CLUSTER_ID;
		
		try {
			$this->timer->reset ();
			$this->timer->start ();
			
			$searchTokens = array ();
			
			$searchTokens = $this->getSearchTokensFromEncodedQuery ( $encodedQuery );
			
			if (isset ( $searchTokens )) {
				$plainText = $encodedQuery;
				
				if (isset ( $searchTokens ['filters'] ))
					$searchTokens ['filters'] = array_merge ( $searchTokens ['filters'], $filters );
				else
					$searchTokens ['filters'] = $filters;
				
				foreach ( $filters as $filterField => $filterValues ) {
					$plainText .= '?_ff=' . $filterField . ',_fv=';
					
					if (! empty ( $filterValues ))
						$plainText .= json_encode ( $filterValues );
				}
				
				$plainText .= ',_ctg=';
				if (isset ( $clusterID ))
					$plainText .= $clusterID;
				
				$encodedFilterQuery = md5 ( serialize ( $searchTokens ) );
				
				if (PG_SHOW_PROCEDURE_TIMES)
					$this->logMessage ( 'Times for search string: ' . $plainText, Zend_Log::INFO );
				
				if (PG_USE_PERSISTENS_QUERIES)
					$cluster = $this->getClusterFromEncodedQuery ( $encodedFilterQuery );
				
				if (! isset ( $cluster )) {
					$cluster = $this->getClusterFromEncodedQuery ( $encodedQuery );
					
					/*
					 * Entities IDs
					 * */
					$entities = array ();
					/*
					 * Entities Classes
					 * */
					$classes = array ();
					
					/*
					 * Entities Labels: this info is retrieved from the labels getter's functions
					 * */
					$labels = array ();
					
					/*
					 * Entities Weights: this info is retrieved from the triples getter's functions
					 * */
					$weights = array ();
					
					$filterCloud = $this->getFiltrerCloudFromEncodedQuery ( $encodedQuery );
					
					$updateFilterCloud = FALSE;
					
					$targetEntities = $cluster->getEntities ( PG_DEFAULT_CLUSTER_ID );
					
					$filteredEntities = $this->getFilteredEntities ( $filters );
					
					$iCount = sizeof ( $filteredEntities );
					for($i = 0; $i < $iCount; $i ++)
						$entitiesByClass [$filteredEntities [$i] ['tableoid']] [] = $filteredEntities [$i] ['subject'];
					
					$filteredEntities = array ();
					
					foreach ( $entitiesByClass as $tableoid => $classEntities ) {
						$class = $this->data ['relnames'] [$tableoid];
						
						if ($this->is_aBibliographicClass ( $class ))
							$filteredEntities = array_merge ( $filteredEntities, $this->getManifestationsFromEntities ( $class, $classEntities ) );
					}
					
					$filteredEntities = array_intersect ( $targetEntities, $filteredEntities );
					
					$weights = $cluster->getEntitiesWeights ( $filteredEntities );
					$labels = $cluster->getEntitiesLabels ( $filteredEntities );
					
					$iCount = sizeof ( $filteredEntities );
					foreach ( $filteredEntities as $entity )
						$entities [] = array ('subject' => $entity, 'typeof' => PG_DEFAULT_CLUSTER_ID, 'label' => $labels [$entity], 'weight' => $weights [$entity] );
					
		// Getting associated entities
					$entitiesForBuild = $this->sdao->getINValueFromArray ( $filteredEntities );
					
					$results = $this->sdao->getEntitiesFromPropertyByEntityIds ( $entitiesForBuild );
					
					$rows = pg_fetch_all ( $results );
					
					if ($rows !== FALSE)
						$entities = array_merge ( $entities, $rows );
					
					$cluster = $this->getCluster ( $entities, FALSE );
					
					if (PG_SAVE_INFO_RETRIEVED) {
						$plainQueries = array ($plainText );
						
						$executionTime = $this->timer->_getelapsed ();
						
						$data = array ();
						
						$ttl = $this->calcDate ( PG_PERSISTENS_TTL_INTERVAL );
						
						$data ['text'] = $encodedFilterQuery;
						$data ['plainTexts'] = array ($plainQueries );
						$data ['timetolive'] = $ttl;
						$data ['cluster'] = $cluster;
						$data ['status'] = PG_PERSISTEN_QUERY_STATUS_ACTIVE;
						$data ['filterCloud'] = $filterCloud;
						$data ['executionTime'] = $executionTime;
						
						$responseObj ['2beCached'] ['persistenQueriesInfo'] [] = $data;
					}
				} else {
					$from = 'from cache';
					
					$executionTime = $this->timer->_getelapsed ();
				}
				
				if (PG_SHOW_PROCEDURE_TIMES) {
					$this->logMessage ( 'searchFiltered:: Triples retrieved (' . $from . ') return time = ' . $executionTime . ' seconds (search term: ' . $plainText . ')', Zend_Log::INFO );
					$this->logMessage ( '------------------------------------------------------------------------------------------', Zend_Log::INFO );
				}
			}
			
			$responseObj ['response'] ['id'] = $encodedFilterQuery;
			$responseObj ['response'] ['count'] = 0;
			$responseObj ['response'] ['description'] = array ();
			
			if (isset ( $cluster ) && is_object ( $cluster )) {
				$responseObj ['response'] ['count'] = $cluster->getEntitiesCount ();
				
				$cluster->sort ( 0, FALSE );
				
				$clusteredEntities = $cluster->getClutersInfo ();
				
				$clusters = array ();
				
				foreach ( $clusteredEntities as $rdfType => $entitiesCount ) {
					$clusters [$rdfType] ['count'] = $entitiesCount;
					
					if (isset ( $this->data ['classLabel'] [$rdfType] ))
						$clusters [$rdfType] ['label'] = $this->getLabel4Class ( $rdfType, $language );
					
					$clusters [$rdfType] ['typeof'] = "owl:Class";
				}
				
				$responseObj ['response'] ['description'] = $clusters;
			}
		
		} catch ( PangeaException $e ) {
			$this->logMessage ( $e->getMessage (), Zend_Log::ERR );
		}
		
		return $responseObj;
	}
	
	public function saveEntity($ctgRecords) {
		
		$responseObj = array ();
		$tracingRows = array ();
		
		$edao = new EntityDAO ();
		
		$this->timer->reset ();
		$this->timer->start ();
		
		if (! empty ( $ctgRecords ['categories'] )) {
			foreach ( $ctgRecords ['categories'] as $category => $properties )
				foreach ( $properties as $property => $values ) {
					if ($this->is_aPropertyWithLiteralRangeSupport ( $property ))
						$propertyRangeType = 'literal';
					else
						$propertyRangeType = 'uri';
					
					foreach ( $values as $valueKey => $value ) {
						$oldValuePresent = (isset ( $value ['old'] ));
						$newValuePresent = (isset ( $value ['new'] ));
						
						if ($oldValuePresent)
							$ctgRecords [$category] [$property] [$valueKey] ['old'] ['type'] = $propertyRangeType;
						
						if ($newValuePresent)
							$ctgRecords [$category] [$property] [$valueKey] ['new'] ['type'] = $propertyRangeType;
					}
				}
		}
		
		if ($ctgRecords ['isbnode'])
			$responseObj ['response'] ['_bnode'] = $ctgRecords ['id'];
		
		$tracingRows = $edao->RDF_CRUD ( $ctgRecords );
		
		$responseObj ['response'] ['result'] = empty ( $tracingRows );
		
		$responseObj ['response'] ['_ids'] = $ctgRecords ['id'];
		
		/**
		 * Actualizacion de info cacheada perteneciente a la entidad actualizada.
		 * 
		 * TODO..
		 * Aqui se hace una actualizacion "capital", es decir, todo los campos (no actualizados y actualizados exitosamente o no) son elinados del cache
		 * y reinsertados en este. En el futuro pudieramos solamente actualizar las propiedades envueltas en una actualizacion exitosa de sus valores.
		 * */
		$idEntity = $ctgRecords ['id'];
		
		$filterCloud = array ();
		
		$triples = $this->buildEntitiesFromDB ( $idEntity, PG_DB_LARGE_BUILD_LEVEL, FALSE, $filterCloud );
		
		$executionTime = $this->timer->_getelapsed ();
		
		if (! empty ( $triples ) && PG_SAVE_INFO_RETRIEVED) {
			$triples = $this->getOptimizedTriplesContiner ( $triples );
			
			$data = array ();
			
			$data ['entityid'] = $idEntity;
			$data ['build Size'] = PG_DB_LARGE_BUILD_LEVEL;
			$data ['triples'] = $triples [$idEntity];
			
			$responseObj ['2beCached'] ['persistenTriplesInfo'] [] = $data;
		}
		
		if (PG_SHOW_PROCEDURE_TIMES) {
			$this->logMessage ( 'saveEntity:: Entity saved return time = ' . $executionTime . ' seconds (entity: ' . $idEntity . ')', Zend_Log::INFO );
			$this->logMessage ( '------------------------------------------------------------------------------------------', Zend_Log::INFO );
		}
		
		return $responseObj;
	}
	
	public function saveInCache($info2beCached) {
		try {
			if (isset ( $info2beCached )) {
				if (isset ( $info2beCached ['persistenQueriesInfo'] ) && is_array ( $info2beCached ['persistenQueriesInfo'] ))
					foreach ( $info2beCached ['persistenQueriesInfo'] as $pQueryInfo )
						$this->savePersistenQuery ( $pQueryInfo );
				
				if (isset ( $info2beCached ['persistenTriplesInfo'] ) && is_array ( $info2beCached ['persistenTriplesInfo'] ))
					foreach ( $info2beCached ['persistenTriplesInfo'] as $pTriplesInfo )
						$this->savePersistenTriples ( $pTriplesInfo );
			}
		} catch ( PangeaIOException $e ) {
			$this->logMessage ( $e->getMessage (), Zend_Log::ALERT );
			
			return false;
		}
		
		return true;
	}
	
	public function WEB_DOCS($id) {
		$edao = new EntityDAO ();
		return $edao->URL_WEB_DOCS ( $id );
	}
	
	public function __destruct() {
		$_SESSION [self::$classKey] = serialize ( $this );
	
		//parent::__destruct ();
	}
}

?>