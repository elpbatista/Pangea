<?php
	if (! isset($_SESSION ['logged'])){
		$referer = $_SERVER ['SERVER_NAME'] . $_SERVER ['PHP_SELF'];
		if (!empty($_SERVER ['QUERY_STRING']))
			$referer = $referer . '?' . $_SERVER ['QUERY_STRING'];
?>
	<form action="w_srvs/gateway.php" method="POST" id="authForm">
		  <input type="hidden" name="_a" value="/a" />		
		  <input type="hidden" name="_ref" value="<?php echo $referer;?>" />
		  <ul>
		  	<li><label>nombre:</label><input type="text" name="_u" /></li>
		  	<li><label>contraseña:</label><input type="password" name="_pwd" /></li>
		  	<li><input type="submit" value="enviar" name="enviar" class="btn" /></li>
		  </ul>
	</form>
<?php 		
	}
?>