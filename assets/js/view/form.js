if (! window.t41) {
	window.t41 = [];
}
if (! window.t41.view) {
	window.t41.view = [];
}

/**
 * @param string id 	div id
 * @param object obj	source object
 * @param object form	form object
 */
window.t41.view.form = function(id,obj,form) {

	this.formId = '#' + id + '_form';
	
	this.id = id;
	
	this.obj = obj;
	
	this.form = form;
	
	if (! this.form.params) {
		this.form.params = {};
	}
	
	this.fields = [];
	
	this.constraints = [];
	
	this.posts = [];
	
	this.re = false;
	
	this.action;
	
	this.labels = {submit:t41.lget('save'), cancel:t41.lget('cancel'), savenew:t41.lget('form:savenew')};
	
	this.patterns = {email:new RegExp(/^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i)};
	
	/**
	 * List of redirection URL
	 */
	this.redirects = {redirect_ok:document.referrer,redirect_abort:document.referrer};
	
	
	this.addButtons = function(container) {

		var submit = new t41.view.button(this.labels.submit, {id:'form_submit', size:'medium', icon:'valid'});
		t41.view.bindLocal(submit, 'click', jQuery.proxy(this,'save'), this.id);
		container.append(submit);

		if (this.form.params.buttons == 'all') {
			var submit = new t41.view.button(this.labels.savenew, {id:'form_submit', size:'medium', icon:'valid'});
			t41.view.bindLocal(submit, 'click', jQuery.proxy(this,'save2'), this.id);
			container.append(submit);
		}
		
		var back = new t41.view.button(this.labels.cancel, {id:'form_reset', size:'medium', icon:'alert'});
		t41.view.bindLocal(back, 'click', jQuery.proxy(this,'redirector',t41.core.status.abort), this.id);
		container.append(back);
	};
	
	
	this.toggleButtons = function() {
		jQuery('#' + id + ' .form_actions').fadeToggle();
	};
	
	
	this.redirector = function(status) {
		switch (status) {
		
			case t41.core.status.ok:
				if (typeof this.redirects.redirect_ok == 'string') {
					window.location.href = this.redirects.redirect_ok;
				}
				break;
				
			case t41.core.status.abort:
				if (typeof this.redirects.redirect_abort == 'function') {
					this.redirects.redirect_abort.call();
				} else if (typeof this.redirects.redirect_abort == 'string') {
					window.location.href = this.redirects.redirect_abort;
				}
				break;
		}
	};
	
	
	/**
	 * Convert t41 properties into JS fields
	 */
	this.convert = function() {
		
		//console.log(this.form.elements);
		// walk through all properties
		for (var i in this.form.elements) {
			var prop = this.form.elements[i];
			var constraints = prop.constraints || {};
			var c = [];
			
			// convert constraints
			for (var j in constraints) {
				c[j] = true;
			}
			
			this.fields[i] = {label:prop.label,
							  value:prop.value,
							  type:prop.type,
							  constraints:prop.constraints
							 };
			
			// add an observer on field with dependency
			if (! prop.constraints.protected && prop.params && prop.params.dependency) {
				new t41.view.form.elementUpdater(this.obj.uuid,i,prop.params.dependency);
			}
		}
	};
	
	
	this.save = function(obj) {
		
		// remove all error spans
		jQuery('#' + this.id + ' span.error').remove();
		jQuery('#' + this.id + ' div.field').removeClass('errorfield');
		
		// get form elements in an key/value array
		var elements = {};
		
		for (var i in this.fields) {
			
			switch (this.fields[i].type) {
			
				case 'multipleElement':
					var values = [];
					jQuery('[name="' + i + '\[\]"]:checked').each(function() { 
						values[values.length] = this.value; });
					elements[i] = values;
					break;
					
				case 'enumElement':
					var elem = jQuery('[name="' + i + '"]');
					elements[i] = elem[0].type == 'select-one' ? jQuery('[name="' + i + '"]').val() : jQuery('[name="' + i + '"]:checked').val()
					// @todo find a better way to address radio sets with none checked
					// undefined should only be found when element is missing because already defined & protected
					if (elements[i] == undefined) elements[i] = "";
					break;
				
				default:
					elements[i] = jQuery('[name="' + i + '"]').val();
					break;
			}
		}
		
		var errors = [];
		var formdata = {};
		
		// control elements
		for (var i in this.fields) {
			var field = this.fields[i];
			var value = elements[i];

			// don't consider fields with a hidden label
			if (jQuery('#label_' + i).css('display') == 'none' || (field.constraints.protected && field.value != null)) {
				continue;
			}
			
			// test mandatory fields for value except if value is undefined (field is in this case not present)
			if (field.constraints.mandatory && value != undefined && (value == "" || value == t41.core.none || value == null)) {
				errors[errors.length] = {msg:t41.lget('form:fielderr') + ' "' + field.label + '"',field:i};
				continue;
			}
			
			if (field.constraints.emailaddress) {
			    if (this.patterns.email.test(value) == false) {
					errors[errors.length] = {msg:'"' + value + '" ' + t41.lget('form:emailerr'),field:i};
					continue;
			    }
			}
			
			// @todo replace currencyElement with constant
			if (value && field.type == 'currencyElement') {
				value = value.replace(',','.');
			}
			
			// register data value
			formdata[i] = value;
		}
		
		if (errors.length > 0) {
			for (var i in errors) {
				var _element = jQuery('#elem_' + errors[i].field);
				var span = document.createElement('span');
				span.setAttribute('class', 'error');
				span.innerHTML = errors[i].msg;
				_element.addClass('errorfield').prepend(span);
			}
			jQuery('#' + errors[0].field).focus();
			return false;
		} else {
			
			// deactivate buttons
			this.toggleButtons();
			
			formdata['uuid'] = this.form.uuid;
			formdata['objuuid'] = this.obj.uuid;
			if (this.form.params.post_ok) {
				formdata['post_ok'] = this.form.params.post_ok;
			}
			if (this.form.params.post_nok) {
				formdata['post_nok'] = this.form.params.post_nok;
			}			

			if (this.form.params.identifier == true) {
				formdata['_identifier'] = jQuery(this.formId).find('#_identifier').val();
			}
			
			if (this.action && typeof this.action == 'function') {
				this.action.call(this,formdata);
			} else {
				// send query to server
				t41.core.call({action:'form/save', data:formdata, callback:jQuery.proxy(this,'retSave')});
			}
		}
	};
	
	
	this.save2 = function(obj) {
		this.redirects.redirect_ok = window.location.href;
		this.re = true;
		this.save(obj);
	};
	
	
	/**
	 * This function is call after form was saved and receive a status code and the saved object
	 * Optional success and failure post actions are then processed and configured redirection is executed
	 */
	this.retSave = function(obj) {
		if (obj.status == t41.core.status.ok) {
			// if a post function has been declared, execute it
			if (this.posts.ok && typeof this.posts.ok == 'function') {
				this.posts.ok.call(this, obj.data); // pass the return object as parameter and the form as context
			}
			var params = this.redirects && this.redirects.redirect_ok ? {defer:true} : {timer:10};
			if (this.re == false) {
				new t41.view.alert(t41.locale.get('form:saveok'), params);
			}
			if (this.redirects && this.redirects.redirect_ok) {
				if (typeof this.redirects.redirect_ok == 'function') {
					this.redirects.redirect_ok.call(this, obj.data);
				} else if (typeof this.redirects.redirect_ok != 'string') {
					var baseurl = this.redirects.redirect_ok[0];
					for (var i in this.redirects.redirect_ok[1]) {
						baseurl += '/' + this.redirects.redirect_ok[1][i] + '/';
						if (this.redirects.redirect_ok[1][i] == 'uuid') {
							baseurl += obj.data.uuid;
						} else {
							baseurl += jQuery('#' + this.redirects.redirect_ok[1][i]).val();
						}
					}
					window.location.href = baseurl;
				} else {
					if (this.redirects.redirect_ok == t41.core.none) {
						jQuery('#' + id + ' .form_actions').fadeIn();
						new t41.view.alert(t41.locale.get('form:saveok'), {timer:3});
					} else {
						window.location.href = this.redirects.redirect_ok;
					}
				}
			}
		} else {
			var params = this.redirects && this.redirects.redirect_nok ? {defer:true} : {timer:10};
			new t41.view.alert("Erreur lors de la sauvegarde", params);
			if (this.redirects && this.redirects.redirect_nok){
				window.location.href = this.redirects.redirect_nok;
			} else {
				// reactivate buttons
				this.toggleButtons();
			}
		}
	};
	
	
	this.reset = function() {
		jQuery('#' + this.id).get(0).reset();
	};
	
	
	/**
	 * Give focus to the first visible element of the form
	 */
	this.focus = function() {
		jQuery(this.formId + ' :input[type="text"]:first').focus();
	};
	
	
	this.show = function() {
		jQuery('#'+this.id).show();
		this.focus();
	}
	
	
	this.hide = function() {
		jQuery('#'+this.id).hide();
	};
	
	
	// constructor
	jQuery('#actions').attr('style','text-align:center');
	this.convert();

	if (form.params) {
		if (form.params.redirect_ok) {
			this.redirects.redirect_ok = form.params.redirect_ok;
			if (typeof form.params.redirect_ok == 'string') {
				// if redirect is an uri, use it also for cancel button
				this.redirects.redirect_abort = form.params.redirect_ok;
			}
		}
		if (form.params.redirect_nok) {
			this.redirects.redirect_nok = form.params.redirect_nok;
		}
		if (form.params.redirect_abort) {
			this.redirects.redirect_abort = form.params.redirect_abort;
		}
		if (form.params.action) this.action = eval(form.params.action);
		if (form.params.labels) this.labels = form.params.labels;
	}

	if (this.form.params && this.form.params.buttons != false) {
		this.addButtons(jQuery(this.formId + ' .form_actions'));
	}
	
	// add tabindex to each field and button
	var selector = ' :input,select,a';
	// fix for Mozilla Bug #338035
	if (jQuery.browser.mozilla == true) selector += '>span';
	jQuery(this.formId).find(selector).each(function(i,o) { 
		jQuery(o).attr('tabindex',t41.core.tabindex);
		t41.core.tabindex += 10;
	});
	
};


/**
 * Update a form element with values matching the value of another element
 */
window.t41.view.form.elementUpdater = function(uuid,dest,src) {
	
	/**
	 * The element(s) to get value from
	 */
	this.src  = src.split(',');
	for (var i in this.src) {
		this.src[i] = this.src[i].split(':'); // extract additional fixed value for query
	}
	
	/**
	 * The element which values are refreshed
	 */
	this.dest = jQuery('#' + dest);
	
	/**
	 * Remote object UUID
	 */
	this.uuid = uuid;
	

	this.prepareDest = function() {
		this.dest.empty().append(new Option('--',t41.core.none));
	};
	
	
	this.refresh = function() {
		this.prepareDest();
		var srcProps = {};
		for (var i in this.src) {
			var values = {0:jQuery('#' + this.src[i][0]).val()};
			if (this.src[i][1]) {
				values[1] = this.src[i][1];
			}
			srcProps[this.src[i][0]] = values;
		}
		var data = {
					uuid:this.uuid,
					srcProperty:srcProps,
					destProperty:{id:this.dest.attr('id'),val:this.dest.val()}
				   };
		t41.core.call({action:'object/depend', data:data, callback:jQuery.proxy(this,'applyRefresh')});
	};
	
	
	this.applyRefresh = function(obj) {
		if (obj.status && obj.status == t41.core.status.ok) {
			if (obj.data.total > 0) {
				var collection = new t41.object.collection(obj.data);
				if (obj.data.value) {
					value = obj.data.value || null;
				}
				this.prepareDest();

				for (var i in collection.members) {
					var member = collection.members[i];
					var option = new Option(member.get('label'),member.uuid, (typeof value != 'undefined' && value == member.uuid));
					this.dest.append(option);
				}
			}
		} 
	};
	
	var element = [];
	for (var i in this.src) {
		element[i] = this.src[i][0];
	}
	t41.view.bindLocal(element, 'change', jQuery.proxy(this, 'refresh'));
};


window.t41.view.form.toggler = function(id, action) {
    var ids = id ? id.split(',') : this.options;
    for (var id in ids) {
            var elems = jQuery("[id$='" + ids[id] + "']");

            switch (action) {
                    case 'show':
                            elems.show();
                            break;
                            
                    case 'hide':
                            elems.hide();
                            //elems.val('');
                            break;
                            
                    default:
                            elems.toggle(); 
                            break;
            }
    }
};

/**
 * UI controls for the Street Number field format
 */
window.t41.view.form.streetNumber = function(id) {

	this.id = id;
	
	this.updateValue = function() {
		var value = jQuery('#' + this.id + '_number').val();
		if (value == 0) {
			value = null;
		}
		if (value) {
			jQuery('#' + this.id + '_ext').prop('disabled',false);
			if (jQuery('#' + this.id + '_ext').val()) {
				value += '.' + jQuery('#' + this.id + '_ext').val();
			}
		} else {
			jQuery('#' + this.id + '_number').val('');
			jQuery('#' + this.id + '_ext').prop('disabled',true).prop('selectedIndex',0);
			value = '_NONE_';
		}
		jQuery('#' + this.id).val(value);
		t41.view.customEvent(this.id, 'change');
	};
	
	
	this.initValue = function() {
		var parts = jQuery('#' + this.id).val().split('.');
		jQuery('#' + this.id + '_number').val(parts[0] || '');
		jQuery('#' + this.id + '_ext').val(parts[1] || '');
		this.updateValue();
	}
	
	var selector = '#' + this.id + '_number,#' + this.id + '_ext';
	t41.view.bindLocal(selector, 'change', jQuery.proxy(this,'updateValue'));
	this.initValue();
};


/**
 * UI controls for the Time field format
 */
window.t41.view.form.timeElement = function(id) {

	this.id = id;
	
	this.updateValue = function() {
		var value = jQuery('#' + this.id + '_hour').val();
		if (value) {
			if (jQuery('#' + this.id + '_minute').prop('selectedIndex') == 0) {
				jQuery('#' + this.id + '_minute').prop('selectedIndex', 1);
			}
			value += ':' + jQuery('#' + this.id + '_minute').val();
		} else {
			value = t41.core.none;
			jQuery('#' + this.id + '_hour').prop('selectedIndex', 0);
			jQuery('#' + this.id + '_minute').prop('selectedIndex', 0);
		}
			
		jQuery('#' + this.id).val(value);
		t41.view.customEvent(this.id, 'change');
	};
	
	
	this.initValue = function() {
		var parts = jQuery('#' + this.id).val().split(':');
		jQuery('#' + this.id + '_hour').val(parts[0] || '');
		jQuery('#' + this.id + '_minute').val(parts[1] || '');
		this.updateValue();
	}
	
	var selector = '#' + this.id + '_hour,#' + this.id + '_minute';
	t41.view.bindLocal(selector, 'change', jQuery.proxy(this,'updateValue'));
	this.initValue();
};

