<?php
include_once 'Util.php';
include_once 'functions.php';
include_once 'variables.php';

 session_start();
 
include_once 'header.php';

     $page_url = $_POST['_refpage'];
     if(!(strpos($page_url, 'logout')=== false)){
      	$length = strlen($page_url);
      	$final_length = $length - 15;
      	$substring = substr($page_url, 0, $final_length);
      	$_POST['_refpage'] = $substring;
     }
			     

if (! isset($_SESSION ['logged'])){
	    if(! empty ($_POST['_refpage']))
	       $_SESSION ['previous_page'] = $_POST['_refpage'];
		$referer = $_SERVER ['SERVER_NAME'] . $_SERVER ['PHP_SELF'];
		if (!empty($_SERVER ['QUERY_STRING'])) $referer = $referer . '?' . $_SERVER ['QUERY_STRING'];
		
?>

<script type="text/javascript">

    //verificar que se llenaron todos los campos, sino mostrar cartel de error
	function isNotEmpty() {
	  var my_form = document.forms[0];
	  var username = my_form._u.value;
	  var pass = my_form._pwd.value;
		
	  if (username == "" || pass == "") {
		  var label = document.getElementById("lb_error_empty");
		  label.style.display= '';
		 return false;
		}		
	  return true;	 	
	 }
</script>

	<form action="w_srvs/gateway.php" method="POST" id="authForm" onSubmit="return isNotEmpty();">
		 <!--  <a href="./" title="inicio"><img src="img/pangea.png" alt="Pangea" /></a> -->
		  <a href="./" title="inicio"><img src="img/logo_gen.png" alt="Pangea" /></a>
		  <input type="hidden" name="_a" value="/a" />		
		  <input type="hidden" name="_ref" value="<?php echo $_SESSION ['previous_page'] ;?>" />
		  <fieldset><legend>¡Dale Pangea!</legend>
			  <ul>
			   	       			    
			  	<li><label>nombre:</label><input type="text" name="_u" /></li>
			  	<li><label>contraseña:</label><input type="password" name="_pwd" /></li>
			  	<li><input type="submit" value="enviar" name="enviar" class="btn" /></li>
			  	
			  	<?php 
			   if(isset($_GET['_invalid'])){ //user invalid?>
			   <!-- Cartelito de error -->
		 	    <li><label> Autenticación fallida!</label>
		        <?php }?>
		        
		        <?php 
			   if(isset($_GET['_empty'])){ //user invalid?>
			   <!-- Cartelito de campos vacíos -->
		 	    <li><label> Debe rellenar todos los campos!</label>
		        <?php }?>
		        
		        <li><label id="lb_error_empty" style="display: none">Debe rellenar todos los campos!</label>
			  </ul>
		  </fieldset>
	</form>
<?php 		
}
include_once 'footer.php';
?>