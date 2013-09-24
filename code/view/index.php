<!DOCTYPE html>
<?php
require 'lessc.inc.php';
try { lessc::ccompile('less/bootstrap.less', 'css/style.css'); } 
catch (exception $ex) { exit('lessc fatal error:<br />'.$ex->getMessage()); }
?>
<html>
  <head>
    <title>bIS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="css/style.css" rel="stylesheet" media="screen">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="js/html5shiv.js"></script>
      <script src="js/respond.min.js"></script>
    <![endif]-->
  
  </head>
  <body>
    <h1>¡Eto e OpenPangea!</h1>

    <script src="js/jquery-1.10.2.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>
