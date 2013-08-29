<?php
global $entityJSON, $description;
$description = array();

/*Le paso un ID y trae la entidad que le toca en JSON*/
function getEntity ($entityID){
	$host = $GLOBALS['host'];
	$gateway = $GLOBALS['gateway'];
	$entityBuilt = new HTTPRequest($host.$gateway.'?_ids='.$entityID.'&_a=/b&_ty=/l');
	/*aquí también hay un parche*/
	//$entityJSON = json_decode(strstr($entityBuilt->DownloadToString(),'{'), true);
	$entityJSON = json_decode($entityBuilt->DownloadToString(), true);
	return ($entityJSON)?$entityJSON[$entityID]:array();
}
/*
Le paso un array de IDses de entidades y devuelve un arreglo con toas las entidades como llaves
mucho más rápido que pasar una a una las entidades a getEntity
*/
function getEntities ($entities,$length='medium'){
	$host = $GLOBALS['host'];
	$gateway = $GLOBALS['gateway'];
	$ty = '';
	if ($length!=='medium'){
		if ($length==='short')$ty='&_ty=/s';
		else if ($length==='large')$ty='&_ty=/l';
	}
	//$entityBuilt = new HTTPRequest($host.$gateway.'?_ids='.implode(',',$entities).'&_a=/bp');
	$entityBuilt = new HTTPRequest($host.$gateway.'?_ids='.implode(',',$entities).$ty);
	$entityJSON = json_decode(strstr($entityBuilt->DownloadToString(),'{'), true);
	//$entityJSON = json_decode($entityBuilt->DownloadToString(), true);
	return ($entityJSON)?$entityJSON:array();
}

/*Le paso un tipo de entidad y una págna y devuelve los 10 resultados de acuerdo al tamaño de página*/
function getFilteredPage($md5,$prpFilter,$pageNumber = 1){
	$host=$GLOBALS['host'];
	$gateway=$GLOBALS['gateway'];
	//$documents = new HTTPRequest($host.$gateway.'?_md5='.$md5.'&_ctg='.$prpFilter.'&_a='.$action.'&_pg='.$pageNumber.(($valFilter)?'&_fv='.$valFilter:''));
	$documents = new HTTPRequest($host.$gateway.'?_md5='.$md5.'&_ctg='.$prpFilter.'&_pg='.$pageNumber);
	$documentsJSON = json_decode(strstr($documents->DownloadToString(),'{'), true);
	//$documentsJSON = json_decode($documents->DownloadToString(), true);
	//return $documentsJSON;
	return ($documentsJSON)?$documentsJSON:array();
}

/*Le paso un rango y un texto: devuelve los 10 resultados de acuerdo al tamaño de página. Necesita un rango válido por defecto empieza en el frbr:Core porque no tiene memoria pa pasarle un punto de entrada más alto*/
function getList($text = '', $pageNumber = 1, $range = 'frbr:Core'){
	$host=$GLOBALS['host'];
	$gateway=$GLOBALS['gateway'];
	$documents = new HTTPRequest($host.$gateway.'?_t='.urlencode($text).'&_a=/l&_pg='.$pageNumber.'&_rg='.$range);
	$documentsJSON = json_decode(strstr($documents->DownloadToString(),'{'), true);
	//print_r($documents);
	//$documentsJSON = json_decode($documents->DownloadToString(), true);
	return ($documentsJSON)?$documentsJSON:array();
}	

/*
$values es un arreglo con los valores que quiero pintar, si es una lista la manda a construir
los links deben filtrar NO construir
*/
function perfValues($key,$values,$filter = false){
	//print_r($values);
	$host = $GLOBALS['host'];
	$idSearch = $GLOBALS['idSearch'];
	//$cons = $GLOBALS['cons'];
	/*$excludeKeys contiene los $key que no se quieren seguir a partir de sus URI*/
	$excludeKeys = array (
									/*'frbr:relatedSubject',*/
									'pangea:hasAvailability',
									'pangea:hasAdquisitionWay',
									'pangea:hasCollection',
									'pangea:hasLanguage',
									'pangea:hasForm'
								);
	$filterKeys = array ();							
	$valueHTML = Array();
	$size = sizeOf($values);
	$envelop = ($size > 1)?'li':'span';
	$wrapper = ($size > 1)?'ul':'span';
	for ($i=0; $i<$size; $i++) {
		//$typeof = (array_key_exists('typeof',$values[$i]))?$values[$i]['typeof']:'';
		$typeof = (isset($values[$i]['typeof']))?$values[$i]['typeof']:'';
		$resource = $values[$i]['value'];
		$type = $values[$i]['type'];
		//$label = (array_key_exists('label',$values[$i]))?$values[$i]['label']:$resource;
		$label = (isset($values[$i]['label']))?$values[$i]['label']:$resource;
		if ($filter){
			$href = ($type === 'uri' || in_array($key,$filterKeys))?$host.'?_md5='.$idSearch.'&_ff='.$key.'&_fv='.$resource:'';
			//$href = ($type === 'uri' || in_array($key,$filterKeys))?$host.'?_md5='.$idSearch.'&_ctg='.$resource:'';
		}
		else {
			$href = ($type === 'literal' || in_array($key,$excludeKeys))?'':$host.'?_ids='.$resource;
		}
		$data = (($resource)?'resource="'.$resource.'"':'').' '.(($typeof)?'typeof="'.$typeof.'"':'');
		//$valueHTML[$i] = '<'.$envelop.' '.(($type === 'uri' && !strpos($values[$i]['value'],':'))?'resource ="'.$resource.'"':'').' typeof="'.$typeof.'" >'.(($href)?'<a href="'.$href.'" title ="filtrar">'.$prefLabel.'</a>':$prefLabel).'</'.$envelop.'>';
		$valueHTML[$i] = '<'.$envelop.' '.$data.' >'.(($href)?'<a href="'.$href.'" title ="filtrar">'.$label.'</a>':$label).'</'.$envelop.'>';
	}
	return '<'.$wrapper.' class="horlist" property="'.$key.'">'.implode('',$valueHTML).'</'.$wrapper.'>';
}
/*
$toOrderArray -> Array a ordenar
$field -> Campo del array por el que queremos ordenarlo (entre comillas).
$inverse -> Su valor será true o false. El valor true lo ordenará de manera descendente y el false (valor por defecto) lo ordenará de manera ascendente.
*/
function orderMultiDimensionalArray ($toOrderArray, $field, $inverse = false) {  
  $position = array();  
  $newRow = array();  
  $key = array_keys($toOrderArray);
	$size = sizeOf($key);
	for ($i=0; $i<$size; $i++){
    $row = $toOrderArray[$key[$i]];
    $position[$key[$i]]  = $row[$field];  
    $newRow[$key[$i]] = $row;
  }  
  if ($inverse) {  
    arsort($position);  
  }  
  else {  
    asort($position);  
  }  
  $returnArray = array();  
  $key = array_keys($position);
	$size = sizeOf($key);
	for ($i=0; $i<$size; $i++){
    $returnArray[$key[$i]] = $newRow[$key[$i]];  
  }  
  return $returnArray;  
}

/*
$array -> Array del que se quieren eliminar componentes
$exclude -> Array con los índices de los componentes de $array que se quieren eliminar
*/
function deleteFromArray ($array,$exclude){
	$size = sizeOf($exclude);
	for ($i=0; $i<$size; $i++){
		unset($array[$exclude[$i]]);
	}
	return $array;
}


function isPage ($page) {
	$self = ($_SERVER['PHP_SELF']);
	$pos = stripos($self, $page);
	return ($pos===false)?false:true;
}


/*********************************************  FUNCIONES DE LA EDICIÓN ****************************************************/

function getProperties ($class,$inherited=false) {
	$properties = array();
	$classes = array();
	array_push($classes,$class);
	if ($inherited) $classes = array_merge($classes,getAncestors($class));
	$key = array_keys($GLOBALS['pangea']['owl:Property']);
	$size = sizeOf($key);
	for ($i=0; $i<$size; $i++) {
		//if ($GLOBALS['pangea']['owl:Property'][$key[$i]]['rdfs:domain']===$class){
		$domain = $GLOBALS['pangea']['owl:Property'][$key[$i]]['rdfs:domain'];
		if (in_array($domain,$classes)){
			array_push($properties,$key[$i]);
		}
	}
	return($properties);
}

//Se le pasa una calse y devuelve el padre
function getParent($class){
	$parent = (isset($GLOBALS['pangea']['owl:class'][$class]['rdf:subClassOf']))?$GLOBALS['pangea']['owl:class'][$class]['rdf:subClassOf']:'';
	return($parent);
}

//Se le pasa una clase y devuelve sus ancestros ordenados en la medida que aumenta el índice el ancestro es más lejano
function getAncestors($class) {
	$ancestors = array();
	$parent = getParent($class);
	do
	  {
		array_push($ancestors,$parent);
		$parent = getParent($parent);	
	  }
	while ($parent);
	return($ancestors);
}

function perfImageEdit($prefImage){
	$image = '
		<div class="thumb">
			<img src="http://dev.pangea.ohc.cu/resource/'.$prefImage.'" alt="imagen preferida" />
			<small>Imagen de la portada</small>
		</div>';
	$noImg =  '
		<div class="thumb">
			<div class="dropbox">
				<span class="message">Suelta tu imagen aquí... ven</span>
			</div>
			<small>sin imagen</small>
		</div>';
	echo ($prefImage)?$image:$noImg;
}

function perfValuesEdit($key,$values){
	$host = $GLOBALS['host'];
	$text = $GLOBALS['text'];
	//$cons = $GLOBALS['cons'];
	/*$excludeKeys contiene los $key que no se quieren seguir a partir de sus URI*/
	$excludeKeys = 	array (
									/*'frbr:relatedSubject',
									'pangea:hasAvailability',
									'pangea:hasAdquisitionWay',
									'pangea:hasCollection',
									'pangea:hasLanguage',
									'pangea:hasForm'*/
								);
	$valueHTML = '';
	$size = sizeOf($values);
	$typeof = $GLOBALS['pangea']['owl:Property'][$key]['rdfs:range'];
	for ($i=0; $i<$size; $i++) {
		if ($values[$i]['type'] === 'uri' && !strpos($values[$i]['value'],':')){
			$prefLabel = (isset($values[$i]['label']))?$values[$i]['label']:$values[$i]['value'].' '.$GLOBALS['error'];
			//if (array_key_exists ($values[$i]['value'],$GLOBALS['description'])){
			if (isset ($GLOBALS['description'][$values[$i]['value']])){
				$GLOBALS['description'][$values[$i]['value']]['count']++;
			}
			else {
				$GLOBALS['description'][$values[$i]['value']]['count']= 1;
			}
			$GLOBALS['description'][$values[$i]['value']]['rdfs:label']=$prefLabel;
		}
		else {
			$prefLabel = $values[$i]['value'];
		}
		$input = '<input type="hidden" name="'.$key.'" value="'.$values[$i]['value'].'" />';
		$valueHTML .= '<li typeof="'.$typeof.'">'.$prefLabel.$input.'</li>';
			//$addBttn = '<li><button>' . $GLOBALS['msg']['add_text'] . '&nbsp;<span class="icon-plus"></span></button></li>';
			//$valueHTML[$i] = ($key === 'rdf:type' )?$frbrNS.substr($values[$i]['value'],5):$values[$i]['value'];
	}
	//$valueHTML[$i++] = '<button class="multiple">' . $GLOBALS['lbl']['empty'] . '&nbsp;<span class="icon-plus"></span></button>';
	$valueHTML .= '<li class="add multiple" typeof="'.$typeof.'">' . $GLOBALS['lbl']['empty'] . '&nbsp;<span class="icon-plus"></span></li>';
	return '<ul class="value horlist">'.$valueHTML.'</ul>';
}


/*
{
	"frbr:embodimentOf":[{"type":"uri","value":2028870,"typeof":"frbr:Expression"}],
	"pangea:place":[{"type":"uri","value":"95432","typeof":"frbr:Place","label":"Santiago de Cuba"}]
}
$properties = array (
	'frbr:embodimentOf'	=> array(array('type=>'uri','value'=>2028870,'typeof'=>'frbr:Expression')),
	'pangea:place'			=> array(array('type'=>'uri','value'=>'95432','typeof=>'frbr:Place','label'=>'Santiago de Cuba'))
);
*/
function newEntity ($typeof,$properties=array()){
	$newEntity = array('rdf:type'=>array(array('type'=>'uri','value'=>$typeof)));
	if ($properties) $newEntity = array_merge($newEntity,$properties);
	return $newEntity;
}

function perfEditableEntity($entity,$header='h2',$tag='li'){

/*variables*/
	$host = $GLOBALS['host'];
	$msg = $GLOBALS['msg'];
	$lbl = $GLOBALS['lbl'];
	$type = (array_key_exists('rdf:type',$entity))?$entity['rdf:type'][0]['value']:'frbr:Manifestation';
	$preflabel = (array_key_exists('skos:prefLabel',$entity))?$entity['skos:prefLabel'][0]['value']:'';
	$prefxlabel = (array_key_exists('skosxl:prefLabel',$entity))?$entity['skosxl:prefLabel'][0]['value']:'';
	//$prefImage = (array_key_exists('foaf:img',$entity))?$entity['foaf:img'][0]['value']:'';
	$title = (array_key_exists('frbr:exemplarOf',$entity))?perfValues('frbr:exemplarOf',($entity['frbr:exemplarOf'])):'';

	$label = ($preflabel)?$preflabel:(($prefxlabel)?getConceptLiteralForm($prefxlabel):'');

	if ($label){
		$input = '<input type="hidden" name="skos:prefLabel" value="'.$label.'" />';
	}
	else {
		$label= '<p class="badge"><span class="icon-pencil"></span> identifica esta entidad <em>recomendado</em></p>';
		$input= '<input type="hidden" name="skos:prefLabel" value="" />';
	}

/*PARCHE se quitan las llaves que no se van a usar en la edición*/

	$excludeKeys = 	array (
									//'foaf:img',
									'pangea:date',
	                //'rdfs:comment', 
	                'skos:note', 
                  //'pangea:note',
	                //'rdfs:Label',
	                /*parche parchoso <-------------------------------------*/
	                /***************** Estas son las etiquetas, ver cómo construir sin dolor*/
	                'pangea:name',
	                'pangea:firstName',
	                'pangea:scientificName',
	                //'skos:altLabel',
	                //'skos:hiddenLabel',
									'skos:prefLabel',
									'skosxl:prefLabel',
	                /***************** Estas son los troncos donde no se echa nada solo se hereda*/
									'pangea:ObjectProperty',
	                'frbr:relatedResponsibleEntity',
	                'pangea:nomen',
									'pangea:price',
	                'rdfs:label',
	                /***************** Estas son las que han de permancer ocultas*/
									'frbr:realizationOf',
									'frbr:realization',
									'frbr:embodimentOf',
									'frbr:embodiment',
									'frbr:exemplar',
									'frbr:exemplarOf',
	                /*pero con tu parche gozo :) <--------------------------*/
	                //'frbr:relatedResponsibleEntity'
									'rdf:type'
								);


	$entity = deleteFromArray($entity,$excludeKeys);

	//echo '<ul class="verlist">';
	//echo ($label)?'<li><'.$header.'>'.(($permalink)?'<a href="'.$host.'?_ids='.$permalink.'" title="permalink">'.$label.'</a>':$label).'</'.$header.'></li>':'<li>escribe una etiqueta pa identificar esta entidad</li>';
	
	//$key = array_keys(deleteFromArray($entity,$excludeKeys));
	$key = array_keys($entity);
	$size = sizeOf($key);
	$entityHTML = '';
	for ($i=0; $i<$size; $i++){		
		$class = $GLOBALS['pangea']['owl:Property'][$key[$i]]['rdfs:range'];
		$property = $key[$i];

/*PARCHE parching*/

		//<---------------------- esto es un parche hasta que se arregle el esquema
		if ($key[$i] === 'pangea:date' || $key[$i] === 'pangea:warning' || $key[$i] === 'pangea:error' || $key[$i] === 'rdfs:comment') {
			$class = $key[$i];
		}
		else if ($key[$i] === 'pangea:contentNote' || $key[$i] === 'pangea:generalNote' || $key[$i] === 'pangea:accompanyNote' || $key[$i] === 'pangea:adquisitionNote' || $key[$i] === 'pangea:itemNote' || $key[$i] === 'pangea:note') {
			$class = 'rdfs:comment';
			$property = 'rdfs:comment';
		}
		else if ($key[$i] === 'pangea:hasForm') {
			if($type === 'frbr:Expression') $class = 'pangea:Typology';
			if($type === 'frbr:Manifestation') $class = 'pangea:DocumentType';
		}		
		else if ($key[$i] === 'pangea:strDate') {
			$class = 'pangea:date';
		}
		else if ($key[$i] === 'pangea:price' || $key[$i] === 'pangea:priceMn' || $key[$i] === 'pangea:priceUsd' || $key[$i] === 'pangea:priceCuc') {
			$class = 'pangea:price';
		}		
		else {
			$class = $GLOBALS['pangea']['owl:Property'][$key[$i]]['rdfs:range'];
		}
		//<---------------------- aquí se acaba el parche hasta que se arregle el esquema

/*perform HTML*/
		$title = (isset($msg[$type][$property]))?' title="'.$msg[$type][$property].'"':'';
		$name = '';
		$entityHTML .=  '<li property="'.$property.'" class="'.$class.'"><span class="label"'.(($title)?$title:'').'>'.((array_key_exists($key[$i],$lbl))?$lbl[$key[$i]]:$key[$i]).'</span>'.perfValuesEdit($key[$i],$entity[$key[$i]]).'</li>';
	}
	//return '<li property="skos:prefLabel" class="xsd:string">'.$input.'<'.$header.' class="value">'.$label.'</'.$header.'></li>'.(($title)?'<li>'.$title.'</li>':'').$entityHTML;
	return '<li property="skos:prefLabel" class="xsd:string"><span  class="value"><'.$header.'>'.$input.$label.'</'.$header.'></span></li>'.(($title)?'<li>'.$title.'</li>':'').$entityHTML;
}

function makeEditable($resource,$entity,$tag='li',$header='h2',$addPrp=array()){
	$host = $GLOBALS['host'];
	$msg = $GLOBALS['msg'];
	$lbl = $GLOBALS['lbl'];
	
	//$entity = getEntity ($resource);
	$newEntity = ($entity)?false:true;
	$type = (array_key_exists('rdf:type',$entity))?$entity['rdf:type'][0]['value']:'frbr:Manifestation';
	$addPrp2ndL = array();
	
	if ($addPrp){
		if ($addPrp['frbr:embodimentOf']) $entity['frbr:embodimentOf']=$addPrp['frbr:embodimentOf'];
		if ($addPrp['frbr:realizationOf'] && $type =='frbr:Manifestation') $addPrp2ndL=$addPrp;
		if ($addPrp['frbr:realizationOf'] && $type =='frbr:Expression') $entity['frbr:realizationOf']=$addPrp['frbr:realizationOf'];
		unset($addPrp);
	}
	
	
	/**/
	$menuEdit = '
	<div class="tbox-edit">
	 	<ul class="pangea">
	 		<li title="Etiqueta" class="rdfs:label xsd:string" typeof="xsd:string"><span class="icon-pencil"></span></li>
	 		<li title="Etiqueta controlada" class="pangea:Form pangea:AdquisitionWay pangea:Availability pangea:Subject pangea:Color pangea:Language pangea:Shape pangea:Collection pangea:DocumentType pangea:Typology" typeof="pangea:DescriptorEntity"><span class="icon-tag"></span></li>
	 		<li title="'.$lbl['pangea:date'].'" class="pangea:date" typeof=""><span class="icon-calendar"></span></li>
	 		<li title="Identificador" class="nomelose" typeof="xsd:string"><span class="icon-barcode"></span></li>
	 		<li title="Enlace externo" class="nomelose" typeof="xsd:string"><span class="icon-globe"></span></li>
	 		<li title="Imagen" class="nomelose" typeof="nomelose"><span class="icon-picture"></span></li>
			<li title="Precio" class="pangea:price" typeof="xsd:float"><span class="icon-money">$</span></li>
	 	</ul>
	 	<ul class="frbr">
	 		<li title="'.$lbl['frbr:Person'].'" class="frbr:Core frbr:ResponsibleEntity frbr:Person" typeof="frbr:Person"><span class="icon-adult"></span></li>
	 		<li title="'.$lbl['frbr:CorporateBody'].'" class="frbr:Core frbr:ResponsibleEntity frbr:CorporateBody" typeof="frbr:CorporateBody"><span class="icon-home"></span></li>
	 		<li title="'.$lbl['frbr:Place'].'" class="frbr:Core frbr:Subject frbr:Place" typeof="frbr:Place"><span class="icon-map-marker"></span></li>
	 		<li title="'.$lbl['frbr:Event'].'" class="frbr:Core frbr:Subject frbr:Event" typeof="frbr:Event"><span class="icon-certificate"></span></li>
	 		<li title="'.$lbl['frbr:Object'].'" class="frbr:Core frbr:Subject frbr:Object" typeof="frbr:Object"><span class="icon-gift"></span></li>
	 		<li title="'.$lbl['frbr:Concept'].'" class="frbr:Core frbr:Subject frbr:Concept" typeof="frbr:Concept"><span class="icon-tags"></span></li>
	 	</ul>
	 	<ul class="comments">
	 		<li title="Comentario" class="rdfs:comment" typeof="xsd:string"><span class="icon-comment"></span></li>
	 		<li title="'.$lbl['pangea:warning'].'" class="pangea:warning" typeof="xsd:string"><span class="icon-warning-sign"></span></li>
	 		<li title="'.$lbl['pangea:error'].'" class="pangea:error" typeof="xsd:string"><span class="icon-remove-circle"></span></li>
	 	</ul>
	</div>';

	/*se obtienen las propiedades que le tocan a la entidad según su definición (esquema)*/
	$properties = array_fill_keys(getProperties($type,$inherited=true),null);

	/*PARCHE profilaxis quita las propiedades que no se quieren arrastrar*/
	$excludeProp = array (
										'pangea:contentNote',
										'pangea:generalNote',
										'pangea:accompanyNote',
										'pangea:adquisitionNote',
										'pangea:itemNote',
										'pangea:note'
	);
	
	$properties = deleteFromArray($properties,$excludeProp);
	
	/*se pegan las propiedades que le tocan a la entidad*/

	$entity = array_merge($entity,array_diff_key($properties,$entity));

	list($pref,$class)=explode(':',$type);

	/*frbr:exemplar*/
	$items = (isset($entity['frbr:exemplar']))?(getEntities (array_map(function($val){return $val['value'];},$entity['frbr:exemplar']),'large')):Array();

	/*perform HTML*/
	//$editableEntityHML = '';
	
	$entityHint = $msg[$class];
	$entityParent = getParent($type);
	$newFrom = '&nbsp;<a href="edit.php?_ids='.uniqid('_').'&_'.strtolower($class).'='.$resource.'">nuevo a partir de&nbsp;<span class="icon-plus"></a>';
	$editableEntityHML =  '<'.$tag.' class="'.(($newEntity)?'new ':'').'edit entity '.strtolower($class).'" resource="'.$resource.'" typeof="'.$type.'">';
	$editableEntityHML .= '<small class="badge notice" title="'.$entityHint.'">'.(($class)?$class:'esto queloqué').'</small>'.(($type=='frbr:Expression' || $type=='frbr:Work' || $entityParent==='frbr:Expression')?$newFrom:'');
	$editableEntityHML .= $menuEdit;
	
	if ($type==='frbr:Item') $name='frbr:Exemplar';
	else if ($type==='frbr:Expression' || $entityParent==='frbr:Expression') $name='frbr:embodimentOf';
	else if ($type==='frbr:Work') $name='frbr:realizationOf';
	
	$input = ($type!='frbr:Manifestation')?'<input type="hidden" value="'.$resource.'" name="'.$name.'[]" />':'';
	
	$editableEntityHML .= $input.'<ul class="verlist">';
	$editableEntityHML .= perfEditableEntity($entity,$header);
		
	if ($items) $editableEntityHML .= '<li><span class="label">ejemplares</span>'.count($items).'</li>';
	
	/*frbr:embodimentOf*/
	if (array_key_exists('frbr:embodimentOf',$entity)) {
		
		$expressions = (isset($entity['frbr:embodimentOf']))?getEntities(array_map(function($val){return $val['value'];},$entity['frbr:embodimentOf']),'large'):array(uniqid('_')=>newEntity('frbr:Expression'));
		
		$expKeys = array_keys($expressions);
		$expSize = sizeOf($expressions);
		$expressionsHTML = '';
		for ($x=0; $x<$expSize; $x++){
			$resource = $expKeys[$x];
			$expression = $expressions[$resource];
			$expressionsHTML .= makeEditable($resource,$expression,'li','h3',$addPrp2ndL);
			//$expressionsHTML .= makeEditable($resource,'li','h3',$addPrp2ndL);
		}
		$editableEntityHML .= '<li class="frbr:Expression" property="frbr:embodimentOf">'
														.'<h3>Contenido <span class="add newEntity" typeof="frbr:Expression"><span class="hide new">'.json_encode(array(uniqid('_')=>newEntity('frbr:Expression'))).'</span>añadir contenido&nbsp;<span class="icon-plus"></span></span>
														&nbsp;<span class="add newEntity" typeof="frbr:Text"><span class="hide new">'.json_encode(array(uniqid('_')=>newEntity('frbr:Text'))).'</span>texto&nbsp;<span class="icon-plus"></span></span>
														&nbsp;<span class="add newEntity" typeof="frbr:Image"><span class="hide new">'.json_encode(array(uniqid('_')=>newEntity('frbr:Image'))).'</span>imagen&nbsp;<span class="icon-plus"></span></span>
														&nbsp;<span class="add newEntity" typeof="pangea:Serial"><span class="hide new">'.json_encode(array(uniqid('_')=>newEntity('pangea:Serial'))).'</span>serial&nbsp;<span class="icon-plus"></span></span>
														</h3> '
														.'<ul class="value">'
															.$expressionsHTML
														.'</ul>'
													.'</li>';
	}

	/*frbr:realizationOf*/
	if (array_key_exists('frbr:realizationOf',$entity)) {
		$newWorkID = uniqid('_');
		$resource = (isset($entity['frbr:realizationOf']))?$entity['frbr:realizationOf'][0]['value']:$newWorkID;
		$work = ($resource != $newWorkID)?getEntity($resource):newEntity('frbr:Work');
		$worksHTML = makeEditable($resource,$work,'div','h3');
		//$worksHTML = makeEditable($resource,'div','h3');
		$editableEntityHML .= '<li class="frbr:Work" property="frbr:realizationOf">'.$worksHTML.'</li>';
	}
	
	$itemsHTML = '';
	if ($items) {
		$itemsKeys = array_keys($items);
		$itemsSize = sizeOf($items);
		for ($x=0; $x<$itemsSize; $x++){
			$resource = $itemsKeys[$x];
			$item = $items[$resource];
			$itemsHTML .= makeEditable($resource,$item,'li','h3');
			//$itemsHTML .= makeEditable($resource,'li','h3');
		}
		/*$editableEntityHML .=	'<li class="frbr:Item" property="frbr:exemplar">'
														.'<h3>Ejemplares <span class="add newEntity" typeof="frbr:Item"><span class="hide new">'.json_encode(array(uniqid('_')=>newEntity('frbr:Item'))).'</span>añadir un ejemplar nuevo&nbsp;<span class="icon-plus"></span></span></h3>'
														.'<ul class="value">'
															.$itemsHTML
														.'</ul>'
													.'</li>';*/
	}
	
	if ($type==='frbr:Manifestation') {
		$editableEntityHML .=	'<li class="frbr:Item" property="frbr:exemplar">'
														.'<h3>Ejemplares <span class="add newEntity" typeof="frbr:Item"><span class="hide new">'.json_encode(array(uniqid('_')=>newEntity('frbr:Item'))).'</span>añadir un ejemplar nuevo&nbsp;<span class="icon-plus"></span></span></h3>'
														.'<ul class="value">'
															.$itemsHTML
														.'</ul>'
													.'</li>';
	}

	$editableEntityHML .= '</ul></'.$tag.'>';
	return $editableEntityHML;
}
  
?>

