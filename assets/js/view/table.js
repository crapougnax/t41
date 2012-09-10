if (! window['t41'] || ! window['t41']['view']) {
	
	alert("Missing t41 or t41 view namespace declaration!");
}

if (! window['t41']['view']['table']) {
	
	window.t41.view.table = function(o) {

		this.id = o.id || 'lignes';
		
		this.build = function(o) {

			this.headers = o.display;
			

			// collection of data
			this.collection = o.collection;

			// event callbacks
			this.callbacks = o.callbacks;
        
			this.events = o.events;
        
			this.table = document.createElement('table');
			this.table.setAttribute('id', this.id);
			this.table.setAttribute('class', 't41 list');

			//var thead = document.createElement('thead');

			// build grid header
			var tr = document.createElement('tr');
			for (i in this.headers) {
        	
				var header = this.headers[i];
				var th = this.cellHeader(header);

				jQuery(tr).append(th);
			}
        
			if (this.events && this.events['row']) {

				for (i in this.events['row']) {
        	
					var cell = document.createElement('th');
					cell.innerHtml = 'actions';
					jQuery(tr).append(cell);
				}
			}
        
			//jQuery(this.table).append(thead);
        	jQuery(this.table).append(tr);

			var tbody = document.createElement('tbody');
			jQuery(this.table).append(tbody);

			this.refresh();
		};

		
		/**
		 * Function called to add rows from the collection parameter
		 */
		this.addRow = function(collection, o) {
			var o = o || {};
			var repeatheaders = o.repeatheaders || false;
			var id = this.id;
			
			if (repeatheaders) {
				jQuery('#'+id+' tr:nth-child(1)').clone().appendTo(jQuery('#'+id));
			}
			// build grid data rows from array rows
			for (var i in collection) {

				if (collection[i].data) {
					var tr = this.rowFromObject(collection[i], i);
				} else {
					var tr = this.row(collection[i].props, collection[i].uuid, i);
				}
				
				// add <tr> to <table>
				jQuery(this.table).append(tr);
				
			}
		};

		
		this.refresh = function(collection) {

			if (collection) {
				this.collection = collection;
				jQuery('#'+this.id + ' tbody').find("tr:gt(0)").remove();
			}

			this.addRow(this.collection);
		};

		this.scroll = function(o) {

		};
		
		
		this.rowFromObject = function(object, i) {

			//console.log(object);
			var tr = document.createElement('tr');
			if (object.uuid) tr.setAttribute('data-uuid', object.uuid);
			if (i) tr.setAttribute('data-id', i);
			
			for (this.k in this.headers) {

				// get column event
				var event = this.headers[this.k].event || null;
			
				// get column formatting arguments
				var args  = this.headers[this.k].args || null;

				// sub property
				if (this.k.substring(0,1) == '_') {
				
					var parts = this.k.substring(1,this.k.length).split('.');
					var subdata = object;

					// recursion
					for (var p in parts) {

						var propname = parts[p];
				
						if (subdata.get(propname) && typeof subdata.get(propname) == 'object') {
							var subdata = subdata.get(propname);
						}
					}

					if (subdata.get(propname)) {
						var cell = this.cell(subdata.getProperty(propname), event, args);
					}
				
				} else {
					var cell = this.cell(object.getProperty(this.k), event, args);
				}
				
				if (cell && this.headers[this.k].type == t41.view.currency) {
					cell.setAttribute('style', 'text-align:right');
				}
				
				// add <td> to <tr>
				jQuery(tr).append(cell);
			}

			/* add a button for each registered row event */
			if (this.events && this.events['row']) {

				for (i in this.events['row']) {
        	
					var cell = document.createElement('td');
					var button = t41.view.button(this.events['row'][i].label, this.events['row'][i].options || {});

					// bind event if declared
					jQuery(button).bind('click', eval(this.events['row'][i].callback));

					jQuery(cell).append(button);
					jQuery(tr).append(cell);
				}
			}
			
			return tr;
		};
		
		
		this.row = function(data, uuid, i) {
    	
			var tr = document.createElement('tr');
			if (uuid) tr.setAttribute('data-uuid', uuid);
			if (i) tr.setAttribute('data-id', i);

			//		tr.setAttribute('data-member', i);

			for (this.k in this.headers) {

				// get column event
				var event = this.headers[this.k].event || null;
			
				// get column formatting arguments
				var args  = this.headers[this.k].args || null;

				// sub property
				if (this.k.substring(0,1) == '_') {
				
					var parts = this.k.substring(1,this.k.length).split('.');
					var subdata = data;

					// recursion
					for (var p in parts) {

						var propname = parts[p];
				
						if (subdata[propname] && subdata[propname].value && subdata[propname].value.props) {
						
							var subdata = subdata[propname].value.props;
						}
					}

					if (subdata[propname]) {
				
						var cell = this.cell(subdata[propname], event, args);
					}
				
				} else {

					var cell = this.cell(data[this.k], event, args);
				}
				
				if (cell && this.headers[this.k].type == t41.view.currency) {
					
					cell.setAttribute('style', 'text-align:right');
				}
				
				// add <td> to <tr>
				jQuery(tr).append(cell);
			}
		
			/* add a button for each registered row event */
			if (this.events && this.events['row']) {

				for (i in this.events['row']) {
        	
					var cell = document.createElement('td');
					var button = t41.view.button(this.events['row'][i].label, this.events['row'][i].options || {});

					// bind event if declared
					jQuery(button).bind('click', eval(this.events['row'][i].callback));

					jQuery(cell).append(button);
					jQuery(tr).append(cell);
				}
			}
        
			return tr;
		};
    
    
		this.refreshRow = function(object, uuid, id) {
    	
			var self = this;
			jQuery(this.table).find("tr:[data-id="+id+"]")
							  .each(function(index,e) { jQuery(e).after(self.row(object.props, uuid, id)).remove(); }
    						   );
		};
    
    
		this.removeRow = function(id) {
    	
			jQuery(this.table).find("tr:[data-id="+id+"]")
			.each(function(index,e) { jQuery(e).fadeOut('slow', function(e) { jQuery(e).remove(); }); }
			);
		};
    
    
		/**
		 * return a <td> element with the value formated
		 * @param array|t41.object.data.property data
		 * @return DOM Element <td>
		 */
		this.cell = function(data, event, args) {
    	
			if (typeof data == 'undefined') { console.log('empty data'); return; }
			var cell = document.createElement('td');
			
			cell.setAttribute('data-id', this.k);
        
			if (event) {
        	
				var input = document.createElement('input');
				input.setAttribute('name', this.k);
				input.setAttribute('type', 'text');
				input.setAttribute('class', 't41 element');

				
				if (typeof data == 'object') {
					input.setAttribute('value', data.get ? data.get(true) : data.value);
					input.setAttribute('size', data.type == 'Integer' ? 5 : 20);
				} else {
					if (data && data.value) input.setAttribute('value', data.value);
					if (data && data.type) input.setAttribute('size', data.type == 'Integer' ? 5 : 20);
					if (data && data.type) input.setAttribute('data-type', data.type);
				}

				// bind event if declared
				if (this.callbacks && this.callbacks.cell) {
					jQuery(input).bind(event, eval(this.callbacks.cell));
				}
				jQuery(cell).append(input);

			} else {

				if (data) {
					
					if (data.get) {
						// data is a t41.object.data.property object
						cell.innerHTML = data.get(true);
					} else {
						cell.innerHTML = typeof data.value != 'undefined' ? t41.view.format(data.value, data.type, args) : null;
					}
				}
			}
			if (data && data.type) cell = this.cellClass(cell, data.type);
        
			return cell;
		};


		
		/**
		 * Returns a header
		 */
		this.cellHeader = function(data) {
			
			if (! data.params) data.params = {};
			var cell = document.createElement('th');
			if (data.type == t41.view.currency) {
				cell.setAttribute('style','text-align:right');
			}
			cell.innerHTML = data.params.label || data.value || '';
			return cell;
		};

		
		this.cellClass = function(cell, type) {
        
			switch (type) {
				case 'String':
					jQuery(cell).addClass('cellstring');
					break;
        
				case 'Enum':
					jQuery(cell).addClass('cellenum');
					break;
                
				case 'Date':
					jQuery(cell).addClass('celldate');
					break;
                
				case 'Currency':
					jQuery(cell).addClass('cellcurrency');
					break;
					
				default:
					break;
			}
			return cell;
		};
    
		
		this.render = function(parent) {
    	
			// display data grid
			var grid = document.createElement('div');

			jQuery(grid).append(this.table);
			jQuery(typeof parent == 'string' ? '#'+parent : parent).append(this.table);
		};

		
		this.asHTML = function() {
			
			return this.table.outerHTML;
		};
		
		
		this.rowSelector = function(rowId) {
			
			var sel = jQuery('#lignes tr[data-id="' + rowId + '"]');
			return (sel.length > 0) ? jQuery(sel[0]) : false;
		};
		
		
		this.cellSelector = function(rowId,cellId) {
			
			// @TODO add controls
			var sel = this.rowSelector(rowId).find('td[data-id="' + cellId + '"]');
			return (sel.length > 0) ? jQuery(sel[0]) : false;
		};
    
		// constructor call
		this.build(o);
	};
}
