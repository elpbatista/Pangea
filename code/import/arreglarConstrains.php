<?php
include_once 'function_import.php';

$parent = "pangea:DatatypeProperty";
function fixConstraints($parent){
	$import = DataImport::getInstance ();
	//primero obtengo todas las tablas a las que hay que cambiarle los constrain
	$tables = $import->getHineritFrom($parent);
	//luego recorro la lista de tablas y voy arreglando los constrain
	
	$total = count($tables);
	for($i = 0; $i < $total; $i ++) {
		$table = $tables[$i];
		$import->fixConstraint3($table);
		echo $i . '<br/>';
	}
}
function deleteTriggers($parent){
	/*las tablas que heredan de pangea:Class solo tienen un trigger que hay que quitar set_numeric_id, las
	 * tablas que heredan de pangea:Property tienen 2 triggers que hay que quitar set_numeric_id y set_numeric_fields*/
	$import = DataImport::getInstance ();
	//primero obtengo todas las tablas a las que hay que quitarle los triggers
	$tables = $import->getHineritFrom($parent);
	//luego recorro la lista de tablas y los voy quitando
	
	$total = count($tables);
	for($i = 0; $i < $total; $i ++) {
		$table = $tables[$i];
		$import->deleteTriggers($table);
		echo $i . '<br/>';
	}
}

fixConstraints($parent);
echo 'ended';
?>