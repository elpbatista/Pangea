<div class="row">
	<div id="maincont">
		<div class="loading">
		<img src="img/loadinfo.gif" alt="loading" /><br /></div> <!-- ¡aguanta que la tan peinando! -->
<?php
$lbl = $GLOBALS['lbl'];

$documentType = ! isset ( $_REQUEST ['_ff'] ) ? 'frbr:Manifestation' : $_REQUEST ['_ff'];
$prpFilter = ! isset ( $_REQUEST ['_ff'] ) ? '' : $_REQUEST ['_ff'];
$valFilter = ! isset ( $_REQUEST ['_fv'] ) ? '' : $_REQUEST ['_fv'];
$ctgFilter = ! isset ( $_REQUEST ['_ctg'] ) ? '' : $_REQUEST ['_ctg'];
$httpRqStr = $host.$gateway.'?'.'_a='.$action;

if ($text) $httpRqStr .= '&_t='.urlencode($text); //<============= a ver a ver
if ($resource) $httpRqStr .= '&_ids='.$resource;
if ($idSearch) $httpRqStr .= '&_md5='.$idSearch;
if ($valFilter) $httpRqStr .= '&_fv='.$valFilter;
if ($ctgFilter) $httpRqStr .= '&_ctg='.$ctgFilter;

/*
if ($text){
	//$httpRqStr .= '_t='.urlencode(utf8_decode($text));
	$httpRqStr .= '&_t='.urlencode($text);
}
else if ($idSearch) {
	//$httpRqStr .= '_md5='.$idSearch.(($prpFilter)?'&_ff='.$prpFilter:'').(($valFilter)?'&_fv='.$valFilter:'').'&_n=1';
	//$httpRqStr .= (($prpFilter)?'&_ff='.$prpFilter:'').(($valFilter)?'&_fv='.$valFilter:'');
}
*/

//print($httpRqStr);
$resultsSet = new HTTPRequest($httpRqStr);
//$resultsJSON = json_decode($resultsSet->DownloadToString(), true);
/*esto es un parche pa tragarme los errores*/
$resultsJSON = json_decode(strstr($resultsSet->DownloadToString(),'{'), true);
$idSearch = $resultsJSON['id'];
$total = $resultsJSON['count'];
$results = $resultsJSON;

//print_r($resultsSet->DownloadToString());

//$pageElementsJSON = getFilteredPage($idSearch,$documentType,$pageNumber,$valFilter,$action);
$pageElementsJSON = getFilteredPage($idSearch,'frbr:Manifestation');
$pageCount = $pageElementsJSON['count'];
$pageElements = $pageElementsJSON['description'];

	if ($pageElements) {
		echo '<ol id="'.$idSearch.'" class="nbrList">';
			include 'inc_entitiesList.php';
		echo '</ol>';
	}
	else {
		echo '<h2>La búsqueda no arrojó ningún resultado</h2>';	
	}
	?>
	</div><!-- maincont -->
<?php 
/*************************************************************************************************/
/******************************************** CLUSTER ********************************************/
/*************************************************************************************************/	
$selected = array();
?> 
	 <div id="sidebar">
	  <div id="info">
	   <a href="<?php echo $host.'edit.php?_ids='.uniqid('_');?>">agregar una entidad nueva&nbsp;<span class="icon-plus"></span></a>
	   <hr />
	   <div class="scrollbar"><div class="track"><div class="thumb1"><div class="end"></div></div></div></div> 
	    <div class="viewport"><div class="overview">
		<ul id="metadata">
			<li><span class="label">buscando</span><span id="request"><?php echo ($resource)?$resource:$text ?></span></li>
			<li><span class="label"><span class ="icon-list"></span>&nbsp;total de resultados</span><span id="results_total"><?php echo $results['count'] ?></span></li>
			<li><span class="label">visibles en la página</span><span id="results_loaded"><?php echo count($pageElements) ?></span></li>	
			<li><span class="label">seleccionados</span><span id="results_selected"><?php echo count($selected) ?></span></li>	
		</ul>
		<?php 
		$currTrunk = $results['description'];
		$keys = array_keys($currTrunk);
		$size = sizeOf($keys);
		
		echo '<ul>';
		for ($i=0; $i<$size; $i++){
			echo '<li>'.(($lbl[$keys[$i]])?$lbl[$keys[$i]]:$keys[$i]).'&nbsp;'.$currTrunk[$keys[$i]]['count'].'</li>';
		}
		echo '</ul>';
		
		?>
		 </div> <!-- overview -->
		 </div> <!-- viewport -->
		</div>
		<div>
		<hr/>
		</div>
		<div id="sideScroll">
	      <div class="scrollbar"><div class="track"><div class="thumb1"><div class="end"></div></div></div></div>
	      <div class="viewport"><div class="overview">
		  <ul id="cluster">
		  <?php
			$selectedBranch[0] = $documentType;
			$excludeTypes = array (
											'frbr:Work',
											'frbr:Expression',
											'frbr:ResponsibleEntity',
											'frbr:Object',
											'frbr:Image',
											'frbr:Item',
											'skosxl:Label'
										);
			$exclude = array_unique(array_merge($excludeTypes,$selectedBranch));
			
			//print_r($currTrunk); //<=================================================================== IMPRIME EL RESULTADO
			for ($i=0; $i<$size; $i++){
				$typeof = $keys[$i];
				$amount = $currTrunk[$typeof]['count'];
				//echo '<li><h3>'.(($keys[$i]!==$selectedBranch[0])?'<a href="'.$host.'?_t='.$text.'&_pg=1&_ff='.$keys[$i].'">'.$keys[$i].'</a>':$keys[$i]).'&nbsp;'.$amount.'</h3></li>';
				//echo '<li class="'.$typeof.'"><h3>'.(($lbl[$typeof])?$lbl[$typeof]:$typeof).'&nbsp;'.$amount.'</h3>';
				//echo '<li class="'.$typeof.'"><h3>'.$typeof.'&nbsp;'.$amount.'</h3>';
				if (!in_array($typeof, $exclude)){
					echo '<li class="'.$typeof.'"><h3>'.(($lbl[$typeof])?$lbl[$typeof]:$typeof).'&nbsp;'.$amount.'</h3>';
					echo '<ul>';
					$pageElementsJSON = getFilteredPage($idSearch,$typeof,1);
					$pageCount = $pageElementsJSON['count'];
					$pageElements = $pageElementsJSON['description'];
					include 'inc_entitiesTinyList.php';
					echo '</ul>';
				}
			echo '</li>';
			}
		?>
		</ul>
	     </div><!-- overview -->
	   </div><!-- viewport -->
	</div><!-- sideScroll -->
  </div><!-- sidebar -->
</div><!-- row -->

