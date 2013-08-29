<a name = "beginning"></a>
<div class="row">
	<div id="maincont">
    <div class="maincont_top">
	  <h2>Estad&iacute;sticas en Pangea</h2>
  <!--  <p class="text">Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Etiam sit amet elit vitae arcu interdum ullamcorper. Nullam ultrices, nisi quis scelerisque convallis, augue neque tempor enim, et mattis justo nibh eu elit. Quisque ultrices gravida pede. Mauris accumsan vulputate tellus. Phasellus condimentum. </p>
    <h2>Totales Lorem Ipsum</h2> -->
    <div id="total" class="yui3-skin-sam" style="width:675px;"></div>
    </br>
    <h3>Totales por Biblioteca</h3>
    <div id="total_graph" class="yui3-skin-sam" style="width:675px;height:700px;"></div> 
    <div id="Consolidated_ForCollection" class="yui3-skin-sam"> </div>
    </br>
    <div id="Cons_ForColl" class="yui3-skin-sam" style="width:675px;height:1700px;">
     <h3>Totales por Colecci&oacute;n</h3>
    </div> 
    </br>
    </br>
    <div id="Consolidated_ForType" class="yui3-skin-sam"></div>
    </br>    
    <div id="Cons_ForType" class="yui3-skin-sam" style="width:675px;height:700px;">
    <h3>Totales por Tipo de documento</h3>
    </div> 
    </br>
    </br>
    <div id="Consolidated_ForEntryConcept" class="yui3-skin-sam"></div>    
    </br>
    <div id="Cons_ForentryConcept" class="yui3-skin-sam" style="width:675px;height:2500px;">
     <h3>Totales por V&iacute;a de entrada</h3>
    </div> 
    <div id="chart-zone">
        <label id="bibliotecas_anchor">Bibliotecas</label>    
    </div>
    <div class="anchorBeginning">	
	    <button type="image" value="Up" id="arrow-up"><span class="icon-arrow-up"></span></button>
	    <a href="#beginning">Subir</a>
	</div>
	</div><!--maincont top-->
	</div><!--maincont-->
 
 
 
   <div id="sidebar" style="display:inline; float: right;" class="yui3-skin-sam yui3-g">
    
      <div id = "sidebarTop" class="sidebar_top">
            <h3>Per&iacute;odo Mostrado</h3>
            <hr class="hr_sidebar"/>
            
            <div id="links" style="padding-left:20px;">
                     <input type="text" id="fecha1" name="fecha1" value="1960-01-01">
                     <div class="calendar_div">
                           <!-- <input id="hideCalendar" name="hideCalendar" type="image" src="img/cal.png">-->
                       <button type="image" value="Calendar" id="hideCalendar"><span class="icon-calendar"></span></button>
                     </div>
                     <p class="since"> / </p>    
                     <input type="text" id="fecha2" name="fecha2"> 
                     <div class="calendar_div">
                           <!-- <input id="hideCalendar2" name="hideCalendar2" type="image" src="img/cal.png"></div> -->
                       <button type="image" value="Calendar" id="hideCalendar2"><span class="icon-calendar"></span></button>                                                      
                            <!-- <input type="button" value="Filtrar" id="btn_filtrar">-->
                     </div>

                   <button type="submit" value="Filtrar" id="btn_filtrar"><span class="icon-filter"></span></button>

            </div> <!-- links -->
              
            <div id="rightcolumn" class="yui3-u" >
              <!-- Container for the calendar -->
                <div id="mycalendar"></div>
                <div id="mycalendar2"></div>
            </div>


            <div id="stats_type" style="float: right;clear: right;" class="yui3-u">
                   <h3>Estad&iacute;sticas por tipo</h3>
                   <hr class="hr_sidebar"/>
                   <input type="checkbox" id="checkbox_collection" name="checkbox_collection" checked="checked" onclick="FilterStatsType(this.id)"> <p class="tipos">Cantidad de documentos por Colecci&oacute;n</p>
                   <input type="checkbox" id="checkbox_type" name="checkbox_type" checked="checked" onclick="FilterStatsType(this.id)"><p class="tipos"> Cantidad de documentos por tipo</p>
                   <input type="checkbox" id="checkbox_entryconcept" name="checkbox_entryconcept" checked="checked" onclick="FilterStatsType(this.id)"><p class="tipos"> Cantidad de documentos adquiridos por v&iacute;a y precios</p>
            </div> 
      </div><!--fin de sidebar_top -->
    
    <hr class=""/>
     <div id="sideScrollStatistics">
                <div class="scrollbar"><div class="track"><div class="thumb1"><div class="end"></div></div></div></div>
	            <div class="viewport"><div class="overview">
                    <div class="library">  
                       <!--   <h3>Instituciones OHCH</h3>
                        <hr class="hr_sidebar"/>
                       <h5 class="special"> Desplazarse hasta:</h5> -->                  
                        <div id="institutions" class="yui3-u" >                     
                            <a href="#bibliotecas_anchor" onclick="return true;"><h3 id="lib-label">Bibliotecas</h3> </a>  
                            <ul id="institutions_list">
			                </ul>                                                      
                        </div>

                    </div> 

                </div> <!-- overview -->
                </div> <!-- viewport -->
     </div> <!-- sideScrollStatistics -->
   
   </div><!--fin del sidebar -->
 
 </div><!-- row -->

				

    
	


