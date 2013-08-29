<!--
<div id="finder">
	<span class="input"><input type="text" class="find"/><button><span class="icon-search"></span></button></span>
	<ul class="reslt"></ul>
</div>
-->

<div id="finder">
	<div>
		<span class="icon-idea"></span> <span class="hint">aquí va el hint</span>
	</div>
	<div>
		<span class="input"><input type="text" class="find"/></span>
	</div>
		<ul class="reslt"></ul>
</div>

<div id="loading">
	<img src="img/loadinfo.gif" alt="loading" /><br />buscando resultados, espere...
</div>

<!--
Este es el composer, aquí se arman los valores que necesitan un formato especial como  el título, las fechas y los códigos de barras
-->
<div id="composer">
	<textarea id="texter"></textarea>
	<p class="label">este es el componedor, aquí se arama lo que ayuda a escribir... si el usuario quiere</p>
	<ul>
		<li id="title"><label>título</label><input type="text" /></li>
		<li id="subtitle"><label>subtítulo</label><input type="text" /></li>
		<li id="ok"><button class="ok">  OK :) </button></li>
	</ul>
</div>