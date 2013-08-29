/*******************************
Autor: Iván Guardado
Web: http://cokidoo.com
Nota: Siéntete libre de utilizar este código, pero agradeceríamos mantuvieses los créditos originales.
**************************************/

(function($){
	$.fn.extend({
		autoResize: function(options){
			//Si no se envía nada, se crea un objeto vacío para que no de error a continuación
			if(!options){
				options = {};
			}
			//Almacena las opciones pasadas a la función o valores predeterminados en su defecto
			var _options = {
				//Maximo en altura que podrá alcanzar, luego se aplicará scrollbar
				maxHeight: options.maxHeight || null,
				//Altura que tomará al coger el foco
				minHeight: options.minHeight || null,
				//Texto que se mostrará cuando esté vacío y sin foco
				textHold: options.textHold || null,
				//Clase que se añadirá cuando recibe el foco
				activeClass: options.activeClass || null
			};
			this.each(function(){
				//Encapsulamos con jQuery
				var $this = $(this);
				//Establece el texto por defecto si ha sido establecido
				if($this.val() == "" && _options.textHold){
					$this.val(_options.textHold);
				}
				//Guarda la altura inicial
				$this.initHeight = $this.css("height");
				//Establece el atributo CSS overflow según el caso
				if(_options.maxHeight){
					$this.css("overflow", "auto");
				}else{
					$this.css("overflow", "hidden");
				}
				//Para guardar el texto y comparar si hay cambios
				var _value = null;
				//Crea el clon del textarea
				var $clon = $this.clone(true);
				//Establece propiedades del clon y lo añade al DOM
				$clon.css({
					visibility: "hidden",
					position: "absolute",
					top: 0,
					overflow: "hidden",
					width: parseInt($this.width())-10
				});
				$clon.attr("name","");
				$clon.attr("id", "");
				$this.parent().append($clon);
				//Aux
				var clon = $clon[0];
				var me = $this;
				//Eventos del textarea
				$this.bind("keyup" , autoFit)
					.bind("focus", function(){
						if(_options.textHold){
							if(this.value == _options.textHold){
								this.value = "";
							}
						}
						if(_options.minHeight){
							me.css("height", _options.minHeight+"px");
							$clon.css("height", _options.minHeight+"px");
							autoFit(true);
						}
						if(_options.activeClass){
							me.addClass(_options.activeClass);
						}
					})
					.bind("blur", function(){
						if(_options.textHold){
							if(this.value == ""){
								this.value = _options.textHold;
								if(_options.minHeight && me.initHeight){
									$clon.css("height", me.initHeight);
									me.css("height", me.initHeight);
									autoFit();
								}
							}
						}else{
							if(_options.minHeight && me.initHeight){
								$clon.css("height", me.initHeight);
								me.css("height", me.initHeight);
								autoFit();
							}
						}
						if(_options.activeClass){
							me.removeClass(_options.activeClass);
						}
					});
				function autoFit(force){
				    	clon.value = me.val();
				    	//Comprueba si ha cambiado el valor del textarea
				    	if (_value != clon.value || force===true){
					    _value = clon.value;
					    var h = clon.scrollHeight;
					    if(_options.maxHeight && h > _options.maxHeight){
						me.css("height", _options.maxHeight + "px");
					    }else{
					    	me.css("height", parseInt(h) + "px");
					    }
						
				    	}
				}
				autoFit();
			});
		}
	})  
}(jQuery));
