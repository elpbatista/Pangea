var dataJson;
var IdsForCollection;
var IdsForType;
var IdsEntryConcept;
var posShowedList;
var listLibAnchors;

function showGraph(tableContainer, chartContainer,libraryName,criteriaText,criteria,institution,libId){	
    YUI().use('charts-legend','node', function (Y) 
     {
	    var libNode = Y.one("#" + tableContainer);
	    Y.one('#'+criteria+libId).remove();
		libNode.append('<div id="'+criteria+libId+'" class="anchorBeginning"><ul><li  onclick="hideGraph(\''+tableContainer+'\',\''+criteria+'\',\''+chartContainer+'\',\''+libId+'\')"><button type="image" value="Close" id="arrow-up"><span class="icon-eye-close"></span></button><a id="view">&nbsp;Ocultar gráfica</a></li></ul></div>');
		libNode.append('<div id="' + chartContainer + '" class="yui3-skin-sam" style="width:675;height:700px;"><label>' + libraryName + '</label><br/><label>' + criteriaText + '</label></div></br>');
			    									
		var DataForChart = ChartDataGenerator(dataJson, criteria, institution, libId);
									
		if(criteria != "forEntryConcept"){
			ChartGenerator(Y, "#" + chartContainer, DataForChart, "bar", "Coleccion",0,300,-45);  
			IdsForCollection.push(chartContainer);
		}
		else {
			ChartGenerator(Y, "#" + chartContainer, DataForChart, "bar", "Via",0,300,-45);
			IdsEntryConcept.push(chartContainer);
		}		
				
	 });	
  }
  
   function showGraphAgain(container,libId,criteria,tableContainer){	
    YUI().use('charts-legend','node', function (Y) 
     {
	    Y.one('#'+criteria+libId).remove();
		Y.one("#" + tableContainer).append('<div id="'+criteria+libId+'" class="anchorBeginning"><ul><li  onclick="hideGraph(\''+tableContainer+'\',\''+criteria+'\',\''+container+'\',\''+libId+'\')"><button type="image" value="Close" id="arrow-up"><span class="icon-eye-close"></span></button><a id="view">&nbsp;Ocultar gráfica</a></li></ul></div>');
		Y.one('#'+container).show();
	 });		
  }
  
   function hideGraph(tableContainer,criteria, container,libId){	
    YUI().use('charts-legend','node', function (Y) 
     {
	    Y.one('#'+container).hide();
		Y.one('#'+criteria+libId).remove();
		Y.one("#" + tableContainer).append('<div id="'+criteria+libId+'" class="anchorBeginning"><ul><li  onclick="showGraphAgain(\''+container+'\',\''+libId+'\',\''+criteria+'\',\''+tableContainer+'\')"><button type="image" value="Open" id="buttonOpen"><span class="icon-eye-open"></span></button><a id="view">&nbsp;Ver gráfica</a></li></ul></div>');
	 });		
  }
   
function showTotalGraph(){	
	YUI().use('charts-legend','node', function (Y) 
		     {
			    Y.one('#total_graph').show();
			 	var DataForTotalsGraph = TotalsTableDataGenerator(dataJson, "forType");
				ChartGenerator(Y, "#total_graph", DataForTotalsGraph, "bar", "Biblioteca",0,300,-45);
				 Y.one('#showTotalGraph').remove();
				 Y.one("#total").append('<div id="showTotalGraph" class="anchorBeginning"><ul><li  onclick="hideTotalGraph()"><button type="image" value="Close" id="arrow-up"><span class="icon-eye-close"></span></button><a id="view">&nbsp;Ocultar gráfica</a></li></ul></div>');
			 });		
	  }	

function showTotalGraphAgain(){	
    YUI().use('charts-legend','node', function (Y) 
     {
	    Y.one('#total_graph').show();
	 	Y.one('#showTotalGraph').remove();
		Y.one("#total").append('<div id="showTotalGraph" class="anchorBeginning"><ul><li  onclick="hideTotalGraph()"><button type="image" value="Close" id="arrow-up"><span class="icon-eye-close"></span></button><a id="view">&nbsp;Ocultar gráfica</a></li></ul></div>');
	 });		
  }	  
  
   function hideTotalGraph(){	
    YUI().use('charts-legend','node', function (Y) 
     {
	    Y.one('#total_graph').hide();
		Y.one('#showTotalGraph').remove();
		Y.one("#total").append('<div id="showTotalGraph" class="anchorBeginning"><ul><li  onclick="showTotalGraphAgain()"><button type="image" value="Open" id="buttonOpen"><span class="icon-eye-open"></span></button><a id="view">&nbsp;Ver gráfica</a></li></ul></div>');
	 });		
  }
   
  function hideConsGraph(container,prevContainer,id){	
       YUI().use('charts-legend','node', function (Y) 
	     {
		    Y.one('#'+container).hide();
			Y.one('#'+id).remove();
			Y.one('#'+prevContainer).append('<div id="'+id+'" class="anchorBeginning"><ul><li onclick="showConsGraphAgain(\''+container+'\',\''+prevContainer+'\',\''+id+'\')"><button type="image" value="Open" id="buttonOpen"><span class="icon-eye-open"></span></button><a id="view">&nbsp;Ver gráfica</a></li></ul></div>');
		 });		
	  }
	  
	  function showConsGraphAgain(container,prevContainer,id){	
       YUI().use('charts-legend','node', function (Y) 
	     {
		    Y.one('#'+container).show();
		 	Y.one('#'+id).remove();
			Y.one("#"+prevContainer).append('<div id="'+id+'" class="anchorBeginning"><ul><li  onclick="hideConsGraph(\''+container+'\',\''+prevContainer+'\',\''+id+'\')"><button type="image" value="Close" id="arrow-up"><span class="icon-eye-close"></span></button><a id="view">&nbsp;Ocultar gráfica</a></li></ul></div>');
		 });		
	  }
	  
	 function showConsolidatedGraph(criteria,container,nRotation,prevContainer,number){	
       YUI().use('charts-legend','node', function (Y) 
	     {
		    Y.one('#'+container).show();
		    if(criteria == 'forEntryConcept'){
			 var DataConsolidatedCollection =ConsEntryConceptTableDataGenerator(dataJson, criteria);
			 ChartGenerator(Y, "#"+container, DataConsolidatedCollection, "bar", "Via",0,300,nRotation);
		    }
			else{
		 	 var DataConsolidatedCollection = ConsolidatedTableDataGenerator(dataJson, criteria);
			 ChartGenerator(Y, "#"+container, DataConsolidatedCollection, "bar", "Coleccion",0,300,nRotation);
			}
			var id ='showConsolidatedGraph'+number;
			Y.one('#'+id).remove();
			Y.one("#"+prevContainer).append('<div id="'+id+'" class="anchorBeginning"><ul><li  onclick="hideConsGraph(\''+container+'\',\''+prevContainer+'\',\''+id+'\')"><button type="image" value="Close" id="arrow-up"><span class="icon-eye-close"></span></button><a id="view">&nbsp;Ocultar gráfica</a></li></ul></div>');
		 });		
	  }

	  
function showAnchors(dataSource, institution) {
    YUI().use('node', function (Y) {
        var count = listLibAnchors.length - posShowedList;
        if (count > 5) count2 = 5;
        else count2 = count;
        for (var i = 0; i < count2; i++) {
            libID = listLibAnchors[posShowedList];
            libraryName = GetLibraryName(dataSource, "forType", libID, institution);
            obj = Y.Node.create('<li><a href="#table' + libID + '"onclick="return true;" id="ins_' + libID + '">' + libraryName + '</a></li></br>');
            Y.one('#institutions_list').insert(obj, Y.one('#more_link'));
            posShowedList++;
        }
        if (count <= 5) Y.one('#more_link').remove();
    });
    $('#sideScrollStatistics').tinyscrollbar_update('relative');
}
function GetLibraryName(dataSource, pType, libraryId, institution) {
    return dataSource[pType].values[institution][libraryId].statistic.name
}
function TotalsTableDataGenerator(dataSource, pType) {
    var dataResult = new Array();
    for (var libId in dataSource[pType].values.Bibliotecas) {
        var statistic = dataSource[pType].values.Bibliotecas[libId];
        var long_name = dataSource[pType].values.Bibliotecas[libId].statistic.name;
        var lib_name;
        switch (long_name) {
            case "Biblioteca Ada Elba P\u00e9rez. Casa de la Poes\u00eda":
                lib_name = "Biblioteca de Poes\u00eda";
                break;
            case "Biblioteca Alfonso Reyes. Casa Benito Ju\u00e1rez":
                lib_name = "Biblioteca de M\u00e9xico";
                break;
            case "Biblioteca de Arqueolog\u00eda. Gabinete de Arqueolog\u00eda":
                lib_name = "Biblioteca de Arqueolog\u00eda";
                break;
            case "Biblioteca del Centro de Tradiciones chinas. Casona de Artes y Tradiciones Chinas":
                lib_name = "Biblioteca Tradiciones Chinas";
                break;
            case "Biblioteca del Museo de la Cer\u00e1mica":
                lib_name = "Biblioteca de la Cer\u00e1mica";
                break;
            case "Biblioteca Gabriela Mistral. Centro Hispano Cubano de Cultura":
                lib_name = "Biblioteca Centro Hispano ";
                break;
            case "Biblioteca Hist\u00f3rica Cubana y Americana Francisco Gonz\u00e1lez del Valle.":
                lib_name = "Biblioteca Hist\u00f3rica";
                break;
            case "Biblioteca Napole\u00f3nica. Museo Napole\u00f3nico":
                lib_name = "Biblioteca Napole\u00f3nica";
                break;
            case "Biblioteca Rabindranat Tagore. Casa de Asia":
                lib_name = "Biblioteca de Asia";
                break;
            case "Biblioteca Ra\u00fal Le\u00f3n Torras. Museo Numism\u00e1tico":
                lib_name = "Biblioteca Numism\u00e1tica";
                break;
            case "Biblioteca Sim\u00f3n Rodr\u00edguez. Casa Sim\u00f3n Bol\u00edvar":
                lib_name = "Biblioteca Sim\u00f3n Rodr\u00edguez";
                break;
            case "Biblioteca Vitrina de Valonia.":
                lib_name = "Biblioteca de Valonia";
                break;
            default:
                lib_name = long_name;
                break
        }
        dataResult[dataResult.length] = {
            Biblioteca: lib_name,
            Items: dataSource[pType].values.Bibliotecas[libId].total,
            MN: dataSource[pType].values.Bibliotecas[libId].Mn,
            Cuc: dataSource[pType].values.Bibliotecas[libId].Cuc,
            USD: dataSource[pType].values.Bibliotecas[libId].Usd,
            Sin_moneda: dataSource[pType].values.Bibliotecas[libId].sin_moneda
        }
    }
    return dataResult
}
function ConsolidatedTableDataGenerator(dataSource, criteria) {
    var dataResult = new Array();
    var statistic = dataSource[criteria].consolidated.Bibliotecas;
    var i = 0;
    for (var colectionName in statistic) {
        dataResult[i] = {
            Coleccion: colectionName,
            Items: statistic[colectionName].items,
            MN: statistic[colectionName].Mn,
            Cuc: statistic[colectionName].Cuc,
            USD: statistic[colectionName].Usd,
            Sin_moneda: statistic[colectionName].sin_moneda
        };
        i++
    }
    return dataResult
}
function ConsEntryConceptTableDataGenerator(dataSource) {
    var dataResult = new Array();
    var statistic = dataSource["forEntryConcept"].consolidated.Bibliotecas;
    var i = 0;
    for (var via in statistic) {
        dataResult[i] = {
            Via: statistic[via].way,
            Items: statistic[via].count,
            MN: statistic[via].Mn,
            Cuc: statistic[via].Cuc,
            USD: statistic[via].Usd,
            Sin_moneda: statistic[via].sin_moneda
        };
        i++
    }
    return dataResult
}
function TableDataGenerator(dataSource, pType, institution, libraryId) {
    var dataResult2 = new Array();
    if (pType != "forEntryConcept") {
        var statistic2 = dataSource[pType].values[institution][libraryId].statistic;
        var i = 0;
        for (var colectionName in statistic2) {
            if (colectionName != "name") {
                dataResult2[i] = {
                    Coleccion: colectionName,
                    Items: statistic2[colectionName].items,
                    MN: statistic2[colectionName].Mn,
                    Cuc: statistic2[colectionName].Cuc,
                    USD: statistic2[colectionName].Usd,
                    Sin_moneda: statistic2[colectionName].sin_moneda
                };
                i++
            }
        }
        dataResult2[i] = {
            Coleccion: "Total",
            Items: dataSource[pType].values[institution][libraryId].total,
            MN: dataSource[pType].values[institution][libraryId].Mn,
            Cuc: dataSource[pType].values[institution][libraryId].Cuc,
            USD: dataSource[pType].values[institution][libraryId].Usd,
            Sin_moneda: dataSource[pType].values[institution][libraryId].sin_moneda
        }
    } else {
        dataResult2 = dataSource[pType].values[institution][libraryId].statistic.ways;
        dataResult2[dataResult2.length] = {
            "way": "Total",
            "count": dataSource[pType].values[institution][libraryId].total,
            "Mn": dataSource[pType].values[institution][libraryId].Mn,
            "Cuc": dataSource[pType].values[institution][libraryId].Cuc,
            "Usd": dataSource[pType].values[institution][libraryId].Usd,
            "Sin_moneda": dataSource[pType].values[institution][libraryId].sin_moneda
        }
    }
    return dataResult2
}
function ChartDataGenerator(dataSource, pType, institution, libraryId) {
    var dataResult = new Array();
    if (pType != "forEntryConcept") {
        var statistic = dataSource[pType].values[institution][libraryId].statistic;
        var i = 0;
        for (var colectionName in statistic) {
            if (colectionName != "name") {
                dataResult[i] = {
                    Coleccion: colectionName,
                    Items: statistic[colectionName].items,
                    MN: statistic[colectionName].Mn,
                    Cuc: statistic[colectionName].Cuc,
                    USD: statistic[colectionName].Usd,
                    Sin_moneda: statistic[colectionName].sin_moneda
                };
                i++
            }
        }
    } else {
        statistic = dataSource[pType].values[institution][libraryId].statistic.ways;
        var i = 0;
        for (var via in statistic) {
            dataResult[i] = {
                Via: statistic[via].way,
                Items: statistic[via].count,
                MN: statistic[via].Mn,
                Cuc: statistic[via].Cuc,
                USD: statistic[via].Usd,
                Sin_moneda: statistic[via].sin_moneda
            };
            i++
        }
    }
    return dataResult
}
function ChartGenerator(Y, renderId, dataSource, typeChart, pCategoryKey, pRotation, pHeight,qRotation) {
    var myChart = new Y.Chart({
        type: typeChart,
        legend: {
            position: "right",
            width: 300,
            height: pHeight,
            styles: {
                hAlign: "center",
                hSpacing: 4
            }
        },
        axes: {
            category: {
                keys: [pCategoryKey],
                type: "category",
                styles: {
                    label: {
                        rotation: pRotation
                    }
                }
            }
        },
        categoryKey: pCategoryKey,
        dataProvider: dataSource,
        horizontalGridlines: true,
        verticalGridlines: true,
        render: renderId
    });
    myChart.set("styles", {
        series: [{
            fill: {
                color: "#fb8072"
            }
        }, {
            fill: {
                color: "#fdb462"
            }
        }, {
            fill: {
                color: "#b3de69"
            }
        }, {
            fill: {
                color: "#8dd3c7"
            }
        }, {
            fill: {
                color: "#BC80BD"
            }
        }]
    });
    myChart.get("legend")._drawLegend();
    var leftAxis = myChart.getAxisByKey("values");
	leftAxis.set("styles", {label:{rotation:qRotation}});
    return myChart
}
function DataTableGenerator(Y, renderId, dataSource, colDef, pSummary, pCaption) {
    var table = new Y.DataTable({
        columns: colDef,
        data: dataSource,
        summary: pSummary,
        caption: pCaption
    });
    table.render(renderId);
    return table
}
function GetJsonData(yui_object, date1, date2) {
    var xmlhttp;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest()
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP")
    }
    var url = "http://dev.pangea.ohc.cu/w_srvs/gateway.php?_a=/st";
    if (date1) url += "&_fd=" + date1;
    if (date2) url += "&_td=" + date2;
    xmlhttp.open("GET", url, false);
    xmlhttp.send();
    return yui_object.JSON.parse(xmlhttp.responseText)
}
function showNodes(Ids, Y) {
    for (i = 0; i < Ids.length; i++) {
        Y.one('#' + Ids[i]).show()
    }
}
function hideNodes(Ids, Y) {
    for (i = 0; i < Ids.length; i++) {
        Y.one('#' + Ids[i]).hide()
    }
}
function FilterStatsType(checkboxId) {
    YUI().use('node', function (Y) {
        var show = Y.one('#' + checkboxId).get("checked");
        if (show) {
            if (checkboxId == "checkbox_entryconcept") showNodes(IdsEntryConcept, Y);
            if (checkboxId == "checkbox_collection") showNodes(IdsForCollection, Y);
            if (checkboxId == "checkbox_type") showNodes(IdsForType, Y)
        } else {
            if (checkboxId == "checkbox_entryconcept") hideNodes(IdsEntryConcept, Y);
            if (checkboxId == "checkbox_collection") hideNodes(IdsForCollection, Y);
            if (checkboxId == "checkbox_type") hideNodes(IdsForType, Y)
        }
    })
}

function RefreshStatistics(date1, date2) {
    (function () {
        YUI().use('charts-legend', 'node', 'datatable', 'json-parse', 'calendar', 'datatype-date', function (Y) {
		             Y.one('#total_graph').hide();
					 Y.one("#Cons_ForColl").hide();
					 Y.one("#Cons_ForType").hide();
					 Y.one("#Cons_ForentryConcept").hide();
            if (date1 || date2) {
                Y.all('#total div').remove(true);
                Y.all('#total_graph div').remove(true);
                Y.all('#Consolidated_ForCollection div').remove(true);
                Y.all('#Cons_ForColl div').remove(true);
                Y.all('#Consolidated_ForType div').remove(true);
                Y.all('#Cons_ForType div').remove(true);
                Y.all('#Consolidated_ForEntryConcept div').remove(true);
                Y.all('#Cons_ForentryConcept div').remove(true);
                Y.all('#chart-zone div').remove(true);
                Y.all('#institutions_list li br').remove(true);
                Y.one('#more_link').remove();
            }
            dataJson = GetJsonData(Y, date1, date2);
            var chartZone = Y.one("#chart-zone");
            var institutionsZone = Y.one("#institutions_list");
            var DataForTotalsTable = TotalsTableDataGenerator(dataJson, "forType");
            var colDef = [{
                key: "Biblioteca",
                label: "Biblioteca",
                sortable: true
            }, {
                key: "Items",
                label: "Cantidad",
                sortable: true
            }, {
                key: "MN",
                label: "MN",
                sortable: true
                
            }, {
                key: "Cuc",
                label: "CUC",
                sortable: true
            }, {
                key: "USD",
                label: "USD",
                sortable: true
            }, {
                key: "Sin_moneda",
                label: "Sin moneda",
                sortable: true
            }];
            DataTableGenerator(Y, "#total", DataForTotalsTable, colDef, "Sum", "Totales por Biblioteca");
            Y.one("#total").append('<div id="showTotalGraph" class="anchorBeginning"><ul><li  onclick="showTotalGraph()"><button type="image" value="Open" id="buttonOpen"><span class="icon-eye-open"></span></button><a id="view">&nbsp;Ver gráfica</a></li></ul></div>');
            var DataConsolidatedCollection = ConsolidatedTableDataGenerator(dataJson, "forCollection");
            var colDefCons1 = [{
                key: "Coleccion",
                label: "Colecci&oacute;n",
                sortable: true
            }, {
                key: "Items",
                label: "Cantidad",
                sortable: true
            }, {
                key: "MN",
                label: "MN",
                sortable: true
            }, {
                key: "Cuc",
                label: "CUC",
                sortable: true
            }, {
                key: "USD",
                label: "USD",
                sortable: true
            }, {
                key: "Sin_moneda",
                label: "Sin moneda",
                sortable: true
            }];
            DataTableGenerator(Y, "#Consolidated_ForCollection", DataConsolidatedCollection, colDefCons1, "Sum", "Totales por Colecci&oacute;n");
            Y.one("#Consolidated_ForCollection").append('<div id="showConsolidatedGraph1" class="anchorBeginning"><ul><li  onclick="showConsolidatedGraph(\'forCollection\',\'Cons_ForColl\',-45,\'Consolidated_ForCollection\',1)"><button type="image" value="Open" id="buttonOpen"><span class="icon-eye-open"></span></button><a id="view">&nbsp;Ver gráfica</a></li></ul></div>');
            var DataConsolidatedCollection2 = ConsolidatedTableDataGenerator(dataJson, "forType");
            var colDefCons2 = [{
                key: "Coleccion",
                label: "Tipo de Documento",
                sortable: true
            }, {
                key: "Items",
                label: "Cantidad",
                sortable: true
            }, {
                key: "MN",
                label: "MN",
                sortable: true
            }, {
                key: "Cuc",
                label: "CUC",
                sortable: true
            }, {
                key: "USD",
                label: "USD",
                sortable: true
            }, {
                key: "Sin_moneda",
                label: "Sin moneda",
                sortable: true
            }];
            DataTableGenerator(Y, "#Consolidated_ForType", DataConsolidatedCollection2, colDefCons2, "Sum", "Totales por Tipo de Documento");
            Y.one("#Consolidated_ForType").append('<div id="showConsolidatedGraph2" class="anchorBeginning"><ul><li  onclick="showConsolidatedGraph(\'forType\',\'Cons_ForType\',-45,\'Consolidated_ForType\',2)"><button type="image" value="Open" id="buttonOpen"><span class="icon-eye-open"></span></button><a id="view">&nbsp;Ver gráfica</a></li></ul></div>	');
            var DataConsolidatedCollection3 = ConsEntryConceptTableDataGenerator(dataJson, "forEntryConcept");
            var colDefCons3 = [{
                key: "Via",
                label: "V&iacute;a de entrada",
                sortable: true
            }, {
                key: "Items",
                label: "Cantidad",
                sortable: true
            }, {
                key: "MN",
                label: "MN",
                sortable: true
            }, {
                key: "Cuc",
                label: "CUC",
                sortable: true
            }, {
                key: "USD",
                label: "USD",
                sortable: true
            }, {
                key: "Sin_moneda",
                label: "Sin moneda",
                sortable: true
            }];
            DataTableGenerator(Y, "#Consolidated_ForEntryConcept", DataConsolidatedCollection3, colDefCons3, "Sum", "Totales por V&iacute;a de entrada");
            Y.one("#Consolidated_ForEntryConcept").append('<div id="showConsolidatedGraph3" class="anchorBeginning"><ul><li  onclick="showConsolidatedGraph(\'forEntryConcept\',\'Cons_ForentryConcept\',-45,\'Consolidated_ForEntryConcept\',3)"><button type="image" value="Open" id="buttonOpen"><span class="icon-eye-open"></span></button><a id="view">&nbsp;Ver gráfica</a></li></ul></div>	');
            if (!IdsForCollection) IdsForCollection = new Array();
            if (!IdsForType) IdsForType = new Array();
            if (!IdsEntryConcept) IdsEntryConcept = new Array();
            listLibAnchors = new Array();
            IdsForCollection.push("Consolidated_ForCollection");
            //IdsForCollection.push("Cons_ForColl");
            IdsForType.push("Consolidated_ForType");
            //IdsForType.push("Cons_ForType");
            IdsEntryConcept.push("Consolidated_ForEntryConcept");
            //IdsEntryConcept.push("Cons_ForentryConcept");
            var chartIndex = 1;
            posShowedList = 0;
            for (var criteria in dataJson) {
                for (institution in dataJson[criteria].values) {
                    var totalInstitutions = 0;
                    for (var libId in dataJson[criteria].values[institution]) {
                        var chartContainer;
                        var tableContainer;
                        var libraryName = GetLibraryName(dataJson, criteria, libId, institution);
                        if (criteria == "forCollection") {
                            chartContainer = "chart" + libId;
                            tableContainer = "table" + libId;
                            //IdsForCollection.push(chartContainer);
                            IdsForCollection.push(tableContainer);
                            var institutionNode = Y.one("#ins_" + libId);
                            if (institutionNode == null) {
                                if (totalInstitutions < 5) institutionsZone.append('<li><a href="#table' + libId + '"onclick="return true;" id="ins_' + libId + '">' + libraryName + '</a></li></br>');
                                if (totalInstitutions == 5) institutionsZone.append('<li class="meta" id="more_link" onclick="showAnchors(dataJson, institution)"><span class="icon-plus"></span>&nbsp;más...</li>');
                                if (totalInstitutions > 5) listLibAnchors.push(libId);
                                totalInstitutions = totalInstitutions + 1;
                            }
                        } else {
                            chartContainer = "chart" + chartIndex;
                            tableContainer = "table" + chartIndex;
                            if (criteria == "forType") {
                                //IdsForType.push(chartContainer);
                                IdsForType.push(tableContainer)
                            } else {
                                if (criteria == "forEntryConcept") {
                                    //IdsEntryConcept.push(chartContainer);
                                    IdsEntryConcept.push(tableContainer)
                                }
                            }
                        }
                        var libraryNode = Y.one("#id" + libId);
                        if (libraryNode == null) {
                            chartZone.append('<div id="id' + libId + '"></div>');
                            libraryNode = Y.one("#id" + libId)
                        }
                        var criteriaText = criteria;
                        if (criteria == "forCollection") criteriaText = "Por Colecci&oacute;n";
                        if (criteria == "forType") criteriaText = "Por Tipo de Documento";
                        if (criteria == "forEntryConcept") criteriaText = "Por v&iacute;a de entrada";
                        libraryNode.append('<div></div><div></div><div id="' + tableContainer + '" class="yui3-skin-sam"></div></br>');
                        //libraryNode.append('<div id="'+criteria+libId+'" class="anchorBeginning"><ul><li  onclick="showGraph(\''+ tableContainer +'\',\''+ chartContainer +'\', \''+ libraryName +'\',\''+ criteriaText +'\',\''+criteria+'\',\''+institution +'\',\''+libId+'\')"><button type="image" value="Open" id="buttonOpen"><span class="icon-eye-open"></span></button><a id="view">&nbsp;Ver gráfica</a></li></ul></div>');
                        
                        
                        var DataForTable = TableDataGenerator(dataJson, criteria, institution, libId);
                        if (criteria != "forEntryConcept") {
                            var tableLabel = "Tipo de Colecci&oacute;n";
                            if (criteria == "forType") tableLabel = "Tipo de Documento";
                            var colDef = [{
                                key: "Coleccion",
                                label: tableLabel,
                                sortable: true
                            }, {
                                key: "Items",
                                label: "Cantidad",
                                sortable: true
                            }, {
                                key: "MN",
                                label: "MN",
                                sortable: true                                
                            }, {
                                key: "Cuc",
                                label: "CUC",
                                sortable: true
                            }, {
                                key: "USD",
                                label: "USD",
                                sortable: true
                            }, {
                                key: "Sin_moneda",
                                label: "Sin moneda",
                                sortable: true
                            }];
                            DataTableGenerator(Y, "#" + tableContainer, DataForTable, colDef, "Sum", libraryName + '<br/>' + criteriaText);
                            Y.one('#'+tableContainer).append('<div id="'+criteria+libId+'" class="anchorBeginning"><ul><li  onclick="showGraph(\''+ tableContainer +'\',\''+ chartContainer +'\', \''+ libraryName +'\',\''+ criteriaText +'\',\''+criteria+'\',\''+institution +'\',\''+libId+'\')"><button type="image" value="Open" id="buttonOpen"><span class="icon-eye-open"></span></button><a id="view">&nbsp;Ver gráfica</a></li></ul></div>');
                        } else {
                            var colDef = [{
                                key: "way",
                                label: "Via",
                                sortable: true
                            }, {
                                key: "count",
                                label: "Cantidad",
                                sortable: true
                            }, {
                                key: "Mn",
                                label: "MN",
                                sortable: true
                            }, {
                                key: "Cuc",
                                label: "CUC",
                                sortable: true
                            }, {
                                key: "USD",
                                label: "USD",
                                sortable: true
                            }, {
                                key: "Sin_moneda",
                                label: "Sin moneda",
                                sortable: true
                            }];
                            DataTableGenerator(Y, "#" + tableContainer, DataForTable, colDef, "Sum", libraryName + '<br/>' + criteriaText);
                            Y.one('#'+tableContainer).append('<div id="'+criteria+libId+'" class="anchorBeginning"><ul><li  onclick="showGraph(\''+ tableContainer +'\',\''+ chartContainer +'\', \''+ libraryName +'\',\''+ criteriaText +'\',\''+criteria+'\',\''+institution +'\',\''+libId+'\')"><button type="image" value="Open" id="buttonOpen"><span class="icon-eye-open"></span></button><a id="view">&nbsp;Ver gráfica</a></li></ul></div>');
                        }
                        chartIndex++
                    };
                    if (institution == "Bibliotecas" && criteria == "forCollection") {
                        var libLabel = Y.one("#lib-label");
                        libLabel.set("innerHTML", "Bibliotecas " + totalInstitutions);
                        $('#sideScrollStatistics').tinyscrollbar_update('relative');
                    }
                }
            }
        })
    })()
}(function () {
    YUI().use('charts-legend', 'node', 'datatable', 'json-parse', 'calendar', 'datatype-date', function (Y) {
        var calendar = new Y.Calendar({
            contentBox: "#mycalendar",
            width: '300px',
            showPrevMonth: true,
            visible: false,
            showNextMonth: true
        }).render();
        var dtdate = Y.DataType.Date;
        var today = new Date();
        var date_output_default = document.getElementById("fecha2");
        date_output_default.value = dtdate.format(today);
        calendar.on("selectionChange", function (ev) {
            var newDate = ev.newSelection[0];
            var date_output = document.getElementById("fecha1");
            date_output.value = dtdate.format(newDate);
            calendar.set('visible', !(calendar.get("visible")))
        });
        Y.one("#hideCalendar").on('click', function (ev) {
            ev.preventDefault();
            calendar.set('visible', !(calendar.get("visible")))
        });
        var calendar2 = new Y.Calendar({
            contentBox: "#mycalendar2",
            width: '300px',
            visible: false,
            showPrevMonth: true,
            showNextMonth: true
        }).render();
        var dtdate = Y.DataType.Date;
        calendar2.on("selectionChange", function (ev) {
            var newDate = ev.newSelection[0];
            var date_output = document.getElementById("fecha2");
            date_output.value = dtdate.format(newDate);
            calendar2.set('visible', !(calendar2.get("visible")))
        });
        Y.one("#hideCalendar2").on('click', function (ev) {
            ev.preventDefault();
            calendar2.set('visible', !(calendar2.get("visible")))
        });
        Y.one("#btn_filtrar").on('click', function (ev) {
            var startDate = Date.parse(document.getElementById("fecha1").value);
            var endDate = Date.parse(document.getElementById("fecha2").value);
            if (startDate >= endDate) {
                alert("La segunda fecha debe ser posterior.");
                return false
            }
            if (isNaN(startDate)) {
                alert("La fecha no tiene un formato correcto: mm/dd/aaaa.");
                return false
            }
            if (isNaN(endDate)) {
                alert("La fecha no tiene un formato correcto: mm/dd/aaaa.");
                return false
            }
            RefreshStatistics(document.getElementById("fecha1").value, document.getElementById("fecha2").value)
        });
        RefreshStatistics("", "")
    })
})();