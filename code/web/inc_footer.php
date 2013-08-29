<div  id="footer" class="row">
	<div class="span3">
		<h3>Sobre Pangea</h3>
		<ul>
			<li><a href="http://wiki.pangea.ohc.cu/">Wiki del proyecto</a></li>
			<li><a href="#">Pangea FAQ</a></li>
			<li><a href="images.php">Pangea Imágenes: una propuesta</a></li>
			<li>·······································</li>
			<li><a href="#">Cómo utilizar Pangea</a> (tutorial en 5 pasos)</li>
		</ul>
		<br />
		<h3>Licencia</h3>
		<p>Todos los contenidos se distribuyen bajo una licencia <a href="http://creativecommons.org/licenses/by-sa/3.0/">CC BY-SA 3.0</a>. Algunos derechos restringidos a favor del Proyecto Pangea y colaboradores</p>
	</div>
	<div class="span3">
		<h3>Servicios</h3>
		
		<ul>
			<li><a href="<?php echo $host?>">Pangea: El Catálogo ß</a></li>
			<li><a href="#">Pangea: Las Imágenes</a></li>
			<li><a href="#">Pangea: Los Mapas</a></li>
			<li><a href="#">Pangea: La Cloud</a></li>
			<li>·······································</li>
			<li><a href="index_statistics.php">Pangea: Las Estadísticas</a></li>
			<li>
			<form id="link_login_form" action="login.php" method="POST">			    
				<input type="hidden" id="_refpage" name="_refpage" value="<?php echo $back_page;?>" />
				<?php if (! isset($_SESSION ['logged'])){?>
				<a href="javascript: document.forms['link_login_form'].submit()">Entrar para catalogar</a> (usuarios internos)
				<?php }?>
			</form>
			</li>
		</ul>
		<br />
		<h3>Traducciones</h3>
		<ul>
			<li><a href="#">Pangea en Español</a></li>
			<li><a href="#">Pangea en Inglés</a></li>
			<li><a href="#">Pangea en Alemán</a></li>
		</ul>
	</div>
	<div class="span3">
		<h3>Contactos</h3>
		<ul>
			<li><a href="http://listas.ohc.cu/mailman/listinfo/pangea">Lista de discusión</a></li>
			<li><a href="mailto:info@pangea.ohc.cu">Información sobre el proyecto</a></li>
			<li><a href="mailto:proyecto@pangea.ohc.cu">Contacto</a></li>
			<li>·······································</li>
			<li><a href="#">Canal RSS</a></li>
			<li><a href="#">Pangea en Facebook</a></li>
			<li><a href="#">Pangea en MySpace</a></li>
			<li><a href="#">Pangea en Hi5</a></li>
			<li><a href="#">Síguenos en Twitter</a> #ppangea</li>
		</ul>
	</div>
	<div class="span3">
		<ul>
			<li><a href="http://www.habananuestra.cu/"><strong>OHC</strong> Oficina del Historiador de la Ciudad</a></li>
			<li><a href="http://www.ohch.cu/"><strong>DP-OHC</strong> Dirección de Patrimonio Cultural</a></li>
			<li><a href="#"><strong>DIC-OHC</strong> Dirección de Informática y Comunicaciones</a></li>
			<li>·······································</li>
			<li><a href="http://www.trialog.ch/">Trialog AG</a></li>
		</ul>
	</div>
</div>

