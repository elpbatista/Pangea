<?php
include_once dirname ( __FILE__ ) . '/../../core/dao/GenericDAO.php'; //verificar camino


class SearchDAO extends GenericDAO {
	function SearchDAO() {
		parent::GenericDAO ();
	}
	
	private function escape_wildcards($text) {
		preg_match_all ( "/" . PG_DBMS_WILDCARDS_PATTERN_REG . "/i", $text, $matches, PREG_OFFSET_CAPTURE );
		
		foreach ( $matches [0] as $found )
			$text = str_replace ( $found [0], str_repeat ( PG_QUERY_FOR_LIKE_ESCAPE_CHAR, 2 ) . $found [0], $text );
		
		return $text;
	}
	
	/**
	 * Enter description here ...
	 * @param $id
	 */
	
	private function readInheritsTable($id) { //se utiliza en el getStructure
		$query = "select pgcl.oid from pg_class pgcl inner join pg_inherits pgin on pgcl.oid = pgin.inhrelid where pgin.inhparent = '$id'";
		$result = pg_query ( $this->dbConn, $query );
		$count = pg_num_rows ( $result );
		
		$resultado = array ();
		for($j = 0; $j < $count; $j ++) {
			$row = pg_fetch_array ( $result );
			
			$resultado [] = $row ["oid"];
			
			$resultado = array_merge ( $resultado, $this->readInheritsTable ( $row ["oid"] ) );
		}
		
		return $resultado;
	}
	
	function deletePersistenQuery($pquery) {
		$query = "delete from \"persistenQueries\" where (text = $1)";
		$data = array ($pquery );
		
		$result = $this->prepareAndExecuteQuery ( $query, $data );
		
		return $result;
	}
	
	function deletePersistenQueryTriples($entityID) {
		$data = array ($entityID );
		
		$query = "delete from \"persistenTriples\" where(entityID = $1);";
		
		$result = $this->prepareAndExecuteQuery ( $query, $data );
		
		return $result;
	}
	
	function getAllRelname() {
		//$query = "select oid,relname from pg_class";
		/*
		 * Solo los OID de nuestras tablas
		 * */
		$query = "SELECT oid,relname FROM pg_class WHERE(relkind = 'r' and relname NOT LIKE 'pg_%' and relname NOT LIKE 'sql_%') ORDER BY relname";
		
		$realnames = array ();
		try {
			$result = pg_query ( $this->dbConn, $query );
			$total = pg_num_rows ( $result );
			
			for($i = 0; $i < $total; $i ++) {
				$row = pg_fetch_array ( $result );
				
				$realnames [$row ['oid']] = $row ['relname'];
			}
		} catch ( Exception $e ) {
		}
		
		return $realnames;
	}
	
	function getBasicPropertiesRelname($parentTable) {
		$query = "select pgcl.relname from pg_class pgcl inner join pg_inherits pgin on pgcl.oid = pgin.inhrelid where(pgin.inhparent = (select oid from pg_class where relname = '" . $parentTable . "'))";
		
		if (PG_LOG_ACTIVITY)
			$this->logQuery ( "FROM getBasicPropertiesRelname function:: " . $query );
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	}
	
	function getBNodeEntityID($bnode) {
		$query = "select ID from \"bNodes\" where(\"idBnode\" = $1)";
		
		$data = array ();
		
		$data [] = $bnode;
		
		$result = $this->prepareAndExecuteQuery ( $query, $data );
		
		return $result;
	}
	
	function getCoreEntitiesByPage($offset, $count) {
		$data = array ();
		
		$data [] = $count;
		$data [] = $offset;
		
		$query = "select id from \"frbr:Core\" as fev left outer join \"persistenTriples\" as ptr on(fev.id = ptr.entityId) where(ptr.triples ISNULL) order by id limit $1 offset $2";
		
		$results = $this->prepareAndExecuteQuery ( $query, $data );
		
		return $results;
	}
	
	function getUnbuildedEndeavours() {
		$query = "select id from \"frbr:Endeavour\"";
		$query .= " where(id NOT in(select entityId from \"persistenTriples\" where(\"build Size\" = 2)))";
		$query .= " order by id";
		
		$results = pg_query ( $this->dbConn, $query );
		
		return $results;
	}
	
	function getEntitiesFromRange($textsToSearch, $property, $range, $onTheStart) {
		
		if (! isset ( $range ))
			$range = PG_OBJECTPROPERTY_TABLENAME;
		
		if (! is_array ( $textsToSearch ))
			$textsToSearch = array ($textsToSearch );
		
		$query = "select subject from \"" . PG_MAGIC_SUBJECT_LABEL_TYPEOF_VIEWNAME . "\" where typeof = '$range'";
		
		if (isset ( $textsToSearch ) && ! empty ( $textsToSearch )) {
			$indexCount = sizeof ( $textsToSearch );
			
			if (PG_QUERY_INDEXING_TYPE == 'Gin') {
				for($i = 0; $i < $indexCount; $i ++) {
					$token = $this->escape_wildcards ( $textsToSearch [$i] );
					
					if (! empty ( $token ))
						$query .= " OR object % '$token'";
				}
			
			} else {
				for($i = 0; $i < $indexCount; $i ++) {
					$token = $this->escape_wildcards ( $textsToSearch [$i] );
					
					if (! empty ( $token )) {
						if (! $onTheStart)
							$token = "%" . $token;
						$query .= " OR object ilike '$token%'";
					}
				}
				
				$query .= " escape E'" . str_repeat ( PG_QUERY_FOR_LIKE_ESCAPE_CHAR, 2 ) . "'";
			}
		}
		/**		
		if (! isset ( $property ))
			$query = "select pc.id as object, pc.tableoid from \"" . PG_CLASS_TABLENAME . "\" pc where numeric_id in ($query)";
		else
			$query = "select pop.object, pc.tableoid from \"" . $property . "\" pop left outer join \"" . PG_CLASS_TABLENAME . "\" pc on(pop.numeric_object = pc.numeric_id) where numeric_object in ($query)";
		 */
		if (! isset ( $property ))
			$query = "select pc.id as object, pc.tableoid from \"" . PG_CLASS_TABLENAME . "\" pc where id in ($query)";
		else
			$query = "select pop.object, pc.tableoid from \"" . $property . "\" pop left outer join \"" . PG_CLASS_TABLENAME . "\" pc on(pop.object = pc.id) where object in ($query)";
		
		if (PG_LOG_ACTIVITY)
			$this->logQuery ( "FROM getEntitiesFromRange function:: " . $query );
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	}
	
	public function getEntitiesFromDatatypeProperty($objectPropertyTable, $datatypePropertyTable, $textsToSearch, $textsToExclude, $selectedTermsIds, $justSelectedTerms = FALSE, $onTheStart) { //poner en la consulta pangea:ObjectProperty
		$result = "";
		
		//$query = "select results.subject, pgc.tableoid as typeof, count(results.subject) as weight from (";
		$query = "select results.subject, pgc.tableoid as typeof, count(results.subject) as weight from (";
		$query .= " WITH matched_entities AS (";
		
		$where = "";
		
		if (! isset ( $datatypePropertyTable ))
			$datatypePropertyTable = PG_DATAPROPERTY_TABLENAME;
		
		if (! isset ( $objectPropertyTable ))
			$objectPropertyTable = PG_OBJECTPROPERTY_TABLENAME;
		
		if (! $justSelectedTerms && ! empty ( $textsToSearch )) {
			
			if (! is_array ( $textsToSearch ))
				$textsToSearch = array ($textsToSearch );
			
			foreach ( $textsToSearch as $search ) {
				$search = $this->escape_wildcards ( $search );
				
				$includePlus = (strpos ( $search, PG_SEARCH_QUERY_AND_CHAR ) === 0);
				
				if ($includePlus)
					$search = str_replace ( PG_SEARCH_QUERY_AND_CHAR, "", $search );
				
				if (! empty ( $where ))
					$where .= ($includePlus) ? " AND " : " OR ";
				
				if (! $onTheStart)
					$search = "%" . $search;
				
				$search .= "%";
				
				$where .= "object ilike '$search'";
			}
			
			if (isset ( $textsToExclude ))
				foreach ( $textsToExclude as $search ) {
					$search = $this->escape_wildcards ( $search );
					
					if (! empty ( $where ))
						$where .= " AND ";
					
					$where .= "object NOT ilike '%$search%'";
				}
			
			if (! empty ( $where ))
				$where = " where $where escape E'" . str_repeat ( PG_QUERY_FOR_LIKE_ESCAPE_CHAR, 2 ) . "'";
			
		//$query .= "SELECT numeric_subject FROM \"" . $datatypePropertyTable . "\"" . $where;
			$query .= "SELECT subject FROM \"" . $datatypePropertyTable . "\"" . $where;
			
			$objectPropertyTable = "pangea:nomen";
		}
		
		if (! empty ( $selectedTermsIds )) {
			if (is_array ( $selectedTermsIds ))
				$selectedTermsIds = $this->getINValueFromArray ( $selectedTermsIds );
			
			if (! empty ( $where ))
				$query .= "  UNION ALL ";
			
		//$query .= "SELECT " . $selectedTermsIds . " as numeric_subject";
			$query .= "SELECT " . $selectedTermsIds . " as subject";
			
			$objectPropertyTable = PG_OBJECTPROPERTY_TABLENAME;
		
		} elseif (empty ( $where ))
			
			return FALSE;
		/**		
		$query .= ")  SELECT numeric_subject FROM \"" . $objectPropertyTable . "\" WHERE(numeric_object IN (SELECT numeric_subject FROM matched_entities))";
		$query .= "  UNION ALL SELECT numeric_subject FROM matched_entities";
		$query .= ") as results left outer join \"" . PG_CLASS_TABLENAME . "\" as pgc on(results.numeric_subject = pgc.numeric_id)";
		$query .= " group by results.numeric_subject, pgc.tableoid";
		 * */
		
		$query .= ")  SELECT subject FROM \"" . $objectPropertyTable . "\" WHERE(object IN (SELECT subject FROM matched_entities))";
		$query .= "  UNION ALL SELECT subject FROM matched_entities";
		$query .= ") as results left outer join \"" . PG_CLASS_TABLENAME . "\" as pgc on(results.subject = pgc.id)";
		$query .= " group by results.subject, pgc.tableoid";
		
		if (PG_LOG_ACTIVITY)
			$this->logQuery ( "FROM getEntitiesFromDatatypeProperty function:: " . $query );
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	}
	
	function getEntitiesFromPropertyAndValues($property, $values, $isLiteralValue) {
		
		//$query = "select pop.subject, pc.tableoid from \"" . $property . "\" pop left outer join \"" . PG_CLASS_TABLENAME . "\" pc on(pop.numeric_subject = pc.numeric_id)";
		$query = "select pop.subject, pc.tableoid from \"" . $property . "\" pop left outer join \"" . PG_CLASS_TABLENAME . "\" pc on(pop.subject = pc.id)";
		
		$where = '';
		
		if (isset ( $values )) {
			$valuesStream = $this->getINValueFromArray ( $values );
			
			if (sizeof ( $values ) == 1)
				if ($isLiteralValue)
					$where .= "pop.object = '" . $valuesStream . "'";
				else
					//$where .= "pop.numeric_object = " . $valuesStream;
					$where .= "pop.object = " . $valuesStream;
			elseif ($isLiteralValue)
				$where .= "pop.object IN ('" . str_replace ( ",", "','", $valuesStream ) . "')";
			else
				//$where .= "pop.numeric_object IN (" . $valuesStream . ")";
				$where .= "pop.object IN (" . $valuesStream . ")";
			
			if (! empty ( $where ))
				$query .= " where(" . $where . ")";
		}
		
		if (PG_LOG_ACTIVITY)
			$this->logQuery ( "FROM getEntitiesFromPropertyAndValues function:: " . $query );
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	}
	
	function getEntitiesFromRangesView($textsToSearch, $ranges, $onTheStart, $sortedByWeights) {
		
		if (! is_array ( $textsToSearch ))
			$textsToSearch = array ($textsToSearch );
		
		$query = "select subject, object as label, typeof, count(distinct subject) as weight from " . PG_MAGIC_SUBJECT_LABEL_TYPEOF_VIEWNAME;
		
		$where = "";
		
		if (isset ( $textsToSearch ) && ! empty ( $textsToSearch )) {
			$indexCount = sizeof ( $textsToSearch );
			
			if (PG_QUERY_INDEXING_TYPE == 'Gin') {
				for($i = 0; $i < $indexCount; $i ++) {
					$textsToSearch [$i] = $this->escape_wildcards ( $textsToSearch [$i] );
					
					if (! empty ( $textsToSearch [$i] )) {
						if (! empty ( $where ))
							$where .= " OR ";
						
						$where .= "object % '" . $textsToSearch [$i] . "'";
					}
				}
			
			} else {
				for($i = 0; $i < $indexCount; $i ++) {
					$textsToSearch [$i] = $this->escape_wildcards ( $textsToSearch [$i] );
					
					if (! empty ( $textsToSearch [$i] )) {
						if (! $onTheStart)
							$textsToSearch [$i] = "%" . $textsToSearch [$i];
						
						if (! empty ( $where ))
							$where .= " OR ";
						
						$where .= "object ilike '" . $textsToSearch [$i] . "%'";
					}
				}
			}
		}
		
		if (! empty ( $where ))
			$where = "(" . $where . ") AND ";
		
		$where .= "tableoid_v in(select oid from pg_class where relname like '%prefLabel')";
		
		if (isset ( $ranges ) && ! empty ( $ranges )) {
			$where .= " AND ";
			
			if (is_array ( $ranges ))
				$where .= "typeof IN('" . implode ( "','", $ranges ) . "')";
			else
				$where .= "typeof = '" . $ranges . "'";
		}
		
		//if (! empty ( $where ))
		$query .= " where " . $where . " group by subject, typeof, object";
		
		if ($sortedByWeights)
			$query .= " order by weights DESC";
		else
			$query .= " order by label ASC";
		
		if (PG_LOG_ACTIVITY)
			$this->logQuery ( "FROM getEntitiesFromRangeView function:: " . $query );
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	}
	
	function getEntitiesFromRangeViewByProperty($textsToSearch, $property, $ranges, $onTheStart) {
		
		if (! is_array ( $textsToSearch ))
			$textsToSearch = array ($textsToSearch );
		
		if (! isset ( $property ))
			$property = PG_OBJECTPROPERTY_TABLENAME;
		
		$where = "";
		
		if (isset ( $textsToSearch ) && ! empty ( $textsToSearch )) {
			$indexCount = sizeof ( $textsToSearch );
			
			if (PG_QUERY_INDEXING_TYPE == 'Gin') {
				for($i = 0; $i < $indexCount; $i ++) {
					$textsToSearch [$i] = $this->escape_wildcards ( $textsToSearch [$i] );
					
					if (! empty ( $textsToSearch [$i] )) {
						$includePlus = (strpos ( $textsToSearch [$i], PG_SEARCH_QUERY_AND_CHAR ) === 0);
						
						if ($includePlus)
							$textsToSearch [$i] = str_replace ( PG_SEARCH_QUERY_AND_CHAR, "", $textsToSearch [$i] );
						
						if (! empty ( $where ))
							$where .= ($includePlus) ? " AND " : " OR ";
						
						$where .= "obj_slt.object % '" . $textsToSearch [$i] . "'";
					}
				}
			
			} else {
				for($i = 0; $i < $indexCount; $i ++) {
					$textsToSearch [$i] = $this->escape_wildcards ( $textsToSearch [$i] );
					
					if (! empty ( $textsToSearch [$i] )) {
						$includePlus = (strpos ( $textsToSearch [$i], PG_SEARCH_QUERY_AND_CHAR ) === 0);
						
						if ($includePlus)
							$textsToSearch [$i] = str_replace ( PG_SEARCH_QUERY_AND_CHAR, "", $textsToSearch [$i] );

						if (! $onTheStart)
							$textsToSearch [$i] = "%" . $textsToSearch [$i];
							
						if (! empty ( $where ))
							$where .= ($includePlus) ? " AND " : " OR ";
						
						$where .= "obj_slt.object ilike '" . $textsToSearch [$i] . "%'";
					}
				}
			}
		}
		
		if (! empty ( $where ))
			$where .= " AND ";
		
		$where = " WHERE(" . $where . "pgc.relname like '%prefLabel'";
		
		if (! empty ( $ranges )) {
			$where .= " AND ";
			
			if (is_array ( $ranges ))
				$where .= "obj_slt.typeof IN('" . implode ( "','", $ranges ) . "')";
			else
				$where .= "obj_slt.typeof = '" . $ranges . "'";
		}
		
		$where .= ")";
		
		$query = "select pop.subject, sub_pngc.tableoid as typeof, sub_slt.object as label, pop.object, obj_pngc.tableoid as object_typeof, obj_slt.object as object_label";
		$query .= " from \"" . $property . "\" pop";
		$query .= " left outer join subject_label_typeof_mv as sub_slt on(pop.subject = sub_slt.subject)";
		$query .= " left outer join subject_label_typeof_mv as obj_slt on(pop.object = obj_slt.subject)";
		$query .= " left outer join \"" . PG_CLASS_TABLENAME . "\" as sub_pngc on(pop.subject = sub_pngc.id)";
		$query .= " left outer join \"" . PG_CLASS_TABLENAME . "\" as obj_pngc on(pop.object = obj_pngc.id)";
		$query .= " left outer join pg_class as pgc on(obj_slt.tableoid_v = pgc.oid)" . $where;
		
		/*
		 * 
		// Minimalistic form
		$query = "select pop.subject, pop.object";
		$query .= " from \"" . $property . "\" pop";
		$query .= " left outer join subject_label_typeof_mv as sub_slt on(pop.subject = sub_slt.subject)";
		$query .= " left outer join subject_label_typeof_mv as obj_slt on(pop.object = obj_slt.subject)";
		$query .= " left outer join pg_class as pgc on(obj_slt.tableoid_v = pgc.oid)" . $where;
		 * */
		
		if (PG_LOG_ACTIVITY)
			$this->logQuery ( "FROM getEntitiesFromRangeView function:: " . $query );
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	}
	
	function getClassFromEntity($entities) { //describable_entity //descriptor_entity
		$query = $this->getINValueFromArray ( $entities );
		
		//$query = "select tableoid, id from \"" . PG_CLASS_TABLENAME . "\" where numeric_id in ($query)";
		$query = "select tableoid, id from \"" . PG_CLASS_TABLENAME . "\" where id in ($query)";
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	}
	
	function getDocumentsForCollection($biblio, $startDate, $endDate) {
		$query = 'select ownerid, alias, ownertype, itemcollection, SUM(CAST(price AS float)) as price, currency, COUNT(itemid) as items from "items_for_collection_mv"';
		if ($biblio == 'todas')
			$query .= "where to_date(entrydate, 'DD/MM/YYYY') BETWEEN '$startDate' and '$endDate'";
		else
			$query .= " where ownername = '$biblio' and to_date(entrydate, 'DD/MM/YYYY') BETWEEN '$startDate' and '$endDate'";
		
		$query .= " group by ownertype, ownerid, ownername, alias, itemcollection, currency  order by ownertype, alias, itemcollection";
		
		$result = pg_query ( $this->dbConn, $query );
		return $result;
	
	}
	
	function getDocumentsForType($biblio, $startDate, $endDate) {
		$query = 'select ownerid, alias, ownertype, itemtype, COUNT (itemid)as items, SUM(CAST(price AS float)) as price, currency from "items_for_collection_mv"';
		if ($biblio == 'todas')
			$query .= "where to_date(entrydate, 'DD/MM/YYYY') BETWEEN '$startDate' and '$endDate'";
		else
			$query .= " where ownername = '$biblio' and to_date(entrydate, 'DD/MM/YYYY') BETWEEN '$startDate' and '$endDate'";
		
		$query .= " group by ownertype, ownerid, ownername, alias, itemtype, currency order by ownertype, alias, itemtype";
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	
	}
	
	function getDocumentsForEntryConcept($biblio, $startDate, $endDate) {
		$query = 'select ownerid, alias, ownertype, adquisitionway, COUNT (itemid) as items, SUM (CAST(price AS float)) as price, currency from "items_for_collection_mv"';
		if ($biblio == 'todas')
			$query .= "where to_date(entrydate, 'DD/MM/YYYY') BETWEEN '$startDate' and '$endDate'"; //((translate(translate(translate(price, ',', ''), '.', ''), '-', '') ~ '^(-)?[0-9]+$') = true)";
		else
			$query .= " where ownername = '$biblio' and to_date(entrydate, 'DD/MM/YYYY') BETWEEN '$startDate' and '$endDate'"; //and ((translate(translate(translate(price, ',', ''), '.', ''), '-', '') ~ '^(-)?[0-9]+$') = true)";
		

		$query .= " group by ownertype, ownerid, ownername, alias, adquisitionway, currency order by ownertype, alias, adquisitionway";
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	}
	
	function getDocumentsForLibrary($startDate, $endDate) {
		$query = 'select ownerid, alias, ownertype, COUNT (itemid)as items, SUM(CAST(price AS float)) as price, currency from "items_for_collection_mv"
		          group by ownertype, ownerid, ownername, alias, currency 
		           order by ownertype, ownername, alias';
		//$query .= //"where to_date(entrydate, 'DD/MM/YYYY') BETWEEN '$startDate' and '$endDate' 
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	
	}
	
	function getObjectsFromPropertyTableBySubject($subjects, $propertyTable) {
		$objects = array ();
		
		$subjects = $this->getINValueFromArray ( $subjects );
		
		//$query = "select object from \"" . $propertyTable . "\" where(numeric_subject in(" . $subjects . "));";
		$query = "select object from \"" . $propertyTable . "\" where(subject in(" . $subjects . "));";
		
		$results = pg_query ( $this->dbConn, $query );
		
		$rows = pg_fetch_all_columns ( $results, 0 );
		
		if ($rows !== FALSE)
			$objects = $rows;
		
		return $objects;
	}
	
	function getEntitiesBasicProperties($entitiesForBuild) {
		if (! is_array ( $entitiesForBuild ) || sizeof ( $entitiesForBuild ) == 1) {
			//$query = "select pr.subject, pr.tableoid, pr.object, pr.id from \"" . PG_BASIC_PROPERTY_TABLENAME . "\" pr where pr.numeric_subject = $1";
			$query = "select pr.subject, pr.tableoid, pr.text_object as object from \"" . PG_BASIC_PROPERTY_TABLENAME . "\" pr where pr.subject = $1";
			
			$data = array ();
			
			if (is_array ( $entitiesForBuild ))
				$data [] = $entitiesForBuild [0];
			else
				$data [] = $entitiesForBuild;
			
			$result = $this->prepareAndExecuteQuery ( $query, $data );
		
		} else {
			
			$subjects = $this->getINValueFromArray ( $entitiesForBuild );
			
			//$query = "select pr.subject, pr.tableoid, pr.object, pr.id from \"" . PG_BASIC_PROPERTY_TABLENAME . "\" pr where pr.numeric_subject in(" . $subjects . ")";
			$query = "select pr.subject, pr.tableoid, pr.text_object as object from \"" . PG_BASIC_PROPERTY_TABLENAME . "\" pr where pr.subject in(" . $subjects . ")";
			
			$result = pg_query ( $this->dbConn, $query );
		}
		
		return $result;
	}
	
	/**
	 * TODO..
	 * Esta funcion debe cambiar cuando se separen los troncos de propiedades.
	 * */
	function getEntitiesDatatypeProperties($entitiesForBuild) {
		// Hear the columns order is important, don't change!
		$query = "select pr.subject, pr.tableoid, pr.object, pr.id, pr.datatype, pr.lang from \"" . PG_DATAPROPERTY_TABLENAME . "\" pr where pr.subject ";
		
		if (! is_array ( $entitiesForBuild ) || sizeof ( $entitiesForBuild ) == 1) {
			$query .= "= $1";
			
			$data = array ();
			
			if (is_array ( $entitiesForBuild ))
				$data [] = $entitiesForBuild [0];
			else
				$data [] = $entitiesForBuild;
			
			$result = $this->prepareAndExecuteQuery ( $query, $data );
		
		} else {
			
			$subjects = $this->getINValueFromArray ( $entitiesForBuild );
			
			$query .= "in(" . $subjects . ")";
			
			$result = pg_query ( $this->dbConn, $query );
		}
		
		return $result;
	}
	
	function getEntitiesDatatypePropertyData($subject, $object) {
		$query = "select pr.subject, pr.tableoid, pr.object, pr.id, pr.datatype, pr.lang from \"" . PG_DATAPROPERTY_TABLENAME . "\" pr where(pr.subject = $1 and pr.object = $2)";
		
		$data = array ();
		
		$data [] = $subject;
		$data [] = $object;
		
		$result = $this->prepareAndExecuteQuery ( $query, $data );
		
		return $result;
	}
	
	function getEntitiesObjectPropertiesFromObject($objects) {
		$query = "select pr.subject, pr.tableoid as typeof, sltv.object as label, pr.id from \"" . PG_OBJECTPROPERTY_TABLENAME . "\" pr LEFT OUTER JOIN \"" . PG_MAGIC_SUBJECT_LABEL_TYPEOF_VIEWNAME . "\" sltv ON(pr.subject = sltv.subject) where pr.object ";
		
		if (! is_array ( $objects ) || sizeof ( $objects ) == 1) {
			$query .= "= $1";
			
			$data = array ();
			
			if (is_array ( $objects ))
				$data [] = $objects [0];
			else
				$data [] = $objects;
			
			$result = $this->prepareAndExecuteQuery ( $query, $data );
		
		} else {
			
			$subjects = $this->getINValueFromArray ( $objects );
			
			$query .= "in(" . $subjects . ")";
			
			$result = pg_query ( $this->dbConn, $query );
		}
		
		return $result;
	}
	
	function getEntitiesObjectProperties($entitiesForBuild) {
		// Hear the columns order is important, don't change!
		$query = "select pr.subject, pr.tableoid, pr.object, pr.id from \"" . PG_OBJECTPROPERTY_TABLENAME . "\" pr where pr.subject ";
		
		if (! is_array ( $entitiesForBuild ) || sizeof ( $entitiesForBuild ) == 1) {
			$query .= "= $1";
			
			$data = array ();
			
			if (is_array ( $entitiesForBuild ))
				$data [] = $entitiesForBuild [0];
			else
				$data [] = $entitiesForBuild;
			
			$result = $this->prepareAndExecuteQuery ( $query, $data );
		
		} else {
			
			$subjects = $this->getINValueFromArray ( $entitiesForBuild );
			
			$query .= "in(" . $subjects . ")";
			
			$result = pg_query ( $this->dbConn, $query );
		}
		
		return $result;
	}
	
	function getEntitiesProperties($entitiesForBuild) {
		// Hear the columns order is important, don't change!
		//$query = "select pr.subject, pr.tableoid, pr.object, pr.id from \"" . PG_PROPERTY_TABLENAME . "\" pr where pr.numeric_subject <filter>";
		$query = "select pr.subject, pr.tableoid, pr.object, pr.id from \"" . PG_PROPERTY_TABLENAME . "\" pr where pr.subject ";
		
		if (! is_array ( $entitiesForBuild ) || sizeof ( $entitiesForBuild ) == 1) {
			$filterValuePart = "= $1";
			
			$query = str_ireplace ( "<filter>", $filterValuePart, $query );
			
			$data = array ();
			
			if (is_array ( $entitiesForBuild ))
				$data [] = $entitiesForBuild [0];
			else
				$data [] = $entitiesForBuild;
			
			$result = $this->prepareAndExecuteQuery ( $query, $data );
		
		} else {
			
			$subjects = $this->getINValueFromArray ( $entitiesForBuild );
			
			$filterValuePart = "in(" . $subjects . ")";
			
			$query = str_ireplace ( "<filter>", $filterValuePart, $query );
			
			$result = pg_query ( $this->dbConn, $query );
		}
		
		return $result;
	}
	
	function getPersistenQueryTriples($entities, $buildSize) {
		$results = array ();
		
		if (! empty ( $entities )) {
			$entities = $this->getINValueFromArray ( $entities );
			
			$query = "select entityID, triples from \"persistenTriples\" where (entityID in($entities) AND \"build Size\" >= $buildSize)";
			
			$result = pg_query ( $this->dbConn, $query );
			
			$rows = pg_fetch_all ( $result );
			
			if ($rows !== FALSE)
				$results = $rows;
		}
		
		return $results;
	}
	
	function getPersistenQuery($pqCode) {
		$query = "select * from \"persistenQueries\" where (text = '$pqCode')";
		
		$result = pg_query ( $this->dbConn, $query );
		
		$rows = pg_fetch_array ( $result );
		
		return $rows;
	}
	
	function getPropertyByEntityIds($entitiesForBuild) { //poner en la consulta pangea:Property
		$entitiesForBuild = $this->sdao->getINValueFromArray ( $entitiesForBuild );
		
		//$query = "select pr.subject, pr.object, pr.tableoid, pr.id from \"" . PG_PROPERTY_TABLENAME . "\" pr where pr.numeric_subject in ('$entitiesForBuild')";
		$query = "select pr.subject, pr.object, pr.tableoid, pr.id from \"" . PG_PROPERTY_TABLENAME . "\" pr where pr.subject in ('$entitiesForBuild')";
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	}
	
	function getPropertiesRelname4MediumBuild() {
		$query = "select pgcl.relname from pg_class pgcl inner join pg_inherits pgin on pgcl.oid = pgin.inhrelid where(pgin.inhparent = (select oid from pg_class where relname = '" . PG_BASIC_PROPERTY_TABLENAME . "'))";
		
		if (PG_LOG_ACTIVITY)
			$this->logQuery ( "FROM getPropertiesRelname4MediumBuild function:: " . $query );
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	}
	
	function getPropertiesRelname4ShortBuild() {
		$query = "select pgcl.relname from pg_class pgcl inner join pg_inherits pgin on pgcl.oid = pgin.inhrelid where(pgin.inhparent in (select oid from pg_class where relname in ('" . PG_ENTITY_NAMING_PROPERTY_TABLENAME . "', '" . PG_CONCEPT_NAMING_PROPERTY_TABLENAME . "')))";
		
		if (PG_LOG_ACTIVITY)
			$this->logQuery ( "FROM getPropertiesRelname4ShortBuild function:: " . $query );
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	}
	
	function getEntitiesFromPropertyByEntityIds($entitiesForBuild) { //poner en la consulta pangea:Property
		//$query = "select pr.subject, pr.object, pcs.tableoid from \"" . PG_OBJECTPROPERTY_TABLENAME . "\" pr left outer join \"" . PG_CLASS_TABLENAME . "\" pcs on(pr.numeric_object = pcs.numeric_id) where pr.numeric_subject in ($entitiesForBuild)";
		//$query = "select pr.object as subject, pcs.tableoid as typeof, count(pr.object) as weight from \"" . PG_OBJECTPROPERTY_TABLENAME . "\" pr left outer join \"" . PG_CLASS_TABLENAME . "\" pcs on(pr.object = pcs.id) where pr.subject in ($entitiesForBuild) group by pr.object, pcs.tableoid";
		$query = "select subject, typeof, object as label, count(object) as weight from \"" . PG_MAGIC_SUBJECT_LABEL_TYPEOF_VIEWNAME . "\" where ";
		$query .= " subject in (select pr.object from \"" . PG_OBJECTPROPERTY_TABLENAME . "\" pr where pr.subject in ($entitiesForBuild))";
		$query .= " group by subject, typeof, object";
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	}
	
	function getNamesByEntityId($entityIds) { //siempre viene un arreglo 
		$entityNames = array ();
		
		if (! isset ( $entityIds ) || empty ( $entityIds ))
			return $entityNames;
		
		elseif (! is_array ( $entityIds ))
			$entityIds = array ($entityIds );
		
		$entityIds = $this->getINValueFromArray ( $entityIds );
		
		$query = "select subject, typeof, object, tableoid_v from \"" . PG_MAGIC_SUBJECT_LABEL_TYPEOF_VIEWNAME . "\" where subject in ($entityIds)";
		
		$result = pg_query ( $this->dbConn, $query );
		
		return $result;
	}
	
	function identifyAmbiguousTerms($terms) {
		$ambiguous = array ();
		
		$terms = str_replace ( ",", "','", str_replace ( ' ', '', $this->getINValueFromArray ( $terms ) ) );
		
		$terms = $this->getLowercaseStream ( $terms );
		
		$query = "select id_concept, concept, parent from ambiguous where replace(lower(concept), ' ', '') in ('$terms')";
		
		$result = pg_query ( $this->dbConn, $query );
		if ($result) {
			$total = pg_num_rows ( $result );
			for($i = 0; $i < $total; $i ++) {
				$row = pg_fetch_array ( $result );
				$ambiguous [$row ['concept']] [] = array ('id' => $row ['id_concept'], 'parent' => $row ['parent'] );
			}
		}
		
		return $ambiguous;
	}
	
	function insertPersistenQuery($data, $forced = FALSE) {
		$result = 0;
		
		if (! isset ( $data ['text'] ))
			return $result;
		
		$pquery = $data ['text'];
		
		if (! $forced)
			$rows = $this->getPersistenQuery ( $pquery );
		
		if ($forced || ! $rows) {
			$paramsIndex = 1;
			$paramsIndexes = '';
			$paramsColumns = '';
			
			foreach ( $data as $key => $value ) {
				$dataStream [] = $value;
				
				if (! empty ( $paramsColumns )) {
					$paramsColumns .= ', ';
					$paramsIndexes .= ', ';
				}
				
				$paramsColumns .= '"' . $key . '"';
				$paramsIndexes .= '$' . $paramsIndex;
				
				$paramsIndex ++;
			}
			
			$query = 'INSERT INTO "persistenQueries"(' . $paramsColumns . ') VALUES (' . $paramsIndexes . ');';
			
			$result = $this->prepareAndExecuteQuery ( $query, $dataStream );
			
			if ($result) {
				$rows = $this->getPersistenQuery ( $pquery );
				
				if (is_array ( $rows ))
					$result = $rows ['id'];
			}
		} else
			$result = $rows ['id'];
		
		return $result;
	}
	
	function insertPersistenTriples($data) {
		$result = 0;
		
		$paramsIndex = 1;
		$paramsIndexes = '';
		$paramsColumns = '';
		
		foreach ( $data as $key => $value ) {
			$dataStream [] = $value;
			
			if (! empty ( $paramsColumns )) {
				$paramsColumns .= ', ';
				$paramsIndexes .= ', ';
			}
			
			$paramsColumns .= '"' . $key . '"';
			$paramsIndexes .= '$' . $paramsIndex;
			
			$paramsIndex ++;
		}
		
		$query = 'INSERT INTO "persistenTriples"(' . $paramsColumns . ') VALUES (' . $paramsIndexes . ');';
		
		$result = $this->prepareAndExecuteQuery ( $query, $dataStream );
		
		return $result;
	}
	
	function is_aBNode($id) {
		$query = "select * from is_bnode($1);";
		
		$data = array ();
		
		$data [] = $id;
		
		$result = $this->prepareAndExecuteQuery ( $query, $data );
		
		return $result;
	}
}
?>