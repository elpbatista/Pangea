<?php
	require_once 'DefineMetaPangea.php'; 
	require_once 'function_import.php'; 
	global $dbconn;
	
	/* homesh */ 
	//$dbconn = pg_connect("host=192.168.4.39 port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A") or die("No fue posible la conexion");
	
	/* Pangea_Roman */
	//$dbconn = pg_connect("host=192.168.4.55 port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A") or die("No fue posible la conexion");
	
	
	/* laurasia */
	$dbconn = pg_connect("host=laurasia.pangea.ohc.cu port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A") or die("No fue posible la conexion");
	
	/* localhost */
	//$dbconn = pg_connect("host=localhost port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A") or die("No fue posible la conexion");
	
	$result = pg_Exec($dbconn, "delete from entity_property;");
  $result = pg_Exec($dbconn, "delete from meta_pangea;");
  $result = pg_Exec($dbconn, "delete from rules;");
	/*foreach ($value[label] as $key1 => $value1) {
			echo $key1."=>".$value1."<br/>";
		}
	*/
	
  foreach ($rules as $key => $value) {
		//print_r($value); echo "<br/>";		
		foreach ($value[property_list] as $key => $value1) {
			//echo $value[table_name]." => ".$value1[property_name]." => ".$value1[property_value]."<br/>";
			$result = pg_Exec($dbconn, "insert into rules (table_name, property, value) values ('{$value[table_name]}','{$value1[property_name]}','{$value1[property_value]}');");
		}
	}
  
	foreach ($entity_type as $key => $value) {
		//print_r($value); echo "<br/>";		
		$cmd = "insert into entity_type (sp_label, en_label, fr_label, de_label, table_name, source) values ('{$value[label][sp]}',{$value[label][en]},{$value[label][fr]},{$value[label][de]},'{$value[table_name]}','{$value[source]}');";
		$result = pg_Exec($dbconn, $cmd);
	}
	
	
	foreach ($document_type as $key => $value) {
		//print_r($value); echo "<br/>";	 
		$oid=doc_type1($value[label][sp]);		
		$cmd = "insert into entity_type (sp_label, en_label, fr_label, de_label, table_name, concept_oid, source) values ('{$value[label][sp]}',{$value[label][en]},{$value[label][fr]},{$value[label][de]},'{$value[table_name]}', $oid,'{$value[source]}');";
		$result = pg_Exec($dbconn, $cmd);
	}
	
	
	foreach ($property_type as $key => $value) {
		$result = pg_query($dbconn, "SELECT id, inverse FROM property_type WHERE table_name='{$value[table_name]}'");
		if (pg_num_rows($result)>0) {
			$row = pg_fetch_row($result);
			$IdParent = $row[0];
			$IdInverse= $row[1];
		} else {
			$IdParent=get_nextval();			
			if ($value[inverse]) {
				$IdInverse=get_nextval();
				$table_inverse='is'.substr($value[table_name], 3);
				$inverse_label = $value[label][sp].' de';
				$cmd = "insert into property_type (id, sp_label, en_label, fr_label, de_label, table_name, is_visible, domain_table, range_table, inverse, source) 
				values ($IdInverse, '{$inverse_label}',{$value[label][en]} ,{$value[label][fr]},{$value[label][de]},'{$table_inverse}', '{$value[is_visible]}',
						'{$value[range_table]}','{$value[domain_table]}', $IdParent, '{$value[inverse_source]}');";
				$result = pg_Exec($dbconn, $cmd);
			} else {				
				$IdInverse='null';							
			}			
			if (isset($value[object_type])) {
				$cmd = "insert into property_type (id, sp_label, en_label, fr_label, de_label, table_name, is_visible, domain_table, range_table, inverse, source, object_type) 
			values ($IdParent, '{$value[label][sp]}',{$value[label][en]},{$value[label][fr]},{$value[label][de]},'{$value[table_name]}',
			'{$value[is_visible]}','{$value[domain_table]}','{$value[range_table]}', $IdInverse, '{$value[source]}', '{$value[object_type]}');";
			} else {
				$cmd = "insert into property_type (id, sp_label, en_label, fr_label, de_label, table_name, is_visible, domain_table, range_table, inverse, source) 
			values ($IdParent, '{$value[label][sp]}',{$value[label][en]},{$value[label][fr]},{$value[label][de]},'{$value[table_name]}',
			'{$value[is_visible]}','{$value[domain_table]}','{$value[range_table]}', $IdInverse, '{$value[source]}');";
			}			
			$result = pg_Exec($dbconn, $cmd);
		  if (!$value[inverse]) $IdInverse=$IdParent;
		}
	
	
		// Para los hijos 
		foreach ($value[sons] as $key1 => $value1) {
		    //print_r($key1 ."=>". $value1);
			$result = pg_query($dbconn, "SELECT id FROM property_type WHERE table_name='{$value1[table_name]}'");
			if (pg_num_rows($result)>0) {
				$row = pg_fetch_row($result);
				$IdSon = $row[0];
			} else {
				$IdSon=get_nextval();
				$IdInverseSon='null';
				//
				if ($value1[inverse]) {
					$IdInverseSon=get_nextval();
					$table_inverse='is'.substr($value1[table_name], 3);
					$inverse_label = $value1[label][sp].' de';
					$cmd = "insert into property_type (id, sp_label, en_label, fr_label, de_label, table_name, is_visible, domain_table, range_table, inverse, parent, source) 
					values ($IdInverseSon, '{$inverse_label}',{$value1[label][en]} ,{$value1[label][fr]},{$value1[label][de]},'{$table_inverse}', '{$value1[is_visible]}',
							'{$value1[range_table]}','{$value1[domain_table]}', $IdSon, $IdInverse, '{$value[inverse_source]}');";
					$result = pg_Exec($dbconn, $cmd);
				}
				if (isset($value[object_type])) {
					$cmd = "insert into property_type (id, sp_label, en_label, fr_label, de_label, table_name, is_visible, domain_table, range_table, inverse, parent, source, object_type) 
					values ($IdSon, '{$value1[label][sp]}',{$value1[label][en]},{$value1[label][fr]},{$value1[label][de]},'{$value1[table_name]}',
					'{$value1[is_visible]}','{$value1[domain_table]}','{$value1[range_table]}', $IdInverseSon, $IdParent, '{$value[source]}', '{$value[object_type]}');";
				}
				else {
					$cmd = "insert into property_type (id, sp_label, en_label, fr_label, de_label, table_name, is_visible, domain_table, range_table, inverse, parent, source) 
					values ($IdSon, '{$value1[label][sp]}',{$value1[label][en]},{$value1[label][fr]},{$value1[label][de]},'{$value1[table_name]}',
					'{$value1[is_visible]}','{$value1[domain_table]}','{$value1[range_table]}', $IdInverseSon, $IdParent, '{$value[source]}');";
				}
				$result = pg_Exec($dbconn, $cmd);
			}
		}
		
		
		//$cmd = "insert into entity_type (sp_label, en_label, fr_label, de_label, table_name, concept_oid) values ('{$value[label][sp]}','{$value[label][en]}','{$value[label][fr]}','{$value[label][de]}','{$value[table_name]}', $oid);";
		//$result = pg_Exec($dbconn, $cmd);
	}
	

	//** Entity_type vs Property_type
  foreach ($entity_property as $key => $value) {
	  //echo $value['entity_type']." => (";
		$result = pg_query($dbconn, "SELECT id FROM entity_type WHERE table_name='{$value[entity_type]}'");
		for ($i=0; $i<pg_num_rows($result); $i++) { 		  
			$row = pg_fetch_row($result,$i);			
			//echo $row[0]." => (";
			foreach ($value['property_type'] as $key1 => $value1) {
				//echo $value1.",";
				$result1 = pg_query($dbconn, "SELECT id FROM property_type WHERE table_name='{$value1}'");
				if (pg_num_rows($result1)>0) {				  
					$row1 = pg_fetch_row($result1);
					$result2 = pg_query($dbconn, "SELECT id FROM entity_property WHERE entity_type=$row[0] and property_type=$row1[0]");
					if (pg_num_rows($result2)>0) {
						$cmd = "UPDATE entity_property SET entity_type=$row[0], property_type=$row1[0] WHERE entity_type=$row[0] and property_type=$row1[0]";
					} else {
						$cmd = "INSERT INTO entity_property (entity_type, property_type) VALUES ($row[0],$row1[0]);";						
					}
					$result2 = pg_Exec($dbconn, $cmd);
				}
			}
		}
	}
	echo "Terminado. <br/>";
	
function doc_type1($document_type) {
	global $dbconn;  
  $result = pg_query($dbconn, "SELECT id FROM cpa_sp WHERE text='{$document_type}'");
  if (pg_num_rows($result)>0) {
    $row = pg_fetch_row($result);
    $IdDoc = $row[0];
  } else {
  	$IdDoc=get_nextval();
    $cmd = "insert into cpa_sp (id, text) values ($IdDoc, '{$document_type}');";
    $result = pg_Exec($dbconn, $cmd);    
  }
	$cmd="select subject from has_pref_term where has_pref_term.value=$IdDoc and has_pref_term.subject in (select distinct document_type.id from document_type)";
	$result = pg_Exec($dbconn, $cmd);
    if (pg_num_rows($result)>0) {
	  $row = pg_fetch_row($result);
	  $IdType = $row[0];	      
	} else {
		$IdType=create_object("document_type");
		create_relation("has_pref_term", "access_point", $IdDoc, "concept", $IdType, true);		
    }	
	$cmd="select document_type.oid from document_type where document_type.id=$IdType";
	$result = pg_Exec($dbconn, $cmd);
	$row = pg_fetch_row($result);
	return $row[0];
}
?>