<?php
session_start();
include_once 'Util.php';
include_once 'variables.php';
include_once 'functions.php';

global $text, $action, $lang, $pageNumber, $list, $ppFilter, $prfLabels, $pageSize;

$text = ! isset ( $_REQUEST ['_t'] ) ? '' : $_REQUEST ['_t'];
$action = ! isset ( $_REQUEST ['_a'] ) ? '' : $_REQUEST ['_a'];
$lang = ! isset ( $_REQUEST ['_lng'] ) ? "sp" : $_REQUEST ['_lng'];
$pageNumber = ! isset ( $_REQUEST ['_pg'] ) ? 1 : $_REQUEST ['_pg'];
$pageSize = ! isset ( $_REQUEST ['_ic'] ) ? 10 : $_REQUEST ['_ic'];
$resource = ! isset ( $_REQUEST ['_ids'] ) ? '' : $_REQUEST ['_ids'];
$level = ! isset ( $_REQUEST ['_n'] ) ? '1' : $_REQUEST ['_n'];

$idSearch = ! isset ( $_REQUEST ['_md5'] ) ? '' : $_REQUEST ['_md5'];

if ($text!='' || $idSearch!='' || $action==='/s'  || $action==='/m') {
	$page = "results";
}
else if ($resource!='' && $action!='/s'  && $action!='/m') {
	$page = "entity";
}
else {
	$page = "home";
}

include 'header.php';

switch ($page) {

	case 'results':
		include 'inc_searchResults.php';
		break;
							
	case 'entity':
		include 'inc_entity.php';
		break;
	
	default:
		echo '<div class="span12">	
			<h2>'.$lbl['main_title'].'</h2>
			<p>'.$txt['main_txt'].'</p>
			<br />';
		include 'inc_gallery.php';
		echo '</div></div>';
}

include 'footer.php';
?>	
