<?php
$file = 'VV.xml';
$fileName = 'C:\Users\Bell\Desktop\rolesPrueba.txt';
if (! $gestor = fopen ( $fileName, 'a' )) {
	echo "No se puede abrir el archivo ($fileName)";
	exit ();
}

$z = new XMLReader ();
//$z->open('data/BH.xml');
$z->open ( 'data/' . $file );

while ( $z->read () && $z->name !== 'ROW' )
	$i = 1;

while ( $z->name === 'ROW' ) {
	
	$node = new SimpleXMLElement ( $z->readOuterXML () );
	
	foreach ( $node->author_personal->DATA as $key => $value ) {
		if ($value) {
			$value = trim ( pg_escape_string ( $value ) );
			if ($value != '') {
				$rol = array ();
				$patron_rol = '/(?<=\()[\w\s]+(?=\))/';
				//$patron_rol = '/[\d{1,4}]+(?=\-)/';
				preg_match ( $patron_rol, $value, $rol );
				if (! empty ( $rol )) {
					//echo $rol [0] . '<br/>';
					$content = $rol [0] . "\n";
					fwrite ( $gestor, utf8_decode($content) );
				}
			}
		}
	}
	echo $i . '<br/>';
	$i ++;
	$z->next ( 'ROW' );
}
?>