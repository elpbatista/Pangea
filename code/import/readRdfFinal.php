<?php
include_once 'function_import.php';

$file = 'rdf.xml';
$reader = new XMLReader ();
if (! $reader->open ( 'data/' . $file ))
	echo 'No se abre el fichero';

$reader->read ();
$id = '';
$lang = '';
$dataType = '';
$object = '';
$table = '';

ini_set ( 'memory_limit', '512M' );

readRDF ( $reader, '', $id, $lang, $dataType, $object, $table );
$reader->close ();

function readRDF($reader, $name, $id, $lang, $dataType, $object, $table) {
	$import = DataImport::getInstance ();
	while ( $reader->read () && $reader->name == 'rdf:RDF' ) {
		
		$reader->next (); //leo un nodo texto
	}
	$reader->next (); //leo el nodo de ontologia
	

	//aqui va la parte de actualizar la secuencia
	updateSequence ( $reader, $import );
	
	readXML ( $reader, $name, $id, $lang, $dataType, $object, $table, $import );
	echo 'Finish...';

}
function updateSequence($reader, $import) {
	while ( $reader->read () && $reader->name != 'pangea:dbSequence' ) {
	}
	
	$reader->read (); //leo el nodo texto que tiene el valor de la secuencia
	

	$value = $reader->value;
	$import->updateSequence ( $value );

}

function readXML($reader, $name, $id, $lang, $dataType, $object, $table, $import) {
	while ( $reader->read () ) {
		switch ($reader->nodeType) {
			case XMLReader::TEXT :
				//este es el valor de la propiedad anterior
				$value = $reader->value;
				//echo 'Texto del nodo: ' . $value . '; <br/>';
				$import->setRelationLiteral ( $id, $table, $value, $dataType, $lang );
				break;
			case XMLReader::ELEMENT :
				$table = $reader->name;
				if (($table !== 'owl:Ontology') && ($table !== 'rdf:RDF')) {
					echo '<br/>Nodo: ' . $table . ' <br/>';
					if ($reader->hasAttributes) {
						
						$id2 = $reader->getAttribute ( 'rdf:ID' );
						if ($id2 != '') { //es una entidad
							//echo 'entidad ' . $table . ' id= ' . $id .'  <br/>';
							$id = $id2;
							$import->insertEntity ( $table, $id );
						} else { //si no tiene el atributo ID es una propiedad
							$object2 = $reader->getAttribute ( 'rdf:resource' );
							if ($object2 != '') { //es una relacion entidad - entidad
								//echo 'Atributo: rdf:resource  = ' . $object . '<br/>';
								$object = $object2;
								
								$import->setRelation ( $id, $table, $object );
							} else { //es una relacion entidad - literal
								$lang = $reader->getAttribute ( 'xml:lang' );
								$dataType = $reader->getAttribute ( 'rdf:datatype' );
							
		//echo 'Atributo: xml:lang ' . ' = ' . $lang . '<br/>';
							//echo 'Atributo: rdf:datatype ' . ' = ' . $dataType . '<br/>';
							}
						}
					
		//while($reader->moveToNextAttribute()){
					//echo 'Atributo: '.$reader->name .' = ' . $reader->value .'; <br/>';
					//}
					

					}
					if (! $reader->isEmptyElement) {
						readXML ( $reader, $reader->name, $id, $lang, $dataType, $object, $table, $import );
					}
				}
				break;
		}
	}

}

/*
function readXML ($reader, $name) {
$textcontent = '';
	while($reader->read()){
		if($reader->nodeType == XMLReader::TEXT){
			$textcontent = $reader->value;
		}
		if($reader->nodeType == XMLReader::ELEMENT){	
				echo 'Nodo: '.$reader->name.' = ' . $textcontent .'<br/>';
				if ($reader->hasAttributes) {
					while($reader->moveToNextAttribute()){
						echo 'Atributos: '.$reader->name .' = ' . $reader->value .'<br/>';
					}
				}
				if(!$reader->isEmptyElement){
					readXML ($reader, $reader->name);
				}
			echo '<br/>';	
			//$reader->next();
		}
	}
}
*/
/*
$reader->read();
	while($reader->read()){
		if($reader->nodeType == XMLReader::ELEMENT){
			if ($reader->name !== 'owl:Ontology') {
				echo 'Nodo: '.$reader->name.' ';
				if ($reader->hasAttributes) {
					while($reader->moveToNextAttribute()){
						echo 'Atributo: '.$reader->name .' = ' . $reader->value.' ';
					}
				}
				if(!$reader->isEmptyElement){
					while($reader->read()){
						if($reader->nodeType == XMLReader::ELEMENT){
							echo '<br/>SubNodo: '.$reader->name .' = ' . $reader->value.' ';
						}
					}
				}
			echo '<br/>';	
			}
			$reader->next();
			
		}
	}
*/
?>