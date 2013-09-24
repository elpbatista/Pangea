<form id="searchForm" action="">
	<fieldset><legend>¡Dale Pangea!</legend>
		<label for="searchBox">buscar</label>
		<input id="searchBox" type="text" class="text" name="_t" value="<?php echo $msg['search_txt'];?>" title="Buscar en Pangea" />
		<input type="hidden" id="pageSize" value="10" name="_ic" />
		<button type="submit" value="submit"><strong class="hide">buscar</strong></button>

	<div id="finder">
		<ul></ul>
	</div>

	</fieldset>


</form>
