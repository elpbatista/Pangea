<?php
include_once dirname ( __FILE__ ) . '/../PGImport.php';
$import = PGImport::getInstance ();

$nameColumn = array ();
$entity = 'frbr:Concept';

$dom = new DOMDocument ();
if (! $dom->load ( dirname ( __FILE__ ) . '/../data/relacionados.xml' )) {
	echo "No puedo abrir el archivo XML.<br/>";
	echo "Abortando...";
	exit ();
}
$rows = $dom->getElementsByTagName ( 'Row' );
$firstRow = true;
$idConcept = '';
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
				switch ($index) { //estamos contando con que todos los conceptos ya estan levantados
					case 0 : //Término, busco el id del concepto
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$label = trim ( pg_escape_string ( $cell->nodeValue ) );
							$idConcept = $import->verifyConcept ( "frbr:Concept", $label );
						}
						/*else {	//el término no existe, lo agrego
							$idEntity = $import->createObject($entity);
							$import->setRelationLiteral ( $idEntity, 'pangea:name', $label, 'xsd:string', 'sp' );
							$import->setRelationLiteral ( $idEntity, 'skos:prefLabel', $label, 'xsd:string', 'sp' );
						}*/
						break;
					case 1 : //Término relacionado
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$label = trim ( pg_escape_string ( $cell->nodeValue ) );
							$labels = explode ( ';', $label );
							foreach ( $labels as $label ) {
								echo $nameColumn [$index] . ' - ' . $label . '<br/>';
								$idConceptRelated = $import->verifyConcept ( "frbr:Concept", trim($label) );
								echo $idConcept ." skos:related ".$idConceptRelated;
								$import->setRelation ( $idConcept, "skos:related", $idConceptRelated );
							}
						
						}
						break;
					case 2 : //Término alternativo
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$label = trim ( pg_escape_string ( $cell->nodeValue ) );
							$labels = explode ( ';', $label );
							foreach ( $labels as $label ) {
								echo $nameColumn [$index] . ' - ' . $label . '<br/>';
								$idLabel = $import->setLabel ( trim ( $label ) ); //lo busca y si no está lo levanta
								echo $idConcept ." skosxl:altLabel ".$idLabel;
								$import->setRelation ( $idConcept, "skosxl:altLabel", $idLabel );
							}
						
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

