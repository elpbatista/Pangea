<?php
include_once dirname ( __FILE__ ) . '/../PGImport.php';
$import = PGImport::getInstance ();

$nameColumn = array ();
$entity = 'frbr:Event';

$dom = new DOMDocument ();
if (! $dom->load ( dirname ( __FILE__ ) . '/../data/eventos.xml' )) {
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
					case 0 : //Nombre
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$label = trim ( pg_escape_string ( $cell->nodeValue ) );
							//$idEntity = $import->insertAndRelateEntity ( $label, $entity, 'pangea:name' );
							//$import->relateEntity ( $label, $idEntity, 'skosxl:prefLabel' );
							$idEntity = $import->createObject($entity);
							$import->setRelationLiteral ( $idEntity, 'pangea:name', $label, 'xsd:string', 'sp' );
							$import->setRelationLiteral ( $idEntity, 'skos:prefLabel', $label, 'xsd:string', 'sp' );
						}
						break;
					case 1 : //Nombre alternativo
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$label = trim ( pg_escape_string ( $cell->nodeValue ) );
							//$import->relateEntity ( $label, $idEntity, 'skosxl:altLabel' );
							$import->setRelationLiteral ( $idEntity, 'skos:altLabel', $label, 'xsd:string', 'sp' );
						}
						break;
					case 2 : //Fecha de inicio
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$value = trim ( pg_escape_string ( $cell->nodeValue ) );
							$import->setRelationLiteral ( $idEntity, 'pangea:startDate', $value, 'xsd:date', 'no' );
						}
						break;
					case 3 : //Feha de terminación
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$value = trim ( pg_escape_string ( $cell->nodeValue ) );
							$import->setRelationLiteral ( $idEntity, 'pangea:endDate', $value, 'xsd:date', 'no' );
						}
						break;
					case 4 : //Lugar donde sucede
						//echo $nameColumn[$index].' - '.$cell -> nodeValue.'<br/>';
						//$nodeValue = trim(pg_escape_string($cell -> nodeValue));
						//$import -> setEntity ($nodeValue, $entity, 'pangea:xType');
						break;
					case 5 : //Lugar involucrado
						//echo $nameColumn[$index].' - '.$cell -> nodeValue.'<br/>';
						//$nodeValue = trim(pg_escape_string($cell -> nodeValue));
						//$import -> setEntity ($nodeValue, $entity, 'pangea:xType');
						break;
					case 6 : //Principales personas
						//echo $nameColumn[$index].' - '.$cell -> nodeValue.'<br/>';
						//$nodeValue = trim(pg_escape_string($cell -> nodeValue));
						//$import -> setEntity ($nodeValue, $entity, 'pangea:name');
						break;
					case 7 : //Síntesis 
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$value = trim ( pg_escape_string ( $cell->nodeValue ) );
							$import->setRelationLiteral ( $idEntity, 'rdfs:comment', $value, 'xsd:string', 'sp' );
						}
						break;
					case 8 : //Tipo de evento  relacion con un nomenclador
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$label = trim ( pg_escape_string ( $cell->nodeValue ) );
							$import->relateEntityWithConcept( $label, $idEntity, 'pangea:hasSubject', 'skosxl:prefLabel', 'pangea:Subject');
						}
						break;
					case 9 : //Número
						//echo $nameColumn[$index].' - '.$cell -> nodeValue.'<br/>';
						//$nodeValue = trim(pg_escape_string($cell -> nodeValue));
						//$import -> setEntity ($nodeValue, $entity, 'pangea:endDate');
						break;
					case 10 : //Palabras clave
						//echo $nameColumn[$index].' - '.$cell -> nodeValue.'<br/>';
						//$nodeValue = trim(pg_escape_string($cell -> nodeValue));
						//$import -> setEntity ($nodeValue, $entity, 'pangea:xType');
						break;
					case 11 : //Relación con otro evento
						//echo $nameColumn[$index].' - '.$cell -> nodeValue.'<br/>';
						//$nodeValue = trim(pg_escape_string($cell -> nodeValue));
						//$import -> setEntity ($nodeValue, $entity, 'pangea:xType');
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

