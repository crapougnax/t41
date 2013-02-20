if (! window.t41) {
	window.t41 = [];
}

if (! window.t41.view) {

	window.t41.view = {	 color:'', size:''
					   , decsep:','
					   , currency:'Currency', date:'Date', datehour:'DateHour'
					   , registry:{}
					   , caller:null
					  };


	/**
	 *  button generation function
	 *  
	 *  options:
	 *  	- size: 	small|medium|large
	 *  	- color:	white|black
	 *  	- icon:		icon class
	 *  	- css:		other css
	 *  	- nolabel:	remove label
	 */
	window.t41.view.button = function(label, options) {
		
		options = options || {};
		
		// create <a> element
		var button = document.createElement('a');
		button.setAttribute('title', label);
		if (options.id) button.setAttribute('id',options.id);
		
		if (! options.size) options.size = t41.view.size;
		if (! options.color) options.color = t41.view.color;
		
		// add css classes
		var css = 'element ';
		if (! options.nolabel) {
			css += 'button ';
		}
		css += options.size ? ' ' + options.size : ' medium';
		if (options.color) css += ' ' + options.color;
		if (options.css)   css += ' ' + options.css;
		
		// create <span> element
		var span = document.createElement('span');
		if (options.help) span.setAttribute('data-help', options.help);
		if (options.icon) {
			
			css += ' icon';
			span.setAttribute('class', options.icon);
		}
		jQuery(button).append(span);
		if (! options.nolabel) {
			jQuery(button).append(label);
		}
		
		button.setAttribute('class', css);
		
		// bind event if declared
		if (options.action) {

			var event = options.event ? options.event : 'click';
			var callback = options.callback ? options.callback : t41.core.defaultCallback;

			t41.view.bind({event:event, action:options.action, callback:callback, data:options.data, element:jQuery(button)});
		}
		
		return button;
	};
	

	
	/* value formatting function */
	window.t41.view.format = function(str, format, args) {

		if (str == 'undefined') return;
		
		var args = args || {};
		
		switch (format) {

			case t41.view.currency:

				var decimals = args.decimals || 2;
				str = parseFloat(str).toFixed(decimals);
				
				str = str.toString();
				var signed = (str.substring(0,1) == '-');

				// entities = yes : HTML escaped (default)
				var curr_symbol = (args.entities && args.entities != 'yes') ? ' \u20AC' : '&nbsp;&euro;';
				var nbr_space = (args.entities && args.entities != 'yes') ? ' ' : '&nbsp;';
				
				if (signed) str = str.substring(1, str.length);

				var sep = str.split('.');
			    
			    if (! sep[1]) {
			    	sep[1] = '00';
			    } else if (sep[1].length == 1) {
			    	sep[1] += '0';

			    } else if (sep[1].length > 2) {
			    	
			    	for (var i = sep[1].length -1; i = 0; i--) {
			    		
			    		if (sep[1].substring(i,1) != '0') break;
			    		sept[1].length = sep[1].length - 1;
			    	}
			    }
			    
			    if (sep[0].length > 3) {
			    	
			    	var count = 0;
			    	var left = '';
			    	var chars = sep[0].split('');
			    	chars.reverse();
			    	for (var i in chars) {
			    		
			    		if (count == 3) {
			    			
			    			left = nbr_space + left;
			    			count = 0;
			    		}
			    		
			    		left = chars[i] + left;
			    		count++;
			    	}
			    	
			    	sep[0] = left;
			    }
			    
			    if (signed) sep[0] = '-' + sep[0];
			    return sep[0] + t41.view.decsep + sep[1] + curr_symbol;
				break;

			case 'Date':
			    var part = str.split(" ");

			    var elem = part[0].split('-');
			    var str = elem[2] + '/' + elem[1] + '/' + elem[0];
			    return str;
			    break;
			    
			case 'DateHour':
			    var part = str.split(" ");

			    var elem = part[0].split('-');
			    var str = elem[2] + '/' + elem[1] + '/' + elem[0];

			    if (part[1]) {
			        var elem = part[1].split(':');
			        str += ' ' + elem[0] + 'h' + elem[1];
			    }
			    return str;
			    break;
			    
			default:
			    return str;
			    break;
			}
	};
	
	
	/**
	 * Bind an element to a function or class method
	 * @param element
	 * @param event
	 * @param action
	 * @param obj
	 */
	window.t41.view.bindLocal = function(element, event, action, obj) {
		
		if (typeof element == 'string' && element.indexOf(',') < 0) {
			var element = jQuery('#' + element);
		}
		
		if (obj && typeof obj == 'string') {
			var obj = jQuery('#' + obj);
		}
		
		if (typeof action != 'function') {
			var action = eval(action);
		}

		jQuery(element).live(event, {caller:obj}, action);
		return element;
	};
	

	/**
	 * unbind any existing same-type event
	 * @param element
	 * @param event
	 */
	window.t41.view.unbind = function(element, event) {
		if (typeof element == 'string') {
			var element = jQuery('#' + element);
		}
		jQuery(element).unbind(event);
		return element;
	};
	

	/**
	 * Bind an event to a DOM element
	 * @param val
	 */
	window.t41.view.bind = function(val) {
		
		var element = typeof(val.element) == 'string' ? jQuery('#'+val.element) : jQuery(val.element);
		
		if (val.callback) {
			if (t41.core[val.callback]) {
				var callback = t41.fn[val.callback];
				
			} else {
				var callback = eval(val.callback);
			}
		} else {
			var callback = t41.core.defaultCallback;
		}

		element.bind(val.event || 'click'
				 , val.data
				 , function(e) {
								 t41.core.call({action:val.action
									 		, data:e.data
			 						  		, context:element
			 						  		, callback:callback
			 							 	 }
									);
							   }
				 );
	};
	
	/**
	 * Simple link resolver
	 * @param value link value, can be absolute url or javascript function
	 * @param obj optional element that triggered the function
	 */
	window.t41.view.link = function(value, obj) { 
		if (value.substring(0,1) == '/' ||  value.substring(0,4) == 'http') {
			document.location = value; 
		} else {
			if (obj) {
				t41.view.caller = obj;
			}
			eval.call(window,value);
		}
	};
	

    window.t41.view.getPosition = function(el, o) {
        var o = o || {};
        var top = o.top || 200;
        var left = o.left || 200;
        var offset = o.offset || 40;
        var position = o.position || 'center';
        var parent = o.parent || jQuery(document);
        var doch = parent.height();
        var docw = parent.width();

        switch(position) {
            case 'center':
                top = Math.floor(doch/2)-Math.floor(el.height()/2);
                left = Math.floor(docw/2)-Math.floor(el.width()/2);
            break;
            case 'top':
                top = offset;
                left = Math.floor(docw/2)-Math.floor(el.width()/2);
            break;
            case 'bottom':
                top = Math.floor(doch)-Math.floor(el.height()+offset);
                left = Math.floor(docw/2)-Math.floor(el.width()/2);
            break;
            case 'left':
                top = Math.floor(doch/2)-Math.floor(el.height()/2);
                left = offset;
            break;
            case 'right':
                top = Math.floor(doch/2)-Math.floor(el.height()/2);
                left = Math.floor(docw)-Math.floor(el.width()+offset);
            break;
            case 'topleft':
                top = offset;
                left = offset;
            break;
            case 'topright':
                top = offset;
                left = Math.floor(docw)-Math.floor(el.width()+offset);
            break;
            case 'bottomright':
                top = Math.floor(doch)-Math.floor(el.height()+offset);
                left = Math.floor(docw)-Math.floor(el.width()+offset);
            break;
            case 'bottomleft':
                top = Math.floor(doch)-Math.floor(el.height()+offset);
                left = offset;
            break;
        }

        left+='px';
        top+='px';

        var css = {'top': top, 'left': left};

        return css;
    };
    

    /**
     * Sets a new overlay object and optionally bind an element to display
     * 
     * Params:
     * dom		: id or dom object to display when the overlay is showed
     * loose	: TRUE defines wether the overlay is hidden when user clicks anywhere on it
     * color	: #000 overlay color
     * opacity	: 75 overlay opacity
     * 
     * @param params
     * @returns {t41.view.overlay}
     */
    window.t41.view.overlay = function(params) {
    
    	this.defaults = {loose:true, color:'#000', opacity:75};
    	this.params = jQuery.extend(true, this.defaults, params);

    	if (this.params.dom) {
    		this.element = typeof this.params.dom == 'string' ? jQuery('#' + this.params.dom) : this.params.dom;
    		this.element.hide();
    	}
    	
    	this.show = function() {
    		if (jQuery('#t41_overlay').length == 0) {
    			
    			var css = {
    						'background-color':this.params.color, 
    						opacity:this.params.opacity/100,
    						filter:'alpha(opacity=' + this.params.opacity + ')', 
    						'-moz-opacity':this.params.opacity/100,
    						'-khtml-opacity':this.params.opacity/100,
    						position:'fixed', 
    						left:0, 
    						top:0, 
    						width:'100%', 
    						height:'100%', 
    						'z-index':10000
    					  };
    			var overlay = document.createElement('div');
    			jQuery(overlay).attr('id', 't41_overlay').css(css);
    			if (this.params.loose) jQuery(overlay).bind('click', jQuery.proxy(this, 'hide'));
    			jQuery(overlay).appendTo(document.body);
    		}
    		
    		if (this.element) {
    			this.element.css({'z-index':15000, width:jQuery(window).width()/1.5, position:'absolute'});
    			this.element.css("top", Math.max(0, ((jQuery(window).height() - this.element.outerHeight()) / 2) + jQuery(window).scrollTop()) + "px");
    			this.element.css('left', Math.max(0, ((jQuery(window).width() - this.element.outerWidth()) / 2) + jQuery(window).scrollLeft()) + "px");
    			this.element.show();
    		}
    		jQuery('#t41_overlay').fadeIn('slow');
    		
    	};
    	
    	this.hide = function() {
    		jQuery('#t41_overlay').fadeOut('slow');
    		if (this.element) this.element.hide();
    	};
    };
    
	
	window.t41.view.component = function(params) {
		
		var params = params || {};
		
		this.title = params.title || '';
		
		this.id = params.id || '';
		
		this.content = params.content || '';
		
		// css instead of class which is kind of a protected keyword
		this.css = params.css || 't41 component';
		
		this.domObject;
		
		this.render = function(parent) {
			
			if (typeof parent == 'string') parent = jQuery('#' + parent);
			
			this.domObject = document.createElement('DIV');
			this.domObject.setAttribute('id', this.id);
			this.domObject.setAttribute('class', this.css);
			
			var title = document.createElement('H4');
			title.setAttribute('class', 'title open');
			title.innerHTML = this.title;
			
			jQuery(this.domObject).append(title);

			var content = document.createElement('DIV');
			content.setAttribute('class','content');
			content.innerHTML = this.content;
			jQuery(this.domObject).append(content);

			parent.append(this.domObject);
		};
		
		
		this.addContent = function(obj) {
			if (this.domObject) {
				jQuery(this.domObject).append(obj);
			} else {
				this.content += obj;
			}
			
			return this;
		};
	};
	
	
    window.t41.view.scroll = function(obj, o) {

        var o = o || {};
        var offset = o.offset || jQuery(obj).outerHeight();
        var scrollpos = jQuery(obj).scrollTop() || 0; // current scroller position
        var speed = o.speed || 'slow';

        offset = offset + scrollpos;

        if (o.more) offset += o.more; // additionnal arbitrary value

        jQuery(obj).animate({
            scrollTop: offset
        }, speed);
    };
    

    /**
     * Fire a custom event on the given element
     * Compatible with IE 8!
     */
    window.t41.view.customEvent = function(element,trigger) {
    	var domObject = document.getElementById(element);
    	if (! domObject) return false;
        if (document.fireEvent) {
        	domObject.fireEvent('on' + trigger);
        } else if (document.createEvent) {
        	var event = document.createEvent("HTMLEvents");
			event.initEvent(trigger, true, false);
			domObject.dispatchEvent(event);
        }
    };
}
