<?php
include_once dirname ( __FILE__ ) . '/../search/services/SearchService.php';

if(isset($_GET['id'])){	
	
	$id = $_GET['id'];
	
	$ss = new SearchService();

	$doc = $ss->WEB_DOCS($id);
	$url = $doc['url'];	
	header('HTTP/1.1 303 See Other');
	header("Location: $url");
	exit;	
}

?>