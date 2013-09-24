<?php
include_once 'function_import.php';

date_default_timezone_set ( 'Cuba' );

$time_start = microtime ( true );

//***********************

$fileName = 'log ' . date ('Y-m-d his A') . '.txt';

$file = 'rdf1.xml';

$reader = new XMLReader ();

if (! $reader->open ( 'data/' . $file ))
	echo 'No se abre el fichero';

	//***********************
if (! $gestor = fopen ( 'data/'.$fileName, 'a+' )) {
	echo "No se puede abrir el archivo ($fileName)";
	exit ();
}

$reader->read ();
$id = '';
$lang = '';
$dataType = '';
$object = '';
$table = '';
$rowId = '';

ini_set ( 'memory_limit', '3072M' );

readRDF ( $reader, '', $id, $lang, $dataType, $object, $table, $rowId, $gestor );
$reader->close ();

$time_end = microtime ( true );
$time = $time_end - $time_start; //calculo el tiempo de ejecución

$content = '<br/>Tiempo de ejecución: ' . $time . ' segundos\n';

fwrite ( $gestor, $content ); //escribo en el fichero el tiempo que se demoro la ejecución
fclose($gestor);

echo $content;


//-----------------------------FUNCIONES-------------------------------------------------------

function updateSequence($reader, $import) {
	while ( $reader->read () && $reader->name != 'pangea:dbSequence' ) {
	}
		
	$reader->read (); //leo el nodo texto que tiene el valor de la secuencia
	
	$value = $reader->value; //este es el valor por donde se quedo la secuencia de ids
	$import->updateSequence ( $value );
	
}


function readRDF($reader, $name, $id, $lang, $dataType, $object, $table, $rowId, $gestor) {
	$import = DataImport::getInstance ();
	while ( $reader->read () && $reader->name == 'rdf:RDF' ) {

		$reader->next (); //leo un nodo texto
	}
	$reader->next (); //leo el nodo de ontologia

	//aqui va la parte de actualizar la secuencia
	updateSequence($reader, $import);
	
	readXML ( $reader, $name, $id, $lang, $dataType, $object, $table, $rowId, $import, $gestor );
	echo 'Finish...';

}


function readXML($reader, $name, $id, $lang, $dataType, $object, $table, $rowId, $import, $gestor) {
	while ( $reader->read () ) {
		switch ($reader->nodeType) {
			case XMLReader::TEXT :
				//este es el valor de la propiedad anterior
				$value = $reader->value;
				//echo 'Texto del nodo: ' . $value . '; <br/>';
				//*************************
				$content = 'Texto del nodo: ' . $value . "\n";
				fwrite ( $gestor, $content );
				$import->setRelationLiteralWithIdRow ( $id, $table, $value, $dataType, $lang, $rowId);
				break;
			case XMLReader::ELEMENT :
				$table = $reader->name;
				if (($table !== 'owl:Ontology') && ($table !== 'rdf:RDF')) {
					//echo '<br/>Nodo: ' . $table . ' <br/>';
					//**************************************
					$content = 'Nodo: ' . $table . "\n";
					fwrite ( $gestor, $content );
					if ($reader->hasAttributes) {
						$id2 = $reader->getAttribute ( 'rdf:ID' );
						if ($id2 != '') { //es una entidad
							//echo 'entidad ' . $table . ' id= ' . $id .'  <br/>';
							//****************************************
							$content = 'Entidad: ' . $table . ' id= ' . $id2 . "\n";
							fwrite ( $gestor, $content );
							$id = $id2;
							
							$import->insertEntity ( $table, $id );
						} else { //si no tiene el atributo ID es una propiedad
							$object = $reader->getAttribute ( 'rdf:resource' );
							if ($object != '') { //es una relacion entidad - entidad
								//echo 'Atributo: rdf:resource  = ' . $object . '<br/>';
								//****************************************
								$rowId = $reader->getAttribute ( 'pangea:dbid' );
								$content = 'Atributo: rdf:resource  = ' . $object . "\n";
								fwrite ( $gestor, $content );
								//$object = $object2;
								$import->setRelationWithIdRow ( $id, $table, $object, $rowId );
								
							} else { //es una relacion entidad - literal
								$lang = $reader->getAttribute ( 'xml:lang' );
								$dataType = $reader->getAttribute ( 'rdf:datatype' );
								$rowId = $reader->getAttribute ( 'pangea:dbid' );
							}
						}
					}
					if (! $reader->isEmptyElement) {
						readXML ( $reader, $reader->name, $id, $lang, $dataType, $object, $table, $rowId, $import, $gestor );
					}
				}
				break;
		}
	}

}

?>