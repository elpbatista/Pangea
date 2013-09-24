<?php
session_start ();

include_once dirname ( __FILE__ ) . '/../../search/services/SearchService.php';

$text = $_REQUEST ['_t'];

if (isset ( $text )) {
	//$text = to_utf8($text);
	//die(unicode_decode($text));
	// Inference Level of the search
	$inferenceLevel = ! isset ( $_REQUEST ['_n'] ) ? 1 : $_REQUEST ['_n'];
	
	// Target page to retrieve from the search Result set
	$page = ! isset ( $_REQUEST ['_pg'] ) ? 1 : $_REQUEST ['_pg'];
	
	// Number of items retrieved for show
	$pageSize = ! isset ( $_REQUEST ['_ic'] ) ? 10 : $_REQUEST ['_ic'];
	
	// Language
	$language = ! isset ( $_REQUEST ['_lng'] ) ? "sp" : $_REQUEST ['_lng'];
	
	$searchSvr = new SearchService ();
	/*
	if (! $_SESSION ['searchService']) {
		$_SESSION ['searchService'] = serialize ( $searchSvr );
	} else {
		$searchSvr = unserialize ( $_SESSION ['searchService'] );
	}
	*/
	
	$resultsJSON = json_encode ( $searchSvr->search ( $text, $inferenceLevel, $page, $pageSize, $language ) );
	
	echo $resultsJSON;
	
	$searchSvr->saveSearchInfoToFile ( $text );
}
?>

