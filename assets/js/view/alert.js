
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

	this.str = str || 'no content';
	this.o = o || {};
	this.buttons = {};
	
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

		if (this.o.level == 'error') this.o.title = 'Erreur';
	};

	this.run = function() {
		// compile options at execution time only
		this.applyOptions();

		var o = this.o;
		var str = this.str;

		// remove any other element with my ID
		jQuery('#'+o.id).remove();

		// put wrapper in the DOM
		jQuery('body').append('<div id="'+o.id+'" class="t41 component alert" style="display: none;" />');

		// link wrapper in 'this'
		this.alert = jQuery('#'+o.id);

		// append the rest of the required HTML
		this.alert.append('<a class="title"></a>');
		
		this.alert.children('.title').append('<span class="icon_"/>'+o.title);

		this.addIcon();

		this.alert.append('<div class="content" />')
			.addClass(o.addClass)
			.children('.content')
				.html(str)
				.after('<div class="buttons" />');

	
		this.addButtons();

		this.show();

		// launch timer if set so
		if (o.timer>0) {
			setTimeout(jQuery.proxy(this, 'close'), o.timer*1000);
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
		this.o.parent.bind('resize.t41_alert_'+this.o.id, function() {
			alert.css(t41.view.getPosition(alert, o));
		});

		// delaying first positionning avoids bogus width/height readings
		setTimeout(function() { alert.css(t41.view.getPosition(alert, o)); }, 10);
		
		if (this.o.overlay) {
			this.showOverlay();
		}

		this.alert.fadeIn();
	};

	
	this.showOverlay = function() {

		// building the overlay
		this.overlay = jQuery('<div class="alert_overlay" />');
		jQuery('body').append(this.overlay);

		// if additional classes were set, put them on overlay too
		this.overlay.addClass(this.o.addClass);

		// persistance option forces to use the alert buttons to hide the overlay+alert 
		if (this.o.persistance == false) {
			this.overlay.bind('click', jQuery.proxy(this, 'close'));
		}
	};

	
	this.hideOverlay = function() {
		// fadeout and remove from DOM
		this.overlay.fadeOut('slow', function() {
			jQuery('.alert_overlay').remove();
		});
	};

	
	this.hide = function() {
		// be nice and start making some space
		this.alert.css('z-index', 100);

		if (this.o.overlay) {
			this.hideOverlay();
		}
		// fadeout and destroy
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
		if (typeof this.o.callbacks.confirm != false) {
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
		for (i in this.o.buttons) {
			if (this.o.buttons[i] == false) continue;
			switch (i) {
			
				case 'close':
					jQuery('#' + this.o.id + ' a.title').append('<div class="close" />');
					jQuery('#' + this.o.id + ' a.title div.close').bind('click', jQuery.proxy(this, 'close'));
					break;
					
				case 'confirm':
					this.buttons.confirm = new t41.view.button(t41.lget('confirm'), {icon:'valid'});
					this.buttons.confirm.setAttribute('id', 'confirm');
					jQuery('#' + this.o.id + ' .buttons').append(this.buttons.confirm);
					//jQuery(button).bind('click', this.o.callbacks.confirm);
					break;
					
				case 'abort':
					var button = new t41.view.button(t41.lget('cancel'), {icon:'delete'});
					jQuery('#' + this.o.id + ' .buttons').append(button);
					jQuery(button).bind('click', jQuery.proxy(this, 'abort'));
					break;
			}
		}
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
	if (this.defaults.autorun == true) {

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
