<?php
include_once 'function_import.php';
include_once 'isbntest.class.php';
//$file = $_POST ['filepath'];
$file = 'CAF4.xml';
//echo $file."<br/>"; exit();
//$value_1 = $_POST ['value_1'];
//$value_2 = $_POST ['value_2'];
//$value_3 = $_POST ['value_3'];


$value_1 = '4001'; //número del primer registro a importar
$value_2 = '4671'; //número del último registro a importar
$value_3 = false;

$patron = '/^\[s\.a\.\]/'; //patron para las fechas
$patron_apellidos = '/[\w\s\-\.' . utf8_encode ( '\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ä\ë\ö\ü\Ä\Ë\Ï\Ö\Ü\ç\à\è\ì\ò\ù\ÿ\’\´\[\]\(\)\…\ã\ý' ) . "]+(?=,)/";
$patron_nombres = '/(?<=,)[\w\s\-\.' . utf8_encode ( '\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ä\ë\ö\ü\Ä\Ë\Ï\Ö\Ü\ç\à\è\ì\ò\ù\ÿ\’\´\[\]\(\)\…\ã\ý' ) . "]+/";
$patron_date_before = '/[\d{1,4}]+(?=\-)/';
$patron_date_after = '/(?<=\-)[\d{1,4}]+/';
$patron_rol = '/(?<=\()[\;\,\.\-\w\s' . utf8_encode ( '\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ä\ë\ö\ü\Ä\Ë\Ï\Ö\Ü\ç\à\è\ì\ò\ù\ÿ\’\´\[\]\(\)\…\ã\ý' ) . ']+(?=\))/';

$languages_sufixes = array ("ruso" => 'rus', "spa" => 'sp', "español" => 'sp', "inglés" => 'en', "italiano" => 'it', "francés" => 'fr', "alemán" => 'de', "catalán" => 'ca', "portugués" => 'pt', "other" => 'null', "gallego" => 'ga', "latín" => 'la', "español-inglés" => 'sp-en', "alemán-inglés" => 'de-en', "inglés-español" => 'en-sp', "inglés-francés" => 'en-fr', "español-francés" => 'sp-fr', 'castellano' => 'sp', "inglés,francés" => 'en-fr', "húngaro/inglés" => 'hun-en', "ruso-español" => 'rus-sp', "español-inglés-francés" => 'sp-en-fr', "inglés-francés-español" => 'en-fr-sp', "inglés-francés" => 'en-fr', "inglés-árabe" => 'en-ara', "japonés" => 'jap', "chino" => 'chi', "chino-inglés" => 'chi-en', "chino-español-inglés" => 'chi-sp-en', "checo" => 'cze', "coreano" => 'kor', "español-alemán" => 'sp-de', "español-ruso" => 'sp-rus', "español alemán francés portugués italiano español-ingles" => 'sp-de-fr-pt-it-sp-en', "inglés-español alemán francés portugués italiano" => 'en-sp-de-fr-pt-it', "español alemán francés portugués italiano español" => 'sp-de-fr-pt-it' );
$bibliotecas = array ('BH' => 'Biblioteca Histórica Cubana y Americana Francisco González del Valle', 'CHC' => 'Biblioteca Gabriela Mistral, Centro Hispanoamericano', 'BPRMV' => 'Biblioteca Pública Rubén Martínez Villena', 'CAR' => 'Biblioteca  Ibn Jaldún, Casa de los Árabes', 'SR' => 'Biblioteca Simón Rodríguez, Casa Simón Bolívar', 'PFV' => 'Biblioteca Pedagógica Félix Varela', 'CMX' => 'Biblioteca Alfonso Reyes, Casa de México', 'CHB' => 'Biblioteca Alejandro de Humboldt, Casa Humboldt', 'RT' => 'Biblioteca Rabindranat Tagore, Casa de Asia', 'MNP' => 'Biblioteca Napoleónica, Museo Napoleónico', 'GA' => 'Biblioteca de Arqueología', 'CAF' => 'Biblioteca Don Fernando Ortiz, Casa de África', 'VH' => 'Biblioteca Francófona, Casa Víctor Hugo', 'TCH' => 'Biblioteca de la Casa de Tradiciones chinas', 'CP' => 'Biblioteca Ada Elba Pérez, Casa de la Poesía', 'MNM' => 'Biblioteca Raúl León Torras, Museo Numismático', 'FVD' => 'Biblioteca Fermín Valdés Domínguez, Casa Natal José Martí', 'BU' => 'Biblioteca Universitaria, Colegio Universitario', 'VV' => 'Biblioteca Vitrina de Valonia', 'MNC' => 'Biblioteca del Museo de la Cerámica' );

$roles_allowed = array ('adaptador' => 'pangea:adapter', 'anotador' => 'pangea:annotator', 'arreglista' => 'pangea:arranger', 'caricaturista' => 'pangea:cartoonist', 'cartógrafo' => 'pangea:cartographer', 'compilador' => 'pangea:compiler', 'corrector' => 'pangea:proofreader', 'dibujante' => 'pangea:draw', 'director' => 'pangea:manager', 'editor' => 'pangea:editor', 'fotógrafo' => 'pangea:photographer', 'grabador' => 'pangea:engraver', 'guionista' => 'pangea:screenwriter', 'ilustrador' => 'pangea:illustrator', 'intérprete' => 'pangea:interpreter', 'introductor' => 'pangea:introducer', 'productor' => 'frbr:producer', 'prologuista' => 'pangea:prologue', 'redactor' => 'pangea:drafter', 'traductor' => 'pangea:translator', 'tutor' => 'pangea:tutor', 'diseñador' => 'pangea:designer', 'compositor' => 'pangea:composer' );
$roles_notes = array ('autor de palabras preliminares' => 'pangea:introducer', 'asistente editorial' => 'pangea:editor', 'codirector' => 'pangea:manager', 'coeditor' => 'pangea:editor', 'director artístico' => 'pangea:manager', 'director de coro' => 'pangea:manager', 'director de la serie' => 'pangea:manager', 'director de orquesta' => 'pangea:manager', 'director editorial' => 'pangea:editor', 'dirección editorial' => 'pangea:editor', 'editor ejecutivo' => 'pangea:editor', 'editor jefe' => 'pangea:editor', 'editor propietario' => 'pangea:editor', 'editor general' => 'pangea:editor', 'ilustrador de cubierta' => 'pangea:illustrator', 'investigador' => 'frbr:creator', 'productor ejecutivo' => 'frbr:producer', 'productor musical' => 'frbr:producer', 'redactor general' => 'pangea:drafter', 'redactor jefe' => 'pangea:drafter', 'redactor principal' => 'pangea:drafter', 'colaborador' => 'frbr:creator', 'estudio preliminar' => 'pangea:introducer', 'estudio introductorio' => 'pangea:introducer', 'grabado de cubierta' => 'pangea:engraver', 'jefe de redacción' => 'pangea:manager', 'declamador' => 'pangea:interpreter' );

/*esto es lo nuevo de los roles*/
$rolesType1 = array ('diseñador', 'caricaturista', 'ilustrador', 'ilustrador de cubierta', 'fotógrafo', 'grabador', 'grabado de cubierta', 'dibujante', 'cartógrafo' );
$rolesType2 = array ('introductor', 'autor de palabras preliminares', 'estudio preliminar', 'estudio introductorio', 'prologuista', 'presentador', 'anotador', 'guionista' );
$rolesType3 = array ('traductor', 'arreglista', 'corrector', 'adaptador' );
$rolesType4 = array ('compilador' );
$rolesType5 = array ('compositor' );
$rolesType6 = array ('músico' );
$rolesType7 = array ('director', 'codirector', 'director artístico', 'director de coro', 'director de la serie', 'director de orquesta', 'jefe de redacción', 'tutor', 'editor', 'coeditor', 'asistente editorial', 'director editorial', 'dirección editorial', 'editor ejecutivo', 'editor jefe', 'editor propietario', 'editor general', 'redactor', 'redactor general', 'redactor jefe', 'redactor principal' );
$rolesType8 = array ('intérprete', 'declamador' );

$rolesType9 = array ('autor', 'coautor', 'investigador', 'colaborador' );
$rolesType10 = array ('productor', 'productor ejecutivo', 'productor musical' );

$arrayForm = array ('diseñador' => 'Diseño', 'caricaturista' => 'Caricatura', 'ilustrador' => 'Ilustración', 'ilustrador de cubierta' => 'Ilustración', 'fotógrafo' => 'Fotografía', 'grabador' => 'Grabado', 'grabado de cubierta' => 'Grabado', 'dibujante' => 'Dibujo', 'cartógrafo' => 'Material cartográfico', 'introductor' => 'Introducción', 'autor de palabras preliminares' => 'Introducción', 'estudio preliminar' => 'Introducción', 'estudio introductorio' => 'Introducción', 'prologuista' => 'Prólogo', 'presentador' => 'Presentación', 'anotador' => 'Anotación', 'guionista' => 'Guión', 'músico' => 'Forma musical' );

$arrayPropertyType3 = array ('traductor' => 'frbr:translation', 'arreglista' => 'frbr:arrangement', 'corrector' => 'frbr:revision', 'adaptador' => 'frbr:adaption' );
/*fin de lo nuevo de los roles*/

$import = DataImport::getInstance ();
$currISBN = new ISBNtest ();
//include_once 'CreateNewMeta.php'; 
//global $dbconn;
/* homesh */
//$dbconn = pg_connect ( "host=db.pangea.ohc.cu port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A" ) or die ( "No fue posible la conexion" );


/* Pangea_Roman */
//$dbconn = pg_connect("host=192.168.4.55 port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A") or die("No fue posible la conexion");


/* laurasia */
//$dbconn = pg_connect("host=laurasia.pangea.ohc.cu port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A") or die("No fue posible la conexion");


/* localhost */
//$dbconn = pg_connect("host=localhost port=5432 dbname=PANGEA_DESA user=pangea password=p@Ng3A") or die("No fue posible la conexion");


/*if ($value_3) {
	$import->delete_tables ();
	$result = pg_query ( $dbconn, "SELECT setval('system_meta_object_id_seq', 1);" );
	$result = pg_query ( $dbconn, "SELECT setval('system_object_id_seq', 1);" );

	// generate_meta();
//exit();
}*/

$z = new XMLReader ();
$z->open ( 'data/' . $file ); //ruta donde se encuentra el xml importar


while ( $z->read () && $z->name !== 'ROW' ) //para moverse por el xml hasta que llegue al primer row
	

	$i = 1;
while ( $z->name === 'ROW' ) {
	if ($i >= $value_1 && $i <= $value_2) {
		$node = new SimpleXMLElement ( $z->readOuterXML () );
		/************************************
		 ********************************************************
		 * ITEM
		 ********************************************************************************************/
		$entity = trim ( pg_escape_string ( $node->id_biblio ) );
		$entity = $bibliotecas [$entity];
		
		if (! empty ( $entity )) {
			//$item = null;
			$number_stock = trim ( pg_escape_string ( $node->register ) ); //número de inventario
			$location = trim ( pg_escape_string ( $node->location ) );
			if ((($number_stock != "**VACIO**") && ($number_stock != '')) || (($location != '') && ($location != "**VACIO**"))) {
				$item = $import->verifyItem ( $entity, $number_stock, $location );
				if ($item)
					echo "item repetido " . $number_stock . '<br/>';
				else { //el item no existe o no lo pude buscar porque no tenia ni numero de inventario ni localizacion
					//$lang = trim ( pg_escape_string ( $node->language ) );
					//if ($lang == "")
					//$lang = 'other';
					//$lang = preg_replace ( "[ ]", "", $lang ); //para quitar espacios intermedios
					//$lang_suf = $languages_sufixes [utf8_encode ( strtolower ( utf8_decode ( $lang ) ) )];
					//if (! $lang_suf)
					//$lang_suf = 'null';
					$IdItem = $import->createObject ( 'frbr:Item' );
					echo 'item ' . $IdItem . '<br/>';
					
					$import->owner ( $entity, $IdItem );
					
					$collection = trim ( pg_escape_string ( $node->collection ) );
					if (($collection) && ($collection != ''))
						$import->collection ( $collection, $IdItem );
					
					if (($number_stock) && ($number_stock != '') && ($number_stock != "**VACIO**")) //número de inventario
						$import->number_stock ( $number_stock, $IdItem );
					else {
						$yelowNote = "Ejemplar sin número de inventario.";
						$import->yelowNote ( $yelowNote, $IdItem );
					}
					
					if (($location) && ($location != "**VACIO**") && ($location != ''))
						$import->location ( $location, $IdItem );
					
	//$currency=ucfirst(pg_escape_string($node->currency));
					$currency = ucfirst ( strtolower ( pg_escape_string ( $node->currency ) ) );
					//if (! empty ( $currency )) {
					$price = trim ( pg_escape_string ( $node->price ) );
					if (($price) && ($price != '')) {
						$price = str_replace ( ',', '.', $price );
						$price = str_replace ( '$', '', $price );
						
						if (is_numeric ( $price )) {
							if (($price == 0) || ($price == 0.00))
								$price = 1.00;
							$import->price_item ( $price, $currency, $IdItem );
						}
					}
					//}
					$entry_date = trim ( pg_escape_string ( $node->in_date ) );
					if (($entry_date) && ($entry_date != ''))
						$import->entry_date ( $entry_date, $IdItem );
					
					$yelowNote = trim ( pg_escape_string ( $node->technicalNotes ) );
					if (($yelowNote) && ($yelowNote != ''))
						$import->yelowNote ( $yelowNote, $IdItem );
					
					$note_item = trim ( pg_escape_string ( $node->note_item ) );
					if (($note_item) && ($note_item != ''))
						$import->item_note ( $note_item, $IdItem );
					
					$note_boundwith = trim ( pg_escape_string ( $node->note_boundWith ) );
					if (($note_boundwith) && ($note_boundwith != ''))
						$import->boundwith_note ( $note_boundwith, $IdItem );
					
					$note_adquisition = trim ( pg_escape_string ( $node->notes_adquisition ) );
					if (($note_adquisition) && ($note_adquisition != ''))
						$import->adquisition_note ( $note_adquisition, $IdItem );
					
					$availability = trim ( pg_escape_string ( $node->availability ) );
					if (($availability) && ($availability != ''))
						$import->availability ( $availability, $IdItem );
					
					$adquisition_way = trim ( pg_escape_string ( $node->way ) );
					if (($adquisition_way) && ($adquisition_way != '')) {
						if ((is_numeric ( $adquisition_way )) || ($adquisition_way == '-'))
							$adquisition_way = "Desconocido";
						else {
							$founded1 = stristr ( $adquisition_way, 'donación' );
							$founded2 = stristr ( $adquisition_way, 'donacion' );
							if ($founded1 || $founded2) {
								if ((strcasecmp ( $adquisition_way, 'Donación' ) != 0) && (strcasecmp ( $adquisition_way, 'Donación de Leal' ) != 0)) {
									//lo relaciono con donacion y pongo una nota
									$import->yelowNote ( $adquisition_way, $IdItem );
									$adquisition_way = 'Donación';
								}
							
							}
						}
						$import->adquisition_way ( $adquisition_way, $IdItem );
					} else {
						$adquisition_way = "Desconocido";
						$import->adquisition_way ( $adquisition_way, $IdItem );
					}
					
					$comission_act = trim ( pg_escape_string ( $node->to_commission ) );
					if (($comission_act) && ($comission_act != ''))
						$import->comission_act ( $comission_act, $IdItem );
					
					$buy_act = trim ( pg_escape_string ( $node->to_buy ) );
					if (($buy_act) && ($buy_act != ''))
						$import->buy_act ( $buy_act, $IdItem );
					
					/********************************************************************************************
					 * MANIFESTATION
					 ********************************************************************************************/
					//Buscar Manifestación
					$isbn = trim ( pg_escape_string ( $node->isbn ) );
					$title = trim ( pg_escape_string ( $node->title ) );
					if (strpos ( $title, '[sic]' ) === false) {
						$arrangedTitle = str_replace ( '[', '', $title ); //si el titulo viene entre corchetes hay que quitarselos para comprobar
						$arrangedTitle = str_replace ( ']', '', $arrangedTitle );
					} else // si viene el [sic] dejo el titulo tal cual viene
						$arrangedTitle = $title;
					$IdManif = NULL;
					$printer = trim ( pg_escape_string ( $node->pr_printer ) );
					$printer_date = trim ( pg_escape_string ( $node->pr_date ) );
					$printer_place = trim ( pg_escape_string ( $node->pr_place ) );
					
					$arrangedPrinter = str_replace ( '[', '', $printer ); //si el impresor viene entre corchetes hay que quitarselos para comprobar
					$arrangedPrinter = str_replace ( ']', '', $arrangedPrinter );
					
					$arrangedPrinterPlace = str_replace ( '[', '', $printer_place ); //si el lugar de impresión viene entre corchetes hay que quitarselos para comprobar
					$arrangedPrinterPlace = str_replace ( ']', '', $arrangedPrinterPlace );
					$arrangedPrinterPlace = str_replace ( '?', '', $arrangedPrinterPlace );
					
					//ESTE PEDASO QUE ESTA COMENTADO ERA COMO SE BUSCABA ANTES LA MANIFESTACION
					/*if (($isbn && $arrangedTitle) && (($isbn != '') && ($arrangedTitle != ''))) {
						
						$result = $import->selectManifestation ( $isbn, $arrangedTitle );
						if (pg_num_rows ( $result ) > 0) {
							$row = pg_fetch_row ( $result );
							$IdManif = $row [0];
							echo "Coincidencia por ISBN+TituloPropio [IdManif = $IdManif] <br/>";
						}
					} else { //Buscar por Título Propio, Impresor, Fecha de impresión y lugar de impresión
						

						if (($arrangedTitle && $arrangedPrinter && $printer_date && $arrangedPrinterPlace) && (($arrangedPrinter != '') && ($arrangedTitle != '') && ($printer_date != '') && ($arrangedPrinterPlace != ''))) {
							
							$result = $import->selectManifestation2P ( $arrangedTitle, $arrangedPrinter, $printer_date, $arrangedPrinterPlace );
							if (pg_num_rows ( $result ) > 0) {
								$row = pg_fetch_row ( $result );
								$IdManif = $row [0];
								echo "Coincidencia por TituloPropio + Impresor + Lugar de impresión + Fecha de impresión [IdManif = $IdManif] <br/>";
							}
						}
					}*/
					//AHORA SE BUSCA ASI
					$result = $import->selectManifestation2P ( $isbn, $arrangedTitle, $arrangedPrinter, $printer_date, $arrangedPrinterPlace );
					if (pg_num_rows ( $result ) > 0) {
						$row = pg_fetch_row ( $result );
						$IdManif = $row [0];
						echo "Coincidencia de [IdManif = $IdManif] por isbn $isbn + TituloPropio $arrangedTitle + Impresor  $arrangedPrinter + Lugar de impresión $arrangedPrinterPlace + Fecha de impresión $printer_date<br/>";
					}
					
					// Si no encontré una manifestation
					if ($IdManif == NULL) {
						$type3 = false; //esta variable se usa mas adelante en la parte de los roles en la obra
						

						$IdManif = $import->createObject ( "frbr:Manifestation" );
						echo 'manifestation ' . $IdManif . '<br/>';
						
						$doc_type = trim ( pg_escape_string ( $node->doc_type ) );
						if ($doc_type == '')
							$doc_type = 'Otro';
						$import->doc_type ( $doc_type, $IdManif );
						
						if (($title) && ($title != '') && ($title != '[]')) {
							
							$import->title ( $arrangedTitle, $IdManif );
						}
						$paralellTitle = trim ( pg_escape_string ( $node->title_paralell ) );
						if (($paralellTitle) && ($paralellTitle != '')) {
							$titleParalellOIT = trim ( pg_escape_string ( $node->title_paralellOIT ) );
							if (($titleParalellOIT) && ($titleParalellOIT != '')) {
								$altLabel = $paralellTitle . ':' . $titleParalellOIT;
								$import->setRelationLiteral ( $IdManif, 'skos:altLabel', $altLabel, 'xsd:string', 'sp' );
							}
						}
						
						if (($isbn) && ($isbn != ''))
							if ($currISBN->valid_isbn ( $isbn ))
								$import->isbn ( $isbn, $IdManif );
							else
								echo "isbn incorrecto " . $isbn . '<br/>';
						
						if (($printer_date) && ($printer_date != '')) {
							$import->printer_strDate ( $printer_date, $IdManif );
							if (preg_match ( $patron, $printer_date )) { //no se sabe la fecha
								//pongo un warning
								$warning = 'Se desconoce la fecha de impresión.';
								$import->warningDate ( $warning, $IdManif );
							} else {
								foreach ( $node->pr_date->DATA as $prdates ) { //aqui vienen las fechas arregladas por nosotros
									if (($prdates)) {
										$prdates = trim ( pg_escape_string ( $prdates ) );
										$import->printer_date ( $prdates, $IdManif );
									}
								}
							}
						}
						if (($printer_place) && ($printer_place != '')) {
							if ($printer_place == '[s.l.]') {
								$note = 'Sin lugar conocido';
								$import->yelowNote ( $note, $IdManif );
							} else {
								$pos = strpos ( $printer_place, '?' );
								if ($pos) {
									$note = 'El lugar ofrece duda.';
									$import->yelowNote ( $note, $IdManif );
								} elseif (strpos ( $printer_place, '[' ) == 0) {
									$pos2 = strpos ( $printer_place, ']' );
									$lenght = strlen ( $printer_place );
									if ($pos2 == $lenght - 1) {
										$note = 'El lugar no fue extraído de la portada.';
										$import->yelowNote ( $note, $IdManif );
									}
								
								}
								$import->printer_place ( $arrangedPrinterPlace, $IdManif );
							}
						}
						
						$note_general = trim ( pg_escape_string ( $node->note_general ) );
						if (($note_general) && ($note_general != ''))
							$import->general_note ( $note_general, $IdManif );
						
						if (($printer) && ($printer != '')) {
							if ($printer == '[s.n.]') {
								$note = 'No se conoce el nombre de la imprenta.';
								$import->yelowNote ( $note, $IdManif );
							} else {
								if (strpos ( $printer, '[' ) == 0) {
									$pos2 = strpos ( $printer, ']' );
									$lenght = strlen ( $printer );
									if ($pos2 == $lenght - 1) {
										$note = 'La imprenta no fue extraída de la portada.';
										$import->yelowNote ( $note, $IdManif );
									}
								
								}
								$import->printer ( $arrangedPrinter, $IdManif );
							}
						}
						
						$pages = trim ( pg_escape_string ( $node->pd_pages ) );
						if (($pages) && ($pages != ''))
							$import->pages ( $pages, $IdManif );
							/*$patron_pages = '/\d+/';
				$matriz = array ();
				preg_match ( $patron_pages, $pages, $matriz );
				if (! empty ( $matriz ))
					$import->pages ( $matriz [0], $IdManif );*/
						
						$dimensions = trim ( pg_escape_string ( $node->pd_dimensions ) );
						if (($dimensions) && ($dimensions != ''))
							if (is_numeric ( $dimensions ))
								$import->dimensions ( $dimensions, $IdManif );
						
	//este año ahora se pone en la expression en la cadena que se arma
						/*$year = trim ( pg_escape_string ( $node->year ) ); //año fiscal
						if (($year) && ($year != ''))
							$import->year ( $year, $IdManif );*/
						
						foreach ( $node->pd_illustrations->DATA as $key => $value ) {
							if (($value)) {
								$illustration = trim ( pg_escape_string ( $value ) );
								if ($illustration != '')
									$import->illustration ( $illustration, $IdManif ); //se levanta una expresion del tipo imagen por cada ilustracion y se relacionan con la manifestacion
							}
						}
						
						/********************************************************************************************
						 * EXPRESSION
						 ********************************************************************************************/
						$IdExpression = NULL;
						if ($IdExpression == NULL) {
							$IdExpression = $import->createObject ( "frbr:Expression" );
							echo 'expression ' . $IdExpression . '<br/>';
							$lang = trim ( pg_escape_string ( $node->language ) );
							if (($lang) && ($lang != ''))
								$import->language_expression ( $lang, $IdExpression );
							
							$editorial = trim ( pg_escape_string ( $node->ed_editorial ) );
							$arrangedEditorial = str_replace ( '[', '', $editorial ); //si la editorial viene entre corchetes hay que quitarselos para comprobar
							$arrangedEditorial = str_replace ( ']', '', $arrangedEditorial );
							
							if (($editorial) && ($editorial != '')) {
								if ($editorial == '[s.n.]') {
									$note = 'No se conoce el nombre de la editorial.';
									$import->yelowNote ( $note, $IdExpression );
								} else {
									if (strpos ( $editorial, '[' ) == 0) {
										$pos2 = strpos ( $editorial, ']' );
										$lenght = strlen ( $editorial );
										if ($pos2 == $lenght - 1) {
											$note = 'La editorial no fue extraída de la portada.';
											$import->yelowNote ( $note, $IdExpression );
										}
									
									}
									$import->editorial ( $arrangedEditorial, $IdExpression );
								}
							
							}
							$editionType = trim ( pg_escape_string ( $node->ed_type ) );
							if (($editionType) && ($editionType != ''))
								$import->editionType ( $editionType, $IdExpression );
							
							$editionNumber = trim ( pg_escape_string ( $node->ed_number ) );
							if (($editionNumber) && ($editionNumber != ''))
								$import->serialNumber ( $editionNumber, $IdExpression ); //el número de la edición se echa en la misma propiedad del numero de serie
							

							$edition_date = trim ( pg_escape_string ( $node->ed_date ) );
							if (($edition_date) && ($edition_date != '')) {
								$import->edition_strDate ( $edition_date, $IdExpression );
								if (preg_match ( $patron, $edition_date )) { //no se sabe la fecha
									//pongo un warning
									$warning = 'Se desconoce la fecha de edición.';
									$import->warningDate ( $warning, $IdExpression );
								} else {
									foreach ( $node->ed_date->DATA as $eddates ) { //aqui vienen las fechas arregladas por nosotros
										if (($eddates)) {
											$eddates = trim ( pg_escape_string ( $eddates ) );
											$import->edition_date ( $eddates, $IdExpression );
										}
									
									}
								}
							}
							
							$edition_place = trim ( pg_escape_string ( $node->ed_place ) );
							$arrangedEditionPlace = str_replace ( '[', '', $edition_place ); //si el lugar de impresión viene entre corchetes hay que quitarselos para comprobar
							$arrangedEditionPlace = str_replace ( ']', '', $arrangedEditionPlace );
							$arrangedEditionPlace = str_replace ( '?', '', $arrangedEditionPlace );
							
							if (($edition_place) && ($edition_place != '')) {
								if ($edition_place == '[s.l.]') {
									$note = 'Sin lugar conocido';
									$import->yelowNote ( $note, $IdExpression );
								} else {
									$pos = strpos ( $edition_place, '?' );
									if ($pos) {
										$note = 'El lugar ofrece duda.';
										$import->yelowNote ( $note, $IdExpression );
									} elseif (strpos ( $edition_place, '[' ) == 0) {
										$pos2 = strpos ( $edition_place, ']' );
										$lenght = strlen ( $edition_place );
										if ($pos2 == $lenght - 1) {
											$note = 'El lugar no fue extraído de la portada.';
											$import->yelowNote ( $note, $IdExpression );
										}
									
									}
									
									$import->edition_place ( $arrangedEditionPlace, $IdExpression );
								}
							}
							
							$edition_country = trim ( pg_escape_string ( $node->country ) );
							if (($edition_country) && ($edition_country != '')) {
								$import->edition_place ( $edition_country, $IdExpression );
							}
							
							//ESTA ES LA PARTE DE LA EXPRESSION QUE SE ARMA
							$prefLabelExpression = '';
							//debería quedar algo como esto 3ra Epoca, vol. XX, año 30, nr. 21, (10 septiembre 1943) 
							$count = 0; //contador para indicar si ya hay al menos un valor anterior en la cadena que se arma						
							

							$epoch = trim ( pg_escape_string ( $node->ps_epoch ) );
							if (($epoch) && ($epoch != '')) {
								$prefLabelExpression = $epoch . ' Época';
								$count = 1;
							}
							
							$psVolume = trim ( pg_escape_string ( $node->ps_volume ) );
							if (($psVolume) && ($psVolume != ''))
								if ($count == 1)
									$prefLabelExpression = $prefLabelExpression . ', vol. ' . $psVolume;
								else
									$prefLabelExpression = 'vol. ' . $psVolume;
							
							$psYear = trim ( pg_escape_string ( $node->ps_year ) );
							if (($psYear) && ($psYear != ''))
								if ($count == 1)
									$prefLabelExpression = $prefLabelExpression . ', año ' . $psYear;
								else
									$prefLabelExpression = 'año ' . $psYear;
							
							$psNumber = trim ( pg_escape_string ( $node->ps_number ) );
							if (($psNumber) && ($psNumber != ''))
								if ($count == 1)
									$prefLabelExpression = $prefLabelExpression . ', nr. ' . $psNumber;
								else
									$prefLabelExpression = 'nr. ' . $psNumber;
							
							$day = trim ( pg_escape_string ( $node->ps_day ) );
							if (($day) && ($day != ''))
								if ($count == 1)
									$prefLabelExpression = $prefLabelExpression . ', (' . $day;
								else
									$prefLabelExpression = '(' . $day;
							
							$month = trim ( pg_escape_string ( $node->ps_months ) );
							if (($month) && ($month != ''))
								if ($day != '')
									$prefLabelExpression = $prefLabelExpression . ' ' . $month;
								else if ($count == 1)
									$prefLabelExpression = $prefLabelExpression . ', (' . $month;
								else
									$prefLabelExpression = '(' . $month;
							
							$year = trim ( pg_escape_string ( $node->year ) ); //año fiscal
							if (($year) && ($year != ''))
								if (($day != '') || ($month != ''))
									$prefLabelExpression = $prefLabelExpression . ' ' . $year;
								else if ($count == 1)
									$prefLabelExpression = $prefLabelExpression . ', (' . $year;
								else
									$prefLabelExpression = '(' . $year;
									
							if (($day != '') || ($month != '') || ($year != '')) //si tienen valor alguno de los datos que van entre parentesis, tengo que cerrar el parentesis		
								$prefLabelExpression = $prefLabelExpression . ')';
							
	                       //como terminé de armar la cadena vuelvo a poner el contador en 0
							$count = 0;
							//FIN DE LA CADENA QUE SE ARMA
							

							if ($prefLabelExpression != '') {
								$import->labelExpression ( $prefLabelExpression, $IdExpression );
							}
							
							$typology_doc = trim ( pg_escape_string ( $node->typology ) );
							if (($typology_doc) && ($typology_doc != '')) {
								$import->typology_doc ( $typology_doc, $IdExpression );
							}
							$note_content = trim ( pg_escape_string ( $node->note_content ) );
							if (($note_content) && ($note_content != ''))
								$import->content_note ( $note_content, $IdExpression );
							
							$volume = trim ( pg_escape_string ( $node->pd_volume ) );
							if (($volume) && ($volume != '')) {
								if (is_integer ( $volume ))
									$import->volume ( $volume, $IdExpression );
							}
							
							$serial = trim ( pg_escape_string ( $node->serial ) );
							$serial = str_replace ( '(', '', $serial );
							$serial = str_replace ( ')', '', $serial );
							$totalVolume = trim ( pg_escape_string ( $node->pd_volumeTotal ) );
							if (($serial) && ($serial != '')) {
								
								$serialNumber = trim ( pg_escape_string ( $node->serial_number ) );
								if (($serialNumber) && ($serialNumber != ''))
									$import->serialNumber ( $serialNumber, $IdExpression );
								
								$subserial = trim ( pg_escape_string ( $node->serial_sub ) );
								if (($subserial) && ($subserial != '')) {
									
									$subSerialNumber = trim ( pg_escape_string ( $node->serial_subNumber ) );
									if (($subSerialNumber) && ($subSerialNumber != ''))
										$import->subSerialNumber ( $subSerialNumber, $IdExpression );
									
	//$idSerial = $import->serialAndSubserial ( $serial, $subserial, $IdExpression );
									$arraySerial = $import->serialAndSubserial ( $serial, $subserial, $IdExpression );
								} else
									$arraySerial = $import->serial ( $serial, $IdExpression );
								
								if ($arraySerial [0] == true) { //quiere decir que la serie no existia y la cree nueva
									

									$issn = trim ( pg_escape_string ( $node->issn ) );
									if (($issn) && ($issn != ''))
										$import->issn ( $issn, $arraySerial [1] );
									
									if (($totalVolume) && ($totalVolume != ''))
										$import->totalVolumeWithSerialId ( $arraySerial [1], $totalVolume );
								
								}
							} else {
								if (($totalVolume) && ($totalVolume != ''))
									$import->totalVolumeWithoutSerialId ( $totalVolume );
							}
							$matAcomp = array ();
							foreach ( $node->pd_accompanyingMaterial->DATA as $key => $value ) {
								if (($value)) {
									$accompanyingMaterial = trim ( pg_escape_string ( $value ) );
									if ($accompanyingMaterial != '')
										//levanto una expression por cada material acompañante, lo que viene en value se esta poniendo como prefLabel
										$matAcomp [] = $import->accompanyingMaterial ( $accompanyingMaterial );
								}
							}
							if (! empty ( $matAcomp )) {
								$matAcomp [] = $IdExpression; //incluyo mi expression en el arreglo para relacionarla con la expression grupo
								$import->group ( $matAcomp ); //creo la expression grupo y la relaciono con mi expression y con todas las demas que levanté
							

							}
							
							/********************************************************************************************
							 * WORK
							 ********************************************************************************************/
							$IdWork = NULL;
							if (empty ( $IdWork )) {
								$IdWork = $import->createObject ( "frbr:Work" );
								echo 'work ' . $IdWork . '<br/>';
								
								$title_uniform = trim ( pg_escape_string ( $node->title_uniform ) );
								if (($title_uniform) && ($title_uniform != '')) //el titulo uniforme antes se le ponia como prefLabel a la obra pero ahora se le va a poner como alternativo
									$import->title_uniform ( $title_uniform, 'sp', $IdWork );
								
								if (($title) && ($title != '') && ($title != '[]')) //se le pone como prefLabel el mismo titulo que a la manifestacion
									$import->title ( $arrangedTitle, $IdWork );
								
								$classif_dewey = trim ( pg_escape_string ( $node->classification ) );
								if (($classif_dewey) && ($classif_dewey != ''))
									$import->classif_dewey ( $classif_dewey, $IdWork );
								
	//Levantando los autores
								foreach ( $node->author_personal->DATA as $key => $value ) {
									if ($value) {
										$value = trim ( pg_escape_string ( $value ) );
										if ($value != '') {
											$nombres = '';
											$apellidos = '';
											$dateBefore = '';
											$dateAfter = '';
											//$s="\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ü\Ü";
											$value = str_replace ( 'î', 'i', $value );
											$value = str_replace ( 'ï', 'i', $value );
											$value = str_replace ( '… [et al]', ' ', $value );
											$value = str_replace ( '… (et. al.)', ' ', $value );
											$value = str_replace ( '… [et al.]', ' ', $value );
											$value = str_replace ( '[et al.]', ' ', $value );
											$value = str_replace ( '[s.n]', ' ', $value );
											
											preg_match ( $patron_apellidos, $value, $apellidos );
											
											preg_match ( $patron_nombres, $value, $nombres );
											
											preg_match ( $patron_date_before, $value, $dateBefore );
											
											preg_match ( $patron_date_after, $value, $dateAfter );
											
											if (! empty ( $nombres ) && ! empty ( $apellidos ) && (! empty ( $dateBefore ) && ! empty ( $dateAfter ))) {
												$author_personal = $apellidos [0] . ', ' . $nombres [0] . ', ' . $dateBefore [0] . '-' . $dateAfter [0];
											
											} elseif (! empty ( $nombres ) && ! empty ( $apellidos ) && ((! empty ( $dateBefore ) && empty ( $dateAfter )))) {
												$author_personal = $apellidos [0] . ', ' . $nombres [0] . ', ' . $dateBefore [0] . '-';
											
											} elseif (! empty ( $nombres ) && ! empty ( $apellidos )) {
												
												$author_personal = $apellidos [0] . ', ' . $nombres [0];
											}
											//verificacion del rol para saber en que tabla hago la relación
											

											if (strpos ( $value, "(" )) { //si viene el rol hay que identificar en que tabla hacer la relación
												$rol = array ();
												
												preg_match ( $patron_rol, $value, $rol ); //cojo el rol que viene entre parentesis
												

												$rol = mb_strtolower ( $rol [0], 'UTF-8' ); //llevo la cadena a minuscula
												

												//$rol = str_replace ( ' y ', ';', $rol ); //estos 2 pasos se deben hacer en el xml
												//$rol = str_replace ( ' e ', ';', $rol );
												

												$roles = explode ( ';', $rol ); //separo por el ; por si hay varios roles
												foreach ( $roles as $rol ) {
													$valor = trim ( $rol ); //le quito los espacios alante y atras
													

													//Verifico en que grupo de roles está
													if (in_array ( $valor, $rolesType1 )) {
														
														/*$table = $roles_allowed [$valor];
														$import->creator_personal ( $author_personal, $IdExpression, $table );*/
														
														$nomenclator = $arrayForm [$valor];
														$import->fixRolesType1and2 ( "frbr:Image", $nomenclator, "Forma gráfica", "pangea:Form", $IdManif, $author_personal );
													
													} elseif (in_array ( $valor, $rolesType2 )) {
														
														/*$table = $roles_notes [$valor];
														$import->creator_personal ( $author_personal, $IdExpression, $table );
													 	$import->yelowNote ( $value, $IdItem ); //en value esta el valor del autor original del filemaker
													   */
														$nomenclator = $arrayForm [$valor];
														$import->fixRolesType1and2 ( "frbr:Text", $nomenclator, "Forma textual", "pangea:Form", $IdManif, $author_personal );
													
													} elseif (in_array ( $valor, $rolesType3 )) {
														$propertyRelation = $arrayPropertyType3 [$valor];
														$import->fixRolesType3 ( $IdManif, $IdExpression, "frbr:Text", $propertyRelation, $author_personal );
														/*tengo que poner una variable que indique que pasé por un rol de tipo 3 
														 * para no enganchar la manifestacion con la expression mas adelante*/
														$type3 = true;
													} elseif (in_array ( $valor, $rolesType4 )) {
														
														$import->fixRolesType4 ( $IdWork, $author_personal );
													
													} elseif (in_array ( $valor, $rolesType5 )) {
														
														$import->fixRolesType5 ( $IdManif, $author_personal );
													
													} elseif (in_array ( $valor, $rolesType6 )) {
														$nomenclator = $arrayForm [$valor];
														$import->fixRolesType6 ( $IdManif, "frbr:Performance", $author_personal, $nomenclator, "Forma", "pangea:Form" );
													
													} elseif (in_array ( $valor, $rolesType7 )) {
														
														$import->fixRolesType7 ( $IdExpression, $author_personal, $valor );
													
													} elseif (in_array ( $valor, $rolesType8 )) {
														
														$import->fixRolesType8 ( $IdManif, "frbr:Performance", $author_personal );
													
													} elseif (in_array ( $valor, $rolesType9 )) { //verifico si es autor el rol porque en los roles compuestos la palabra no se puede quitar para no perder el rol
														

														$import->creator_personal ( $author_personal, $IdWork, 'frbr:creator' );
													
													} elseif (in_array ( $valor, $rolesType10 )) {
														
														$this->relateEntityWithEntity ( $author_personal, $IdManif, 'frbr:producer', 'skos:prefLabel', 'frbr:Person' );
													
													} /*elseif ($valor == 'autor') { //verifico si es autor el rol porque en los roles compuestos la palabra no se puede quitar para no perder el rol
														$import->creator_personal ( $author_personal, $IdWork, 'frbr:creator' );
													
													}elseif ($valor == 'coautor') { //lo relaciono con la obra como autor y agrego una nota
														$import->creator_personal ( $author_personal, $IdWork, 'frbr:creator' );
														$import->yelowNote ( $value, $IdItem ); //en value esta el valor del autor original del filemaker
													

													} */
												}
												//siempre pongo una nota para no perder el dato original del filemaker
												$import->yelowNote ( $value, $IdItem ); //en value esta el valor del autor original del filemaker
											

											} else //si no viene el rol, el autor se le pone directamente a la obra
												$import->creator_personal ( $author_personal, $IdWork, 'frbr:creator' );
										
										}
									}
								}
								
								foreach ( $node->author_corporateBody->DATA as $key => $value ) {
									if (($value)) {
										$author_corporate = trim ( pg_escape_string ( $value ) );
										if ($author_corporate != '') {
											$author_corporates = explode ( ';', $author_corporate );
											foreach ( $author_corporates as $author_corporate )
												$import->creator_corporate ( $author_corporate, $IdWork );
										}
									}
								}
								foreach ( $node->author_Event->DATA as $key => $value ) {
									if (($value)) {
										$author_event = trim ( pg_escape_string ( $value ) );
										if ($author_event != '') {
											$author_events = explode ( ';', $author_event );
											foreach ( $author_events as $author_event )
												$import->creator_event ( $author_event, $IdWork );
										}
									}
								}
								
								foreach ( $node->subject_concept->DATA as $key => $value ) {
									if (($value)) {
										$subject_concept = trim ( pg_escape_string ( $value ) );
										if ($subject_concept != '') {
											$subject_concepts = explode ( ';', $subject_concept );
											foreach ( $subject_concepts as $subject_concept )
												$import->subject_concept ( $subject_concept, $IdWork );
										}
									}
								}
								foreach ( $node->subject_event->DATA as $key => $value ) {
									if (($value)) {
										$subject_event = trim ( pg_escape_string ( $value ) );
										if ($subject_event != '') {
											$subject_events = explode ( ';', $subject_event );
											foreach ( $subject_events as $subject_event )
												$import->subject_event ( $subject_event, $IdWork );
										}
									}
								}
								foreach ( $node->subject_place->DATA as $key => $value ) {
									if (($value)) {
										$subject_place = trim ( pg_escape_string ( $value ) );
										if ($subject_place != '') {
											$subject_places = explode ( ';', $subject_place );
											foreach ( $subject_places as $subject_place )
												$import->subject_place ( $subject_place, $IdWork );
										}
									}
								}
								foreach ( $node->subject_personal->DATA as $key => $value ) {
									if (($value)) {
										$subject_personal = trim ( pg_escape_string ( $value ) );
										if ($subject_personal != '') {
											$subject_personals = explode ( ';', $subject_personal );
											foreach ( $subject_personals as $subject_personal )
												$import->subject_personal ( $subject_personal, $IdWork );
										}
									}
								}
								foreach ( $node->subject_CB->DATA as $key => $value ) {
									if (($value)) {
										$subject_corporate = trim ( pg_escape_string ( $value ) );
										if ($subject_corporate != '') {
											$subject_corporates = explode ( ';', $subject_corporate );
											foreach ( $subject_corporates as $subject_corporate )
												$import->subject_corporate ( $subject_corporate, $IdWork );
										}
									}
								}
								//FALTA LA RELACION DE MATERIA CON TITULO
								foreach ( $node->subject_object->DATA as $key => $value ) {
									if (($value)) {
										$subject_object = trim ( pg_escape_string ( $value ) );
										if ($subject_object != '') {
											$subject_objects = explode ( ';', $subject_object );
											foreach ( $subject_objects as $subject_object )
												$import->subject_object ( $subject_object, $IdWork );
										}
									}
								}
								
								$literary_form = trim ( pg_escape_string ( $node->form ) );
								if (($literary_form) && ($literary_form != '')) {
									$import->literary_form ( $literary_form, $IdWork );
								}
							}
							//create_relation("frbr:realization", $IdWork, $IdExpression, true);
							$import->create_relation ( "frbr:realization", $IdExpression, $IdWork );
						}
						//create_relation("frbr:embodiment", $IdExpression, $IdManif, true);
						if ($type3 == false) //significa que no pase por ningun rol tipo 3 y que tengo que relacionar a la manifest con la expres
							$import->create_relation ( "frbr:embodiment", $IdManif, $IdExpression );
					}
					//create_relation("frbr:exemplar", $IdManif, $IdItem, true);
					$import->create_relation ( "frbr:exemplar", $IdItem, $IdManif );
					
					/********************************************************************************************
					 * Cierre
					 ********************************************************************************************/
					echo "Record[$i] Finished " . $number_stock . "<br/>";
				
	//echo $number_stock . "Finished <br/>";
				

				}
			} else
				
				echo "item con numero de inventario vacio. </br>";
		}
		
		unset ( $node );
	}
	if ($i == $value_2)
		break;
	$z->next ( 'ROW' );
	$i ++;
}
$z->close ();
//pg_close ( $dbconn );
?> 