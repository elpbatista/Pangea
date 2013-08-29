<?php
include_once 'Util.php';
include_once 'functions.php';
include_once 'variables.php';

$lbl = $GLOBALS['lbl'];
/*habría que poner las label en singular...*/

$range = ! isset ( $_REQUEST ['_rg'] ) ? 'frbr:Core' : $_REQUEST ['_rg'];
$text = ! isset ( $_REQUEST ['_t'] ) ? '' : $_REQUEST ['_t'];
$page = ! isset ( $_REQUEST ['_pg'] ) ? 1 : $_REQUEST ['_pg'];

$linkable = ! isset ( $_REQUEST ['_lnk'] ) ? false : true;

$optionsJSON = getList($text,$page,$range);
//print_r($optionsJSON);
$options = (isset($optionsJSON['description']))?$optionsJSON['description']:array();
$amount = (isset($optionsJSON['count']))?$optionsJSON['count']:0;
if ($options){
	$key = array_keys($options);
	$size = sizeOf($key);
	//$size=count($options);
	$listElements = array();
	$entityView = '';
		for ($j=0; $j<$size; $j++){
			$label = $options[$key[$j]]['label'];
			$typeof = $options[$key[$j]]['typeof'];
			if ($linkable){
				$entityView = '<a href="'.$host.'?_a=/s&_ids='.$key[$j].'" title="'.(($lbl[$typeof])?$lbl[$typeof]:$typeof).'">'.$label.'</a>';
			}
			else {
				$entityView = $label;
			}
			$listElements[$j] = '<li typeof="'.$typeof.'">'.$entityView.'<input type="hidden" value="'.$key[$j].'" /></li>';
		}
	//if ($amount>$size*$page) $listElements[++$j]='<li class="meta"><button class="meta"><span class="icon-plus"></span>&nbsp;mostrar más... página&nbsp;<span class="_pg">'.($page+1).'</span></button></li>';
	if ($amount>$size*$page) $listElements[++$j]='<li class="next"><span class="icon-plus"></span>&nbsp;mostrar más... página&nbsp;<span class="_pg">'.($page+1).'</span></li>';
	if ($page == 1) $listElements=array_merge(array('<li class="meta">'.$amount.'&nbsp;resultados</li>'),$listElements); 
	//print_r($options); // hasta ahora no viene typeof
	echo implode($listElements);	
}
else {
	echo '<li>No se encontraron resultados</li>';
}
?>
