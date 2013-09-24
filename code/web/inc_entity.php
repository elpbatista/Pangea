<?php 
$entity = getEntity ($resource);
$type = (isset($entity['rdf:type']))?$entity['rdf:type'][0]['value']:'';
list($pref,$class)=explode(':',$type);
//print_r($entity); //<=================================================================== IMPRIME LO QUE TRAE LA ENTIDAD
//$labelType = getLabel($type);

function perfEntity($entity,$header='h2',$permalink=''){
	$host = $GLOBALS['host'];
	$lbl = $GLOBALS['lbl'];
	$resource = $GLOBALS['resource'];
	$excludeKeys = 	array (
									'rdf:type',
									'frbr:exemplarOf',/**/
									'frbr:realizationOf',
									'frbr:realization',
									'frbr:embodimentOf',
									'frbr:embodiment',
									'skos:prefLabel',
									'skosxl:prefLabel',
									'foaf:img',
									'frbr:exemplar',
	                'pangea:date',
	                'rdfs:comment', 
	                'skos:note', 
                  'pangea:note',
									'rdfs:Label',
	                'pangea:nomen',
	                'frbr:relatedResponsibleEntity'
								);
	$type = (isset($entity['rdf:type']))?$entity['rdf:type'][0]['value']:'';
	$preflabel = (isset($entity['skos:prefLabel']))?$entity['skos:prefLabel'][0]['value']:'';
	$prefxlabel = (isset($entity['skosxl:prefLabel']))?$entity['skosxl:prefLabel'][0]['value']:'';
	$prefImage = (isset($entity['foaf:img']))?$entity['foaf:img'][0]['value']:'';
	$title = (isset($entity['frbr:exemplarOf']))?perfValues('frbr:exemplarOf',($entity['frbr:exemplarOf'])):'';
	$exemplars = (isset($entity['frbr:exemplar']))?$entity['frbr:exemplar']:Array();
	$label = ($preflabel)?$preflabel:(($prefxlabel)?getConceptLiteralForm($prefxlabel):'');
	list($pref,$class)=explode(':',$type);
	$image = '
		<div class="thumb">
			<img src="http://dev.pangea.ohc.cu/resource/'.$prefImage.'" alt="imagen preferida" />
			<small>Imagen de la portada</small>
		</div>';
	//echo '<div class="entity '.strtolower($class).'" resource="'.$resource.'" typeof="'.$type.'">';
	//echo '<div class="entity" resource="'.$resource.'" typeof="'.$type.'">';
	//echo ($prefImage)?$image:'';
	echo ($label)?'<'.$header.'>'.(($permalink)?'<a href="'.$host.'?_ids='.$permalink.'" title="permalink">'.$label.'</a>':$label).'</'.$header.'>':'';
	//echo '<'.$header.'>'.$label.'</'.$header.'>';
	echo '<ul class="verlist">';
	echo ($title)?'<li>'.$title.'</li>':'';
	$key = array_keys(deleteFromArray($entity,$excludeKeys));
	$size = sizeOf($key);
	for ($i=0; $i<$size; $i++){
		//echo '<dt>'.getLabel($key[$i]).'</dt><dd>'.perfValues($key[$i],$entity[$key[$i]]).'</dd>';
		echo '<li><span class="label">'.(($lbl[$key[$i]])?$lbl[$key[$i]]:$key[$i]).'</span>'.perfValues($key[$i],$entity[$key[$i]]).'</li>';
	}
	if ($exemplars) echo '<li><span class="label">ejemplares</span>'.count($exemplars).'</li>';
	echo '</ul>';
	//echo '</div>';
}

function perfImages($prefImage){
	echo '
		<div class="thumb">
			<img src="http://dev.pangea.ohc.cu/resource/'.$prefImage.'" alt="imagen preferida" />
			<small>Imagen de la portada</small>
		</div>';
}


?>
<div class="row">
	<div id="maincont">
		
<?php		
		echo '<div class="entity '.strtolower($class).'" resource="'.$resource.'" typeof="'.$type.'">';
		
		$prefImage = (array_key_exists('foaf:img',$entity))?$entity['foaf:img'][0]['value']:'';
		if ($prefImage) perfImages($prefImage);
			//echo '<small class="badge notice">esto es una manifestación</small>';
			echo '<ul class="verlist">';
				echo perfEntity($entity,'h1',$permalink=$resource);
				if ($type=='frbr:Manifestation') {
					$expressions = (isset($entity['frbr:embodimentOf']))?$entity['frbr:embodimentOf']:Array();
					$expressions = array_map(function($val){return $val['value'];}, $expressions);
					$expressions = getEntities ($expressions,'large');
					if ($expressions) {
						$expKeys = array_keys($expressions);
						$expSize = sizeOf($expressions);
						for ($x=0; $x<$expSize; $x++){
							echo '<li class="frbr:embodimentOf">';
								echo '<div class="entity expression" resource="'.$expKeys[$x].'" typeof="frbr:Expression">';
									//echo '<small class="badge notice">esto es una expresión</small>';
									$expression = $expressions[$expKeys[$x]];
									echo '<ul class="verlist">';
										perfEntity($expression,'h3');
										$workID = (isset($expression['frbr:realizationOf']))?$expression['frbr:realizationOf'][0]['value']:Array();
										if ($workID) {
											echo '<li class="frbr:realizationOf">';
												echo '<div class="entity work" resource="'.$workID.'" typeof="frbr:Work">';
													//echo '<small class="badge notice">esto es una obra</small>';
													$workBuilt = getEntity ($workID);
													echo '<ul class="verlist">';
														perfEntity($workBuilt,'h3');
													echo '</ul>';
												echo '</div>';
											echo '</li>';
										}
									echo '</ul>';
								echo '</div>';
							echo '</li>';
						}
					}
					$items = (isset($entity['frbr:exemplar']))?$entity['frbr:exemplar']:Array();
					$items = array_map(function($val){return $val['value'];}, $items);
					$items = getEntities ($items,'large');
					//print_r($items);
					if ($items) {
						//echo '<h2 id="exenplars">Ejemplares</h2>';
						$itemsKeys = array_keys($items);
						$itemsSize = sizeOf($items);
						for ($x=0; $x<$itemsSize; $x++){
							$item = $items[$itemsKeys[$x]];
							echo '<li class="frbr:exemplar">';
								echo '<div class="entity item" resource="'.$itemsKeys[$x].'" typeof="'.$item['rdf:type'][0]['value'].'">';
									//echo '<small class="badge notice">esto es un ítem</small>';
									echo '<ul class="verlist">';
										perfEntity($item,'h3');
									echo '</ul>';
								echo '</div>';
							echo '</li>';	
						}
					}
				}
			echo '</ul>';
		echo '</div>';


?>
	
	</div>
	<div id="sidebar">
	<?php echo ($per_editar)?'<a href="'.$host.'edit.php?_ids='.$resource.'&_a=/bu">editar</a>':''; ?>
	</div>
</div>
