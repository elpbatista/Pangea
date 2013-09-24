<?php

	global $dbconn;
	$dbconn = pg_connect("host=localhost port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A") or die("No fue posible la conexion");
	include_once 'function_import.php';
	
	delete_tables();
	
	$z = new XMLReader;
	$z->open('data/BH.xml');
	//$z->open('data/'.$file);	
	
	while ($z->read() && $z->name !== 'ROW');
	$i=1;
	while ($z->name === 'ROW') {
		//if ($i>=$value_1 && $i<=$value_2) {
			$node = new SimpleXMLElement($z->readOuterXML());
					//$IdManif=create_object("manifestation");
						
					//Autores
					/*foreach ($node->author_personal->DATA as $key => $value) {
						if ($value) {
							$authors = explode(";", $value);
							echo $value." => <br/>";
							foreach ($authors as $key1 => $value1) {
								//$s="\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ü\Ü";
								$DataAuthor=GetNames($value1);
								if ($DataAuthor[last_name] && $DataAuthor[first_name]) {
									$author_personal=trim(pg_escape_string($DataAuthor[last_name].", ".$DataAuthor[first_name]));
								} else {
									$author_personal=trim(pg_escape_string($DataAuthor[first_name]));
								}
								//print_r($DataAuthor); echo "<br/>";
								echo $author_personal; echo "<br/>";								 								
								author_personal($author_personal, "manifestation", $IdManif);
							}							
						}*/
						
					//Materias
					$IdWork=create_object("work");	
					foreach ($node->subject_personal->DATA as $key => $value) {
						if ($value) {
							$authors = explode(";", $value);
							echo $value." => <br/>";
							foreach ($authors as $key1 => $value1) {
								//$s="\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ü\Ü";
								$DataAuthor=GetNames($value1);
								if ($DataAuthor[last_name] && $DataAuthor[first_name]) {
									$subject_personal=trim(pg_escape_string($DataAuthor[last_name].", ".$DataAuthor[first_name]));
								} else {
									$subject_personal=trim(pg_escape_string($DataAuthor[first_name]));
								}
								//print_r($DataAuthor); echo "<br/>";
								echo $subject_personal; echo "<br/>";								 								
								subject_personal($subject_personal,$IdWork);
							}							
						}
					}
		//}
		unset($node);
		$z->next('ROW');
	}			
					
?>