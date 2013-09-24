<?php
include_once 'Util.php';
include_once 'functions.php';
include_once 'variables.php';

$idSearch = $_REQUEST ['_md5'];
$documentType = ! isset ( $_REQUEST ['_ff'] ) ? 'frbr:Manifestation' : $_REQUEST ['_ff'];
//$valFilter = ! isset ( $_REQUEST ['_fv'] ) ? '' : $_REQUEST ['_fv'];
//$prpFilter = ($valFilter || $action == '/o')?$documentType:'rdf:type|'.$documentType;
//$prpFilter = ($valFilter)?$documentType:'rdf:type|'.$documentType;

$pageNumber = ! isset ( $_REQUEST ['_pg'] ) ? 1 : $_REQUEST ['_pg'];
$pageSize = ! isset ( $_REQUEST ['_ic'] ) ? 10 : $_REQUEST ['_ic'];

/*aquí se le pasa el tipo de página _pt=ty es la lista solo de etiquetas*/
$pageType = ! isset ( $_REQUEST ['_pt'] ) ? '' : $_REQUEST ['_pt'];
$total = ! isset ( $_REQUEST ['_tt'] ) ? 0 : $_REQUEST ['_tt'];

$pageElementsJSON = getFilteredPage($idSearch,$documentType,$pageNumber);
$pageCount = $pageElementsJSON['count'];
$pageElements = $pageElementsJSON['description'];

if ($pageType==='ty') {
	$typeof = $documentType;
	include 'inc_entitiesTinyList.php';
}
else {
	include 'inc_entitiesList.php';
}
?>
