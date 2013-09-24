<?php
//header('Content-Type: text/html;  charset=UTF-8');
global $pangea, $host, $gateway, $labels, $user_name, $error, $bullet, $frbrNS, $per_editar, $text, $txt, $msg, $lbl, $resource, $idSearch, $emptyEntities;

$service_name = 'Catálogo &beta;';
$location = '';
$current_lang = 'es';
$user_name = 'PB';
$pass = '';
$can_edit = true;
$error = 'qº|ºp';
$bullet = '&nbsp;&middot;&nbsp;';
$pangeaURI = 'http://pangea.ohc.cu/resource#';
$frbrNS = 'http://purl.org/vocab/frbr/core#';

//$host = 'http://'.$_SERVER['SERVER_NAME'].'/pangea/'; /*hacerlo genérico PB*/

$host = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
$host = str_replace(substr (strrchr ($host, '/'), 1 ), '', $host);

$per_editar= 'editar';

//guardo en la variable $back_page la página en la que estoy para regresar despues del login
$back_page = 'http://'.$_SERVER ['SERVER_NAME'] . $_SERVER ['PHP_SELF'];
 if (!empty($_SERVER ['QUERY_STRING'])) $back_page = $back_page . '?' . $_SERVER ['QUERY_STRING'];
 //para pasarlo por get se encripta:
//$back_page = urlencode($back_page);	

$gateway = 'w_srvs/gateway.php';

/*estos son los textos en español, el fichero json tiene el $current_lang como parte del nombre*/

$textJSON = new HTTPRequest($host.'pangea.'.$current_lang.'.json');
$text = json_decode($textJSON->DownloadToString(), true);
$txt = $text ['txt'];
$msg = $text ['msg'];
$lbl = $text ['lbl'];

$pangeaJSON = new HTTPRequest($host.'schema.json');
$pangea = json_decode($pangeaJSON->DownloadToString(), true);


$emptyEntity = array ( 
	'_Work' => array (
		'rdf:type'=> array (
			array ('value'=>'frbr:Work')
		)
	),
	'_Expression' => array (
		'rdf:type'=> array (
			array ('value'=>'frbr:Expression')
		)
	),
	'_Manifestation' => array (
		'rdf:type'=> array (
			array ('value'=>'frbr:Manifestation')
		)
	),
	'_Item' => array (
		'rdf:type'=> array (
			array ('value'=>'frbr:Item')
		)
	)
);


?>
