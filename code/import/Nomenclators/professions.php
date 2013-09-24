<?php
include_once dirname ( __FILE__ ) . '/../PGImport.php';
global $import;
//Obtengo una instancia de la clase, realiza la conexion
$import = PGImport::getInstance ();
//$import -> deleteTables();


//variables que se deben cambiar para cada tipo de concepto
$nomenclatorTable = "pangea:Subject";
$parent = "pangea:Subject";
//$schemeName = "Esquema de Conceptos";
$excludeNode = array ();

//Leo el archivo XML
$z = new XMLReader ();
if (! $z->open ( dirname ( __FILE__ ) . '/../data/professions.xml', LIBXML_NOBLANKS )) {
	echo "No puedo abrir el archivo XML.<br/>";
	echo "Abortando...";
	exit ();
}

//Comienzo a recorrer el arbol del XML
while ( $z->read () && $z->name !== 'sheet' )
	;
while ( $z->name === 'sheet' ) {
	$node = new SimpleXMLElement ( $z->readOuterXML () );
	//Compruebo si existe algun nodo que deba saltar
	if (in_array ( $node->topic->title, $excludeNode )) {
		$node_0 = $node->topic->children->topics->topic;
	} else {
		$node_0 = $node->topic;
	}
	//Comienzo el ciclo
	doNode ( $node_0, $nomenclatorTable, $parent );
	//$node_1 = $value_0->children->topics->topic;
	

	echo "<br/>";
	unset ( $node );
	$z->next ( 'sheet' );
}
$z->close ();

function doNode($node_0, $nomenclatorTable, $parent) {
	global $import;
	foreach ( $node_0 as $key_0 => $value_0 ) {
		$nodeValue = trim ( pg_escape_string ( $value_0->title ) );
		//Paso el valor a hasLabel
		$label = $import->hasLabelNomenclator ( $nodeValue );
		if ($label) { //siempre lo trato como si fuera nuevo
			if ($value_0->children) { //Tiene hijos
				echo "-" . $nodeValue . " (crear TABLA debajo de " . $nomenclatorTable . ")<br/>";
				//$tableName = $import->isParentNomenclator ( $nodeValue, $label, $parent );
				$idNomenclator = $import->createNomenclator ( $label, $nomenclatorTable, $parent );
				if ($idNomenclator) {
					$node_1 = $value_0->children->topics->topic; //aqui cojo todos sus hijos
					doNode ( $node_1, $nomenclatorTable, $idNomenclator );
				}
			} else { //No tiene hijos
				$idNomenclator = $import->createNomenclator ( $label, $nomenclatorTable, $parent );
				//$idConcept = $import->isSonNomenclator ( $label, $parent );
				echo "---" . $nodeValue . " (crear ID en " . $parent . ")<br/>";
			}
		
		}
	} //Cierro el foreach
}

echo "Done...";
?>	


