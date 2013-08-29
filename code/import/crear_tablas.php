<?php
  include_once 'function_import.php';
	global $dbconn;
	$dbconn = pg_connect("host=192.168.4.39 port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A") or die("No fue posible la conexion");
	$z = new XMLReader();	
	$z->open("data/BH.xml");
		
	while ($z->read() && $z->name !== 'ROW');	
	while ($z->name === 'ROW') { 		
		$node = new SimpleXMLElement($z->readOuterXML());
		$entity=trim(pg_escape_string($node->id_biblio));
		if (!empty($entity)) {
			$search  = array('á', 'é', 'í', 'ó', 'ú', 'ñ', ' ', '.');
			$replace = array('a', 'e', 'i', 'o', 'u', 'n', '_', '_');
			$entity = "item_".strtolower(str_ireplace($search, $replace, $entity));
			$result = pg_query($dbconn, "SELECT tablename FROM pg_tables WHERE tablename = '$entity'");
			if (pg_num_rows($result)==0) {
		  	echo "La tabla no está </br>";
				$cmd="CREATE TABLE $entity (CONSTRAINT {$entity}_pkey PRIMARY KEY (id)) INHERITS (item) WITH (OIDS=TRUE); ALTER TABLE $entity OWNER TO pangea;";
				$result = pg_query($dbconn, $cmd);
		  	if (!$result) echo "No se pudo crear la tabla </br>";
	    }
			
			$collection = trim(pg_escape_string($node->collection));
			if (!empty($collection)) {
				$collection = $entity."_".strtolower(str_ireplace($search, $replace, $collection));
				//echo $collection."<br/>";
				$result = pg_query($dbconn, "SELECT tablename FROM pg_tables WHERE tablename = '$collection'");	
				if (pg_num_rows($result)==0) {
					echo "La tabla no está </br>";
					$cmd="CREATE TABLE $collection (CONSTRAINT {$collection}_pkey PRIMARY KEY (id)) INHERITS ($entity) WITH (OIDS=TRUE); ALTER TABLE $collection OWNER TO pangea;";
					$result = pg_query($dbconn, $cmd);
					if (!$result) echo "No se pudo crear la tabla </br>";
				}			
			}
		}
		
		
		
		unset($node);
		$z->next('ROW');
	}	
	$z->close();
	echo "Terminado.";
/*
	$biblio="item_"."BóñH";
  $result = pg_query($dbconn, "SELECT tablename FROM pg_tables WHERE tablename = '$biblio'");	
	if (pg_num_rows($result)==0) {
		echo "La tabla no está </br>";
		$cmd="CREATE TABLE $biblio (CONSTRAINT {$biblio}_pkey PRIMARY KEY (id)) INHERITS (item) WITH (OIDS=TRUE); ALTER TABLE $biblio OWNER TO pangea;";
		$result = pg_query($dbconn, $cmd);
		if (!$result) echo "No se pudo crear la tabla </br>";
	}
	$collection=$biblio."_Rara";
	$result = pg_query($dbconn, "SELECT tablename FROM pg_tables WHERE tablename = '$collection'");	
	if (pg_num_rows($result)==0) {
		echo "La tabla no está </br>";
		$cmd="CREATE TABLE $collection (CONSTRAINT {$collection}_pkey PRIMARY KEY (id)) INHERITS ($biblio) WITH (OIDS=TRUE); ALTER TABLE $collection OWNER TO pangea;";
		$result = pg_query($dbconn, $cmd);
		if (!$result) echo "No se pudo crear la tabla </br>";
	}
*/
?>
