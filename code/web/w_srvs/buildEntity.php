<?php
session_start ();
include_once dirname ( __FILE__ ) . '/../../search/services/SearchService.php';

$id = $_REQUEST ['id'];

if (isset ( $id )) {
	$language = ! isset ( $_REQUEST ['_lng'] ) ? "sp" : $_REQUEST ['_lng'];
	$size = ! isset ( $_REQUEST ['_ty'] ) ? "m" : $_REQUEST ['_ty'];
	$modelName = ! isset ( $_REQUEST ['_model'] ) ? '' : $_REQUEST ['_model'];
	
	$searchSvr = new SearchService ();
	/*
	if (! $_SESSION ['searchService']) {
		$_SESSION ['searchService'] = serialize ( $searchSvr );
	} else {
		$searchSvr = unserialize ( $_SESSION ['searchService'] );
	}
	*/
	$resultsJSON = json_encode ( $searchSvr->buildEntities ( $id, $modelName, $size, $language ) );
	
	echo $resultsJSON;
}
?>