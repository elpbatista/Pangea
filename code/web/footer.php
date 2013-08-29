		</div> <!-- main -->
		
		<footer>
			<div class="container">
				<?php if ($page === 'home') include_once 'inc_footer.php'; ?>
			</div>
		</footer>		
		
		<p id="patron"><a href="http://validator.w3.org/check?uri=referer"><img src="img/sw-rdfa-blue.png" alt="Valid XHTML + RDFa!" /></a></p>
		<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
		<script type="text/javascript" src="js/jquery-ui-1.9.2.custom.min.js"></script>
		<script type="text/javascript" src="js/jquery.masonry.min.js"></script> 
		<!-- <script type="text/javascript" src="js/jquery-highlight.js"></script> -->
		<script type="text/javascript" src="js/jquery.tinyscrollbar.min.js"></script>
		<script type="text/javascript" src="js/main.js"></script>
			
		<?php if ((isPage('edit.php')) || (isPage('newAnabel.php'))) { ?>
		<script type="text/javascript" src="js/jquery.cokidoo-textarea.js"></script>
		<!-- <script type="text/javascript" src="js/jquery.filedrop.js"></script>
		<script type="text/javascript" src="js/filedrop-min.js"></script>-->
		<script type="text/javascript" src="js/jquery.jeditable.mini.js"></script>
		<script type="text/javascript" src="js/edit.js"></script>
		<!-- <script type="text/javascript" src="js/imagesup.js"></script>-->
			
		<?php } 
		  if(isPage('index_statistics.php')){?>
		   <script src="js/build/yui/yui-min.js" type="text/javascript"></script> 	
	       <script src="js/stats_functions.js" type="text/javascript"></script>
	   <?php } ?>
	</body>
</html>
