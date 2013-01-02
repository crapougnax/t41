if (! window.t41) {
	window.t41 = [];
}
if (window.t41.view) {
	window.t41.view = [];
}

if (! window.t41.view.grid) {
	
	/**
	 * Grid object, display a server-side collection
	 * @param param
	 * @returns {t41.view.grid}
	 */
	window.t41.view.grid = function(param) {

		this.collection = new t41.object.collection(param.obj);
		
		this.callbacks = {remove:null,before:{remove:null},after:{remove:null}};
		
		this.backend;
		
		
		this.setCallback = function(type, func) {
			
			if (type.indexOf('/') != -1) {
				var parts = type.split('/');
				this.callbacks[parts[0]][parts[1]] = func;
			} else {

				this.callbacks[type]= func;
			}
			return this;
		};
		
		
		this.setBackend = function(obj) {
			
			this.backend = obj;
			return this;
		};
		
		
		this.remove = function(mid) {
		
			var mid = jQuery(t41.view.caller).closest('tr').data('member');
			var obj = this.collection.getMember(mid);

			if (obj == false) {
				
				new t41.view.alert("Erreur lors de la récupération du membre associé");
				return false;
			}
			
			var data = {obj:obj, mid:mid, container:jQuery(t41.view.caller).closest('tr')};
			
			if (this.callbacks.before.remove) {
				ret = t41.core.funcCaller(this.callbacks.before.remove, data);
			}
			
			if (this.callbacks.remove) {
				ret = t41.core.funcCaller(this.callbacks.remove, data);
			}
			
			if (this.callbacks.after.remove) {
				ret = t41.core.funcCaller(this.callbacks.after.remove, data);
			}
		};
	};
}
