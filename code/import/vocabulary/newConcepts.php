<?php
include_once dirname ( __FILE__ ) . '/../PGImport.php';
global $import;
//Obtengo una instancia de la clase, realiza la conexion
$import = PGImport::getInstance ();


//variables que se deben cambiar para cada tipo de concepto
$parent = "frbr:Concept";
$prevParent = "frbr:Concept";
$schemeName = "Esquema de Conceptos";
$excludeNode = array ("Conceptos");

//Leo el archivo XML
$z = new XMLReader ();
if (! $z->open ( dirname ( __FILE__ ) . '/../data/concepts.xml', LIBXML_NOBLANKS )) {
	echo "No puedo abrir el archivo XML.<br/>";
	echo "Abortando...";
	exit ();
}

//Chequeo si ya existe el Esquema, si no inserto un objeto de Label y Scheme con sus relaciones
$idSchemeName = $import->setLabel ( $schemeName );
$idScheme = $import->getRelationByLiteral ( "skos:ConceptScheme", "skosxl:hiddenLabel", $idSchemeName );
if (! $idScheme) {
	$idScheme = $import->createObject ( "skos:ConceptScheme" );
	$import->setRelation ( $idScheme, "skosxl:hiddenLabel", $idSchemeName );
}
echo "IDScheme: " . $idScheme . "<br/>";

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
	doNode ( $node_0, $prevParent, $parent, $idScheme );
	//$node_1 = $value_0->children->topics->topic;
	

	echo "<br/>";
	unset ( $node );
	$z->next ( 'sheet' );
}
$z->close ();

function doNode($node_0, $prevParent, $parent, $idScheme) {
	global $import;
	foreach ( $node_0 as $key_0 => $value_0 ) {
		$nodeValue = trim ( pg_escape_string ( $value_0->title ) );
		//Chequear si es ambiguo
		$ambiguous = $import->findAmbiguous ( $nodeValue ); //verifica solo si trae los parentesis
		if ($ambiguous [0])
			$nodeValue = $ambiguous [1]; //el label sin los parentesis
		//Paso el valor a hasLabel
		$label = $import->hasLabel ( $nodeValue );
		if ($label [0] != "hasLabel") { //Es un titulo nuevo
			

			if ($value_0->children) { //Tiene hijos
				echo "-" . $nodeValue . " (crear TABLA debajo de " . $parent . ")<br/>";
				$idConcept = $import->createConcept ( $label [1], $parent, $idScheme, true );
				$node_1 = $value_0->children->topics->topic; //aqui cojo todos sus hijos
				

				doNode ( $node_1, $parent, $idConcept, $idScheme );
			
			} else { //No tiene hijos
				$idConcept = $import->createConcept ( $label [1], $parent, $idScheme );
				echo "---" . $nodeValue . " (crear ID en " . $parent . ")<br/>";
			}
			if ($ambiguous [0])
				$import->setAmbiguous ( $idConcept, $ambiguous [1], $ambiguous [2] );

		} else { //Es un titulo repetido
			if ($ambiguous [0]) { //Si es ambiguo
				if ($value_0->children) { //Si tiene hijos
					echo "---" . $nodeValue . " (crear TABLA debajo de " . $parent . ")<br/>";
					$idConcept = $import->createConcept ( $label [1], $parent, $idScheme, true );
					//Recorro todos sus hijos
					$node_1 = $value_0->children->topics->topic; //aqui cojo todos sus hijos
					//$parent = doNode ($node_1, $value_0->title, $parent, $idScheme);
					foreach ( $node_1 as $key_1 => $value_1 ) {
						$nodeValue = trim ( pg_escape_string ( $value_1->title ) );
						$label = $import->hasLabel ( $nodeValue );
						$idConcept2 = $import->createConcept ( $label [1], $idConcept, $idScheme );
						//echo "---" . $nodeValue . " (crear ID en " . $tableName . ")<br/>";
					}
				} else { //No tiene hijos
					$idConcept = $import->createConcept ( $label [1], $parent, $idScheme );
					echo "---" . $nodeValue . " (crear ID en " . $parent . ")<br/>";
				}
				$import->setAmbiguous ( $idConcept, $ambiguous [1], $ambiguous [2] );
			} else { //No es ambiguo, relacionar el id de concept que ya esta relacionado con ese label con el nuevo padre
				echo "---" . $nodeValue . " (es un titulo repetido de " . $parent . ")<br/>";
				//$import->updateParent ( $nodeValue, $parent );
				$import->updateParent ( $label [1], $parent );
			}
		
		//$parent = $prevParent;
		}
	} //Cierro el foreach
}

echo "Done...";
?>	