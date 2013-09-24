<?php
include_once 'Util.php';
include_once 'variables.php';
include_once 'functions.php';
$excludeType = array (
								'frbr:Item',
								'frbr:Expression',
								'frbr:Work'
								);
global $text, $action, $lang, $pageNumber, $idSearch, $list, $ppFilter, $md5;
$text = ! isset ( $_REQUEST ['_t'] ) ? '' : $_REQUEST ['_t'];
$action = ! isset ( $_REQUEST ['_a'] ) ? '/fc' : $_REQUEST ['_a'];
$lang = ! isset ( $_REQUEST ['_lng'] ) ? "sp" : $_REQUEST ['_lng'];
$pageNumber = ! isset ( $_REQUEST ['_pg'] ) ? 1 : $_REQUEST ['_pg'];
$pageSize = ! isset ( $_REQUEST ['_ic'] ) ? 10 : $_REQUEST ['_ic'];
$idSearch = ! isset ( $_REQUEST ['md5'] ) ? '' : $_REQUEST ['md5'];
$resource = ! isset ( $_REQUEST ['_ids'] ) ? '' : $_REQUEST ['_ids'];

if ($text) {
	$resultsSet = new HTTPRequest($host.$gateway.'?_t='.urlencode(utf8_decode($text)).'&_pg='.$pageNumber.'&_ic='.$pageSize); 
	$resultsJSON = json_decode($resultsSet->DownloadToString(),true);
	$md5 = $resultsJSON['id'];
	$total = $resultsJSON['count'];
	$results = $resultsJSON['description'];
	if ($total>0) {
		foreach ($results as $key => $value) {
			if (!in_array($key,$excludeType)){
				$documentType = $key;
				//$documentType = ! isset ( $_REQUEST ['_ff'] ) ? 'frbr:Manifestation' : $_REQUEST ['_ff'];
				/*$valFilter = ! isset ( $_REQUEST ['_fv'] ) ? '' : $_REQUEST ['_fv'];
				$ppFilter = ($valFilter || $action == '/o')?$documentType:'rdf:type|'.$documentType;				
				$documents = new HTTPRequest($host.$gateway.'?md5='.$md5.'&_ff='.$ppFilter.'&_a='.$action.'&_pg='.$pageNumber.(($valFilter)?'&_fv='.$valFilter:''));
				$documentsJSON = json_decode($documents->DownloadToString(), true);
				$pageElements = $documentsJSON['description'];
				$pageCount = $documentsJSON['count'];*/
				//echo '<h1>"'.$text.'" arrojó '.$total.' resultados</h1>';
				echo '<h2>'.$documentType.'&nbsp;'.$value['count'].'</h2>';				
				//include 'inc_pagination.php';
				//echo '<ol start="'.((($pageNumber-1)*$pageSize)+1).'">';
				echo '<ul>';
				/*foreach ($pageElements as $resource) {
					echo '<li id="'.$resource.'" class="dragme">';
					include 'inc_entitySmall.php';
					echo '</li>';
				}*/
				echo '</ul>';
				unset($resource);
				//include 'inc_pagination.php';
			}
		}
	
	}
	else {
		echo '<h2>La búsqueda no arrojó ningún resultado</h2>'."\n";	
	}
} /*end if($text)*/
else {
	echo '<h2>NO pinchó</h2>';
} 
?>