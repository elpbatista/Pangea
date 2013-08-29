<?php
/*echo "<pre>";

$file = "data/BH.xml";
echo $file."\n";
global $inTag;

$inTag = "";
$xml_parser = xml_parser_create();
xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1);
xml_set_processing_instruction_handler($xml_parser, "pi_handler");
xml_set_default_handler($xml_parser, "parseDEFAULT");
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_set_character_data_handler($xml_parser, "contents");

if (!($fp = fopen($file, "r"))) {
    if (!xml_parse($xml_parser, $data, feof($fp))) {
       die( sprintf("XML error: %s at line %d",
                            xml_error_string(xml_get_error_code($xml_parser)),
                            xml_get_current_line_number($xml_parser)));
    }
}
while ($data = fread($fp, 4096)) {
    if (!xml_parse($xml_parser, $data, feof($fp))) {
       die( sprintf("XML error: %s at line %d",
                            xml_error_string(xml_get_error_code($xml_parser)),
                            xml_get_current_line_number($xml_parser)));
    }
}
xml_parser_free($xml_parser);

function startElement($parser, $name, $attrs) {

    global $inTag;
    global $depth;
       
    $padTag = str_repeat(str_pad(" ", 3), $depth);

    if (!($inTag == "")) {
        echo "&gt;";
    }
    echo "\n$padTag&lt;$name";
    foreach ($attrs as $key => $value) {
        echo "\n$padTag".str_pad(" ", 3);
        echo " $key=\"$value\"";
    }
    $inTag = $name;
    $depth++;
}

function endElement($parser, $name) {

    global $depth;
   global $inTag;
    global $closeTag;
       
    $depth--;

   if ($closeTag == TRUE) {
       echo "&lt/$name&gt;";
       $inTag = "";
   } elseif ($inTag == $name) {
       echo " /&gt;";
       $inTag = "";
   } else {
         $padTag = str_repeat(str_pad(" ", 3), $depth);
       echo "\n$padTag&lt/$name&gt;";
    } 
}
 
function contents($parser, $data) {

    global $closeTag;
   
    $data = preg_replace("/^\s+/", "", $data);
    $data = preg_replace("/\s+$/", "", $data);

    if (!($data == ""))  {
        echo "&gt;$data";
        $closeTag = TRUE;
    } else {
        $closeTag = FALSE;
     }
}

function parseDEFAULT($parser, $data) {
   
    $data = preg_replace("/</", "&lt;", $data);
    $data = preg_replace("/>/", "&gt;", $data);
    echo $data;
}

function pi_handler($parser, $target, $data) {

    echo "&lt;?$target $data?&gt;\n";
}
echo "</pre>";*/

//$a = 'Timoneda, Juan de… [et al.]';
//echo $a. '<br/>' ;
//$a = utf8_encode($a);
//$a = str_replace ( '… [et al.]', ' ', $a );
//echo $a;
//… (et. al.)
//… [et al]


/*$arreglo=array("Anabel" => '6',"ana" => '2',"pepe" => '4',"Baby" => "5");

//usort($arreglo,"strnatcasecmp");
ksort($arreglo);
for($x=0;$x<count($arreglo);$x++)
 echo $arreglo[$x]."<br>";  
 foreach ($arreglo as $key => $val) {
    echo $key ." = " . $val . "<br>";
} */
/*
$xml = simplexml_load_string($getResult);
$json = json_encode($xml);
$array = json_decode($json,TRUE);*/

/*$ruta_fichero='data/personas.xml';

$contenido = "";
$da = fopen($ruta_fichero,"r");
if($da)
{
while (fgets($da))
{
$aux = fgets($da);
$contenido.=$aux;
}
fclose($da);
} 
echo $contenido;*/
/*$file = 'MNC.xml';
$name = explode ( '.', $file );
echo $name;*/

/*$array=array( 
	0=>array(1=>"Título",2=>"Autor",3=>"Editorial"),
	1=>array(1=>"El médico",2=>"Noah Gordon",3=>"Time Warner")
);

$coinsidencias 		= array();
$palabra_a_buscar 	= "Autor";
foreach($array as $key=>$value){
	$indice = array_search($palabra_a_buscar,$value);
	if($indice){
		$coinsidencias[]=$value[$indice];
	}
}
print_r($coinsidencias);*/
/*$b = 1;
$array=array( 
	0=>array(1=>"Título",2=>"Autor",3=>"Editorial"),
	1=>array(1=>"El médico",2=>"Noah Gordon",3=>"Time Warner")
);
$indice = array_search ( "fdaghdfh", $a );
if ($indice == 0 )
  $b = 2;
  echo $b;*/

/*  $sujeto = "abcdefABCDEF123456áéíóú@ ,;<>'Äèëêïüýò";
//$patron = '/[\w\s\.\;\@\;\<\>]/';
$patron = '/[[:ascii:]]/';
preg_match_all($patron, $sujeto, $coincidencias);
print_r($coincidencias);*/
/*$arreglos = array ('autor' => array (0 => array (1 => 'Pepe', 2 => 1 ), 1 => array (1 => 'Juan', 2 => 1 ) ) );

foreach ( $arreglos ['autor'] as $key2 => $value2 ) {
	$indice = array_search ( 'Pepe', $value2 );
	if ($indice) {
		$arreglos ['autor'][$key2][2] ++;
		$b = true;
		break;
	}
}
if (! $b)
	$arreglos ['autor'] [] = array (1 => 'Pepe', 2 => '1' );
print_r ( $arreglos );*/

/*$arreglos = array ('autor' => array (0 => array (1 => 'Pepe', 2 => 1 ), 1 => array (1 => 'Juan', 2 => 1 ) ) );

foreach ( $arreglos as $key => $value ) {
	ksort ( $value ); //ordenamos los arreglos
	print_r ( $value );

	//$fp = fopen ( $directory . '/' . $key . '.txt', 'a' );
//foreach ( $value as $value2 ) {
//	fwrite ( $fp, "$value2[1] \t $value2[2]" . PHP_EOL ); //escribimos en los ficheros
//	}
//fclose ( $fp );
}*/
/*
$sujeto = utf8_decode("Jesús") ;
$patron = '/[[:ascii:]]/';
preg_match_all($patron, $sujeto, $coincidencias);
print_r($coincidencias);
*/
$arreglo=array("Anabel" => '6',"ana" => '2',"pepe" => '4',"Baby" => "5");

ksort($arreglo);
print_r($arreglo);
?>