<?php
  $file=$_POST['filepath'];
  //echo $file."<br/>"; exit();
	$value_1=$_POST['value_1'];
	$value_2=$_POST['value_2'];
	$value_3=$_POST['value_3'];
	
  $languages_sufixes=array("español" => "sp", "inglés" => "en", "italiano" => "it", "francés" => "fr", "alemán" => "de", "catalán" => "ct", "other" => "ot");	
	include_once 'function_import.php'; 
	global $dbconn;
	/* homesh */ 
	$dbconn = pg_connect("host=192.168.4.39 port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A") or die("No fue posible la conexion");
	
	/* Pangea_Roman */
	//$dbconn = pg_connect("host=192.168.4.55 port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A") or die("No fue posible la conexion");
	
	
	/* laurasia */
	//$dbconn = pg_connect("host=laurasia.pangea.ohc.cu port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A") or die("No fue posible la conexion");
	
	/* localhost */
	//$dbconn = pg_connect("host=localhost port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A") or die("No fue posible la conexion");

	
	//exit();
	if ($value_3) {
		 delete_tables();
		 $result = pg_query($dbconn, "SELECT setval('entity_property_id_seq', 1);");
		 $result = pg_query($dbconn, "SELECT setval('system_meta_object_id_seq', 1);");
		 $result = pg_query($dbconn, "SELECT setval('system_object_id_seq', 1);");		 
	}

	$z = new XMLReader;
	//$z->open('data/BH.xml');
	$z->open('data/'.$file);	
	
	while ($z->read() && $z->name !== 'ROW');
	$i=1;
	while ($z->name === 'ROW') {
		if ($i>=$value_1 && $i<=$value_2) {
			$node = new SimpleXMLElement($z->readOuterXML());		
			/********************************************************************************************
			 *      ITEM
			 ********************************************************************************************/
			$entity=trim(pg_escape_string($node->id_biblio));
			if (!empty($entity)) {
				  $collection = trim(pg_escape_string($node->collection));
				  $IdItem=bibliographic_item($entity,$collection);
					//$IdItem=create_object("item");
					$lang=$node->language;
					if ($lang=="") $lang="other"; 
					$lang_suf=$languages_sufixes[utf8_encode(strtolower(utf8_decode($lang)))];
					if ($lang_suf=="") $lang_suf="ot";
					
					$currency=strtolower(pg_escape_string($node->currency));
					if (!empty($currency)) {
						$price=trim(pg_escape_string($node->price));		
					  if ($price) price_item($price, $currency, $IdItem);
					} 
					/*
					if (empty($currency)) $currency="mn";
					$price=$node->price;		
					if ($price) price_item($price, $currency, $IdItem);
					*/ 
					
					$entry_date=trim(pg_escape_string($node->in_date));
					if ($entry_date) entry_date("'{$entry_date}'",$IdItem);
					$note_item=trim(pg_escape_string($node->note_item));
					if ($note_item) note_item($note_item,$IdItem);
					$note_boundwith=trim(pg_escape_string($node->note_boundWith));
					if ($note_boundwith) note_boundwith($note_boundwith,$IdItem);
					$note_adquisition=trim(pg_escape_string($node->notes_adquisition));
					if ($note_adquisition) note_adquisition($note_adquisition,$IdItem);
					$location=trim(pg_escape_string($node->location));
					if ($location && $location<>"**VACIO**") location($location,$IdItem);
					$availability=trim(pg_escape_string($node->availability));
					if ($availability) availability($availability,$IdItem);
					$adquisition_way=trim(pg_escape_string($node->way));
					if ($adquisition_way) adquisition_way($adquisition_way,$IdItem);
					$commission_act=trim(pg_escape_string($node->to_commission));
					if ($commission_act) commission_act($commission_act,$IdItem);
					$buy_act=trim(pg_escape_string($node->to_buy));
					if ($buy_act) buy_act($buy_act,$IdItem);
					/********************************************************************************************
					 *      MANIFESTATION
					 ********************************************************************************************/
					$IdManif=create_object("manifestation");
					create_relation("has_exemplifies", "manifestation", $IdManif, "item", $IdItem, true);
					$doc_type=$node->doc_type;
					if ($doc_type=="") $doc_type="Otro";
					doc_type($doc_type,$IdManif);
					//Levantando los títulos
					$IdTitle=create_object("title");
					create_relation("has_title", "", $IdTitle, "", $IdManif, true);
					$title_proper=trim(pg_escape_string($node->title_proper));
					if ($title_proper) title_proper($title_proper,$lang_suf,$IdTitle);			
					$title_parallel=trim(pg_escape_string($node->title_paralell));
					if ($title_parallel) title_parallel($title_parallel,$lang_suf,$IdTitle);	
					$title_notitle=trim(pg_escape_string($node->title_noTitle));
					if ($title_notitle) title_notitle($title_notitle,$lang_suf,$IdTitle);
					$title_translated=trim(pg_escape_string($node->title_translated));
					if ($title_translated) title_translated($title_translated,$lang_suf,$IdTitle);
					$title_variant=trim(pg_escape_string($node->title_variant));
					if ($title_variant) title_variant($title_variant,$lang_suf,$IdTitle);
					$title_contributedInf=trim(pg_escape_string($node->title_contributedInf));
					if ($title_contributedInf) title_contributedInf($title_contributedInf,$lang_suf,$IdTitle);
					$title_oit=trim(pg_escape_string($node->title_oit));
					if ($title_oit) title_oit($title_oit,$lang_suf,$IdTitle);
					$title_parallel_oit=trim(pg_escape_string($node->title_paralellOIT));
					if ($title_parallel_oit) title_oit_parallel($title_parallel_oit,$lang_suf,$IdTitle);
					//Levantando los autores
					foreach ($node->author_personal->DATA as $key => $value) {
						if ($value) {
							
							//$s="\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ü\Ü";
							$patron_apellidos="/[\w\s\-\.".utf8_encode("\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ä\ë\ö\ü\Ä\Ë\Ï\Ö\Ü\ç\à\è\ì\ò\ù\ÿ\’\´")."]+(?=,)/";				
							preg_match($patron_apellidos, $value, $apellidos);
							$patron_nombres="/(?<=,)[\w\s\-\.".utf8_encode("\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ä\ë\ö\ü\Ä\Ë\Ï\Ö\Ü\ç\à\è\ì\ò\ù\ÿ\’\´")."]+/";					  
							preg_match($patron_nombres, $value, $nombres);
							$author_personal=trim(pg_escape_string($apellidos[0].",".$nombres[0]));				 
							author_personal($author_personal, "manifestation", $IdManif);
						}
					}
					foreach ($node->author_corporateBody->DATA as $key => $value) {
						if ($value) {			
							$author_corporate=trim(pg_escape_string($value));				 
							author_corporate($author_corporate, "manifestation", $IdManif);
						}
					}
					foreach ($node->author_Event->DATA as $key => $value) {
						if ($value) {			
							$author_event=trim(pg_escape_string($value));				 
							author_event($author_event, "manifestation", $IdManif);
						}
					}
					$isbn=trim(pg_escape_string($node->isbn));
					if ($isbn) isbn($isbn,$IdManif);
					$issn=trim(pg_escape_string($node->issn));
					if ($issn) issn($issn,$IdManif);
					$printer_date=trim(pg_escape_string($node->pr_date));
					if ($printer_date) printer_date($printer_date,$IdManif);
					$printer_place=trim(pg_escape_string($node->pr_place));
					if (!empty($printer_place)) {			
						printer_place($printer_place,$IdManif);
					}
					$note_general=trim(pg_escape_string($node->note_general));
					if ($note_general) note_general($note_general,$IdManif);
					$note_content=trim(pg_escape_string($node->note_content));
					if ($note_content) note_content($note_content,$IdManif);
					$editorial=trim(pg_escape_string($node->ed_editorial));
					if ($editorial) editorial($editorial,$IdManif);
					$printer=trim(pg_escape_string($node->pr_printer));
					if ($printer) printer($printer,$IdManif);
					$classif_dewey=trim(pg_escape_string($node->classification));
					if ($classif_dewey) classif_dewey($classif_dewey,$IdManif);			
					$pages=trim(pg_escape_string($node->pd_pages));
					$patron_pages="/\d+/";
					preg_match($patron_pages,$pages,$matriz);
					if ($matriz[0]) pages($matriz[0],$IdManif);
					$edition_country=trim(pg_escape_string($node->country));
					if (!empty($edition_country)) {			
						edition_country($edition_country,$IdManif,true);
					}	
							
					/********************************************************************************************
					 *      EXPRESSION
					 ********************************************************************************************/
					$IdExpression=create_object("expression");
					create_relation("has_materializes", "expression", $IdExpression, "manifestation", $IdManif, true);
					$lang=trim(pg_escape_string($node->language));
					if ($lang) {
						//echo "|".$lang."| => |".utf8_encode(strtolower(utf8_decode($lang)))."|<br/>";
						language_expression($lang,$IdExpression);
					}
					$typology_doc=trim(pg_escape_string($node->typology));
					if (!empty($typology_doc)) {			
						typology_doc($typology_doc,$IdExpression);
					}
					$edition_date=trim(pg_escape_string($node->ed_date));		
					if ($edition_date)	edition_date($edition_date,$IdExpression);
					$edition_place=trim(pg_escape_string($node->ed_place));
				  if (!empty($edition_place)) {			
						edition_place($edition_place,$IdExpression);
					}
					/********************************************************************************************
					 *      WORK
					 ********************************************************************************************/
					$IdWork=create_object("work");
					create_relation("has_realizes", "work", $IdWork, "expression", $IdExpression, true);
					$title_uniform=trim(pg_escape_string($node->title_uniform));
					if ($title_uniform) {
						title_uniform($title_uniform,$lang_suf,$IdWork);
					}	else {
						title_uniform($title_proper,$lang_suf,$IdWork);
					}
					foreach ($node->author_personal->DATA as $key => $value) {
						if ($value) {
							$patron_apellidos="/[\w\s\-\.".utf8_encode("\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ü\Ü")."]+(?=,)/";				
							preg_match($patron_apellidos, $value, $apellidos);
							$patron_nombres="/(?<=,)[\w\s\-\.".utf8_encode("\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ü\Ü")."]+/";	
							preg_match($patron_nombres, $value, $nombres);
							$author_personal=trim(pg_escape_string($apellidos[0].",".$nombres[0]));				 
							author_personal($author_personal, "work", $IdWork);
						}
					}
					foreach ($node->author_corporateBody->DATA as $key => $value) {
						if ($value) {			
							$author_corporate=trim(pg_escape_string($value));				 
							author_corporate($author_corporate, "work", $IdWork);
						}
					}
					foreach ($node->author_Event->DATA as $key => $value) {
						if ($value) {			
							$author_event=trim(pg_escape_string($value));				 
							author_event($author_event, "work", $IdWork);
						}
					}
					foreach ($node->subject_concept->DATA as $key => $value) {
						if ($value) {			
							$subject_concept=trim(pg_escape_string($value));				 
							subject_concept($subject_concept,$IdWork);
						}
					}
					foreach ($node->subject_event->DATA as $key => $value) {
						if ($value) {			
							$subject_event=trim(pg_escape_string($value));				 
							subject_event($subject_event,$IdWork);
						}
					}
					foreach ($node->subject_place->DATA as $key => $value) {
						if ($value) {			
							$subject_place=trim(pg_escape_string($value));				 
							subject_place($subject_place,$IdWork);
						}
					}
					foreach ($node->subject_personal->DATA as $key => $value) {
						if ($value) {			
							$subject_personal=trim(pg_escape_string($value));				 
							subject_personal($subject_personal,$IdWork);
						}
					}
					foreach ($node->subject_CB->DATA as $key => $value) {
						if ($value) {			
							$subject_corporate=trim(pg_escape_string($value));				 
							subject_corporate($subject_corporate,$IdWork);
						}
					}
					foreach ($node->subject_object->DATA as $key => $value) {
						if ($value) {			
							$subject_object=trim(pg_escape_string($value));				 
							subject_object($subject_object,$IdWork);
						}
					}
					/*foreach ($node->subject_title->DATA as $key => $value) {
						if ($value) {			
							$subject_title=trim(pg_escape_string($value));				 
							subject_title($subject_title,$IdWork);
						}
					}*/	
					$literary_form=trim(pg_escape_string($node->form));
					if ($literary_form) {
						literary_form($literary_form,$IdWork);
					}
					
					echo "Record[$i] Finished <br/>";	
			}
			
			unset($node);	
		}
		if ($i==$value_2) break;
	  $z->next('ROW');
	  $i++;
	}
	$z->close();
	pg_close($dbconn);	
?> 