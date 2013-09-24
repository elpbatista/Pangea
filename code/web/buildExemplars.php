<?php
include_once 'Util.php';
include_once 'functions.php';
include_once 'variables.php';

$exemplars = ! isset ( $_REQUEST ['exemplars'] ) ? '' : json_decode(stripslashes($_REQUEST ['exemplars']),true);

/*
if(json_decode($xnpl) == NULL) {
	echo $xnpl." not valid json!";
}
else {
	$exemplars = json_decode($xnpl, true);
}
*/
$exemplars = array_map(function($val){return $val['value'];}, $exemplars);
$exemplars = getEntities ($exemplars,'large');
//print_r($exemplars);
if ($exemplars) {
	$exnKeys = array_keys($exemplars);
	$exnSize = sizeOf($exemplars);
	for ($e=0; $e<$exnSize; $e++){
		$exemplar = $exemplars[$exnKeys[$e]];
		$prefLabel = (array_key_exists('skos:prefLabel',$exemplar))?$exemplar['skos:prefLabel'][0]['value']:'';
		$owner = (array_key_exists('frbr:owner',$exemplar))?$exemplar['frbr:owner']:Array();
		$availability = (array_key_exists('pangea:hasAvailability',$exemplar))?$exemplar['pangea:hasAvailability']:Array();
		$prefImage = (array_key_exists('foaf:img',$exemplar))?$exemplar['foaf:img'][0]['value']:'';
		$image = '
			<div class="thumb small">
				<img src="http://dev.pangea.ohc.cu/resource/'.$prefImage.'" alt="portada de prueba" />
			</div>';
		echo '<li class="entity">
			<div class="nbr">'.($e+1).'</div>'
			.(($prefImage)?$image:'').
			'<ul>
				<li><h4><a href="'.$host.'?_ids='.$exnKeys[$e].'">'.$prefLabel.'</a></h4></li>
				<li>'.perfValues('frbr:owner',$owner,$filter=true).'</li>
				<li>'.perfValues('pangea:hasAvailability',$availability,$filter=true).'</li>
			</ul>
		</li>';
	}
}
?>
