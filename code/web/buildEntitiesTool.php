<?php
include_once dirname ( __FILE__ ) . '/../search/services/SearchService.php';

$searchSvr = new SearchService ();

$offset = 0;

$itemsCount = 10 * PG_DEFAULT_PAGE_SIZE;

$dao = new SearchDAO ();

$results = $dao->getCoreEntitiesByPage ( $offset, $itemsCount );

if (isset($_REQUEST['_ty']))
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

while ( pg_num_rows ( $results ) > 0 ) {
	$ids = pg_fetch_all_columns ( $results, 0 );
	
	$returnObjects = $searchSvr->build ( $ids, $size );
	
	if (isset ( $returnObjects ['2beCached'] ) && ! empty ( $returnObjects ['2beCached'] ))
		$searchSvr->saveInCache ( $returnObjects ['2beCached'] );
	
	$offset += PG_DEFAULT_PAGE_SIZE + 1;
	
	$results = $dao->getCoreEntitiesByPage ( $offset, $itemsCount );
}
?>