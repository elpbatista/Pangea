<?php
//header ('Content-type: text/html; charset=utf-8');
$time_start = microtime ( true );

$reader = new XMLReader ();
$arreglos = array ();
$file = 'MNC.xml';
$characters = array ();

$name = explode ( '.', $file );
$directory = 'dataReview/' . $name [0];

$fp = fopen ( $directory . '/characters.txt', 'a+' );

if (! $reader->open ( 'data/' . $file ))
	echo 'no se abre el fichero';

while ( $reader->read () ) {
	if ($reader->localName == "ROW") {
		while ( $reader->next () ) {
			
			$node = $reader->expand ();
			$dom = new DomDocument ();
			$n = $dom->importNode ( $node, true );
			$dom->appendChild ( $n );
			$sxe = simplexml_import_dom ( $n );
			if ($reader->nodeType == XMLREADER::END_ELEMENT) { //si es el último elemento
				//vamos a escribir en el fichero
				print_r ( $characters );
				ksort ( $characters );
				
				$fp = fopen ( $directory . '/characters.txt', 'a' );
				foreach ( $characters as $key => $value ) {
					
					fwrite ( $fp, $key . ' | ' . $value [0] . ' | ' . $value [1] . PHP_EOL ); //escribimos en los ficheros
				

				}
				fclose ( $fp );
			
			} else {
				
				foreach ( $sxe as $key => $value ) {
					
					if ($value) {
						
						//$value = utf8_decode($value);
						//$patron = '/[[:ascii:]]/';
						//preg_match_all ( $patron, $value, $coincidencias ); //para separar los caracteres
						//$longitud = strlen ( $value );
						$value = utf8_decode($value);
						$charactersArray = str_split ( $value );//se separan los caracteres del string y se guardan en un arreglo
						
						//for($i = 0; $i < $longitud; $i ++) {
						foreach ( $charactersArray as $char ) {
							//$coincidencias = $value {$i};
							// $b = utf8_encode($char);
							$b =  mb_convert_encoding($char, 'utf-8');
							//echo 'caracter ' . $b . '<br/>';
							if ($char != ' ') {
								if (array_key_exists ( $b, $characters )) {
									//$hex = sprintf("%02x", $value2 );
									$characters [$b] [1] ++;
								} else {
									//$hex = sprintf("%04x", $value2 );
									//$value3 = utf8_encode($value2);
									$hex = ord ($b); //devuelve el ascii de un caracter
									$characters [$b] = array (0 => $hex, 1 => 1 );
								}
							}
						
						}
					}
				}
				$firstElement = false;
			
			}
		
		}
	
	}
}

$time_end = microtime ( true );
$time = $time_end - $time_start;
echo '<br/>Tiempo de ejecuci�n: ' . $time . ' segundos\n';
//memReport();
?>

