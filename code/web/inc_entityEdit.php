<?php
$tag = 'li';
$header = 'h2';
list($pref,$class)=explode(':',$type);

/*frbr:embodimentOf*/
$expressions = (array_key_exists('frbr:embodimentOf',$entity))?((isset($entity['frbr:embodimentOf']))?getEntities(array_map(function($val){return $val['value'];},$entity['frbr:embodimentOf']),'large'):array('_Expression'=>newEntity('frbr:Expression'))):Array();

/*frbr:realizationOf*/

$work = (array_key_exists('frbr:realizationOf',$entity))?((isset($entity['frbr:realizationOf']))?getEntity($entity['frbr:realizationOf'][0]['value']):newEntity('frbr:Work')):Array();

/*frbr:exemplar*/
$items = (isset($entity['frbr:exemplar']))?(getEntities (array_map(function($val){return $val['value'];},$entity['frbr:exemplar']),'large')):Array();
//print_r($items);

if ($type === 'frbr:Manifestation') {
	$tag='div';
	$header = 'h1';
}

echo '<'.$tag.' class="edit entity '.strtolower($class).'" resource="'.$resource.'" typeof="'.$type.'">';
	include 'inc_menu_edit.php';
	echo '<ul class="verlist">';
	echo perfEditableEntity($entity,$header);
		
	if ($items) echo '<li><span class="label">ejemplares</span>'.count($items).'</li>';
	
	if ($expressions) {
		$expKeys = array_keys($expressions);
		$expSize = sizeOf($expressions);
		for ($x=0; $x<$expSize; $x++){
			$type = 'frbr:Expression';
			$resource = $expKeys[$x];
			$expression = $expressions[$resource];
			$tag='li';
			//print_r($entity);
			include 'inc_entityEdit.php';
		}
	}

	if ($work) {
		$type = 'frbr:Work';
		$entity = $work;
		$tag='div';
		include 'inc_entityEdit.php';
	}
	
	if ($items) {
		$itemsKeys = array_keys($items);
		$itemsSize = sizeOf($items);
		for ($x=0; $x<$itemsSize; $x++){
			$type = 'frbr:Item';
			$resource = $itemsKeys[$x];
			$entity = $items[$resource];
			$tag='li';
			include 'inc_entityEdit.php';
		}
	}

	echo '</ul>';
echo '</'.$tag.'>';
		 
/*echo '<'.$tag.' class="edit entity '.strtolower($class).'" resource="'.$resource.'" typeof="'.$type.'">';
$prefImage = (array_key_exists('foaf:img',$entity))?$entity['foaf:img'][0]['value']:'';
perfImageEdit($prefImage);
	echo '<small class="badge notice">'.(($class)?$class:'esto queloqué').'</small>';
	include 'inc_menu_edit.php';
	echo '<ul class="verlist">';
		//echo perfEntityEdit($entity,'h1',$permalink=$resource);
		echo perfEditableEntity($entity,'h1');
		
		if ($type=='frbr:Manifestation') {
			
			if ($items) echo '<li><span class="label">ejemplares</span>'.count($items).'</li>';
			
			if ($expressions) {
				$expKeys = array_keys($expressions);
				$expSize = sizeOf($expressions);
				//for ($x=0; $x<$expSize; $x++){
				echo '<li class="frbr:Expression" property="frbr:embodimentOf">';
					
					echo '<h3>Contenido <span class="add newEntity" typeof="frbr:Expression"><span class="hide new">'.json_encode($newExpression).'</span>añadir contenido&nbsp;<span class="icon-plus"></span></span></h3> ';
					
					echo '<ul class="multivalue">';
						for ($x=0; $x<$expSize; $x++){
							$expression = $expressions[$expKeys[$x]];
							echo '<li class="edit entity expression" resource="'.$expKeys[$x].'" typeof="frbr:Expression">';
								echo '<small class="badge notice">esto es una expresión</small>';
								//echo $editMenu;
								include 'inc_menu_edit.php';
								echo '<ul class="verlist">';
									echo perfEntityEdit($expression,'h3');

									$Work = (isset($expression['frbr:realizationOf']))?$expression['frbr:realizationOf']:$newWork;
									$workID = $Work[0]['value'];
									$workBuilt = ($workID != '_Work')?getEntity($workID):$emptyEntity['_Work'];												

									echo '<li class="frbr:Work" property="frbr:realizationOf">';
										echo '<input type="hidden" name="frbr:realizationOf" value="_Work" />';
										echo '<div class="edit entity work" resource="'.$workID.'" typeof="frbr:Work">';
											echo '<small class="badge notice">esto es una obra</small>';
											include 'inc_menu_edit.php';
											echo '<ul class="verlist">';
												echo perfEntityEdit($workBuilt,'h3');
											echo '</ul>';
										echo '</div>';
									echo '</li>';

								echo '</ul>';
							echo '</li>';
						}
					echo '</ul>';
				echo '</li>';
			}
			//$items = (isset($entity['frbr:exemplar']))?$entity['frbr:exemplar']:Array();
			$items = array_map(function($val){return $val['value'];}, $items);
			$items = getEntities ($items,'large');
			if ($items) {
				//echo '<h2 id="exenplars">Ejemplares</h2>';
				$itemsKeys = array_keys($items);
				$itemsSize = sizeOf($items);
				for ($x=0; $x<$itemsSize; $x++){
					$item = $items[$itemsKeys[$x]];
					echo '<li class="frbr:Item" property="frbr:exemplar">';
						echo '<h3>Ejemplares <span class="add newEntity" typeof="frbr:Item"><span class="hide new">'.json_encode($newExemplar).'</span>añadir un ejemplar nuevo&nbsp;<span class="icon-plus"></span></span></h3>';
						echo '<ul class="multivalue">';
							echo '<li class="edit entity item" resource="'.$itemsKeys[$x].'" typeof="'.$item['rdf:type'][0]['value'].'">';
								echo '<small class="badge notice">esto es un ítem</small>';
								include 'inc_menu_edit.php';
								echo '<ul class="verlist">';
									echo perfEntityEdit($item,'h3');
								echo '</ul>';
							echo '</li>';
						echo '</ul>';	
					echo '</li>';	
				}
			}
		}
	echo '</ul>';
echo '</'.$tag.'>';*/
?>