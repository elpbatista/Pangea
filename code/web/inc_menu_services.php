<ul id="menu_services">
<?php
    if(isset($_GET['_logout'])){
    	unset ( $_SESSION ['logged'] );
		unset ( $_SESSION ['currentUser'] );
		unset ( $_SESSION ['userPermissions'] );
    }

	$user = (isset($_SESSION ['logged']) && isset($_SESSION ['currentUser'])) ? $_SESSION ['currentUser'] : "Anónimo";
?>
	<li><strong><a href="#"><?php echo $user; ?></a></strong></li>

	<li><?php include 'inc_menu_lang.php' ?></li>

	<li><a href="<?php echo $host; ?>" title="inicio"><span class="icon-home"></span></a></li>
	
<?php if (isset($_SESSION ['logged']) && $_SESSION ['logged']) {?>
   <!--  <li><a href="/login.php">salir</a></li>	-->	
    <?php
       if(strpos($back_page, '?')=== false)
       {?>
       <li><a href="<?php echo $back_page?>?_logout='true'">salir</a></li>	 
	  <?php }
	  else {?>
	  	<li><a href="<?php echo $back_page?>&_logout='true'">salir</a></li>
	  <?php }
	  ?>
<?php };

?>	
</ul>
