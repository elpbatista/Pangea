<?php
/*
$statement=array("caricaturista","diseñador","editor","director","redactor","compilador","prologuista","notas","traductor","presentación","curador");
$titles=array("Dra.","Dr.","Ing.","Lic.","MsC.");
$patron_nombres="/\b[\w\s\.\-".utf8_encode("\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ü\Ü")."]+/";
$patron_date_before="/[\d{1,4}]+(?=\-)/";
$patron_date_after="/(?<=\-)[\d{1,4}]+/";
*/

include_once 'function_import.php'; 

$z = new XMLReader;
if(!$z->open('data/MNC.xml')){ print "can't open file";}

while ($z->read() && $z->name !== 'ROW');

while ($z->name === 'ROW') {
$node = new SimpleXMLElement($z->readOuterXML());

    foreach ($node->author_personal->DATA as $key => $value) {
    if ($value)	{
    	$thevalue = explode(";", $value);
    	foreach ($thevalue as $value1) {
    		$result = GetNames($value1);
      	print_r($result);
      	echo "<br />";
    	}
   	  echo "<br />";
    }//foreach
    }
    $z->next('ROW');
}//while


//Revisar los apostrofes
//Separar 1ro por el ; luego aplicar
/*         
   	   preg_match_all($patron_nombres, $value, $matriz);
   	   //print_r ($matriz);
	    $size= sizeof($matriz[0]);
	    switch ($size) {
		case 1:
		 echo "Nombre:  ".$matriz[0][0]."<br />";  //Jaly Vazquez
		break;
		case 2:
	       if (in_array($matriz[0][1],$statement)){
		 echo "<b>Nombre:  ".$matriz[0][0]."</b><br />";
		 echo "<b>Menci&oacute;n:  ".$matriz[0][1]."</b><br />";
             }else{
               echo "<b>Apellidos:  ".$matriz[0][0]."</b><br />";
               echo "<b>Nombre:  ".$matriz[0][1]."</b><br />";
	       }//if
		break;
		case 3://Divito, Ana Mar�a, 1946-
		 preg_match_all($patron_date_before, $matriz[0][1], $before);
		 preg_match_all($patron_date_after, $matriz[0][1], $after);
		 preg_match_all($patron_date_before, $matriz[0][2], $before2);
		 preg_match_all($patron_date_after, $matriz[0][2], $after2);
 		 if (is_numeric($before[0][0]) or is_numeric($after[0][0])) {
		    echo "<b>Nombre:  ".$matriz[0][0]."</b><br />";
		    echo "<b>Fecha Nac.:  ".$before[0][0]."</b><br />";
		    echo "<b>Fecha Muerte:  ".$after[0][0]."</b><br />";
		    echo "<b>Menci&oacute;n:  ".$matriz[0][2]."</b><br />";
             }elseif (is_numeric($before2[0][0]) or is_numeric($after2[0][0])) {
		    echo "<b>Apellidos:  ".$matriz[0][0]."</b><br />";
		    echo "<b>Nombre:  ".$matriz[0][1]."</b><br />";
		    echo "<b>Fecha Nac.:  ".$before2[0][0]."</b><br />";
		    echo "<b>Fecha Muerte:  ".$after2[0][0]."</b><br />";
		 }else{
		    echo "<b>Apellidos:  ".$matriz[0][0]."</b><br />";
		    echo "<b>Nombre:  ".$matriz[0][1]."</b><br />";
		    echo "<b>Menci&oacute;n:  ".$matriz[0][2]."</b><br />";
             }

		break;
		default:
		 preg_match_all($patron_date_before, $matriz[0][1], $before);
		 preg_match_all($patron_date_after, $matriz[0][1], $after);
		 preg_match_all($patron_date_before, $matriz[0][2], $before2);
		 preg_match_all($patron_date_after, $matriz[0][2], $after2);
 		 if (is_numeric($before[0][0]) or is_numeric($after[0][0])) {
		    echo "<b>Nombre:  ".$matriz[0][0]."</b><br />";
		    echo "<b>Fecha Nac.:  ".$before[0][0]."</b><br />";
		    echo "<b>Fecha Muerte:  ".$after[0][0]."</b><br />";
		    echo "<b>Menci&oacute;n:  ".$matriz[0][2]."</b><br />";
		    echo "<b>Menci&oacute;n:  ".$matriz[0][3]."</b><br />";
             }elseif (is_numeric($before2[0][0]) or is_numeric($after2[0][0])) {
		    echo "<b>Apellidos:  ".$matriz[0][0]."</b><br />";
		    echo "<b>Nombre:  ".$matriz[0][1]."</b><br />";
		    echo "<b>Fecha Nac.:  ".$before2[0][0]."</b><br />";
		    echo "<b>Fecha Muerte:  ".$after2[0][0]."</b><br />";
		    echo "<b>Menci&oacute;n:  ".$matriz[0][3]."</b><br />";
		 }else{
		    echo "<b>Apellidos:  ".$matriz[0][0]."</b><br />";
		    echo "<b>Nombre:  ".$matriz[0][1]."</b><br />";
		    if (in_array($matriz[0][2],$titles)) {
		    echo "<b>T&iacute;tulo:  ".$matriz[0][2]."</b><br />";
		    }else{
		    echo "<b>Menci&oacute;n:  ".$matriz[0][2]."</b><br />";
                }
		    echo "<b>Menci&oacute;n:  ".$matriz[0][3]."</b><br />";
             }

		break;

	    }//switch
*/
?>