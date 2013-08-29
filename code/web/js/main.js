//carga los textos de la interfaz
$.getJSON(host+'pangea.'+lang+'.json', function(data) {
	text = data;
	//txt = text ['txt'];
	msg = text ['msg'];
	//aquí asigno los mensajes a las variables pa usarlos :)
	defaultText = msg['search_txt']
});

var searchForm = $('#searchForm');
var fixedSearchForm = $('.jumbotron #searchForm');
var fixedHeader = $('header:has(.jumbotron)');
var fixedMain = $('#main.home');

var searchBoxes = $(".text");
var searchBox = $("#searchBox");
var sideScroll = $('#sideScroll');

var clw = parseInt($('#patron').css('min-width'));
var sid = $('.nbrList').attr('id');
var pgs = $('#pageSize').attr('value');
var ttl = $('#results_total').text();
var rld = ($('#maincont .nbrList').children('li.entity')).length;
var pgn = (parseInt(rld/pgs))+1;
var wkn = false;

var load = $('<li class="load">¡aguanta que la tan cargando!</li>');

// efectos en el evento focus (foto) para ambas cajas de busqueda
searchBoxes.focus(function() {
	$(this).addClass("active");
});
searchBoxes.blur(function() {
	$(this).removeClass("active");
});
// Mostramos / ocultamos el texto por defecto si es necesario
searchBox.focus(function() {
	if ($(this).attr("value") == defaultText)
		$(this).attr("value", "");
});
searchBox.blur(function() {
	if ($(this).attr("value") == "")
		$(this).attr("value", defaultText);
});

/*
 * Esta función "jq" escapa los caracteres no permitidos dentro de los ids para
 * los selectores y pone el # se usa así $(jq('some.id'))
 */
function jq(myid) {
	return '#' + myid.replace(/(:|\.)/g, '\\$1');
}

function addPage(){
	wkn = true;
	$('#maincont li:has(span.icon-plus)').remove();
	$('#maincont .nbrList').append(load);
	$.post('buildEntitiesList.php', {_md5:sid,_pg:pgn,_ff:'frbr:Manifestation',_tt:ttl},
	function(result) {
  	$('.load').remove();
  	$('#maincont .nbrList').append(result);
  	rld = ($('#maincont .nbrList').children('li.entity')).length;
  	$('#results_loaded').html(rld);
  	pgn = pgn+1;
  	wkn = false;
	});
}

$(document).ready( function() {
	
	/* Aquí va lo de las imágenes como Pinterest */
	$('.gallery').masonry({
		itemSelector : 'li',
		columnWidth : clw
	}).imagesLoaded(function() {
		$('.gallery').masonry('reload');
	});

	/* aquí me echo el aguanta que la tan peinando */
	$('.loading').remove();



	$("#searchBox").on('keyup',function(e){
		var trg = $(e.target);
		e.stopPropagation();
		clearTimeout(trg.data('timeout'));
	  trg.data('timeout', setTimeout(function(){
	  	//var rng='frbr:Person';
			var txt=trg.attr('value');
			//$('#finder').prepend(loading);
			$.post('buildLists.php', {_t:txt,_lnk:1}, function(result){
				$('#finder').show();
				$('#finder').children('ul').empty();
				$('#finder').children('ul').append(result);
				$('#finder').focus();
				//valFinder();
				//alert(result);
			});
	  	
	  }, 200));
	
	});
	


/*Este es el toggle que enseña y esconde los ejemplares*/
  $('.toggle').live('click', function() {
	  var clkd = $(this);
	  var xnpl = ''+clkd.find('span').text();
		var rslt = $(this).parent('li').children('ul');
		var url = 'buildExemplars.php';
		if (rslt.text()!=''){
			rslt.toggle('slow');
		}
		else {
			$.post(url, {exemplars:xnpl},
	  	function(result) {
	    	rslt.append(result).toggle('slow');
	  	});
		}
		return false;
	});

/*Esto e lo que resalta los elementos de la lista que coinciden con el cluster	
falta terminarlo y no lo soportan los navegadores antiguos
*/
	$('#cluster li[resource]').live({
	  mouseover: function(e) {
	    var rsc = $(this).attr('resource');
	    $('.entity:has([resource='+rsc+'])').addClass('resalta');
	    $(this).css({'background':'yellow'});
	  },
	  mouseout: function(e) {
			$(this).css({'background':'none'});
	    $('.entity').removeClass('resalta');
	  }
	});
	
/*Esto e lo que agrega entidades al carrito
falta terminarlo*/
	var rsl = ($('#results_selected').text())*1;
	$('.addToCart').live('click',function(e){
		e.preventDefault();
		$(this).parents('.entity').css({'background':'cyan'});
		rsl++;
		$('#results_selected').html(rsl);
	});
	
/*Esto e lo que rellena la página a mano*/
	var omp = false;
	$('#maincont li.meta:has(span.icon-plus)').live('click', function(e) {
		e.stopPropagation();
		addPage();
	});

/*Esto es lo que rellena las listas del cluster*/
	$('#cluster li.meta:has(span.icon-plus)').live('click', function(e) {
		e.stopPropagation();
		var trg = $(this);
		var dct = trg.attr('typeof');
		var pgn = trg.children('span._pg').text();
		var cnt = trg.parent('ul');
		wkn = true;
		trg.remove();
		cnt.append(load);
		$.post('buildEntitiesList.php', {_md5:sid,_pg:pgn,_ff:dct,_pt:'ty'},
  	function(result) {
    	load.remove();
    	cnt.append(result);
    	sideScroll.tinyscrollbar_update('relative');
    	wkn = false;
  	});
	});
	
/*aquí mis dos kilitos sobre el alto de las páginas*/
	sideScroll.children('div').height($(window).height()-$('header').height()-$('.navbar').height()-$('#info').height()-$('footer').height()-70);
	$('#sideScrollStatistics').children('div').height($(window).height()-$('header').height()-$('.navbar').height()-$('#sidebarTop').height()-$('footer').height()-120);
	
/*esto pone los sidebar scroleables*/
	sideScroll.tinyscrollbar();
	$('#info').tinyscrollbar();
	$('#sideScrollStatistics').tinyscrollbar();
	
	/*Esto e lo que maneja el scroll de la página*/
	var tempScrollTop, currentScrollTop = 0;
	
	var navbarHeight = $('.navbar').height();
	var headerHeight = fixedHeader.height();
	
	var headerOffset = Math.round(headerHeight-navbarHeight+(navbarHeight/3)-fixedSearchForm.height());

  var offset = fixedSearchForm.offset();
  var searchFormYPos = (offset != null)?offset.top:0;
  var searchFormYOffset = Math.round(searchFormYPos-navbarHeight-(navbarHeight/6));
	var mainOffset = navbarHeight+headerHeight;
	
	$(window).scroll(function(){
    currentScrollTop = $(window).scrollTop();
    var scrollBottom = $(document).height() - $(window).height() - currentScrollTop;

	  //$('#numerito').html(currentScrollTop+'&nbsp;'+headerOffset+'&nbsp;'+mainOffset+'&nbsp;'+clw);
	  
    if (tempScrollTop < currentScrollTop ){
		//scrolling down
	    if(currentScrollTop > searchFormYOffset){
	    	$('.jumbotron p.big').css({'margin-top':fixedSearchForm.height()});
	    	fixedSearchForm.addClass('fixed');
	    }
			if(currentScrollTop > headerOffset){
	    	fixedHeader.addClass('fixed').css({'top':-headerOffset});
				fixedMain.css({'margin-top':mainOffset});
	    }
	    if(scrollBottom<100 && rld<ttl && wkn==false){
				addPage();
	    }
    }
		else if (tempScrollTop > currentScrollTop ){
		//scrolling up
			if(currentScrollTop < searchFormYOffset){
	    	$('.jumbotron p.big').css({'margin-top':0});
	    	fixedSearchForm.removeAttr('class');
	    }
			if(currentScrollTop < headerOffset){
				fixedHeader.removeAttr('class').removeAttr('style');
				fixedMain.css({'margin-top':0});
	    }
		}
		tempScrollTop = currentScrollTop;
		$('#sidebar').css({'top':currentScrollTop+$('header').height()+$('.navbar').height()+22});
    return false;
	});
	
	
 //esto es para el ancla de la página de estadísticas para que el cambio no sea brusco
    $('a[href*=#]').click(function() {

    if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'')
        && location.hostname == this.hostname) {

            var $target = $(this.hash);

            $target = $target.length && $target || $('[name=' + this.hash.slice(1) +']');

            if ($target.length) {

                var targetOffset = $target.offset().top;

                $('html,body').animate({scrollTop: targetOffset}, 500);

                return false;

           }

      }

  });
});
