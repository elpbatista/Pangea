<?php
include_once dirname ( __FILE__ ) . '/../../search/services/SearchService.php';
include_once dirname ( __FILE__ ) . '/../../authentication/services/AuthenticationService.php';

if (PG_APP_DEBUG_MODE) {
	/*
	 * Pendings for test:
	 * 
	 * http://dev.pangea.ohc.cu/?_md5=5cd26809f86dceaa49ef27404161d335&_ff=pangea:hasForm&_fv=92458
	 * http://dev.pangea.ohc.cu/?_md5=7b6671fdae0cd5c4f3a1d2235feb3641&_ff=pangea:hasForm&_fv=92438
	 * http://dev.pangea.ohc.cu/?_a=/m&_md5=d869d42097d94b97cee1bff59817c997&_ids=97335
	 * http://dev.pangea.ohc.cu/?_t=nacion cubana&_a=/l
	 * 
	 * {"id":"bf8635861dbab3faf0273ecc52a7cd6f","count":13956,"description":{"frbr:Manifestation":{"count":5548,"label":"Manifestation","typeof":"owl:Class"},"frbr:Work":{"count":4981,"label":"Work","typeof":"owl:Class"},"frbr:Item":{"count":1085,"label":"Item","typeof":"owl:Class"},"frbr:ResponsibleEntity":{"count":836,"label":"ResponsibleEntity","typeof":"owl:Class"},"frbr:CorporateBody":{"count":679,"label":"CorporateBody","typeof":"owl:Class"},"frbr:Expression":{"count":331,"label":"Expression","typeof":"owl:Class"},"skosxl:Label":{"count":119,"label":"Label","typeof":"owl:Class"},"frbr:Concept":{"count":115,"label":"Concept","typeof":"owl:Class"},"frbr:Event":{"count":113,"label":"Event","typeof":"owl:Class"},"pangea:Serial":{"count":75,"label":"Serial","typeof":"owl:Class"},"frbr:Object":{"count":51,"label":"Object","typeof":"owl:Class"},"frbr:Person":{"count":7,"label":"Person","typeof":"owl:Class"},"pangea:Subject":{"count":7,"label":"Subject","typeof":"owl:Class"},"frbr:Place":{"count":6,"label":"Place","typeof":"owl:Class"},"pangea:AdquisitionWay":{"count":2,"label":"Adquisition Way","typeof":"owl:Class"},"pangea:Collection":{"count":1,"label":"Collection","typeof":"owl:Class"}}}
	 * */
	
	//$_REQUEST ['_t'] = 'editorial=Abril vuelo';
	//$_REQUEST ['_t'] = 'editorial=Abril';
	//$_REQUEST ['_t'] = 'tit=Colonial';
	//$_REQUEST ['_t'] = 'tit="Revolucion y Cultura"+ tipo=Revista';
	//$_REQUEST ['_t'] = 'Libro + tipo=Revista';
	$_REQUEST ['_t'] = 'cuba';
	
	//$_REQUEST ['_ff'] = "pangea:hasForm";
	//$_REQUEST ['_fv'] = "92438";
	//$_REQUEST ['_md5'] = "7b6671fdae0cd5c4f3a1d2235feb3641";
	//$_REQUEST ['_md5'] = "98a8c1123b8f397288a4bda429d757da";	
	//$_REQUEST ['_md5'] = "bf8635861dbab3faf0273ecc52a7cd6f"; // nacion cubana	
	//$_REQUEST ['_md5'] = "bf8635861dbab3faf0273ecc52a7cd6f"; // "camilo cienfuegos"
	//$_REQUEST ['_md5'] = "dba05f266401b2a7307725f3cfaab421"; // "editorial=Abril"
	

	//$_REQUEST ['_ids'] = "1525101,630351,2368359,2882563,370518,1204564,2590599,1214917,1022608,3066754";
	//$_REQUEST ['_ids'] = "97335"; // Escuelas militares Camilo Cienfuegos
	

	$_REQUEST ['_a'] = "/l";
	//$_REQUEST ['_rg'] = "pangea:DescriptorEntity";
	$_REQUEST ['_rg'] = "frbr:Place";

	//$_REQUEST ['_ctg'] = "pangea:AdquisitionWay";
//$_REQUEST ['_ctg'] = "frbr:Concept";
//$_REQUEST ['_ctg'] = "frbr:Manifestation";


//$_REQUEST ['_ids'] = "_518d820fb1bd7";
//$_REQUEST ['frbr:exemplar'] [] = "247605";
//$_REQUEST ['frbr:exemplar'] [] = "4549582";


//$_REQUEST ['_ty'] = "/l";


//$_REQUEST ['_ids'] = "367556,226088,2363835,2366283,2377419,2378215,2383808,2386263,2441565,385067,394001,802419,806497,808425,822265,830019,830583,854334,858839,874590";


//$_REQUEST ['_ids'] = "_5195150b0a608";	
//$_REQUEST ['skos:prefLabel'] [] = "Texto";
//$_REQUEST ['rdf:type'] = "frbr:Text";
//$_REQUEST ['frbr:creator'] [] = "2028874";
//$_REQUEST ['frbr:creator'] [] = "1688043";
}
/*
 * Actions:
 * 
 *  - Retrieve Schema (/sch)
 *  		params:	none
 *    
 *  - Statistics (/st)
 *  		params: none
 *  
 *  - List (/l)
 *  		params: property ranges (_rg) | properties (_pr)
 *  				[
 *  				search terms (_t)  
 *  				page (_pg)
 *  				page size (_ic)
 *  				language (_lng)
 *  				]  
 *  - Search (/s)
 *  		params: search terms (_t)
 *  				[
 *  				inference level (_n)  
 *  				page (_pg)
 *  				page size (_ic)
 *  				language (_lng)
 *  				]
 *  
 *  		CCL:
 *  			<search-text>	::= <search-term> | <search_term> [+] <search-text>
 *  			<search-term>	::= <plain-text-term> | <key-value-term> | <exact-term> | <exclude-term> | <reserved-term>
 *  			<plain-text> 	::= <expression>
 *  			<key-value> 	::= <key> = <expression>
 *  			<exact-term> 	::= "<plan-text>"
 *  			<expression> 	::= literal | literal " " <expression>
 *  			<exclude-term>	::= -literal
 *  			<reserved-term>	::= "[" <CLASS> "]" | "[" <RESERVED WORD> "]"
 *  			<key>			::= <PROPERTY> | <SEUDO-PROPERTY>
 *  
 *  - Name (/n)
 *  		params: entity (ies) (_id/_ids)
 *  
 *  - Build (/b)
 *  		params: entity (ies) (_id/_ids)
 *					[
 *					size (_ty)
 *					language (_lng)
 *					all levels (_r)
 *					]
 *
 *  - Build paging by an entity property (/bp)
 *  		params: entity (ies) (_id/_ids)
 *					[
 *					size (_ty)
 *					filter properties (_prs)
 *  				page (_pg)
 *  				page size (_ic)
 *					language (_lng)
 *					all levels (_r)
 *					]
 *      
 *  - Build for edit (/bu)
 *  		params: entity (ies) (_id/_ids)
 *					size (_ty) = /l
 *   
 *  - Insert (/i)
 *  		params: entity (ies) (_id/_ids)
 *  				{fieldName,fieldValue}+
 *  
 *  - Update (/u)
 *  		params: entity (ies) (_id/_ids)
 *  				{fieldName,fieldValue}+
 *  
 *  - Delete (/d)
 *  		params: entity (_id)
 *  
 *  - Filter by Property(/f):
 *   		params: persisten query code (_md5)
 *   				filter field (_ff)
 *					field value (_fv)   
 *					[
 *					filter cluster ID (_ctg)
 *  				page (_pg)
 *  				page size (_ic)
 *					language (_lng)
 *					all levels (_r)
 *					]   
 *   
 *  (en este caso la uri define tipo de filtrado)
 *  
 *  - Filter by Cluster (/fc):
 *   		params: persisten query code (_md5)
 *					[
 *   				filter cluster ID (_ctg)
 *  				page (_pg)
 *  				page size (_ic)
 *					language (_lng)
 *					]
 *  
 *  - Paging Cluster (/cp): (deprecated)
 *   		params: filter cluster ID (_ctg)
 *   				[
 *  				page (_pg)   
 *   				]
 *     
 *  - Order (/o)
 * 			params: property uri (_of : order fild)
 * 					direction (_d :: /a {default} | /d)
 * 
 *  - Authentication Login (/a)
 * 			params: user name (_u)
 * 					password (_pwd)
 * 					[
 * 					referer (_ref)
 * 					] 
 * 
 *  - Authentication Login (/ot)
 * 			params: [referer (_ref)]
 * 
 *  Se puede ordenar lo filtrado??? Es algo que debemos analizar.
 * */

$language = ! isset ( $_REQUEST ['_lng'] ) ? PG_DEFAULT_LANGUAGE : $_REQUEST ['_lng'];
$page = ! isset ( $_REQUEST ['_pg'] ) ? PG_DEFAULT_INITIAL_PAGE_INDEX : $_REQUEST ['_pg'];
$pageSize = ! isset ( $_REQUEST ['_ic'] ) ? PG_DEFAULT_PAGE_SIZE : $_REQUEST ['_ic'];

$text = NULL;

if (isset ( $_REQUEST ['_t'] ))
	$text = $_REQUEST ['_t'];

$md5SearchString = NULL;

if (isset ( $_REQUEST ['_md5'] ))
	$md5SearchString = $_REQUEST ['_md5'];

$returnObjects = array ();

$deleteBefore = false;
$checkExistence = false;

/*
 * Getting action
 * */
$action = '';
if (isset ( $_REQUEST ['_a'] ))
	$action = $_REQUEST ['_a'];

unset ( $_REQUEST ['_a'] );

if (empty ( $action )) {
	$action = '/s';
	if ((isset ( $_REQUEST ['_id'] )) || (isset ( $_REQUEST ['_ids'] )))
		$action = '/b';
	
	elseif (isset ( $_REQUEST ['_pr'] ) || isset ( $_REQUEST ['_rg'] ))
		$action = '/l';
	
	elseif (isset ( $md5SearchString ) && ! empty ( $md5SearchString )) {
		if (isset ( $_REQUEST ['_ff'] ) || isset ( $_REQUEST ['_fv'] ))
			$action = '/f';
		
		elseif (isset ( $_REQUEST ['_ctg'] ))
			$action = '/fc';
	} elseif (! isset ( $_REQUEST ['_t'] ) || empty ( $_REQUEST ['_t'] ))
		$action = '';
}

if (! empty ( $action )) {
	/**
	 * TODO..
	 * 
	 * El metodo factory retorna instancias nuevas de esta clase. Quizas no estoy estableciendo bien la session y 
	 * por eso no puedo hacer persistente a esta clase dentro de la misma sesion. 
	 *
	 */
	$searchSvr = new SearchService ();
	/*
	if (isset ( $_SESSION ["searchSrv"] ))
		$searchSvr = unserialize ( $_SESSION ["searchSrv"] );
	else {
		$searchSvr = new SearchService ();
		
		$_SESSION ["searchSrv"] = serialize ( $searchSvr );
	}
	//$searchSvr = SearchService::factory ( 'searchService' );
 
	 * */
	
	switch ($action) {
		case "/sch" : // Get DB Schema
			

			$response = $searchSvr->getSchema ();
			
			break;
		
		case "/s" : // Search
			

			if ((isset ( $text ) && ! empty ( $text )) || (isset ( $md5SearchString ) && ! empty ( $md5SearchString )) || isset ( $_REQUEST ['_ids'] )) {
				$inferenceLevel = ! isset ( $_REQUEST ['_n'] ) ? PG_DEFAULT_INFERENCE_LEVEL : $_REQUEST ['_n'];
				
				$selectedTermsIds = array ();
				
				$justSelectedTermsIds = FALSE;
				
				if (isset ( $_REQUEST ['_ambiguos'] ))
					$selectedTermsIds = explode ( ',', $_REQUEST ['_ambiguos'] );
				elseif (isset ( $_REQUEST ['_ids'] )) {
					$justSelectedTermsIds = TRUE;
					
					$selectedTermsIds = explode ( ',', $_REQUEST ['_ids'] );
				}
				
				$returnObjects = $searchSvr->search ( $text, $md5SearchString, $selectedTermsIds, $justSelectedTermsIds, $inferenceLevel, $page, $pageSize, $language );
				
				//TODO..
				/* 
				* Aqui se debe invocar a la clase de la Capa de Presentacion asociada 
				* al modo de visualizacion solicitado (JSON, HTML, XML, Plain Text)
				* */
				$response = $returnObjects ['response'];
				
				$deleteBefore = true;
			}
			
			break;
		
		case "/l" : // List
			

			$requestProperties = array ();
			$requestRanges = array ();
			
			if (isset ( $_REQUEST ['_pr'] ))
				$requestProperties = explode ( ',', $_REQUEST ['_pr'] );
			
			elseif (isset ( $_REQUEST ['_rg'] ))				
				$requestRanges = explode ( ',', $_REQUEST ['_rg'] );
			
			$returnObjects = $searchSvr->searchListed ( $requestProperties, $requestRanges, $text, $page, $pageSize, $language );
			
			$response = $returnObjects ['response'];
			
			break;
		
		case "/m" : // Merge
			

			$searchsInfo = array ();
			$plainQuery = "";
			
			if (isset ( $md5SearchString ) && ! empty ( $md5SearchString )) {
				$searchsInfo ['_md5'] = explode ( ',', $md5SearchString );
				
				$plainQuery .= "md5::" . str_ireplace ( ',', '+', $md5SearchString );
			}
			
			if (isset ( $_REQUEST ['_ids'] )) {
				if (isset ( $_REQUEST ['_ambiguos'] ))
					$searchsInfo ['selectedIds'] = explode ( ',', $_REQUEST ['_ambiguos'] );
				
				elseif (isset ( $_REQUEST ['_ids'] ))
					$searchsInfo ['selectedIds'] = explode ( ',', $_REQUEST ['_ids'] );
				
				if (! empty ( $plainQuery ))
					$plainQuery .= "+";
				
				$plainQuery .= "selectedIds::" . implode ( '+', $searchsInfo ['selectedIds'] );
			}
			
			if (isset ( $text ) && ! empty ( $text )) {
				$searchsInfo ['text'] = $text;
				
				if (! empty ( $plainQuery ))
					$plainQuery .= "+";
				
				$plainQuery .= "text::(" . $searchsInfo ['text'] . ")";
			}
			
			$returnObjects = $searchSvr->searchMerge ( $searchsInfo, $plainQuery, $language );
			
			$response = $returnObjects ['response'];
			
			break;
		
		case "/b" : // Build
		case "/bu" : // Build for update
		case "/bp" : // Build by page
			

			$ids = ! isset ( $_REQUEST ['_id'] ) ? $_REQUEST ['_ids'] : $_REQUEST ['_id'];
			
			$allLevels = FALSE;
			
			if (isset ( $ids ) && ! empty ( $ids )) {
				$ids = explode ( ",", $ids );
				
				$size = PG_DB_MEDIUM_BUILD_LEVEL;
				
				if (isset ( $_REQUEST ['_ty'] )) {
					$size = $_REQUEST ['_ty'];
					
					switch ($size) {
						case PG_SHORT_BUILD_LEVEL :
							$size = PG_DB_SHORT_BUILD_LEVEL;
							break;
						
						case PG_LARGE_BUILD_LEVEL :
							$size = PG_DB_LARGE_BUILD_LEVEL;
							break;
						
						default :
							$size = PG_DB_MEDIUM_BUILD_LEVEL;
					}
				}
				
				$filterProperties = array ();
				
				if (isset ( $_REQUEST ['_prs'] ))
					$filterProperties = explode ( ",", str_ireplace ( ' ', '', $_REQUEST ['_prs'] ) );
					
				/*
				 * Esto desactiva la paginacion por propiedad y permite que se entreguen todos los valores asociados a
				 * la misma. 
				 * */
				$page = 0;
				
				$returnObjects = $searchSvr->build ( $ids, $size, $language, $filterProperties, $page, $pageSize, $allLevels );
				
				$response = $returnObjects ['response'];
				$deleteBefore = true;
			}
			
			break;
		
		case "/n" : // Naming Entity		
			

			$ids = ! isset ( $_REQUEST ['_id'] ) ? $_REQUEST ['_ids'] : $_REQUEST ['_id'];
			
			if (isset ( $ids ) && ! empty ( $ids )) {
				$ids = explode ( ",", $ids );
				
				$returnObjects = $searchSvr->getNamedEntities ( $ids );
				
				$response = $returnObjects ['response'];
			}
			
			break;
		
		// deprecated..
		case "/cp" : // Cluster Paging		
			

			$clusterID = ! isset ( $_REQUEST ['_ctg'] ) ? '' : $_REQUEST ['_ctg'];
			
			$returnObjects = $searchSvr->getClusterInPage ( $md5SearchString, $clusterID, $page );
			
			$response = $returnObjects ['response'];
			
			break;
		
		// deprecated..
		case "/lp" : // Paging List		
			

			$filterCategoryID = ! isset ( $_REQUEST ['_ctg'] ) ? '' : $_REQUEST ['_ctg'];
			
			/*
			$returnObjects = $searchSvr->getTriplesFilteredByCluster ( $md5SearchString, $filterCategoryID, $page, $pageSize, $language );
			
			$response = $returnObjects ['response']; 
			 * */
			
			break;
		
		case "/fc" : // Filter by Cluster or Paging		
			

			if (isset ( $md5SearchString ) && ! empty ( $md5SearchString )) {
				// Cluster ID
				$filterCategoryID = ! isset ( $_REQUEST ['_ctg'] ) ? '' : $_REQUEST ['_ctg'];
				
				$returnObjects = $searchSvr->getEntitiesFilteredByCluster ( $md5SearchString, $filterCategoryID, $page, $pageSize, $language );
				
				$response = $returnObjects ['response'];
			}
			
			break;
		
		case "/f" : // Filter
			

			// Cluster ID
			$filterCategoryID = ! isset ( $_REQUEST ['_ctg'] ) ? PG_DEFAULT_CLUSTER_ID : $_REQUEST ['_ctg'];
			// Filter property
			$filterField = ! isset ( $_REQUEST ['_ff'] ) ? '' : $_REQUEST ['_ff'];
			// Filter value
			$filterValue = ! isset ( $_REQUEST ['_fv'] ) ? '' : $_REQUEST ['_fv'];
			
			if (! empty ( $md5SearchString ) && (! empty ( $filterField ) || ! empty ( $filterValue ))) {
				
				$filters = array ();
				
				if (empty ( $filterField ))
					$filterField = 'none';
				
				if (! empty ( $filterValue ))
					$filters [$filterField] = explode ( ',', $filterValue );
				else
					$filters [$filterField] = NULL;
				
				$returnObjects = $searchSvr->searchFiltered ( $md5SearchString, $filterCategoryID, $filters, $page, $pageSize, $language );
				
				$response = $returnObjects ['response'];
				$checkExistence = true;
			}
			
			break;
		
		case "/o" : // Filter or Paging
			

			// Cluster ID
			$sortCategoryID = ! isset ( $_REQUEST ['_ctg'] ) ? '' : $_REQUEST ['_ctg'];
			// Filter property
			$sortField = ! isset ( $_REQUEST ['_ff'] ) ? NULL : $_REQUEST ['_ff'];
			// Filter value
			$sortValue = ! isset ( $_REQUEST ['_fv'] ) ? NULL : $_REQUEST ['_fv'];
			
			if (! empty ( $md5SearchString ) && ! empty ( $filterField )) {
				$returnObjects = $searchSvr->searchFiltered ( $md5SearchString, $sortCategoryID, $sortField, $sortValue, $page, $pageSize, $language );
				
				$response = $returnObjects ['response'];
				$checkExistence = true;
			}
			
			break;
		
		case "/i" : // Insert an entity
		case "/u" : // Update an entity			
			

			if (isset ( $_REQUEST ['_ids'] )) {
				$idEntity = $_REQUEST ['_ids'];
				$entityType = isset ( $_REQUEST ['rdf:type'] ) ? $_REQUEST ['rdf:type'] : NULL;
				
				unset ( $_REQUEST ['_ids'] );
				unset ( $_REQUEST ['rdf:type'] );
				
				$crudRecords = $searchSvr->getEntityCRUDForSave ( $idEntity, $entityType, $_REQUEST );
				
				$returnObjects = $searchSvr->saveEntity ( $crudRecords );
				
				$response = $returnObjects ['response'];
			
	//$response ['_ids'] = $idEntity;
			//$response ['rdf:type'] = $entityType;
			//$response ['_params'] = $_REQUEST;
			}
			
			break;
		
		case "/d" : // Delete an entity		
			

			$idEntity = ! isset ( $_REQUEST ['_id'] ) ? $_REQUEST ['_ids'] : $_REQUEST ['_id'];
			
			if (isset ( $idEntity ) && ! empty ( $idEntity ))
				$searchSvr->deleteEntity ( $idEntity );
			
			break;
		
		case "/st" : // Get Pangea Stats		
			

			$fDate = (isset ( $_REQUEST ['_fd'] )) ? $_REQUEST ['_fd'] : '';
			$tDate = (isset ( $_REQUEST ['_td'] )) ? $_REQUEST ['_td'] : '';
			
			$returnObjects ['forCollection'] = $searchSvr->documentsForCollection ( $fDate, $tDate );
			$returnObjects ['forType'] = $searchSvr->documentsForType ( $fDate, $tDate );
			$returnObjects ['forEntryConcept'] = $searchSvr->documentsForEntryConcept ( $fDate, $tDate );
			//$returnObjects ['forLibrary'] = $searchSvr->DocumentsForLibrary ( $fDate, $tDate );
			$response = $returnObjects;
			
			break;
		
		case "/a" : // Authentication login		
			

			$user = strtolower ( $_REQUEST ['_u'] );
			$pwd = $_REQUEST ['_pwd'];
			
			/* * */
			
			if (isset ( $user ) && isset ( $pwd ) && ! empty ( $user ) && ! empty ( $pwd )) {
				/**
				 * La variable $_SERVER ['HTTP_REFERER'] es rellenada por el browser usado por 
				 * el cliente. No es de uso obligatorio por lo tanto no se puede garantizar su
				 * presencia dentro del header. Por esto usamos una variable de formulario donde
				 * se guarda la pagina desde donde se viene...
				 * */
				$referer = $_REQUEST ['_ref'];
				//$referer = $_SERVER['HTTP_REFERER'];				
				

				$authenticationSvr = new AuthenticationService ();
				
				$validUser = $authenticationSvr->user_pass_valid ( $user, $pwd );
				
				if ($validUser) {
					session_start ();
					$_SESSION ['currentUser'] = $user;
					$_SESSION ['logged'] = $validUser;
					//$_SESSION ['currentUserRoles'] = $authenticationSvr->get_user_roles ( $user );
					$_SESSION ['userPermissions'] = $authenticationSvr->fill_permissions_array ( $user ); //los permisos del user
					

					if (isset ( $_REQUEST ['_ref'] ) && ! empty ( $_REQUEST ['_ref'] )) {
						$referer = $_REQUEST ['_ref'];
					} else {
						//$referer = 'http://' . $_SERVER ['HTTP_HOST'] . $_SERVER ['PHP_SELF'];
						//$referer = str_replace ( substr ( strrchr ( $referer, '/' ), 1 ), '', $referer );
						$referer = "/index.php";
					}
					header ( 'Location: ' . $referer );
					break;
				} else { //user invalid
					unset ( $_SESSION ['logged'] );
					unset ( $_SESSION ['currentUser'] );
					unset ( $_SESSION ['userPermissions'] );
					
					//mostrar el formulario de login de nuevo con un mensaje de error.
					

					header ( "Location: http://" . $_SERVER ['HTTP_HOST'] . "/login.php?&_invalid='true'" );
					break;
				}
				
				if (isset ( $referer ) && ! empty ( $referer ))
					header ( 'Location: http://' . $referer );
			} else {
				//algún campo llegó vacío
				header ( "Location: http://" . $_SERVER ['HTTP_HOST'] . "/login.php?&_empty='true'" );
				break;
			}
			
			break;
		
		case "/ot" : // Authentication logout
			

			unset ( $_SESSION ['logged'] );
			unset ( $_SESSION ['currentUser'] );
			unset ( $_SESSION ['userPermissions'] );
			
			/** TODO 
			 * Esto es temporal, recuerda que el gateway puede estar corriendo en un server
			 * independiente al server de publicacion
			 */
			/*if (isset ( $_REQUEST ['_ref'] ) && ! empty ( $_REQUEST ['_ref'] ))
				$referer = $_REQUEST ['_ref'];
			else {
				$referer = $_SERVER ['HTTP_HOST'] . $_SERVER ['PHP_SELF'];
				$referer = str_replace ( substr ( strrchr ( $referer, '/' ), 1 ), '', $referer );
			}*/
			
			header ( 'Location: http://' . $_REQUEST ['_ref'] );
			
			break;
	}
	
	if (isset ( $response ) && ! empty ( $response ))
		echo json_encode ( $response );
	
	if (PG_SAVE_INFO_RETRIEVED && isset ( $returnObjects ['2beCached'] ) && ! empty ( $returnObjects ['2beCached'] ))
		$searchSvr->saveInCache ( $returnObjects ['2beCached'] );
	
	unset ( $searchSvr );
} else {
	// TODO..
	// Este mensaje por supuesto debe cambiar :)
	$response ['ERROR'] = "You must select a valid option!!";
	
	echo json_encode ( $response );
}
?>