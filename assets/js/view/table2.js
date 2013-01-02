if (! window.t41) {
	window.t41 = [];
}
if (! window.t41.view) {
	window.t41.view = [];
}

if (! window['t41']['view']['table2']) {
	
	window.t41.view.table2 = function(params) {

		if (! params) params = {};
		
		this.id = params.id || 'lignes';
		
		this.mode = params.mode || 'rows'; // or 'columns'

		this.headers = params.headers || {};
		
		// collection of data
		this.collection = params.collection || {};

		// event callbacks
		this.callbacks = params.callbacks || {};
    
		this.events = params.events || {};

		this.domObject;

		
		this.init = function() {
			
			var table = document.createElement('TABLE');
			table.setAttribute('id', this.id);
			table.setAttribute('class', 't41 component table');
			
			switch (this.mode) {
			
				case 'rows':
					// build grid header
					var thead = document.createElement('tr');
					for (i in this.headers) {
						var th = this.cellHeader(this.headers[i]);
						jQuery(thead).append(th);
					}
					
					if (this.events && this.events['row']) {

						for (i in this.events['row']) {
		        	
							var cell = document.createElement('th');
							cell.innerHtml = 'actions';
							jQuery(thead).append(cell);
						}
					}
		        
					jQuery(table).append(thead);
					break;
				
				case 'columns':
					// do nothing yet
					break;
			}
			
			this.domObject = table;
		};
		
		
		this.render = function(parent) {
			
			if (! this.domObject) this.init();
			this.populate();
			
			jQuery(typeof parent == 'string' ? '#'+parent : parent).append(this.domObject);
		};
		
		
		this.populate = function(collection) {
			
			var collection = collection || this.collection;
			
			switch (this.mode) {
			
				case 'rows':
					jQuery(this.domObject).find("tr:gt(0)").remove();
					for (var i in collection) {
		        	
						var tr = this.row(collection[i].props, collection[i].uuid, i);
						jQuery(this.domObject).append(tr);
					} 
					break;
					
				case 'columns':
					jQuery(this.domObject).find("tr").remove();

					for (var i in this.headers) {
					
						var tr = document.createElement('TR');
						var cell = this.cellHeader(this.headers[i]);
						
						// get column event
						var event = this.headers[i].event || null;
					
						// get column formatting arguments
						var args  = this.headers[i].args || null;
						
						jQuery(tr).append(cell);

							for (var j in collection) {
								// sub property
								if (i.substring(0,1) == '_') {
								
									var parts = i.substring(1,i.length).split('.');
									var subdata = collection[j].props;

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

									var cell = this.cell(collection[j].props[i], event, args);
								}
								
								jQuery(tr).append(cell);
							}
						jQuery(this.domObject).append(tr);
					}
					break;
			}
		};
		

		this.refresh = function(collection) {
    	
			if (collection) {
				this.collection = collection;
				jQuery(this.table).find("tr:gt(0)").remove();

			}
    	
			// build grid data rows from array rows
			for (var i in this.collection) {
        	
				var tr = this.row(this.collection[i].props, this.collection[i].uuid, i);
			
				// add <tr> to <table>
				jQuery(this.table).append(tr);
			}    	
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
					var button = t41.view.button(this.events['row'][i].label,{size:'small'});

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
		 * @param array data
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
				input.setAttribute('value', data.value);
				input.setAttribute('size', data.type == 'Integer' ? 5 : 20);
				input.setAttribute('data-type', data.type);
				input.setAttribute('class', 't41 element');

				// bind event if declared
				if (this.callbacks && this.callbacks.cell) {
					jQuery(input).bind(event, eval(this.callbacks.cell));
				}
				jQuery(cell).append(input);

			} else {

				//data.value = t41.view.format(data.value, data.type, args);
        
				cell.innerHTML = t41.view.format(data.value, data.type, args);
			}
			cell = this.cellClass(cell, data.type);
        
			return cell;
		};


		/**
		 * Returns a header
		 */
		this.cellHeader = function(data) {
			
			if (! data.params) data.params = {};
			var cell = document.createElement('th');
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
    
		
		this.rowSelector = function(rowId) {
			
			var sel = jQuery('#lignes tr[data-id="' + rowId + '"]');
			return (sel.length > 0) ? jQuery(sel[0]) : false;
		};
		
		
		this.cellSelector = function(rowId,cellId) {
			
			// @TODO add controls
			var sel = this.rowSelector(rowId).find('td[data-id="' + cellId + '"]');
			return (sel.length > 0) ? jQuery(sel[0]) : false;
		};
	};
}
