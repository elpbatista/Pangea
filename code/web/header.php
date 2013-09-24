<!DOCTYPE HTML>
<?php
require 'lessc.inc.php';
try {
    lessc::ccompile('less/styles.less', 'styles/style.css');
} catch (exception $ex) {
    exit('lessc fatal error:<br />'.$ex->getMessage());
}
?>
<html xml:lang="en" version="XHTML+RDFa 1.0"
    xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Pangea :: Catálogo &beta;</title>
		<link rel="stylesheet" href="styles/style.css" media="all" />
		<link rel="stylesheet" href="styles/prepara.css" media="all" />
<!--
		<link rel="stylesheet" href="styles/jquery-ui.css" media="all" />
		
		<link rel="stylesheet/less" href="less/bootstrap.less" media="all" />
		<script src="js/less-1.3.0.min.js"></script>
		-->
		
		<!--[if lte IE 7]><script src="js/lte-ie7.js"></script><![endif]-->
		
		<link rel="Shortcut Icon" href="img/favicon.png" type="image/x-icon" />
	
		<script type="text/javascript">
			var host='<?php echo $host;?>';
			var lang='<?php echo $current_lang;?>' || 'es';
		</script>

	</head>
	<body>
		<div class="navbar navbar-fixed-top">
			<div class ="container">
				<?php include_once 'inc_menu_services.php'; ?>
				<a href="./" title="inicio"><img src="img/pangea.png" alt="logo" id="logo" /></a>
				<?php echo '<p class="brand">'.$service_name.'</p>'; ?>
			</div>
		</div>

		  <header <?php echo ($page != "home")?'class="fixed"':'';?>>
		     <div class="container <?php echo (($page == "home")?'jumbotron':''); ?>">		 
				<?php if ($page == "home") { ?>
				<h1><?php echo $txt['the_title'];?></h1>
				<p><?php echo $txt['sub_title'];?></p>
				<?php } ?>
				<?php if (isPage('index.php') || isPage('images.php')) include_once 'inc_searchForm.php'; ?>
				<?php echo ($page == "home")?'<p class="big">496 707 entidades y 3 541 379 relaciones...</p>':''; ?>
			 </div>
		  </header>
	
		<div id="main" <?php echo ($page == "home" || isPage('edit.php') || isPage('index_statistics.php'))?'class="home"':'';?>>
<?php
	/** TODO..
	 * 
	 *  Se debe crear una funcion que dado un conjuntos de roles y permisos permita determinar
	 *  si un usuario tiene determinados privilegios para ejecutar determinadas acciones 
	 *  (visualizar, insertar, modificar, eliminar) sobre ciertos elementos (paginas, entidades,
	 *  propiedades). De momento.... lo hacemos asi de sencillo.
	 * 
	 * */
	$can_edit = false;

	if (isset($_SESSION ['userPermissions']) && !empty($_SESSION ['userPermissions']))
		$can_edit = (strpos ( $_SESSION ['userPermissions'], $per_editar ) !== false);
?>			
