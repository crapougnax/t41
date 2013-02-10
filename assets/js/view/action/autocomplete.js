if (! window.t41) {
	window.t41 = [];
}
if (! window.t41.view) {
	window.t41.view = [];
}

if (! window.t41.view.action) {
	window.t41.view.action = {registry:{}};
}

if (! window.t41.view.action.autocomplete) {

	/**
	 * Autocompleter object
	 * @param obj
	 * @param element
	 * @returns {window.t41.view.action.autocomplete}
	 */
	window.t41.view.action.autocomplete = function(obj,element) {
	
		/*
		 * Ordered collection of properties to display
		 */
		this.display = obj.data.display;
		
		// real (hidden) field target
		this.target = obj.data.target;

		
		this.minChars = 3;
		
		/*
		 * Number of the last server call
		 * We only accept to handle the one response holding this sequence number
		 */
		this.sequence = 0;
		
		
		this.displayMode = 'table'; // or 'list';
		
		this.previous;
		
		// callbacks
		this.callbacks = obj.callbacks || {};
		
		if (! this.callbacks['display']) this.callbacks['display'] = 't41.view.action.autocomplete.display';
		if (! this.callbacks['select'])  this.callbacks['select']  = 't41.view.action.autocomplete.select';
		if (! this.callbacks['extend'])  this.callbacks['extend']  = 't41.view.action.autocomplete.extendSuggestions';

		
		// server-side action uuid 
		this.uuid = obj.data.uuid;

		// DOM element where autocomplete action occurs
		this.element = jQuery('#' + element);
		this.props = element + '_acprops';
		
		// Server Uri
		this.action = obj.data.action || 'action/autocomplete';
		
		
		this.currentSuggestions = {};
		
		
		this.init = function() {
			
			// add accessories DOM elements
			var span = document.createElement('SPAN');
			span.setAttribute('id', this.target + '_display');
			span.setAttribute('title', 'cliquez pour modifier');
			t41.view.bindLocal(span, 'click', t41.view.action.autocomplete.reset, this);
			this.element.after(span);
			
			// if a value exists, transform it
			if ((val = jQuery('#' + this.target).val()) != '') {
				this.initSavedValue(val);
			}
			
			// bind observer
			t41.view.bindLocal(this.element, 'keyup change', t41.view.action.autocomplete.observer, this);
			this.element.focus();
		};
		
		
		/**
		 * Detect & convert initial existing value
		 */
		this.initSavedValue = function(val) {
			var config = {
							action:this.action,
							callback:jQuery.proxy(this, 'displaySavedValue'),
							data:{
									_id:val,
									uuid:this.uuid
								 }
					 };
			t41.core.call(config);
		};
		
		
		this.displaySavedValue = function(obj) {
			var val = obj.data.collection[obj.data._id];
			this.setValue(obj.data._id, this.prepareDisplay(val));
		};
		
		
		/**
		 * Display suggestions collection
		 */
		this.displaySuggestions = function(data) {
			
			this.currentSuggestions = data.collection;
			
			var size = 0;
			for (var id in this.currentSuggestions) {
				if (this.currentSuggestions.hasOwnProperty(id)) size++;
			}
			if (size == 1) {
				for (var id in this.currentSuggestions) {
					this.defaultSelect(id);
					return true;
				}
			}
			switch (this.displayMode) {
			
				case 'table':
					this._displayAsTable();
					break;
					
				case 'list':
					this._displayAsList();
					break;
			}

			jQuery('#'+this.props).remove();
			jQuery('<div></div>').addClass('t41 component autocompletepropsgrid').attr('id', this.props).appendTo('body');

			var helper = this.getHelper(data);
			var helpertext = helper.txt;
			var css = helper.css;
			var id = this.props;
			var element = this.element.attr('id');

			jQuery('<h4>').appendTo(jQuery('#'+this.props)).html(helpertext).attr('id', id+'_helper').addClass(css);

			if (css=='more') {
				var button = new t41.view.button(t41.lget('ac:extend'), {icon:'more-blue',css:'ac_extend',id:id+'_extend'});
				jQuery('#' + this.props).append(button);
			}

			jQuery(document).bind('click.propsgrid', function(e) {

				if (e.target.id==id+'_extend' || jQuery(e.srcElement).parent().attr('id')==id+'_extend') {
					t41.view.action.autocomplete.extender(t41.view.registry[element]);

				} else if (e.target.id!=id) {
					jQuery('#'+id).remove();
					jQuery(document).unbind('click.propsgrid');
					t41.view.registry[element].offset = 0;
				}
			});

			if (data.total > 0) {
				this.table.render(jQuery('#' + this.props));
				t41.view.bindLocal(jQuery('#' + this.props + '_table'), 'click', eval(this.callbacks['select']), this);
			}

			this.refreshSuggestionsPosition();
		};

		
		this.getHelper = function(data) {

			if (data.total == 0) {
				var helpertext = t41.lget('ac:noresult');
				var css = 'none';
			} else if (data.total == 1) {
				var helpertext = t41.lget('ac:oneresult');
				var css = 'one';
			} else if (data.max > data.total) {
				var helpertext = t41.lget('ac:moreresults', {vars:[data.total, data.max]});
				var css = 'more';
			} else {
				var helpertext = t41.lget('ac:manyresults', {vars:[data.total]});
				var css = 'many';
			}

			return {txt:helpertext, css:css};
		};

		
		this.refreshSuggestionsPosition = function() {
		
			var grid = jQuery('#' + this.props);
			jQuery(document).unbind('resize.propsgrid');
			var offset = this.element.offset();
			var height = this.element.height()+4;
			var top = parseInt(offset.top) + parseInt(height) + 5 + 'px';
			var left = parseInt(offset.left) + 'px';
			grid.css({position:'absolute', top:top, left:left});
			
			// refresh position on document resize (broken ATM)
			jQuery(document).bind('resize.propsgrid', function(){
				t41.view.autocomplete.refreshSuggestionsPosition(input, grid);
			});
		};

		
		this._displayAsTable = function() {
			
			this.table = new t41.view.table({
				'display': this.display,
				'collection': this.currentSuggestions,
				'id': this.props+'_table'
			});
			
		};
		
		
		this._displayAsList = function() {
			this._displayAsTable();
		};
		
		
		this.defaultSelect = function(id) {
			if (! this.currentSuggestions[id]) {
				console.log('Missing member for key ' + id);
			}

			this.resetSuggestions();
			var selected = this.currentSuggestions[id];
			this.setValue(id, this.prepareDisplay(selected));
			if (this.callbacks.postSelect && typeof this.callbacks.postSelect == 'function') {
				this.callbacks.postSelect.call(this,selected,id);
			}
		};
		
		
		this.prepareDisplay = function(obj) {
			var label = '';
			for (var i in this.display) {
				label += obj.props[i] && obj.props[i].value != null ? obj.props[i].value + ' ': '';
			}
			return label || '???';
		};
		
		
		this.setValue = function(key, label) {
			this.element.hide();
			var width = this.element.width()+5;
			var display = jQuery('#' + this.target + '_display').css({
				display: 'inline-block',
				cursor: 'pointer',
				'text-align':'left',
				'margin-left':'5px'
			});
			display.html(label);
			jQuery('#' + this.target).val(key);
			this.triggerChangeEvent();
		};
		
		
		this.resetValue = function() {
			var display = jQuery('#' + this.target + '_display');
			display.hide();
			jQuery('#' + this.target).val('');
			this.resetSuggestions();
			this.offset = 0;
			if (this.callbacks.postReset && typeof this.callbacks.postReset == 'function') {
				this.callbacks.postReset.call(this);
			}
			this.triggerChangeEvent();
		};
		
		
		this.resetSuggestions = function() {
			jQuery('#'+this.props).remove();
			this.element.val('').show().focus();
			this.previous = null;
		};

		
		/**
		 * trigger a 'change' event upon value setting so external observers can catch it
		 */
		this.triggerChangeEvent = function() {
			t41.view.customEvent(this.target, 'change');
		};
	};

	
	window.t41.view.action.autocomplete.extendSuggestions = function(obj) {

		switch (obj.status) {
		
			case t41.core.status.ok:
				var ac = t41.view.registry[this.attr('id')];
				
				// ignore queries coming back home too late
				if (ac.sequence > obj.data._sequence) {
					console.log('ignored server late response #' + obj.data._sequence + ' for query "' + obj.data._query + '".');
					return false;
				}
				
				// abort if there are no new results 
				if (ac.offset >= obj.data.max || obj.data.collection.length==0) {
					return false;
				}

				// store the new set of results
				for (d in obj.data.collection) {
					ac.currentSuggestions[d] = obj.data.collection[d];
				}

				var element = this.attr('id');
				var table = jQuery('#'+t41.view.registry[element].table.id);

				// add some space for scrollbar, only once
				if (!table.hasClass('scroll')) {
					table.width(table.width()+20);
					table.height(table.height()+40);
					jQuery('a#'+element+'_acprops_extend.icon').css({
						'margin-right': 30
					});
				}
				table.addClass('scroll');

				// update or set offset value
				if (ac.offset) {
					ac.offset += obj.data.total;
				} else {
					ac.offset = 10;
				}

				// push collection into the table
				t41.view.registry[element].table.addRow(
					obj.data.collection,
					{'repeatheaders': true}
				);

				// update helper text
				var data = obj.data;
				data.total = ac.offset;
				var helper = ac.getHelper(data);
				jQuery('#'+ac.props+'_helper').html(helper.txt).removeClass('none one many more').addClass(helper.css);

				// remove the 'extend results' link?
				if (helper.css!='more') {
					jQuery('#'+ac.props+'_extend').remove();
				}

				// scroll down to show latests results
				if (true) {
					t41.view.scroll(jQuery('#'+ac.props + ' tbody'), {'more': 80});
				}

				break;
			
			default:
				console.log(obj);
				break;
		}

	};


	window.t41.view.action.autocomplete.extender = function(obj) {

		var search = jQuery(obj.element.selector).val();
		var ac = obj;

		if (!ac.offset) {
			ac.offset = 0;
		}
		var offset = ac.offset;
		
		var config = {
						action:ac.action,
						callback:eval( ac.callbacks['extend'] ),//t41.view.registry[ jQuery(ac.element).attr('id') ].callbacks['extend'],
					 	context:ac.element,
					 	//method:'get',
						data:{_query:search,uuid:ac.uuid,_sequence:++ac.sequence, _offset:offset}
					 };
		
		//console.log(config);

		t41.core.call(config);
	};
	
	
	/**
	 * Autocompleter Observer
	 * @param obj
	 */
	window.t41.view.action.autocomplete.observer = function(obj) {
	
		var search = (jQuery(obj.target).val());
		var ac = obj.data.caller ? obj.data.caller : obj.data;

		if (search.length < ac.minChars || (search == ac.previous)) {
			return;
		}
		
		ac.previous = search;
		
		var config = {
						action:ac.action,
						callback:eval(ac.callbacks.display),
					 	context:ac.element,
					 	//method:'get',
						data:{_query:search,uuid:ac.uuid,_sequence:++ac.sequence}
					 };
		
		if (ac.callbacks.preQuery && typeof ac.callbacks.preQuery == 'function') {
			config = ac.callbacks.preQuery.call(this,config);
		}
		t41.core.call(config);
	};
	
	
	/**
	 * Default autocompleter Ajax-success Handler
	 * restore the autocomplete object and call the displaySuggestions() methods with the received collection
	 * @param obj
	 */
	window.t41.view.action.autocomplete.display = function(obj) {

		switch (obj.status) {
		
			case t41.core.status.ok:
				var ac = t41.view.registry[this.attr('id')];
				
				// ignore queries coming back home too late
				if (ac.sequence > obj.data._sequence) {
					//console.log('ignored server late response #' + obj.data._sequence + ' for query "' + obj.data._query + '".');
					return false;
				}
				
				ac.displaySuggestions(obj.data);
				ac.offset = obj.data.total;
				break;

			case t41.core.status.err:
				new t41.view.alert(t41.lget('err:bkd'),	{title: t41.lget('err:lbl_srv'), autorun:true, level:'srv_error'});
				break;
			
			default:
				console.log(obj);
				break;
		}
	};
	
	
	window.t41.view.action.autocomplete.select = function(obj) {

		var ac = obj.data.caller;
		var id = '';

		switch (ac.displayMode) {
		
			case 'table':
				var id = jQuery(obj.target).parent('tr').attr('data-id');
				break;
				
			case 'list':
				break;
		}
		if (id) ac.defaultSelect(id); //callbacks['select'] + '()');// "(" + obj + ")");
	};
	
	
	window.t41.view.action.autocomplete.reset = function(obj) {
		
		var ac = obj.data.caller;
		ac.resetValue();
	};
}
