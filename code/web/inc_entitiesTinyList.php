<?php
$lkey = array_keys($pageElements);
$lsize = sizeOf($lkey);
for ($j=0; $j<$lsize; $j++){
	$href = $host.'?_a=/m&_md5='.$idSearch.'&_ids='.$lkey[$j];
	echo '<li resource="'.$lkey[$j].'" typeof="'.($pageElements[$lkey[$j]]['typeof']).'"><a href="'.$href.'">'.($pageElements[$lkey[$j]]['label']).'</a> '.($pageElements[$lkey[$j]]['count']).'</li>';
}
	echo ($pageCount>($pageSize*$pageNumber))?'<li class="meta" typeof="'.$typeof.'"><span class="icon-plus"></span>&nbsp;más...<span class="_pg">'.($pageNumber+1).'</span></li>':'';
?>