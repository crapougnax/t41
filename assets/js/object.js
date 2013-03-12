if (! window.t41) {
	window.t41 = [];
}

if (! window.t41.object) {

	
	/**
	 * Returns a new instance of a t41.object.base object
	 * @param data
	 * @returns {t41.object.base}
	 * @deprecated use t41.object.factory
	 */
	window.t41.object = function(data) {
		var obj = new t41.object.base();
		if (data) {
			obj.uuid = data.uuid;
			obj.data = new t41.object.data(data.props);
			obj.params = t41.core.setParameters(data.params);
		}
		return obj;
	};

	
	/**
	 * Returns a new instance of a t41.object.base object
	 * @param data
	 * @returns {t41.object.base}
	 */	
	window.t41.object.factory = function(data) {
		
		var obj = new t41.object.base();
		if (data) {
			obj.uuid = data.uuid;
			obj.data = new t41.object.data(data.props);
			obj.params = t41.core.setParameters(data.params);
		}
		return obj;		
	};
	
	
	/**
	 * Simple ObjectModel\DataObject representation
	 * @param properties
	 * @returns {window.t41.object.data}
	 */
	window.t41.object.data = function(properties) {
	
		this.get = function(key,formatted) {
		
			try {
				return this.properties[key].get(formatted);
			} catch (e) {
				console.log('undefined property ' + key);
			}
		};
		
		this.getProperty = function(key) {
			try {
				return this.properties[key];
			} catch (e) {
				console.log('undefined property ' + key);
			}
		};
	
		this.set = function(key,val) {
		
			this.properties[key].val = val;
			return this;
		};
		
		this.setProperties = function(data) {
		
			this.properties = {};
			for (var i in data) {
			
				//console.log(data[i]);
				this.properties[i] = new t41.object.data.property(data[i].type, data[i].uuid ? data[i] : data[i].value);
				
				// register object display value
				if (data[i].uuid && typeof data[i].value == 'string') this.properties[i].display = data[i].value;
				
				// @todo add values, contraints, etc.
			}
		};
		
		this.toArray = function(selected) {
			
			var array = {};
			for (var i in this.properties) {
				if (selected && selected.indexOf(i) == -1) continue;
				array[i] = this.properties[i].val;
			}
			
			return array;
		};
		
		// constructor
		this.setProperties(properties);
	};
	

	/**
	 * Property handler
	 */
	window.t41.object.data.property = function(type, val) {
		
		
		// store display value (for objects)
		this.display;
		
		this.get = function(formatted) {
			
			switch (this.type) {
			
				case 'Object':
					return this.display || this.val;
					break;
					
				case 'Currency':
					return !formatted || formatted == false ? this.val : t41.view.format(this.val,'Currency');
					break;
					
				case 'Date':
					return !formatted || formatted == false ? this.val : t41.view.format(this.val,'Date');
					break;
					
				default:
					return this.val;
					break;
			}
		};
		
		
		this.set = function(val) {
			
			switch (this.type) {
			
				case 'Object':
					//console.log(val);
					if (val && val.value && val.value.props && ! val.props) {
						val.props = val.value.props;
						val.value = null;
					}
					val = new t41.object(val);
					break;
				
				case 'Collection':
					var c = new t41.object.collection();
					c.populate(val);
					val = c;
					break;
			}
			
			this.val = val;
			return this;
		};
		
		
		// constructor
		this.type = type;
		this.set(val);
	};
	
	
	/* base object instance */
	window.t41.object.base = function () {
		
		this.data,this.params,this.uuid;
		
		this.get = function(key, formatted) {
			return this.data && typeof this.data.get(key) != 'undefined' ? this.data.get(key, formatted): null;
		};
		
		this.getProperty = function(key) {
			return this.data && this.data.getProperty(key) ? this.data.getProperty(key): null;
		};
		
		this.set = function(key,val) {
			return this.data.set(key,val);
		};
		
		this.save = function() {
			t41.core.ajax(t41.core.backend + '/object/save', {uuid:this.uuid, data:this.data.toArray()});
		};
	};
	
	
	/**
	 * This object represents a t41\ObjectModel\Collection
	 * @param param may be either a reduced collection or an uuid 
	 * @returns {t41.object.collection}
	 */
	window.t41.object.collection = function(param) {
		
		this.members = {};
		
		/**
		 * Execute a query on backend
		 */
		this.find = function() {};
		
		
		this.getMember = function(id) {
			
			return this.members[id] || false;
		};
		
		
		/**
		 * Populate collection with given json-originated object
		 */
		this.populate = function(obj) {
			var c = {};
			jQuery.each(obj, function(i,o) { c[i] = new t41.object(o); });
			this.members = c;
		};
		
		// instanciation
		if (typeof param == 'object') {
			this.populate(param['collection']);
			this.uuid = param.uuid;
			
		} else {
			this.uuid = param;
		}
	};
}
