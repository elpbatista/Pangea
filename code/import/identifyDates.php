<?php
$file = 'MNC.xml';
$fileName = 'C:\Users\Bell\Desktop\entryDates.txt';
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
	//in_date
	//pr_date
	//ed_date
	$entry_date = trim ( pg_escape_string ( $node->in_date ) );
	$entity = trim ( pg_escape_string ( $node->id_biblio ) );
	if (($entry_date) && ($entry_date != '')) {
		$content = $entry_date. ' '.$entity . "\n";
		fwrite ( $gestor, utf8_decode ( $content ) );
	}
	
	echo $i . '<br/>';
	$i ++;
	$z->next ( 'ROW' );
}
?>