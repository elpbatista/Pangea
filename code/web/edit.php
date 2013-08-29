<?php
$page = "edit";
include_once 'Util.php';
include_once 'functions.php';
include_once 'variables.php';
include_once 'header.php';
$resource = ! isset ( $_REQUEST ['_ids'] ) ? '' : $_REQUEST ['_ids'];

$withWork = ! isset ( $_REQUEST ['_work'] ) ? '' : $_REQUEST ['_work'];
$withExpr = ! isset ( $_REQUEST ['_expression'] ) ? '' : $_REQUEST ['_expression'];

/*
$type = 'frbr:Manifestation';



/*$preflabel = (array_key_exists('skos:prefLabel',$entity))?$entity['skos:prefLabel'][0]['value']:'';

//$type = (array_key_exists('rdf:type',$entity))?$entity['rdf:type'][0]['value']:'frbr:Manifestation';
if (isset($entity['rdf:type'][0]['value'])){
	$type = $entity['rdf:type'][0]['value'];
}
else {
	$newEntity = true;
}
*/
$properties = array (
	'frbr:embodimentOf' => (($withExpr)?array(array('type'=>'uri','value'=>$withExpr,'typeof'=>'frbr:Expression')):null),
	'frbr:realizationOf' => (($withWork)?array(array('type'=>'uri','value'=>$withWork,'typeof'=>'frbr:Work')):null)
);

$entity = getEntity ($resource);
?>

<div class="row">
	
	<div id="maincont">
		<?php echo makeEditable($resource,$entity,'div','h1',$properties); ?>
	</div>

	<div id="sidebar">
	  <div id="info">
	   	<?php echo '<a href="' . $host . '?_ids=' . $resource . '&_a=/bp">ver</a>';?>
	   	<br />
	   	<a href="<?php echo $host.'edit.php?_ids='.uniqid('_');?>">nueva entidad&nbsp;<span class="icon-plus"></span></a>
	  	<br />
	  </div>
	  <div id="sideScroll">
	    <div class="scrollbar"><div class="track"><div class="thumb1"><div class="end"></div></div></div></div>
	    <div class="viewport"><div class="overview">
		<?php
		//echo '<a href="' . $host . '?_ids=' . $resource . '&_a=/bp">ver</a>';
		$currTrunk = orderMultiDimensionalArray ( $GLOBALS ['description'], 'count', $inverse = true );
		$key = array_keys ( $currTrunk );
		$size = sizeOf ( $key );
		echo '<ul>';
		for($i = 0; $i < $size; $i ++) {
			$amount = $currTrunk [$key [$i]] ['count'];
			$label = $currTrunk [$key [$i]] ['rdfs:label'];
			echo '<li>' . $label . '&nbsp;' . $amount . '</li>';
		}
		echo '</ul>';
		?>
		</div><!-- overview -->
		</div><!-- viewport -->
	  </div><!-- sideScroll-->	
	</div><!-- sidebar -->

</div>

<?php include_once 'footer.php'; ?>
