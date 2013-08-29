<ul class="gallery">
	<li class="span4">
		<h2><?php echo $lbl['post_title'];?></h2>
		<p><?php echo $txt['post_txt'];?></p>
	</li>
<?php 
$items = array ("367556","226088","2363835","2366283","2377419","2378215","2383808","2386263","2441565","385067","394001","802419","806497","808425","822265","830019","830583","854334","858839","874590");
$entities = getEntities ($items);
//print_r($entities);
$keys = array_keys($entities);
$size = sizeOf($keys);
for ($i=0; $i<$size; $i++){
	$title = $entities[$keys[$i]]['frbr:exemplarOf'][0]['label'];
	$image = $entities[$keys[$i]]['foaf:img'][0]['value'];
	echo '<li class="thumb">
		<a href="'.$host.'?_ids='.$keys[$i].'"><img alt="'.$image.'" src="http://dev.pangea.ohc.cu/resource/'.$image.'"></a>
		<p>'.$title.'</p>
	</li>';
	/*echo '<li class="thumb">
		<a href="http://pb.ohc.cu/pangea/?_ids='.$keys[$i].'">'.$image.'</a>
		<p>'.$title.'</p>
	</li>';*/
}
?>
</ul>
