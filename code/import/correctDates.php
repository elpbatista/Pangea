<?php
$file = 'VH3.xml';
$file2 = 'VH4.xml';
//$fileName = 'C:\Users\Bell\Desktop\rolesPrueba.txt';
/*if (! $gestor = fopen ( $fileName, 'a' )) {
	echo "No se puede abrir el archivo ($fileName)";
	exit ();
}*/

/*$z = new XMLReader ();
//$z->open('data/BH.xml');
$z->open ( 'C:\Users\Bell\Desktop/' . $file );

while ( $z->read () && $z->name !== 'ROW' )
	$i = 1;

while ( $z->name === 'ROW' ) {
	
	$node = new SimpleXMLElement ( $z->readOuterXML () );

	foreach ( $node->author_personal->DATA as $key => $value ) {
		
		if ($value) {
			$autor = $node->author_personal->DATA; 
			$value = trim ( pg_escape_string ( $node->id_biblio ) );
			if ($value != '') {
				$rol = array ();
				$patron_rol = '/(?<=\()[\w\s]+(?=\))/';
				
				preg_match ( $patron_rol, $value, $rol );
				if (! empty ( $rol )) {
					
					//hago las verificaciones
					
					//actualizo el valor del rol luego de las verificaciones

					//$domNode = dom_import_simplexml ( $node );
					$newValue = 'hola';
					//$domNode->setAttribute ( $key, $newValue );
					//$node = simplexml_import_dom ( $domNode );//lo vulevo a convertir a simpleXmlElement para seguirlo recorriendo
					$autor->addChild('0', $newValue);
									
	             //$content = $rol [0] . "\n";
				//fwrite ( $gestor, utf8_decode($content) );
				//}
				}
			}
		}
	}
	echo $i . '<br/>';
	$i ++;
	//aqui habria que escribir el nodo en el fichero
	$z->next ( 'ROW' );
}
*/

$doc = new DOMDocument ();
$doc->load ( 'C:\Users\Bell\Desktop\xml salva junio/' . $file );

$patron_1 = '/^\[c [0-9]{4}\]/';
$patron_2 = '/^[0-9]{4} \[i\.e\. [0-9]{4}\]/';
$patron_3 = '/^\[[0-9]{4}\?\]/';
$patron_4 = '/^\[ca [0-9]{4}\]/';
$patron_5 = '/^\[[0-9]{3}\-\]/';
$patron_6 = '/^\[[0-9]{3}\-\?\]/';
$patron_7 = '/^\[[0-9]{2}\-\-\]/';
$patron_8 = '/^\[[0-9]{2}\-\-\?\]/';
$patron_9 = '/^[0-9]{4}\-[0-9]{4}/';
$patron_10 = '/^\[[0-9]{4}\]/';
$patron_normal = '/^[0-9]{4}/';
//las fechas que se arreglan son la fecha de entrada 'in_date', la fecha de edición 'ed_date' y la fecha de impresión 'pr_date'
$list = $doc->getElementsByTagName ( 'ed_date' ); //aquí se debe cambiar el tipo de fecha que se va a arreglar

for($i = 0; $i < $list->length; $i ++) {
	$date = $list->item ( $i );
	$value = $date->nodeValue;
	if ($value != '') {
		
		$newdate = array ();
		if (preg_match ( $patron_10, $value )) {
			//hay que quitar los corchetes y quedarse con el año
			$newdate [] = str_replace ( '[', '', $value );
			$newdate [0] = '00/00/' . trim ( str_replace ( ']', '', $newdate [0] ) );
			
		} elseif (preg_match ( $patron_normal, $value )) {
			//viene el año solo			
			$newdate [] = '00/00/' . $value;
			
		} elseif (preg_match ( $patron_1, $value )) {
			//hay que coger el año quitando la c y los corchetes
			$newdate [] = str_replace ( '[c', '', $value );
			$newdate [0] = '00/00/' . trim ( str_replace ( ']', '', $newdate [0] ) );
		
		} elseif (preg_match ( $patron_2, $value )) {
			//hay que coger los dos años
			$temp = str_replace ( '[i.e.', '*', $value );
			$temp = trim ( str_replace ( ']', '', $temp ) );
			$temp = explode ( '*', $value );
			foreach ( $temp as $temp ) {
				$newdate [] = '00/00/' . $temp;
			}
		} elseif (preg_match ( $patron_3, $value )) {
			//hay que coger el año quitando el signo de interrogacion y los corchetes
			$newdate [] = str_replace ( '[', '', $value );
			$newdate [0] = '00/00/' . trim ( str_replace ( '?]', '', $newdate [0] ) );
		
		} elseif (preg_match ( $patron_4, $value )) {
			//hay que coger el año quitando la ca y los corchetes
			$temp = str_replace ( '[ca', '', $value );
			$temp = trim ( str_replace ( ']', '', $temp ) );
			$firstdate = $temp - 5;
			$lastdate = $temp + 5;
			$newdate [] = '00/00/' . $firstdate;
			$newdate [] = '00/00/' . $lastdate;
		
		} elseif (preg_match ( $patron_5, $value )) {
			//hay que hacer un rago de fechas de 10 años y quitar los corchetes y el quión
			$temp = str_replace ( '[', '', $value );
			$temp = trim ( str_replace ( '-]', '', $temp ) );
			$newdate [] = '00/00/' . $temp . '0';
			$newdate [] = '00/00/' . $temp . '9';
		
		} elseif (preg_match ( $patron_6, $value )) {
			//hay que hacer un rago de fechas de 10 años y quitar los corchetes, el quión y el signo de interrogacion
			$temp = str_replace ( '[', '', $value );
			$temp = trim ( str_replace ( '-?]', '', $temp ) );
			$newdate [] = '00/00/' . $temp . '0';
			$newdate [] = '00/00/' . $temp . '9';
		
		} elseif (preg_match ( $patron_7, $value )) {
			//hay que hacer un rago de fechas de 100 años y quitar los corchetes y los quiones
			$temp = str_replace ( '[', '', $value );
			$temp = trim ( str_replace ( '--]', '', $temp ) );
			$newdate [] = '00/00/' . $temp . '00';
			$newdate [] = '00/00/' . $temp . '99';
		
		} elseif (preg_match ( $patron_8, $value )) {
			//hay que hacer un rago de fechas de 100 años y quitar los corchetes, los quiones y el signo de interrogacion
			$temp = str_replace ( '[', '', $value );
			$temp = trim ( str_replace ( '--?]', '', $temp ) );
			$newdate [] = '00/00/' . $temp . '00';
			$newdate [] = '00/00/' . $temp . '99';
		
		} elseif (preg_match ( $patron_9, $value )) {
			//hay que quitar el guión y coger las dos fechas
			$temp = explode ( '-', $value );
			foreach ( $temp as $temp1 ) {
				$newdate [] = '00/00/' . $temp1;
			}
		
		}
		
		if (! empty ( $newdate )) {
			foreach ( $newdate as $key => $new ) {
				$newNode = $doc->createElement ( 'DATA', $new );
				$date->appendChild ( $newNode );
			}
		}
	
	}
	//}
	

	echo $i . '</br>';
}

$doc->save ( 'C:\Users\Bell\Desktop\xml salva junio/' . $file2 );
echo 'ok';

?>