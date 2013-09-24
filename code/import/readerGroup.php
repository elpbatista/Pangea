<?php
	//global $dbconn;
  /*$rest = substr("has_manifestation_form", 3); 
  print "|".$rest."|<br/>";
  exit();*/	

	$z = new XMLReader();	
	//$z->open("data/_MegaPangea.xml");
	$z->open("data/BH.xml");
	$datos = array();	
		
	while ($z->read() && $z->name !== 'ROW');
	$i=1; 
	while ($z->name === 'ROW') { 		
		$node = new SimpleXMLElement($z->readOuterXML());
		
		$valor=$node->subject_object->DATA;
		if (is_array($valor)) {
			foreach ($valor as $key => $value) {
				$valor=(string)$value;
				(array_key_exists($valor, $datos))? $datos[$valor]++ : $datos[$valor]=1;
			}
		} else {
			$valor=trim((string)$valor);
			(array_key_exists($valor, $datos))? $datos[$valor]++ : $datos[$valor]=1; 
		}
		//print $valor."<br/>";
		unset($node);
		$z->next('ROW');
	  $i++;
	  //if ($i==21350) exit();
	}
	//asort($datos); //Ordena por el valor ascendente
	//arsort($datos); //Ordena por el valor descendente
	ksort($datos); //Ordena por la clave ascendente
	//krsort($datos); //Ordena por la clave descendente
	foreach ($datos as $key => $value) {	  
		echo $key." => ".$value."<br/>";
	}
	$z->close();
?>