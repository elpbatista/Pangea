<?php
include_once dirname ( __FILE__ ) . '/../PGImport.php';
$import = PGImport::getInstance ();

$nameColumn = array ();
$entity = 'frbr:Person';

$dom = new DOMDocument ();
if (! $dom->load ( dirname ( __FILE__ ) . '/../data/personas.xml' )) {
	echo "No puedo abrir el archivo XML.<br/>";
	echo "Abortando...";
	exit ();
}
$rows = $dom->getElementsByTagName ( 'Row' );
$firstRow = true;
$idEntity = '';
foreach ( $rows as $row ) {
	$cells = $row->getElementsByTagName ( 'Cell' );
	if (! $firstRow) {
		$index = 0;
		foreach ( $cells as $cell ) {
			$cellIndex = $cell->getAttribute ( 'ss:Index' );
			if ($cellIndex) {
				$index = $cellIndex - 1;
			}
			if ($cell->nodeValue) {
				//echo $nameColumn[$index].'-'.$cell -> nodeValue.'<br/>';
				switch ($index) {
					case 0 : //Término preferido 
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$nodeValue = trim ( pg_escape_string ( $cell->nodeValue ) );
							//$idEntity = $import->insertAndRelateEntity ( $nodeValue, $entity, 'skosxl:prefLabel' );
							$idEntity = $import->createObject($entity);
							$import->setRelationLiteral ( $idEntity, 'skos:prefLabel', $nodeValue, 'xsd:string', 'sp' );
						} else
							echo 'No existe termino preferido.';
						break;
					case 1 : //Nombres
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$nodeValue = trim ( pg_escape_string ( $cell->nodeValue ) );
							//$import->relateEntity ( $nodeValue, $idEntity, 'pangea:name' );
							$import->setRelationLiteral ( $idEntity, 'pangea:firstName', $nodeValue, 'xsd:string', 'sp' );
						}
						break;
					case 2 : //Apellidos
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$nodeValue = trim ( pg_escape_string ( $cell->nodeValue ) );
							//$import->relateEntity ( $nodeValue, $idEntity, 'pangea:lastName' );
							$import->setRelationLiteral ( $idEntity, 'pangea:lastName', $nodeValue, 'xsd:string', 'sp' );
						}
						break;
					case 3 : //Fecha Nacimiento
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$nodeValue = trim ( pg_escape_string ( $cell->nodeValue ) );
							$import->setRelationLiteral ( $idEntity, 'pangea:birthDate', $nodeValue, 'xsd:date', 'no' );
						}
						break;
					case 4 : //Lugar Nacimiento relacion con un lugar
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$nodeValue = trim ( pg_escape_string ( $cell->nodeValue ) );
							$import->relateEntityWithEntity ( $nodeValue, $idEntity, 'pangea:birthPlace', 'skos:prefLabel', 'frbr:Place' );
						}
						break;
					case 6 : //Fecha Muerte
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$nodeValue = trim ( pg_escape_string ( $cell->nodeValue ) );
							$import->setRelationLiteral ( $idEntity, 'pangea:deathDate', $nodeValue, 'xsd:date', 'no' );
						}
						break;
					case 7 : //Lugar Muerte 
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$nodeValue = trim ( pg_escape_string ( $cell->nodeValue ) );
							$import->relateEntityWithEntity ( $nodeValue, $idEntity, 'pangea:deathPlace', 'skos:prefLabel', 'frbr:Place' );
						}
						break;
					case 10 : //Títulos nobiliarios 
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$nodeValue = trim ( pg_escape_string ( $cell->nodeValue ) );
							//$import->relateEntity ( $nodeValue, $idEntity, 'pangea:prefix' );
							$import->setRelationLiteral ( $idEntity, 'pangea:namePrefix', $nodeValue, 'xsd:string', 'sp' );
						}
						break;
					case 12 : //Seudónimos label alternativo
						$matrix = array ();
						//echo $nameColumn[$index].' - '.$cell -> nodeValue.'<br/>';
						if (! ($cell->nodeValue == '')) {
							$nodeValue = trim ( pg_escape_string ( $cell->nodeValue ) );
							preg_match_all ( '/\b[\w\s\.\-' . utf8_encode ( '\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ä\ë\ö\ü\Ä\Ë\Ï\Ö\Ü\ç\à\è\ì\ò\ù\ÿ\’\´' ) . ']+/', $nodeValue, $matrix );
							foreach ( $matrix [0] as $key => $value ) {
								echo $nameColumn [$index] . ' - ' . $value . '<br/>';
								//$import->relateEntity ( trim ( $value ), $idEntity, 'skosxl:altLabel' );
								$import->setRelationLiteral ( $idEntity, 'skos:altLabel', trim ( $value ), 'xsd:string', 'sp' );
								
							}
							unset ( $matrix );
						}
						break;
					case 13 : //Anagramas 
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$nodeValue = trim ( pg_escape_string ( $cell->nodeValue ) );
							//$import->relateEntity ( $nodeValue, $idEntity, 'skosxl:altLabel' );
							$import->setRelationLiteral ( $idEntity, 'skos:altLabel', $nodeValue, 'xsd:string', 'sp' );
						}
						break;
					case 14 : //Iniciales 
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$nodeValue = trim ( pg_escape_string ( $cell->nodeValue ) );
							//$import->relateEntity ( $nodeValue, $idEntity, 'skosxl:altLabel' );
							$import->setRelationLiteral ( $idEntity, 'skos:altLabel', $nodeValue, 'xsd:string', 'sp' );
						}
						break;
					case 15 : //Conceptos Autorizados  relacion con nomencladores
						$matrix = array ();
						if (! ($cell->nodeValue == '')) {
							$nodeValue = trim ( pg_escape_string ( $cell->nodeValue ) );
							// No hay responsabilidades en los conceptos
							preg_match_all ( '/\b[\w\s\.\-' . utf8_encode ( '\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ä\ë\ö\ü\Ä\Ë\Ï\Ö\Ü\ç\à\è\ì\ò\ù\ÿ\’\´' ) . ']+/', $nodeValue, $matrix );
							foreach ( $matrix [0] as $key => $value ) {
								echo $nameColumn [$index] . ' - ' . $value . '<br/>';
								//$import->relateEntityWithEntity ( trim ( $value ), $idEntity, 'pangea:xType', 'skosxl:prefLabel', 'frbr:Concept' );
								$import->relateEntityWithConcept(  trim ( $value ), $idEntity, 'pangea:hasSubject', 'skosxl:prefLabel', 'pangea:Subject');
							}
							
							unset ( $matrix );
						}
						break;
					case 17 : //Síntesis biográfica 
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$nodeValue = trim ( pg_escape_string ( $cell->nodeValue ) );
							$import->setRelationLiteral ( $idEntity, 'rdfs:comment', $nodeValue, 'xsd:string', 'sp' );
						}
						break;
				}
			}
			++ $index;
		}
	} else { //inserto en la matriz los nombres de los campos de la primera fila
		foreach ( $cells as $cell ) {
			array_push ( $nameColumn, $cell->nodeValue );
		}
	}
	$firstRow = false;
	echo '<br/>';
}
//print_r($nameColumn);


echo 'Done...';

?>

