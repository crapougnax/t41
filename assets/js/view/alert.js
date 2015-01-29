if (! window.t41) {
	window.t41 = [];
}
if (! window.t41.view) {
	window.t41.view = [];
}

/**
 * build an informational box and (default behaviour) display it
 * to prevent immediate display, set t41.view.alert.defaults.autorun to false
 * 
 * 
 * @param str message to display
 * @param o parameters
 * @returns {window.t41.view.alert}
 */
window.t41.view.alert = function(str,o) {

	this.str = str || '';
	this.o = o || {};
	this.buttons = {};
	this.visible = false;
	
	// default options
	this.defaults = {
		autorun:true,
		id: 'alert_window',
		defer:false,
		title: 'Information',
		overlay: true,
		addClass: '',
		persistance: false,
		timer: 0,
		position: 'center',
		parent: jQuery(window),
		bindOn: null,
		offset: 40,
		level: 'notice',
		icon: null,
		buttons: {
			close: true,
			confirm: false,
			abort: false
		},
		callbacks: {
			close: function(o) { o.hide(); },
			confirm: false,
			abort:function(o) {o.hide(); }
		},
		labels: {
			confirm: null,
			abort: null			
		}
	};

	this.levels = {
		notice: 'help',
		warning: 'alert',
		error: 'delete',
		ui_error: 'delete',
		srv_error: 'delete',
		peer_error: 'delete'
	};

	this.applyOptions = function() {
		// extend given options with default ones
		this.o = jQuery.extend(true, this.defaults, this.o);
		if (this.o.level == 'error' && ! this.o.title) this.o.title = 'Erreur';
	};

	this.run = function() {
		// compile options at execution time only
		this.applyOptions();

		// remove any other element with my ID
		jQuery('#' + this.o.id).remove();

		// put wrapper in the DOM
		jQuery('body').append('<div id="' + this.o.id + '" class="t41 component alert" style="display:none;" />');

		// link wrapper in 'this'
		this.alert = jQuery('#' + this.o.id);

		// append the rest of the required HTML
		this.alert.append('<a class="title"></a>');
		
		this.alert.children('.title').append('<span class="icon_"/>' + this.o.title);

		this.addIcon();

		this.alert.append('<div class="content" />')
			.addClass(this.o.addClass)
			.children('.content')
				.html(this.str)
				.after('<div class="buttons" />');

	
		this.addButtons();
		this.show();

		// launch timer if set so
		if (this.o.timer > 0) {
			setTimeout(jQuery.proxy(this, 'close'), this.o.timer*1000);
		}
	};
	
	
	this.setContent = function(str,level) {
		this.str = str;
		if (this.visible == true) {
			this.alert.find('.content').html(this.str);
		}
	};
	
	
	this.addContent = function(str,params) {
		// remove waiting image
		this.alert.find('.wait').remove();
		
		var params = params || {};
		if (params.level) {
			switch (params.level) {
				case 'warning':
					str = '<span style="color:orange">' + str + '</span>';
					break;
				
				case 'error':
					str = '<span style="color:red;font-weight:bold">' + str + '</span>';
				break;
				
				case 'waiting':
					// we don't want to keep the waiting image in this.str 
					this.str += str;
					this.alert.find('.content').html(this.str + '<img src="/assets/images/waiting.gif" class="wait" style="vertical-align:middle"/>');
					return;
					break;
				
				case 'return': //return status
					if (str == t41.core.status.ok) {
						str = '<span style="font-weight:bold;color:green">OK</span><br/>';
					} else {
						str = '<span style="font-weight:bold;color:red;text-align:right;width:100%">ERR</span><br/>';
					}
					break;
			}
		}
		
		this.str += str;
		if (this.visible == true) {
			this.alert.find('.content').html(this.str);
		}
	};	

	
	this.show = function() {
		var alert = this.alert;
		var o = {
					position: this.o.position,
					offset: this.o.offset,
					parent: this.o.parent
				};
		// bind position update on parent resize
		this.o.parent.bind('resize.t41_alert_' + this.o.id, function() {
			alert.css(t41.view.getPosition(alert, o));
		});

		// delaying first positionning avoids bogus width/height readings
		setTimeout(function() { alert.css(t41.view.getPosition(alert, o)); }, 10);
		
		if (this.o.overlay) {
			this.showOverlay();
		}

		this.alert.css('z-index', 15000);
		this.alert.fadeIn();
		this.visible = true;
	};

	
	this.showOverlay = function() {
		// building the overlay
		this.overlay = new t41.view.overlay({loose:this.o.persistance});
		this.overlay.show();

		// if additional classes were set, put them on overlay too
		//this.overlay.addClass(this.o.addClass);

		// persistance option forces to use the alert buttons to hide the overlay+alert 
		if (this.o.persistance == false) {
			//this.overlay.bind('click', jQuery.proxy(this, 'close'));
		}
	};

	
	this.hideOverlay = function() {
		// fadeout and remove from DOM
		this.overlay.hide();
		//	jQuery('.alert_overlay').remove();
	};

	
	this.hide = function() {
		// be nice and start making some space
		this.alert.css('z-index', 100);

		if (this.o.overlay) {
			this.overlay.hide(); //hideOverlay();
		}
		// fadeout and destroy
		this.visible = false;
		this.alert.fadeOut('slow', function() {
			jQuery.proxy(this, 'destroy');
		});
	};

	/* Remove alert from the DOM */
	this.destroy = function() {
		// remove from DOM
		this.alert.remove();
		// unbind listeners
		var id = this.o.id;
		this.o.parent.unbind('resize.t41_alert_'+id);
	};

	this.close = function () {
		if (typeof this.o.callbacks.close == 'function') {
			this.o.callbacks.close(this);
		} else {
			this.hide();
		}
	};

	this.confirm = function () {
		if (typeof this.o.callbacks.confirm == 'function') {
			this.o.callbacks.confirm(this);
		} else if (this.o.callbacks.confirm != 'undefined'){
			eval(this.o.callbacks.confirm);
		}
	};

	this.abort = function () {
		if (typeof this.o.callbacks.abort == 'function') {
			this.o.callbacks.abort(this);
		} else {
			this.hide();
		}
	};

	/* Create buttons and bind event handlers */
	this.addButtons = function() {
		for (var i in this.o.buttons) {
			if (this.o.buttons[i] == false) continue;
			switch (i) {
			
				case 'close':
					jQuery('#' + this.o.id + ' a.title').append('<div class="close" />');
					jQuery('#' + this.o.id + ' a.title div.close').bind('click', jQuery.proxy(this, 'close'));
					break;
					
				case 'confirm':
					var label = this.o.labels.confirm || t41.lget('confirm:button');
					this.buttons.confirm = new t41.view.button(label, {icon:'valid'});
					this.buttons.confirm.setAttribute('id', 'confirm');
					t41.view.bindLocal(this.buttons.confirm, 'click', jQuery.proxy(this,'confirm'));	
					jQuery('#' + this.o.id + ' .buttons').append(this.buttons.confirm);
					break;
					
				case 'abort':
					var button = new t41.view.button(t41.lget('cancel'), {icon:'delete'});
					jQuery('#' + this.o.id + ' .buttons').append(button);
					jQuery(button).bind('click', jQuery.proxy(this, 'abort'));
					break;
			}
		}
	};

	
	/**
	 * Add the given t41.view.button object to the button div and bind given callback
	 */
	this.addButton = function(button, callback) {
		jQuery('#' + this.o.id + ' .buttons').append(button);
		jQuery(button).bind('click', callback);
	};

	
	this.addIcon = function() {
		if (this.o.icon===false) {
			// no icon
			this.alert.children('.title').removeClass('icon');
		} else {
			var icon = jQuery('#'+this.o.id+' .title span.icon_');
			switch(this.o.icon) {
				case null:
					// use level value for icon
					icon.addClass(this.levels[this.o.level]);
				break;
				default:
					// use specified string
					icon.addClass(this.o.icon);
				break;
			}
			this.alert.children('.title').addClass('icon').addClass('element').addClass('medium');
		}
	};
	
	// constructor
	if ((o && o.autorun && o.autorun == true) || ((!o || typeof o.autorun == 'undefined') && this.defaults.autorun == true)) {

		// run at once if param says so
		this.applyOptions();
		
		if (this.o.defer == true) {
			// register alert for later
			t41.core.setCookie('t41DeferredAlert', str);
		} else {
			this.run();
		}
	}
};


window.t41.view.alert.runDeferred = function() {
	
	//return false;
	if (t41.core.getCookie('t41DeferredAlert') != false) {
		
		new t41.view.alert(t41.core.getCookie('t41DeferredAlert'), {timer:5});
		t41.core.removeCookie('t41DeferredAlert');
	}
};


window.t41.view.alert.confirm = function(content,callbacks,title) {
	var content = content || t41.locale.get('confirm:message');
	return new t41.view.alert(content,{
										title:title || t41.locale.get('confirm:title'), 
										buttons:{confirm:true, abort:true},
										callbacks:callbacks
		  							  }
							 );
};

