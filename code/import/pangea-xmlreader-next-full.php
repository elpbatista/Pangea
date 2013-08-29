<?php
$time_start = microtime ( true );

$reader = new XMLReader ();
$arreglos = array ();
$file = 'MNC.xml';
//$characters = array();

$name = explode ( '.', $file );
$directory = 'dataReview/' . $name [0];
mkdir ( $directory );



$firstElement = true;
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
			if ($reader->nodeType == XMLREADER::END_ELEMENT) { // si es el ultimo elemento
				
				foreach ( $arreglos as $key => $value ) {
					ksort ( $value ); //ordenamos los arreglos
					//$fp = fopen ( $directory . '/' . $key . '.txt', 'a' );
					$fp = fopen ( $directory . '/' . $key . '.csv', 'a' );
					foreach ( $value as $value2 ) {
						//fwrite ( $fp, "$value2[1] \t $value2[2]" . PHP_EOL ); //escribimos en los ficheros
					fwrite ( $fp, utf8_decode($value2[1]).'|'.$value2[2]. PHP_EOL ); 
					}
					fclose ( $fp );
				}
			

			} else {
				if ($firstElement) { //si es el primer elemento creo los ficheros y los arreglos
					

					foreach ( $sxe as $key => $value ) {
						
						//fopen ( $directory . '/' . $key . '.txt', 'a+' );
						fopen ( $directory . '/' . $key . '.csv', 'a+' );
						$arreglos [$key] = array ();
						$value = trim ( $value );
						if ($value) {
							
							if (empty ( $arreglos [$key] ))
								
								$arreglos [$key] [] = array (1 => $value, 2 => 1 );
							else {
								$founded = false;
								foreach ( $arreglos [$key] as $key2 => $value2 ) {
									$indice = array_search ( $value, $value2 );
									if ($indice) {
										$arreglos [$key] [$key2] [2] ++;
										$founded = true;
										break;
									}
								
								}
								if (! $founded)
									$arreglos [$key] [] = array (1 => $value, 2 => 1 );
							}
						}
					}
					$firstElement = false;
				} else { //elementos intermedios
					

					foreach ( $sxe as $key => $value ) {
						$value = trim ( $value );
						
						if ($value) {
							echo $value . '<br/>';
							if (empty ( $arreglos [$key] ))
								$arreglos [$key] [] = array (1 => $value, 2 => 1 );
							else {
								$founded = false;
								foreach ( $arreglos [$key] as $key2 => $value2 ) {
									$indice = array_search ( $value, $value2 );
									if ($indice) {
										$arreglos [$key] [$key2] [2] ++;
										$founded = true;
										break;
									}
								
								}
								if (! $founded)
									$arreglos [$key] [] = array (1 => $value, 2 => 1 );
							}
						}
					}
				}
			}
			/*switch ($reader->nodeType) {
				case (XMLREADER::END_ELEMENT) :
					{
						echo 'ultimo';
						foreach ( $sxe as $key => $value ) {
							$value = trim ( $value );
							if ($value) {
								foreach ( $$key as $key2 => $value2 ) {
									$indice = array_search ( $value, $value2 );
									if ($indice == 0)
										$value2 [1] ++;
									else
										$var = array (0 => $value, 1 => '1' );
									array_push ( $$key, $var );
								}
							}
							ksort ( $$key ); //ordenamos los arreglos
							foreach($$key as $a => $b){
								echo $a. '=>'. $b;
							}
							$fp = fopen ( $directory . '/' . $key . '.txt', 'a' );
							foreach ( $$key as $key3 => $value3 ) {
								fwrite ( $fp, "$value3[0] \t $value3[1]" . PHP_EOL ); //escribimos en los ficheros
							}
							fclose ( $fp );
						}
						break;
					}
				case (XMLREADER::ELEMENT) :
					{
						echo 'elemento';
						if ($firstElement) { //si es el primer elemento creo los ficheros y los arreglos
							echo 'primero';
							foreach ( $sxe as $key => $value ) {
								
								fopen ( $directory . '/' . $key . '.txt', 'a+' );
								$$key = array ();
								$value = trim ( $value );
								if ($value) {
									echo $value . '<br/>';
									foreach ( $$key as $key2 => $value2 ) {
										$indice = array_search ( $value, $value2 );
										if ($indice == 0)
											$value2 [1] ++;
										else
											$var = array (0 => $value, 1 => '1' );
										array_push ( $$key, $var );
									}
								
								}
							}
							$firstElement = false;
						} else {
							echo 'segundo';
							foreach ( $sxe as $key => $value ) {
								$value = trim ( $value );
								
								if ($value) {
									echo $value . '<br/>';
									foreach ( $$key as $key2 => $value2 ) {
										$indice = array_search ( $value, $value2 );
										if ($indice == 0)
											$value2 [1] ++;
										else
											$var = array (0 => $value, 1 => '1' );
										array_push ( $$key, $var );
									}
								}
							}
						}
						break;
					}
			
			//}*/
		
		//}
		}
	
	}
}

$time_end = microtime ( true );
$time = $time_end - $time_start;
echo '<br/>Tiempo de ejecuci�n: ' . $time . ' segundos\n';
//memReport();
?>

