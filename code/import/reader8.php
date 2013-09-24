<?php
$file=$_POST['filepath'];
$value_1=$_POST['value_1'];
$value_2=$_POST['value_2'];
$value_3=$_POST['value_3'];

$i=1;

$z = new XMLReader;
if(!$z->open($file,LIBXML_NOBLANKS )){ print "can't open file";}

while ($z->read() && $z->name !== 'ROW');

while ($z->name === 'ROW') {
    if ($i>=$value_1 && $i<=$value_2) {
		$node = new SimpleXMLElement($z->readOuterXML());
	    foreach ($node as $key => $valor) {
		  if (count($valor)==0) {
			if ($valor) print $key." = ".$valor."<br/>";
		  }else {
			  foreach ($valor as $key1 => $value1) 
			  if ($value1) {
			  	print $key." = ".$value1."<br/>";
			  }
		  }
		}
	    echo "<br/><br/>";
	}
    if ($i==$value_2) break;  
    $i++;
    $z->next('ROW');
  
}
?>