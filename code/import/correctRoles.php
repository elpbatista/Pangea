<?php
$file = 'HB.xml'; //nombre del xml a corregir
$file2 = 'HB2.xml'; //nombre con el que se creará el xml corregido

$doc = new DOMDocument ();
$doc->load ( 'C:\Users\Bell\Desktop\xml salva junio/' . $file ); //ruta donde se encuentra el xml a corregir

$list = $doc->getElementsByTagName ( 'author_personal' );

for($i = 0; $i < $list->length; $i ++) {
	$a = $list->item ( $i );
	$node = simplexml_import_dom ( $a ); //lo convierto a simpleXmlElement para poderle pedir los hijos
	$arrayParejas = array ();
	foreach ( $node->children () as $autor ) {
		$value = trim ( pg_escape_string ( $autor ) );
		if ($value != '') {
			//aqui van todas las verificaciones incluyendo la parte de quedarme solo con el rol
			if (strpos ( $value, "(" )) { //verifico si viene el rol
				$oldRol = array ();
								
				$patron_rol = '/(?<=\()[\;\,\.\-\w\s' . utf8_encode ( '\á\é\í\ó\ú\Á\É\Í\Ó\Ú\ñ\Ñ\ä\ë\ö\ü\Ä\Ë\Ï\Ö\Ü\ç\à\è\ì\ò\ù\ÿ\’\´\[\]\(\)\…\ã\ý' ).']+(?=\))/';
				preg_match ( $patron_rol, $value, $oldRol ); //cojo el rol que es lo que viene entre parentesis
				
				$rol = mb_strtolower ( $oldRol [0], 'UTF-8' ); //llevo la cadena a minuscula
				
				$rol = str_replace ( ' y ', ';', $rol );
				$rol = str_replace ( ' e ', ';', $rol );
				$rol = str_replace ( ',', ';', $rol );
				
				$roles = explode ( ';', $rol ); //separo por el ; por si hay varios roles
				$j = 1;
				
				foreach ( $roles as $rol ) {
					$found = false;
					$rol = trim($rol);
					if ($rol == 'autor' || $rol == 'autores' || $rol == 'poeta' || $rol == 'relator' || $rol == 'creador' || $rol == 'escritor' || $rol == 'autora' || $rol == 'creadora' || $rol == 'investigación y textos' || $rol == 'craedor' || $rol == 'autor según investigaciones de la biblioteca' || $rol == 'texto' || $rol == 'textos' || $rol == 'creador de xiii' || $rol == 'autor según las investigaciones de la biblioteca' || $rol == 'auto' || $rol == 'credaor' || $rol == 'composición del texto' || $rol == 'a' || $rol == '1' || $rol == '1977' || $rol == 'autor del texto' || $rol == 'autro' || $rol == 'autot') {
						
						if (sizeof ( $roles ) > 1) // dejar el rol autor en caso de que sea compuesto
							$newRol = 'autor';
						else //si es un solo rol hay que borrar los parentesis
							$newRol = '';
						$found = true;
					} elseif ($rol == 'edición' || $rol == 'editora' || $rol == 'consejo editorial' || $rol == 'editores' || $rol == 'preparación editorial' || $rol == 'coordinación editorial' || $rol == 'comisión editora' || $rol == 'edición de textos' || $rol == 'presidente comisión editora' || $rol == 'responsable de edición' || $rol == 'editora ejecutiva' || $rol == 'edisión' || $rol == 'eduitor' || $rol == 'presidente del consejo editorial') {
						
						$newRol = 'editor';
						$found = true;
					} elseif ($rol == 'pintor' || $rol == 'ilustraciones' || $rol == 'ilustración' || $rol == 'ilustradora' || $rol == 'ilustraciones con grabado' || $rol == 'ilustraciones interiores' || $rol == 'ilustraciónes' || $rol == 'ilustratrador') {
						
						//hay que cambiar el rol a ilustrador
						$newRol = 'ilustrador';
						$found = true;
					} elseif ($rol == 'prólogo' || $rol == 'prol' || $rol == 'prologo' || $rol == 'próloguista' || $rol == 'prolguista' || $rol == 'prooólogo' || $rol == 'prológo' || $rol == 'carta prólogo' || $rol == 'prologista' || $rol == 'prologuistas' || $rol == 'proemio' || $rol == 'prlogo' || $rol == 'autor de prefacio') {
						
						//hay que cambiar el rol a prologuista
						$newRol = 'prologuista';
						$found = true;
					} elseif ($rol == 'directora' || $rol == 'dirección general' || $rol == 'director general' || $rol == 'dirección' || $rol == 'directores' || $rol == 'directiva' || $rol == 'directeur' || $rol == 'ditrector' || $rol == 'director de la colección' || $rol == 'director de la publicación' || $rol == 'directora de la investigación' || $rol == 'directora honoraria' || $rol == 'directorr' || $rol == 'presidente') {
						
						//hay que cambiar el rol a director
						$newRol = 'director';
						$found = true;
					} elseif ($rol == 'corregidor' || $rol == 'correción' || $rol == 'correctora' || $rol == 'correción de estilo' || $rol == 'correcciones') {
						
						//hay que cambiar el rol a corrector
						$newRol = 'corrector';
						$found = true;
					} elseif ($rol == 'introducción'|| $rol == 'introduccion'  || $rol == 'estudios introductorios' || $rol == 'introducción de textos' || $rol == 'introductora' || $rol == 'introd' || $rol == 'introd.' || $rol == 'notas introductorias' || $rol == 'ensayo introductorio' || $rol == 'introducción del texto' || $rol == 'introductctor') {
						
						//hay que cambiar el rol a introductor
						$newRol = 'introductor';
						$found = true;
					} elseif ($rol == 'traducción' || $rol == 'traductora' || $rol == 'traducciones' || $rol == 'translator' || $rol == 'traduccion' || $rol == 'truaductor' || $rol == 'traducido' || $rol == 'traduictor' || $rol == 'transductor') {
						
						//hay que cambiar el rol a traductor
						$newRol = 'traductor';
						$found = true;
					} elseif ($rol == 'recompilador' || $rol == 'compiladora' || $rol == 'compilación' || $rol == 'compiladores') {
						
						//hay que cambiar el rol a compilador
						$newRol = 'compilador';
						$found = true;
					} elseif ($rol == 'redacción' || $rol == 'redactora' || $rol == 'jefe de redacción' || $rol == 'redacción final' || $rol == 'redactor jefe' || $rol == 'redactor responsable' || $rol == 'redacción literaria' || $rol == 'redactor general' || $rol == 'redactor principal') {
						
						//hay que cambiar el rol a redactor
						$newRol = 'redactor';
						$found = true;
					} elseif ($rol == 'fotos' || $rol == 'fotografo' || $rol == 'fotógrafa' || $rol == 'foto de cubierta' || $rol == 'fotografias' || $rol == 'foto de autor' || $rol == 'foto de contracubierta' || $rol == 'foto de la portada' || $rol == 'foto de portada' || $rol == 'fotos color') {
						
						//hay que cambiar el rol a fotógrafo
						$newRol = 'fotógrafo';
						$found = true;
					} elseif ($rol == 'coautora' || $rol == 'correlatores') {
						
						//hay que cambiar el rol a coautor
						$newRol = 'coautor';
						$found = true;
					} elseif ($rol == 'investigación' || $rol == 'investigadora') {
						
						//hay que cambiar el rol a investigador
						$newRol = 'investigador';
						$found = true;
					} elseif ($rol == 'dibujos' || $rol == 'dibujo') {
						
						//hay que cambiar el rol a dibujante
						$newRol = 'dibujante';
						$found = true;
					} elseif ($rol == 'producción' || $rol == 'productor ejecutivo' || $rol == 'producción ejecutiva' || $rol == 'productora ejecutiva') {
						
						//hay que cambiar el rol a productor
						$newRol = 'productor';
						$found = true;
					} elseif ($rol == 'ilustración de cubierta' || $rol == 'ilustración de portada' || $rol == 'ilustración cubierta' || $rol == 'ilustración portada' || $rol == 'ilustrador cubierta') {
						
						//hay que cambiar el rol a ilustrador de cubierta
						$newRol = 'ilustrador de cubierta';
						$found = true;
					} elseif ($rol == 'notas' || $rol == 'notas explicativas') {
						
						//hay que cambiar el rol a anotador
						$newRol = 'anotador';
						$found = true;
					} elseif ($rol == 'grabados') {
						
						//hay que cambiar el rol a grabador
						$newRol = 'grabador';
						$found = true;
					} elseif ($rol == 'grabado cubierta') {
						
						//hay que cambiar el rol a grabado de cubierta
						$newRol = 'grabado de cubierta';
						$found = true;
					} elseif ($rol == 'interprete' || $rol == 'solista' || $rol == 'cantante') {
						
						//hay que cambiar el rol a intérprete
						$newRol = 'intérprete';
						$found = true;
					} elseif ($rol == 'adaptación') {
						
						//hay que cambiar el rol a adaptador
						$newRol = 'adaptador';
						$found = true;
					} elseif ($rol == 'guión') {
						
						//hay que cambiar el rol a guionista
						$newRol = 'guionista';
						$found = true;
					} elseif ($rol == 'director de arte' || $rol == 'dirección de arte') {
						
						//hay que cambiar el rol a director artístico
						$newRol = 'director artístico';
						$found = true;
					} elseif ($rol == 'tutora') {
						
						//hay que cambiar el rol a tutor
						$newRol = 'tutor';
						$found = true;
					} elseif ($rol == 'composición') {
						
						//hay que cambiar el rol a compositor
						$newRol = 'compositor';
						$found = true;
					} elseif ($rol == 'director orquesta sinfónica de venezuela') {
						
						//hay que cambiar el rol a director de orquesta
						$newRol = 'director de orquesta';
						$found = true;
					} elseif ($rol == 'preliminares') {
						
						//hay que cambiar el rol a autor de palabras preliminares
						$newRol = 'autor de palabras preliminares';
						$found = true;
					}elseif ($rol == 'presentación' || $rol== 'presentadora') {
						
						//hay que cambiar el rol a presentador
						$newRol = 'presentador';
						$found = true;
					}
					if ($found == true) {
						if ($j == 1) {
							$lastRol = $newRol;
						} else
							$lastRol .= '; ' . $newRol;

					} else {
						//tengo que ver que hago
						if ($j == 1) {
							$lastRol = $rol;
						} else
							$lastRol .= '; ' . $rol;
					
					}
					$j ++;
				}
				if ($lastRol == '') //quito hasta los parentesis
					$newAutor = str_replace ( '(' . $oldRol [0] . ')', $lastRol, $value );
				else //mantengo los parentesis y solo cmabio el rol
					$newAutor = str_replace ( $oldRol [0], $lastRol, $value );
				$domNode = dom_import_simplexml ( $autor ); //lo vuelvo a convertir en DOMDocument
				$newNode = $doc->createElement ( 'DATA', $newAutor );
				$arrayParejas [] = array ($domNode, $newNode );
			
			} else //si no vienen los parentesis
				$domNode = dom_import_simplexml ( $autor );

		}
	}
	foreach ( $arrayParejas as $pareja ) {
		$a->replaceChild ( $pareja [1], $pareja [0] );
	}
echo $i. '</br>';
}

$doc->save ( 'C:\Users\Bell\Desktop\xml salva junio/' . $file2 );
echo 'ok';

?>