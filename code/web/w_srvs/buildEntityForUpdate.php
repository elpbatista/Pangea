<?php
session_start ();
include_once dirname ( __FILE__ ) . '/../../search/services/SearchService.php';

$idEntity = $_REQUEST ['idEntity'];

if (isset ( $idEntity )) {
	$language = !isset ( $_REQUEST ['_lng'] ) ? "sp" : $_REQUEST ['_lng'];
	$concept = !isset ( $_REQUEST ['concept'] )? '': $_REQUEST ['concept'];
	$size = !isset ( $_REQUEST ['_size'] ) ? "m" : $_REQUEST ['_size'];
	$searchSvr = new SearchService ();

	$resultsJSON = json_encode ( $searchSvr->buildEntityForUpdate( $idEntity, $concept, $language, $size ) );
	
	echo $resultsJSON;
}
?>