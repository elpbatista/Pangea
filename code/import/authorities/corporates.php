<?php
include_once dirname ( __FILE__ ) . '/../PGImport.php';
$import = PGImport::getInstance ();

$nameColumn = array ();
$entity = 'frbr:CorporateBody';

$dom = new DOMDocument ();
if (! $dom->load ( dirname ( __FILE__ ) . '/../data/corporativas.xml' )) {
	echo "No puedo abrir el archivo XML.<br/>";
	echo "Abortando...";
	exit ();
}
$rows = $dom->getElementsByTagName ( 'Row' );
$firstRow = true;
$idEntity = '';
$entityExist = false;
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
					case 0 : //Entidad corporativa
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$label = trim ( pg_escape_string ( $cell->nodeValue ) );
							//$idEntity = $import->insertAndRelateEntity ( $label, $entity, 'pangea:name' ); //el nombre ahora se echa en el prefLabel descontrolado
							//$import->relateEntity ( $label, $idEntity, 'skosxl:prefLabel');
							//antes de insertarla verifico que ya no este
							$exist = $import->verifyEntity($label,'skos:prefLabel', $entity );
							if($exist){
								$entityExist = true;
								echo "la entidad ya existe.";
								break;
							}							 
							$idEntity = $import->createObject($entity);
							$import->setRelationLiteral ( $idEntity, 'pangea:name', $label, 'xsd:string', 'sp' );
							$import->setRelationLiteral ( $idEntity, 'skos:prefLabel', $label, 'xsd:string', 'sp' );
						}
						break;
					case 1 : //Fecha de referencia
						if($entityExist)
						  break;
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$value = trim ( pg_escape_string ( $cell->nodeValue ) );
							$import->setRelationLiteral ( $idEntity, 'pangea:startDate', $value, 'xsd:date', 'no' );
						}
						break;
					case 2 : //Lugar
						//if($entityExist)
						 // break;
						//echo $nameColumn[$index].' - '.$cell -> nodeValue.'<br/>';
						//$nodeValue = trim(pg_escape_string($cell -> nodeValue));
						//$import -> setEntity ($nodeValue, $entity, 'pangea:startDate');
						break;
					case 3 : //País
						//if($entityExist)
						  //break;
						//echo $nameColumn[$index].' - '.$cell -> nodeValue.'<br/>';
						//$nodeValue = trim(pg_escape_string($cell -> nodeValue));
						//$import -> setEntity ($nodeValue, $entity, 'pangea:endDate');
						break;
					case 4 : //Iniciales (T. Alternativo)
						if($entityExist)
						  break;
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$label = trim ( pg_escape_string ( $cell->nodeValue ) );
							//$import->relateEntity ( $label, $idEntity, 'skosxl:altLabel' ); //ahora se echa en el altLabel descontrolado
							$import->setRelationLiteral ( $idEntity, 'skos:altLabel', $label, 'xsd:string', 'sp' );
						}
						break;
					case 5 : //Observaciones
						if($entityExist)
						  break;
						echo $nameColumn [$index] . ' - ' . $cell->nodeValue . '<br/>';
						if (! ($cell->nodeValue == '')) {
							$value = trim ( pg_escape_string ( $cell->nodeValue ) );
							$import->setRelationLiteral ( $idEntity, 'rdfs:comment', $value, 'xsd:string', 'sp' );
						}
						break;
					case 6 : //Tipo relacion con un nomenclador
						if($entityExist)
						  break;
						if (! ($cell->nodeValue == '')) {
							echo "propiedad tipo";
							$label = trim ( pg_escape_string ( $cell->nodeValue ) );
							$labels = explode ( ';', $label );
							foreach ( $labels as $label ) {
								echo $nameColumn [$index] . ' - ' . $label . '<br/>';
								$import->relateEntityWithConcept( trim(pg_escape_string($label)), $idEntity, 'pangea:hasSubject', 'skosxl:prefLabel', 'pangea:Subject' );
							}
						}
						break;
					case 7 : //Indización 
						//echo $nameColumn[$index].' - '.$cell -> nodeValue.'<br/>';
						//$nodeValue = trim(pg_escape_string($cell -> nodeValue));
						//$import -> setEntity ($nodeValue, $entity, 'rdfs:comment');
						break;
				
				}
			}
			$entityExist = false; //vuelvo a poner el valor en falso para ingresar la próxima entidad
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
print_r ( $nameColumn );

echo 'Done...';

?>

