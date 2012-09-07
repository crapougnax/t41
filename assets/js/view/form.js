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
	
	
	/**
	 * List of redirection URL
	 */
	this.redirects = {redirect_ok:document.referrer};
	

	this.addButtons = function(container) {

		var submit = new t41.view.button("Sauver", {id:'form_submit', size:'medium', icon:'valid'});
		t41.view.bindLocal(submit, 'click', jQuery.proxy(this,'save'), this.id);
		container.append(submit);
		
		var back = new t41.view.button("Annuler", {id:'form_reset', size:'medium', icon:'alert'});
		t41.view.bindLocal(back, 'click', function() { history.back(); }, this.id);
		container.append(back);
	};
	
	/**
	 * Convert t41 properties into JS fields
	 */
	this.convert = function() {
	
		// walk through all properties
		for (var i in this.obj.props) {
			
			var prop = this.obj.props[i];
			var constraints = prop.params.constraints || {};
			var c = [];
			
			// convert constraints
			for (var j in constraints) {
				c[j] = true;
			}
			
			this.fields[i] = {label:prop.params.label,
							  value:prop.value,
							  type:prop.type,
							  constraints:c
							 };
		}
	};
	
	this.save = function(obj) {
		
		// get js object handling form
		//var form = window[obj.data.caller];

		// remove all error spans
		jQuery('#' + this.id + '_form span.error').remove();
		
		// get form elements in an key/value array
//		var selector = '#' + this.id + '_form .field > :input';
		var elements = [];
		jQuery.map(jQuery(this.formId).serializeArray(), function(n, i){ elements[n['name']] = n['value'];});
		
		var errors = [];
		var formdata = {};
		
		// control elements
		for (var i in this.fields) {
			var field = this.fields[i];
			var value = elements[i];

			if (field.constraints.mandatory && value == "") {
				errors[errors.length] = {msg:'Valeur requise pour le champ "' + field.label + '"',field:i};
			}
			
			if (value && field.type == t41.view.currency) {
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
			formdata['uuid'] = this.obj.uuid;
			if (this.form.params.post_ok) {
				formdata['post_ok'] = this.form.params.post_ok;
			}
			if (this.form.params.post_nok) {
				formdata['post_nok'] = this.form.params.post_nok;
			}			

			// send query to server
			t41.core.call({action:'object/update', data:formdata, callback:jQuery.proxy(this,'retSave')});
		}
	};
	
	
	this.retSave = function(obj) {
		
		if (obj.status == t41.core.status.ok) {
			
			// if a post function has been declared, execute it
			if (this.posts.ok && typeof this.posts.ok == 'function') {
				this.posts.ok.call(obj.data); // pass the server response as context
			}
			var params = this.redirects && this.redirects.redirect_ok ? {defer:true} : {timer:10};
			new t41.view.alert("Sauvegarde effectuée", params);
			if (this.redirects && this.redirects.redirect_ok){
				window.location.href = this.redirects.redirect_ok;
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
	if (this.form.params && this.form.params.buttons != false) this.addButtons(jQuery('#form_actions'));
	this.convert();
	if (form.params) {
		if (form.params.redirect_ok) this.redirects.redirect_ok = form.params.redirect_ok;
	}
};
