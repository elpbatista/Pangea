var dragme = $('.dragme');
var dropme = $('.dropme');
var loading = '<div class="loading"><img src="img/loadinfo.gif" alt="loading" /><br />Por favor. espere...</div>';
var txtarea = $('li textarea');

var prperty = $('#maincont .edit [property]');
//var prempty = $('#maincont .edit [property]:not(:has(input))');
var prempty = $('#maincont .edit [property]:not([property*="skos:prefLabel"]):not([property*="frbr:exemplar"]):not(:has(input))');
//var prempty = $('#maincont .edit [property*="skos:prefLabel"]');
var editbox = $('#maincont .edit .tbox-edit');

//aquí escondo toas las propiedades vacías
prempty.hide();
editbox.hide();

var fndtxt = $('#finder .find');
//var loading = $('<div id="loading" />').load('buildHTML.php #loading').html();

//$('.edit .verlist').addClass('patch');

//$('#menu_services').prepend('<li id="cartelito" title="esta máquina no habla... computa">numeritos</li>');

/*aquí ta to lo que se menea en el Pangea ete a lo que se le puede hacer click y eso*/

$('#maincont').on({
	click:handleClick,
	mouseover:handleMouseover,
	mouseleave:handleMouseleave,
	mouseout:handleMouseout,
	keydown:handleKeydown,
	keyup:handleKeyup,
	change:handleChange
});

function handleClick(e){
	//e.preventDefault();
	e.stopPropagation();
	
	var trg = $(e.target);
		
	//si cliquea en el #finder
	if (trg.parents('#finder').length>0){
		if(trg.is('.reslt li:not([class])')){
			//$('#finder').data({'range':rng,'resource':rsc,'entity':ent,'property':prp});
			//var crd = add.attr('class');
			/*if (crd==='single'){
				trg.parents('li').find('.horlist li:not(.add)').remove();
				$('#finder .selected').removeAttr('class');
			}*/
		  //------
		  var prp = $('#finder').data('property');
		  var add = prp.find('.add'); //add +
		  //------
		  trg.clone().insertBefore(add);
		  trg.addClass('selected');
		  update(prp);
		}
	}
	
	
	
	//lo que pasa adentro del composer
	else if (trg.parents('#composer').length>0){
		if(trg.attr('class')==='del'){
			trg.closest('li').remove();
			titlepit();
		}
		else if(trg.attr('class')==='ok'){
			$('#texter').mouseleave();
		}
		return false;
	}
	
	//si cliquea en una entidad editable
		else if (trg.parents('.edit').length>0 && !trg.is('textarea')){
		
		//quita el  finder
		//prperty.removeClass('editing');
		$('#maincont .edit [property]').removeClass('editing');
		$('#finder').mouseleave();
		$('#texter').mouseleave();
		
		var prp = trg.closest('[property]'); //clicked property
		var rng = prp.attr('class'); //range
		var ent = trg.closest('.entity'); //entity
		var rsc = ent.attr('resource'); //resource
		var typ = ent.attr('typeof'); //typeof
		
		var add = prp.find('.add'); //add +
		
		//esto interroga al clickiao
		//var cnt = trg.closest('li');
		var cnt = trg.closest('*:has(input)');
		var val = cnt.children('input').attr('value');
		
		//eliminar valor
		if(trg.hasClass('del')){
			$('#finder li:has(input[value="'+val+'"])').removeAttr('class');
			cnt.remove();
			update(prp);
		}
		//agregar una nueva entidad
		else if(trg.hasClass('newEntity')){
			var newEntity = trg.find('.new').text();
			$.post('buildNewEntity.php',{'new':newEntity},function(result){
				
				//$('[class*="Item"] ul.multivalue').append(result);
				prp.children('ul.value').append(result);
				$('[property]:not([property*="skos:prefLabel"]):not(:has(input))').hide();
				$('.tbox-edit').hide();
				
				if (prp.attr('property')=='frbr:exemplar'){
					/*alert(
						prp.children('ul.multivalue').children('li').last().attr('resource')+'->rdf:type->frbr:Item'
						+'\n'+
						ent.attr('resource')+'->frbr:exemplar->'+prp.children('ul.multivalue').children('li').last().attr('resource')
						)*/
					n_rsc =	prp.children('ul.value').children('li').last().attr('resource');
					arg = {_a:'/u',_ids:n_rsc,'rdf:type':'frbr:Item'};
					$.post('w_srvs/gateway.php',arg,function(result){
						obj=$.parseJSON(result);
						$('[resource="'+obj['_bnode']+'"]').attr('resource',obj['_ids']).children('input:hidden').attr('value',obj['_ids']);
						//console.log(n_ent.attr('resource'));
						//tengo que mandarlos tos pabajo, la propiedad completa
						update(prp); 
					});
				}
			})
		}
		//si cliquea en una de las herramientas levanto todas las propiedades posibles  y entonces de esas que levanto selecciono una y sige el curso natural del asunto. En ese caso escondería el resto de las propiedades que están vacías. Se puede pensar en un modo de todo visible.
		else if (trg.parents('.tbox-edit').length>0){
			// aquí escondo to lo que hay por ahí que tiene que tar escondío
			prempty.hide();
			//aquí parto los posibles rangos en un array
			//cnt = trg.closest('li');
			var prg = (trg.closest('li').attr('class')).split(' ');
			
			//aquí recuerdo el typeof del elemento clickeao
			ent.data({'clicked':trg.closest('li').attr('typeof')});
			
			//aquí itero sobre el array y saco a la lú las propiedades
			$.each(prg,function(index,value){
				ent.children('.verlist').children('[class*="'+value+'"]').show().addClass('editing');
			});
		}
		
		//modificar o agregar valor (llamar al #finder o sacar una caja pa escribir)
		// aquí e donde voy a empezar a meter el #composer
		else{
			if (rng === 'xsd:string' || rng==='xsd:integer' || rng==='xsd:float' || rng==='xsd:date' || rng==='pangea:price' || rng==='pangea:warning' || rng==='pangea:error' || rng==='rdfs:comment' || rng==='pangea:date'){
				var cmpClass = '#texter';
				if (typeof(val)  === 'string')trg.remove();
				if (prp.attr('property') === 'skos:prefLabel') {
					cnt.empty();
					if (typ==='frbr:Manifestation')cmpClass='#composer';
				}
				prp.append($( '<div />' ).attr({id:'cmploader'}))
				$('#cmploader').load('buildHTML.php '+cmpClass,function(){
					$('li textarea').autoResize();
					$('#texter').attr({value:val});
					//si está el composer...
					if (('#composer').length>0){
						titletia(val);						
					}
					else {
						$('li textarea').focus();
					}
				});
				return false;
			}
			else if (rng === undefined) {
				return false;
			}
			else if (trg.is('a')) {
				$.loadPage(trg.attr('href'));
			}
			else {
				//aquí pego el finder
				$('#maincont').append($('<div>').load('buildHTML.php #finder',function(){
					//if (tof==='frbr:Endeavour') $('#finder').prepend($('<span>').load('buildHTML.php #manual'));
					//trg.css({'background':'red'});
					$('#finder').data({'range':ent.data('clicked'),'resource':rsc,'entity':ent,'property':prp});
					$('#finder .hint').html('editando la entidad: '+rsc+'<br>que es del tipo: '+typ+'<br>en la propiedad: '+prp.attr('property')+'<br>que se rellena con: '+rng);
					$('#finder .find').focus();
				}));
			
			}
		}
	}
	return false;
};

function _handleClick(e){
	e.preventDefault();
	e.stopPropagation();
	var trg = $(e.target);
	var edt = jQuery.data(document.body,'editing');
	var cnt = trg.closest('li');
	var val = cnt.children('input').attr('value');
	var scn = cnt.closest('[property]');
	//var scn = edt.closest('[property]');
	var add = scn.find('.add');
	var crd = add.attr('class');

	if(trg.is('textarea') || trg.is('input') || trg.is('#finder') || trg.is('.entity')) return false;

	else if(trg.hasClass('del')) {
		$('#finder li:has(input[value="'+val+'"])').removeAttr('class');
		cnt.remove();
		persist(add);
	}
	/*aquí e cuando selecciona algo de la listica del #finder*/
	else if(trg.is('.reslt li:not([class])')){
		if (crd==='single'){
			trg.parents('li').find('.horlist li:not(.add)').remove();
			$('#finder .selected').removeAttr('class');
		} 
	  //------
	  //add = edit.closest('[property]').find('.add');
	  //------
	  trg.clone().insertBefore(add);
		trg.addClass('selected');
		persist(add);
	}
	else if(trg.hasClass('next')){
		trg.remove();
		$.post('buildLists.php', {_rg:scn.attr('class'),_t:$('#finder .find').attr('value'),_pg:trg.find('._pg').text()}, function(result){
			$('#finder .reslt').append(result);
			valFinder();
		});
	}
	else {
		//$('#cartelito').html(trg.attr('id'));
		$('#finder').add('li textarea').remove();
		jQuery.data(document.body,'editing',trg); //<------------------------- este e el que estoy editando
		var tof = cnt.attr('typeof');
		if (tof==='xsd:string' || tof==='xsd:integer' || tof==='xsd:float' || tof==='xsd:date'){
			scn.append($( '<textarea />' ).attr({value:val}));
			if (val != null) trg.remove();
			$('li textarea').autoResize();
			$('li textarea').focus();
		}	
		else {
			$('#maincont').append($('<div>').load('buildHTML.php #finder',function(){
				//if (tof==='frbr:Endeavour') $('#finder').prepend($('<span>').load('buildHTML.php #manual'));
				trg.css({'background':'red'});
				$('#finder').attr({'range':tof});
				$('#finder .find').focus();
			}));
		}
	}	
};

function handleChange(e){
	
	var trg = $(e.target);
	e.stopPropagation();
	//var rng=trg.closest('[property]').attr('class');
	if ($('#finder').length>0){
		var rng= $('#finder').data('range');
		var prp= $('#finder').data('property').attr('property');
		var txt=trg.attr('value');
	}
	if (rng === 'xsd:string' && prp != 'skos:prefLabel') {
	  //esto e lo que hace si es una cadena pero no es una etiqueta preferida/alternativa o sea dejar escribir
	}
	else {
	  $('#finder').prepend(loading);
	  $.post('buildLists.php', {_rg:rng,_t:txt}, function(result){
		$('#finder .loading').add('#finder .reslt li').remove();
		$('#finder .reslt').append(result);
		valFinder();
	  });
	}
	
};

function handleMouseover(e){
	e.stopPropagation();
	var trg = $(e.target);
	if (trg.is('.edit')){
		trg.children('.tbox-edit').show();
	}
	else if(trg.is('[property]')){
		$('span.icon-remove').remove();
		trg.find('.horlist li:not(.add)').append('<span class="del icon-remove" title="cómeme león, cómeme"></span>');
	}
	else if(trg.is('.del')){
		trg.closest('li').animate({backgroundColor:'#F7D6D6',color:'#BC4949'}, 1000);
	}
};

function handleKeydown(e) {
	var trg = $(e.target);
	e.stopPropagation();
	if (e.keyCode == 13) {
		if (trg.is('textarea')){
			e.preventDefault();
			trg.mouseleave();
		}
		else if (trg.is('#composer input')){
			e.preventDefault();
			titlepit()
			var val = $('#composer textarea').attr('value');
			titletia(val)
			//$('#composer textarea').mouseleave();
		}
  }
};

$('input[type=text]').data('timeout', null);
function handleKeyup(e) {
	var trg = $(e.target);
	e.stopPropagation();
	clearTimeout(trg.data('timeout'));
  trg.data('timeout', setTimeout(function(){
  	trg.change();
  }, 300));
};

function handleMouseleave(e){
	/*hay que revisar como funciona este evento*/
	e.preventDefault();
	e.stopPropagation();
	var trg = $(e.target);
	
	if (trg.is('#finder')) trg.fadeOut('slow');
	
	else if(trg.is('textarea')){
		var txt = trg.attr('value');
		var prp = trg.closest('[property]');
  	var ent = trg.closest('.entity'); //entity
  	//var add = trg.closest('li').find('.add');
  	var add = prp.find('.add');
  	$((txt)?'<li typeof="'+add.attr('typeof')+'">'+txt+'<input type="hidden" name="'+prp.attr('property')+'[]" value="'+txt+'" /></li>':'').insertBefore(add);
		if (txt != '' && prp.attr('property') == 'skos:prefLabel') {
			prp.html('<span class="value"><h3><input type="hidden" name="'+prp.attr('property')+'[]" value="'+txt+'" />'+txt+'</h3></span>');
		}
		if (txt == '' && prp.attr('property') == 'skos:prefLabel') {
			prp.html('<span class="value"><h3><input type="hidden" name="'+prp.attr('property')+'[]" value="" /><p class="badge"><span class="icon-pencil"></span> identifica esta entidad <em>recomendado</em></p></h3></span>');
		}
		if (ent.is('.new')){
			$.post('buildLists.php', {_rg:'frbr:Manifestation',_t:'"'+txt+'"'}, function(result){
				alert(result)
			});

		}
		else{
			update(prp);
			//$('#composer').fadeOut('slow');
			$('#texter').fadeOut('slow');
		}
	}
	
	else if (trg.is('.edit')){
		trg.children('.tbox-edit').hide();
	}	

}

function handleMouseout(e){
	/*hay que revisar como funciona este evento*/
	//e.stopPropagation();
	var trg = $(e.target);
	if(trg.is('.del')){
		trg.closest('li').animate({backgroundColor:'transparent',color:'#333'});
	}
}

dragme.live("mouseover", function() { 
	if (!$(this).data("init")) { 
		$(this).data("init", true); 
		$(this).draggable({
			helper:	'clone',
			revert:	true,
			snap:		true,
			cursor:	'move'
		});
	}
});

/*
edtabl.live("mouseover", function() { 
	if (!$(this).data("init")) { 
		$(this).data("init", true); 
		$(this).draggable({
			distance: 30,
			helper: 'clone',
			revert: false,
			//snap: true,
			//cursor: 'move',
			stop: function(event, ui) {
				$(this).remove();
			}
		});
	}
});
*/

dropme.live("mouseover", function() { 
	if (!$(this).data("init")) { 
		$(this).data("init", true); 
		$(this).droppable({
			//hoverClass: 'ui-state-highlight',
			//activeClass: 'ui-state-highlight',
			drop: handleDropEvent,
			out: handleOutEvent
		});
	}
});

function handleDropEvent( event, ui ) {
  var labl = ui.draggable.find('h3').text();
  var list = $(this).find('ul');
  var prop = $(this).find('input').attr('name');
  var inpt = $( '<input />' ).attr({type:"hidden",name:prop,value:ui.draggable.attr('id')});
  $( '<li class="edtabl"></li>' ).text( labl ).append(inpt).appendTo( list );
  ui.draggable.draggable( 'option', 'revert', false );
}

function handleOutEvent( event, ui ) {
	//var draggable = ui.draggable;
	//var _ids = draggable.attr('value');
	//var remvme = $(this).find('input[value="' + _ids + '"]');
	//var remvme = $(this).find('input').filter('[value="' + _ids + '"]');
	//$(this).$('input[value="' + _ids + '"]').remove();
	//var remvme = $('input[value="' + _ids + '"]');
	//remvme.remove();
}

function valFinder(){
	/*probar en ie8 porque devuelve null en vez de -1*/
	var selected = $('#finder').closest('[property]').find('.horlist li input[type=hidden]').map(function(){return $(this).attr('value');}).get();
	$('#finder input[type=hidden] ').attr({'name':$('#finder').closest('[property]').attr('property')});
	$('#finder li:has(input[type=hidden])').each(function(){
		if($.inArray($(this).children('input').attr('value'),selected) > -1) {
			$(this).addClass('selected');
		}
	});
}

function update(prp){
	var rsc = prp.closest('.entity').attr('resource');
	//var val = (prp.children('.horlist').find('input:hidden')).map(function(){return $(this).attr('value');}).get();
	var val = (prp.children('.value').children('*').children('input:hidden')).map(function(){return $(this).attr('value');}).get();
	var prp = prp.attr('property');
	arg = {_a:'/u',_ids:rsc};
	//arg[prp] = (val[0]!=undefined)?escape(val):null;
	arg[prp] = (val[0]!=undefined)?val:null;
	console.log(arg);
	$.post('w_srvs/gateway.php',arg,function(result){
		//alert(result); <====================================================
	});
}

//desarma el título y mete cada pedacito en el lugar que le tocaría del composer
function titletia(val){
	var pie = val.split(' : ');
	var lng = pie.length;
	var tit = $('#composer #title');
	var sub = $('#composer #subtitle');
	var stt = $('#composer li:not(#subtitle):not(#title):not(#ok)');
	stt.remove();
	tit.find('input').attr('value','');
	sub.find('input').attr('value','');
	$('#composer #title input').attr('value',pie[0]);
	for (var i=1;i<lng;i++){
		$('<li class="subtitle'+i+'"><label>subtítulo</label><input type="text" value="'+pie[i]+'" /><button class="del" title="¡cómeme león, cómeme!">quitar</button></li>').insertBefore(sub);
	}
	return false; 
}

//arma la cadena del título con los pedacitos del composer y la pega en el texter
function titlepit(){
	//var ttl = $('#composer li:not([id=subtitle]) input[type=text]').map(function(){return $(this).attr('value');}).get();
	var ttl = $('#composer input[type=text]').map(function(){return $(this).attr('value');}).get();
	if (ttl[ttl.length-1]==='')ttl.pop();
	$('#texter').attr('value',ttl.join(' : '));
	return false;
}

