<?php
include_once dirname ( __FILE__ ) . '/../PGImport.php';
$import = PGImport::getInstance ();

$nameColumn = array ();
$entity = 'frbr:Place';

$dom = new DOMDocument ();
if (! $dom->load ( dirname ( __FILE__ ) . '/../data/lugares.xml' )) {
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
				switch ($index) {
					case 0 : //Nombre del lugar
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$label = trim ( pg_escape_string ( $cell->nodeValue ) );
							$exist = $import->verifyEntity ( $entity, $label );
							if (! $exist) {
								$idEntity = $import->createObject ( $entity );
								$import->setRelationLiteral ( $idEntity, 'pangea:name', $label, 'xsd:string', 'sp' );
								$import->setRelationLiteral ( $idEntity, 'skos:prefLabel', $label, 'xsd:string', 'sp' );
							} else
								echo "lugar repetido " . $label;
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

