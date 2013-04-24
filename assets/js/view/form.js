if (! window.t41) {
	window.t41 = [];
}
if (! window.t41.view) {
	window.t41.view = [];
}

window.t41.view.form = function(id,obj,form) {
	
	this.formId = '#' + id;
	
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
	
	this.labels = {submit:"Sauver"};
	
	/**
	 * List of redirection URL
	 */
	this.redirects = {redirect_ok:document.referrer};
	
	this.addButtons = function(container) {

		var submit = new t41.view.button(this.labels.submit, {id:'form_submit', size:'medium', icon:'valid'});
		t41.view.bindLocal(submit, 'click', jQuery.proxy(this,'save'), this.id);
		container.append(submit);

		if (this.form.params.buttons == 'all') {
			var submit = new t41.view.button("Sauver & Nouveau", {id:'form_submit', size:'medium', icon:'valid'});
			t41.view.bindLocal(submit, 'click', jQuery.proxy(this,'save2'), this.id);
			container.append(submit);
		}
		
		var back = new t41.view.button("Annuler", {id:'form_reset', size:'medium', icon:'alert'});
		t41.view.bindLocal(back, 'click', function() { history.back(); }, this.id);
		container.append(back);
	};
	
	
	/**
	 * Convert t41 properties into JS fields
	 */
	this.convert = function() {
	
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
		jQuery('#' + this.id + '_form span.error').remove();
		
		// get form elements in an key/value array
		var elements = {};
		jQuery.map(jQuery(this.formId).serializeArray(), function(e) { elements[e.name] = e.value;});
		
		var errors = [];
		var formdata = {};
		
		// control elements
		for (var i in this.fields) {
			var field = this.fields[i];
			var value = elements[i];

			if (field.constraints.mandatory && value == "") {
				errors[errors.length] = {msg:'Valeur requise pour le champ "' + field.label + '"',field:i};
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
				var span = document.createElement('span');
				span.setAttribute('class', 'error');
				span.innerHTML = errors[i].msg;
				jQuery('#elem_' + errors[i].field).append(span);
			}
			jQuery('#' + errors[0].field).focus();
			return false;
		} else {
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
				new t41.view.alert("Sauvegarde effectuée", params);
			}
			if (this.redirects && this.redirects.redirect_ok){
				if (typeof this.redirects.redirect_ok != 'string') {
					var baseurl = this.redirects.redirect_ok[0];
					for (var i in this.redirects.redirect_ok[1]) {
						baseurl += '/' + this.redirects.redirect_ok[1][i] + '/';
						baseurl += jQuery('#' + this.redirects.redirect_ok[1][i]).val();
					}
					window.location.href = baseurl;
				} else {
					window.location.href = this.redirects.redirect_ok;
				}
			}
		} else {
			var params = this.redirects && this.redirects.redirect_nok ? {defer:true} : {timer:10};
			new t41.view.alert("Erreur lors de la sauvegarde", params);
			if (this.redirects && this.redirects.redirect_nok){
				window.location.href = this.redirects.redirect_nok;
			}
		}
	};
	
	
	// constructor
	jQuery('#actions').attr('style','text-align:center');
	this.convert();
	if (form.params) {
		if (form.params.redirect_ok) this.redirects.redirect_ok = form.params.redirect_ok;
		if (form.params.action) this.action = eval(form.params.action);
		if (form.params.labels) this.labels = form.params.labels;
	}
	if (this.form.params && this.form.params.buttons != false) this.addButtons(jQuery('#form_actions'));
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
		this.dest.empty().append(new Option('--',''));
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


/**
 * UI controls for the Street Number field format
 */
window.t41.view.form.streetNumber = function(id) {

	this.id = id;
	
	this.updateValue = function() {
		var value = jQuery('#' + this.id + '_number').val();
		if (value) {
			jQuery('#' + this.id + '_ext').prop('disabled',false);
			if (jQuery('#' + this.id + '_ext').val()) {
				value += '.' + jQuery('#' + this.id + '_ext').val();
			}
		} else {
			jQuery('#' + this.id + '_ext').prop('disabled',true);
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

