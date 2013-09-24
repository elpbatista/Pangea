<?php
//$entityHTML = '';
$start = (($pageNumber-1)*$pageSize)+1;
$responsibility = array (
									'frbr:responsible',
									'frbr:Work' => 'frbr:creator',
									'frbr:Expression' => 'frbr:realizer',
									'frbr:Manifestation' => 'frbr:producer',
									'frbr:Item' => 'frbr:owner'
									);
//print_r($pageElements);//____________________________________________________________
$entities = getEntities (array_keys($pageElements));
$keys= array_keys($entities);
$size = sizeOf($keys);
for ($e=0; $e<$size; $e++){
	$entityHTML = '';
	$entity = $entities[$keys[$e]];
	//print_r($entity);
	$responsible = array();
	$notes = array();
	$generalNote = array();
	$contentNote = array();
	$producer = array();
	$place = array();
	$strDate = array();
	$yearQuitar = array();
	$warnings = 0;
	$errors = 0;

	$type = $pageElements[$keys[$e]]['typeof'];
	//$label = $pageElements[$keys[$e]]['label'];
	
	$label = (isset($entity['skos:prefLabel']))?$entity['skos:prefLabel'][0]['value']:'';
	//$prefImage = (array_key_exists('foaf:img',$entity))?$entity['foaf:img'][0]['value']:'';
	$prefImage = (isset($entity['foaf:img']))?$entity['foaf:img'][0]['value']:'';
	$image = '
		<div class="thumb">
			<img src="http://dev.pangea.ohc.cu/resource/'.$prefImage.'" alt="portada de prueba" />
			<small>Imagen de la portada</small>
		</div>';
	$form = (isset($entity['pangea:hasForm']))?$entity['pangea:hasForm']:Array();
	$subjects = (isset($entity['pangea:hasSubject']))?$entity['pangea:hasSubject']:Array();
	$strDate = (isset($entity['pangea:strDate']))?$entity['pangea:strDate'][0]['value']:'';
	$yearQuitar = (isset($entity['pangea:year']))?$entity['pangea:year'][0]['value']:'';
	$place = (isset($entity['pangea:place']))?$entity['pangea:place']:Array();

	$warning = (isset($entity['pangea:warning']))?sizeOf($entity['pangea:warning']):0;
	$error = (isset($entity['pangea:error']))?sizeOf($entity['pangea:error']):0;
	$warnings += $warning;
	$errors += $error;
	$nbr = $start++;
	//$entityHTML .= '<li class="entity'.(($nbr%2==0)?' par':'').'" resource="'.$keys[$e].'" typeof="'.$type.'">
	$entityHTML .= '<li class="entity" resource="'.$keys[$e].'" typeof="'.$type.'">
	<div class="nbr">'.$nbr.'</div>'
	.(($prefImage)?$image:'').
	'<ul class="verlist">
	<li><h3 property="label"><a href="'.$host.'?_ids='.$keys[$e].'">'.$label.'</a></h3></li>'
	.(($form)?'<li>'.perfValues('pangea:hasForm',$form,$filter=true).'</li>':'');
	//.(($subjects)?'<li>'.perfValues('pangea:hasSubject',$subjects,$filter=true).'</li>':'');

	if ($type==='frbr:Manifestation') {
		$producer = (isset($entity['frbr:producer']))?$entity['frbr:producer']:Array();
		
		/*relaciones*/
		$expressions = (isset($entity['frbr:embodimentOf']))?$entity['frbr:embodimentOf']:Array();
		$expressions = array_map(function($val){return $val['value'];}, $expressions);
		$expressions = getEntities ($expressions);

		if ($expressions) {
			$expKeys = array_keys($expressions);
			$expSize = sizeOf($expressions);
			for ($x=0; $x<$expSize; $x++){
				$expression = $expressions[$expKeys[$x]];
				//print_r($expression);
				$contentNote = (isset($expression['pangea:contentNote']))?$expression['pangea:contentNote']:Array();
				$notes = array_merge($notes,$contentNote);
				$realizationForm = (isset($expression['pangea:hasForm']))?$expression['pangea:hasForm'][0]['label']:'';
				$realizer = (isset($expression['frbr:realizer']))?$expression['frbr:realizer']:Array();
				$realizationlabel = (isset($expression['skos:prefLabel']))?$expression['skos:prefLabel'][0]['value']:'';
				$realizationDate = (isset($expression['pangea:strDate']))?$expression['pangea:strDate'][0]['value']:'';
				$realizationPlace = (isset($expression['pangea:place']))?$expression['pangea:place']:Array();
				$work = (isset($expression['frbr:realizationOf']))?$expression['frbr:realizationOf'][0]['value']:'';

				$warning = (isset($expression['pangea:warning']))?sizeOf($expression['pangea:warning']):0;
				$error = (isset($expression['pangea:error']))?sizeOf($expression['pangea:error']):0;
				$warnings += $warning;
				$errors += $error;
				
				if ($work) {
					$work = getEntity ($work); //<===================== poner en getEntities() pa aprovechar pangea:bareNecessities
					$creator = (isset($work['frbr:creator']))?$work['frbr:creator']:Array();
					$entityHTML .= ($creator)?('<li><span class="label">'.(($realizationForm)?$realizationForm.'&nbsp;de':'por').'</span>'.perfValues('frbr:creator',$creator,$filter=true).'</li>'):'';
					$subject = (isset($work['frbr:relatedSubject']))?$work['frbr:relatedSubject']:Array();
					$entityHTML .= ($subject)?('<li><span class="label">temática</span>'.perfValues('frbr:relatedSubject',$subject,$filter=true).'</li>'):'';

					$warning = (isset($work['pangea:warning']))?sizeOf($work['pangea:warning']):0;
					$error = (isset($work['pangea:error']))?sizeOf($work['pangea:error']):0;
					$warnings += $warning;
					$errors += $error;

				}
				
				if ($realizer||$realizationPlace||$realizationDate) {
					$entityHTML .= '<li><span class="label">'.(($realizationlabel)?$realizationlabel:'realización').'</span>'
					.(($realizer)?perfValues('frbr:realizer',$realizer,$filter=true):'')
					.(($realizationPlace)?(($realizer)?$bullet:'').perfValues('pangea:place',$realizationPlace,$filter=true):'')
					.(($realizationDate)?(($realizer || $realizationPlace)?$bullet:'').$realizationDate:'')
					.'</li>';
				}

			}
		}

		if ($producer||$place||$strDate||$yearQuitar) {
			$entityHTML .= '<li><span class="label">producción</span>'
			.(($producer)?perfValues('frbr:producer',$producer,$filter=true):'')
			.(($place)?(($producer)?$bullet:'').perfValues('pangea:place',$place,$filter=true):'')
			.(($strDate||$yearQuitar)?(($producer||$place)?$bullet:'').$strDate.$yearQuitar:'')
			.'</li>';
		}

		$generalNote = (isset($entity['pangea:generalNote']))?$entity['pangea:generalNote']:Array();
		$notes = array_merge($notes,$generalNote);
		
		$entityHTML .= ($notes)?('<li>'.perfValues('pangea:note',$notes).'</li>'):'';

		//$exemplars = (array_key_exists('frbr:exemplar',$entity))?$entity['frbr:exemplar']:Array();
		$exemplars = (isset($entity['frbr:exemplar']))?$entity['frbr:exemplar']:Array();
		if ($exemplars) {
			$exemplarsSize = sizeOf($exemplars);
//			$entityHTML .= '<li><span class="label"><a href="#" class="toggle" title="tócamela de nuevo, Sam">'.$exemplarsSize.'&nbsp;ejemplar'.(($exemplarsSize>1)?'es':'').'&nbsp;&darr;&uarr;<span class="hide">'.json_encode($exemplars).'</span></a></span><ul class="exemplars"></ul></li>';
			$entityHTML .= '<li><div class="toggle"><span class ="icon-list"></span>&nbsp;Ejemplares<span class="hide">'.json_encode($exemplars).'</span>&nbsp;'.$exemplarsSize.'</div><ul class="exemplars"></ul></li>';
		}
	} 	
/*
	$prop = array_keys($entity);
	$amount = sizeOf($prop);
		for ($p=0; $p<$size; $p++){
			$_property = $prop[$p];
			echo '<li property="'.$_property.'" class="'.$GLOBALS['pangea']['owl:Property'][$_property]['rdfs:range'].'"><span class="label">'.$_property.'</span>'.perfValues($_property,$entity[$_property]).'</li>';
		}
*/
	$entityHTML .= '</ul>
		<ul class="fncmenubar">'
					.(($warnings > 0)?('<li><span class="badge warning" title="'.$warnings.'&nbsp;alerta'.(($warnings>1)?'s':'').'">'.$warnings.'</span></li>'):'')
					.(($errors > 0)?('<li><span class="badge important" title="'.$errors.'&nbsp;error'.(($errors>1)?'es':'').'">'.$errors.'</span></li>'):'')
					.'<li><a href="'.$host.'?_ids='.$keys[$e].'">ver detalles</a></li>'
					.'<li class="addToCart"><a href="#">seleccionar</a></li>'
					.(($per_editar)?'<li><a href="'.$host.'edit.php?_ids='.$keys[$e].'&_a=/bu">editar</a></li>':'').'
				</ul>
	</li>';
	echo $entityHTML;
}
echo ($total>$pageSize*$pageNumber)?'<li class="meta"><button class="meta"><span class="icon-plus"></span>&nbsp;más...<span class="_pg">'.($pageNumber+1).'</span></button></li>':'';
//echo $entityHTML;
?>
