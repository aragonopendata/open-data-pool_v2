//create our editable grid
var editableGrid = new EditableGrid("DemoGridFull", {
	enableSort: true, // true is the default, set it to false if you don't want sorting to be enabled
	editmode: "absolute", // change this to "fixed" to test out editorzone, and to "static" to get the old-school mode
	editorzoneid: "edition", // will be used only if editmode is set to "fixed"
	pageSize: 10,
	maxBars: 10
});

//helper function to display a message
function displayMessage(text, style) { 
	_$("message").innerHTML = "<p class='" + (style || "ok") + "'>" + text + "</p>"; 
} 

//helper function to get path of a demo image
function image(relativePath) {
	return "pool-v2/estilos-api/images/" + relativePath;
}

//helper function to get duplicates in nombrefiled
function duplicadoNombre(nombre) {
	var duplicados=0;
    var contador = editableGrid.getRowCount();
	for (var rowIndex = 0; rowIndex < contador; rowIndex++) {
	   var valor = editableGrid.getValueAt(rowIndex, 0);
	   if (valor.trim()===nombre.trim() && editableGrid.lastSelectedRowIndex!=rowIndex){
		duplicados++;
	   }
	}
	return !(duplicados==1);
}


//this will be used to render our table headers
function InfoHeaderRenderer(message) { 
	this.message = message; 
	this.infoImage = new Image();
	this.infoImage.src = image("information.png");
};

InfoHeaderRenderer.prototype = new CellRenderer();
InfoHeaderRenderer.prototype.render = function(cell, value) 
{
	if (value) {
		// here we don't use cell.innerHTML = "..." in order not to break the sorting header that has been created for us (cf. option enableSort: true)
		var link = document.createElement("a");
		link.href = "javascript:alert('" + this.message + "');";
		link.appendChild(this.infoImage);
		cell.appendChild(document.createTextNode("\u00a0\u00a0"));
		cell.appendChild(link);
	}
};

String.prototype.isEmpty = function() {
    return (this.length === 0 || !this.trim());
};

//this function will initialize our editable grid
EditableGrid.prototype.initializeGrid = function() 
{
	with (this) {

		// use a special header renderer to show an info icon for some columns
		setHeaderRenderer("nombre", new InfoHeaderRenderer("Nombre de la vista"));
		setHeaderRenderer("periodicidad", new InfoHeaderRenderer("Periodicidad de actualización para esta vista"));		
		setHeaderRenderer("fecha", new InfoHeaderRenderer("Última fecha de actualización realizada para esta vista"));
		setHeaderRenderer("hora", new InfoHeaderRenderer("Última hora de actualización realizada para esta vista"));
		setHeaderRenderer("estado", new InfoHeaderRenderer("Última traza realizada para esta vista"));
		setHeaderRenderer("logs", new InfoHeaderRenderer("Enlace al archivo de texto log para esta carga"));
		setHeaderRenderer("archivos", new InfoHeaderRenderer("Enlaces de archivos resultantes del proceso del la última actualización para esta vista"));
	
		getColumn("nombre").cellEditor.minWidth = 200;
		getColumn("periodicidad").cellEditor.minWidth = 105;		
		getColumn("fecha").cellEditor.minWidth = 105;
		getColumn("hora").cellEditor.minWidth = 105;
		getColumn("estado").cellEditor.minWidth = 300;
		getColumn("logs").cellEditor.minWidth = 200;
		getColumn("archivos").cellEditor.Width = 500;
		getColumn("action").cellEditor.minWidth = 75;

		setCellRenderer("nombre", new CellRenderer({render: function(cell, value) { 			
			var nombreCarga = editableGrid.getValueAt(cell.rowIndex,0);
			cell.innerHTML = nombreCarga.replace(/[+]/g, " ");
			}
		})); 

		// use a flag image to render the selected country
		setCellRenderer("archivos", new CellRenderer({render: function(cell, value) { 
			var estado = editableGrid.getValueAt(cell.rowIndex,4);
			var nombreCarga = editableGrid.getValueAt(cell.rowIndex,0);
			nombreCarga = nombreCarga.replace(/[+]/g, " ");

			if (estado.indexOf("Error")>0) {			   
			   cell.innerHTML = value ? "<a target='_blank' href='publicacion-v2/Error/" + value.toLowerCase() + " " + nombreCarga + "/datos.csv' alt='datos.csv'><img border='0' alt='csv' title=\"Ver Archivo de carga CSV\" src='" + image("excel.png")+ "'></a>&nbsp" +
									    "<a target='_blank' href='publicacion-v2/Error/" + value.toLowerCase() + " " + nombreCarga + "/datos.n3' alt='datos.n3'><img border='0' alt='n3'  title=\"Ver Archivo de carga N3\" src='" + image("file.png") + "'/></a>" : ""; 
			} else if (estado.indexOf("Sin Procesar")>0) {				
			   cell.innerHTML = value ? "<a target='_blank' href='publicacion-v2/NoProcesados/" + value.toLowerCase() + " " + nombreCarga + "/datos.csv' alt='datos.csv'><img border='0' alt='csv' title=\"Ver Archivo de carga CSV\"  src='" + image("excel.png")+ "'></a>&nbsp" +
			                            "<a target='_blank' href='publicacion-v2/NoProcesados/" + value.toLowerCase() + " " + nombreCarga + "/datos.n3' alt='datos.n3'><img border='0' alt='n3'  title=\"Ver Archivo de carga N3\" src='" + image("file.png") + "'/></a>" : ""; 
			} else {
			   cell.innerHTML = value ? "<a target='_blank' href='publicacion-v2/Procesados/" + value.toLowerCase() + " " + nombreCarga + "/datos.csv' alt='datos.csv'><img border='0' alt='csv' title=\"Ver Archivo de carga CSV\"  src='" + image("excel.png")+ "'></a>&nbsp" +
				                        "<a target='_blank' href='publicacion-v2/Procesados/" + value.toLowerCase() + " " + nombreCarga + "/datos.n3' alt='datos.n3'><img border='0' alt='n3' title=\"Ver Archivo de carga N3\"  src='" + image("file.png") + "'/></a>" : "";
			}
			cell.style.textAlign = "center"; }
		})); 

		// use a flag image to render the selected country
		setCellRenderer("logs", new CellRenderer({
				render: function(cell, value) { 
					var estado = editableGrid.getValueAt(cell.rowIndex,4);
					var carpeta = editableGrid.getValueAt(cell.rowIndex,6);
					var nombreCarga = editableGrid.getValueAt(cell.rowIndex,0);
					nombreCarga = nombreCarga.replace(/[+]/g, " ");
					if (estado.indexOf("Error")>0) {						
						cell.innerHTML = value ? "<a target='_blank' href='publicacion-v2/Error/" + carpeta + " " + nombreCarga + "/" + value.toLowerCase() + "' alt='Archivo log'><img border='0' alt='log' title=\"Ver Archivo de carga log\" src='" + image("file.png") + "'/></a>" : ""; 
					 } else if (estado.indexOf("Sin Procesar")>0) {
						cell.innerHTML = value ? "<a target='_blank' href='publicacion-v2/NoProcesados/" + carpeta + " " + nombreCarga + "/" + value.toLowerCase() + "' alt='Archivo log'><img border='0' alt='log' title=\"Ver Archivo de carga log\" src='" + image("file.png") + "'/></a>" : ""; 
					 } else {						
						cell.innerHTML = value ? "<a target='_blank' href='publicacion-v2/Procesados/" + carpeta + " " + nombreCarga + "/" + value.toLowerCase() + "' alt='Archivo log'><img border='0' alt='log' title=\"Ver Archivo de carga log\" src='" + image("file.png") + "'/></a>" : ""; 		
					 }
					 cell.style.textAlign = "center"; 
				}
		}));
		
	

		// render for the action column
		setCellRenderer("action", new CellRenderer({render: function(cell, value) {
			// this action will remove the row, so first find the ID of the row containing this cell 
			var rowId = editableGrid.getRowId(cell.rowIndex);

			cell.innerHTML = "<a onclick=\"if (confirm('Esta seguro que desea elininar esta vista ? ')) { editableGrid.remove(" + cell.rowIndex + "); } \" style=\"cursor:pointer\">" +
			"<img src=\"" + image("delete.png") + "\" border=\"0\" alt=\"delete\" title=\"Borrar fila\"/></a>";

			cell.innerHTML+= "&nbsp;<a onclick=\"editableGrid.duplicate(" + cell.rowIndex + ");\" style=\"cursor:pointer\">" +
			"<img src=\"" + image("duplicate.png") + "\" border=\"0\" alt=\"duplicate\" title=\"Duplicar fila\"/></a>";

		}}));


		// add a cell validator to check that the age is in [15, 100[
		addCellValidator("hora", new CellValidator({ 
			isValid: function(value) { return value == "" || (/^([0-1]?[0-9]|2[0-4]):([0-5][0-9])(:[0-5][0-9])?$/.test(value)); }
		}));

		// add a cell validator to check that the age is in [15, 100[
		addCellValidator("nombre", new CellValidator({ 
				isValid: function(value) { return value == "" || (duplicadoNombre(value)); }
		}));

		// register the function that will handle model changes
		modelChanged = function(rowIndex, columnIndex, oldValue, newValue, row) { 
			if ((newValue.length === 0 || !newValue.trim())){
				newValue="vacio";
			}
			var nombreValor = this.getColumnName(columnIndex);
			var valorid = this.getRowId(rowIndex);
			var valorValor = btoa(encodeURIComponent(newValue));
			var ruta = "/pool-administracion-cargas-v2/cargavistas/actualiza/" + valorid + "/" + nombreValor + "/" + valorValor;
			$.post(ruta)
			.done(function() {
				displayMessage("El valor para '" + nombreValor + "' en la fila " + valorid + " ha cambiado de '" + oldValue + "' a '" + newValue + "'");
			})
			.fail(function() {
			  alert("Error de actualización para el valor '" + nombreValor + "' en la fila " + valorid+ ". No se ha cambiado de '" + oldValue + "' a '" + newValue + "'");
			});
	
			//if (this.getColumnName(columnIndex) == "continent") this.setValueAt(rowIndex, this.getColumnIndex("country"), ""); // if we changed the continent, reset the country
		};

		// update paginator whenever the table is rendered (after a sort, filter, page change, etc.)
		tableRendered = function() { this.updatePaginator(); };


		rowSelected = function(oldRowIndex, newRowIndex) {
			if (oldRowIndex < 0) displayMessage("Fila seleccionada '" + this.getRowId(newRowIndex) + "'");
			else displayMessage("La fila seleccionada ha cambiado de '" + this.getRowId(oldRowIndex) + "' a '" + this.getRowId(newRowIndex) + "'");
		};

		rowRemoved = function(oldRowIndex, rowId) {
			var ruta = "/pool-administracion-cargas-v2/cargavistas/borra/" + rowId;
			$.ajax(ruta, {
				data: { 
						// you can pass some parameters to the controller here
				},
				success: function(data) {
					displayMessage("Fila Eliminada:'" + oldRowIndex + "' - ID = " + rowId);
				},
				error: function() {
					displayMessage("Error No Eliminada '" + oldRowIndex + "' - ID = " + rowId);
				}
			});
		
		};

 

		// render the grid (parameters will be ignored if we have attached to an existing HTML table)
		renderGrid("tablecontent", "testgrid", "tableid");

		// set active (stored) filter if any
		_$('filter').value = currentFilter ? currentFilter : '';

		// filter when something is typed into filter
		_$('filter').onkeyup = function() { editableGrid.filter(_$('filter').value); };

		// bind page size selector
		$("#pagesize").val(pageSize).change(function() { editableGrid.setPageSize($("#pagesize").val()); });
		$("#barcount").val(maxBars).change(function() { editableGrid.maxBars = $("#barcount").val();  });
	}
};

EditableGrid.prototype.onloadXML = function(url) 
{
	// register the function that will be called when the XML has been fully loaded
	this.tableLoaded = function() { 
		displayMessage("Grid loaded from XML: " + this.getRowCount() + " row(s)"); 
		this.initializeGrid();
	};

	// load XML URL
	this.loadXML(url);
};

EditableGrid.prototype.onloadJSON = function(url) 
{
	// register the function that will be called when the XML has been fully loaded
	this.tableLoaded = function() { 
		displayMessage("Registros cargados desde JSON: " + this.getRowCount() + " Fila(s)"); 
		this.initializeGrid();
	};

	// load JSON URL
	this.loadJSON(url);
};

EditableGrid.prototype.onloadHTML = function(tableId) 
{
	// metadata are built in Javascript: we give for each column a name and a type
	this.load({ metadata: [
						   { name: "nombre", datatype: "string", editable: true },
						   { name: "periodicidad", datatype: "string", editable: true, values: {"diaria":"Diaria","semanal":"Semanal", "mensual":"Mensual", "anual":"Anual", "demanda":"A demanda" } },	                       
						   { name: "fecha", datatype: "date", editable: true },	 
						   { name: "hora", datatype: "time", editable: true },	    
						   { name: "estado", datatype: "string", editable: true },
						   { name: "logs", datatype: "html", editable: true },
						   { name: "archivos", datatype: "html", editable: true },
	                       { name: "action", datatype: "html", editable: false }
	                       ]});

	// we attach our grid to an existing table
	this.attachToHTMLTable(_$(tableId));
	displayMessage("Grid attached to HTML table: " + this.getRowCount() + " row(s)"); 

	this.initializeGrid();
};

EditableGrid.prototype.duplicate = function(rowIndex) 
{
	// copy values from given row
	var values = this.getRowValues(rowIndex);
	values['nombre'] = values['nombre'] + ' (copia)';

	// get id for new row (max id + 1)
	var newRowId = 0;
	for (var r = 0; r < this.getRowCount(); r++) newRowId = Math.max(newRowId, parseInt(this.getRowId(r)) + 1);
	var datos = btoa(encodeURIComponent(values['nombre']+ "|" + 
				     values['periodicidad']+ "|" +
	                 values['fecha']+ "|" +
	                 values['hora']+ "|" +
	                 values['estado']+ "|" +
	                 values['logs']+ "|" +
					 values['archivos']));
	var ruta = "/pool-administracion-cargas-v2/cargavistas/inserta/" + datos;
	$.post(ruta)
	.done(function() {
		displayMessage("Fila duplicada");
	})
	.fail(function() {
	     //alert("Error de Insercion");
	});
	this.insertAfter(rowIndex, newRowId, values); 		 
	// add new row
	location.reload(true);
};


//function to render the paginator control
EditableGrid.prototype.updatePaginator = function()
{

	var paginator = $("#paginator").empty();
	var nbPages = this.getPageCount();

	// get interval
	var interval = this.getSlidingPageInterval(20);
	if (interval == null) return;

	// get pages in interval (with links except for the current page)
	var pages = this.getPagesInInterval(interval, function(pageIndex, isCurrent) {
		if (isCurrent) return "" + (pageIndex + 1);
		return $("<a>").css("cursor", "pointer").html(pageIndex + 1).click(function(event) { editableGrid.setPageIndex(parseInt($(this).html()) - 1); });
	});

	// "first" link
	var link = $("<a>").html("<img src='" + image("gofirst.png") + "'/>&nbsp;");
	if (!this.canGoBack()) link.css({ opacity : 0.4, filter: "alpha(opacity=40)" });
	else link.css("cursor", "pointer").click(function(event) { editableGrid.firstPage(); });
	paginator.append(link);

	// "prev" link
	link = $("<a>").html("<img src='" + image("prev.png") + "'/>&nbsp;");
	if (!this.canGoBack()) link.css({ opacity : 0.4, filter: "alpha(opacity=40)" });
	else link.css("cursor", "pointer").click(function(event) { editableGrid.prevPage(); });
	paginator.append(link);

	// pages
	for (p = 0; p < pages.length; p++) paginator.append(pages[p]).append(" | ");

	// "next" link
	link = $("<a>").html("<img src='" + image("next.png") + "'/>&nbsp;");
	if (!this.canGoForward()) link.css({ opacity : 0.4, filter: "alpha(opacity=40)" });
	else link.css("cursor", "pointer").click(function(event) { editableGrid.nextPage(); });
	paginator.append(link);

	// "last" link
	link = $("<a>").html("<img src='" + image("golast.png") + "'/>&nbsp;");
	if (!this.canGoForward()) link.css({ opacity : 0.4, filter: "alpha(opacity=40)" });
	else link.css("cursor", "pointer").click(function(event) { editableGrid.lastPage(); });
	paginator.append(link);
};
