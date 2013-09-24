<?php
include_once 'Util.php';
include_once 'functions.php';
include_once 'variables.php';

$newEntities = ! isset ( $_REQUEST ['new'] ) ? '' : json_decode(stripslashes($_REQUEST ['new']),true);
$keys = array_keys($newEntities);
//print_r($newEntities);
echo makeEditable($keys[0],$newEntities[$keys[0]],'li','h3');
?>
