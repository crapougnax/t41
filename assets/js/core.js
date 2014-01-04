/* hack intented at IE8 and lowers lack of console */
window.console = window.console || {log:function(){}};


if (! window.t41) { window.t41 = []; }

if (! window.t41.core) {

(function() {
	
	window.t41.core = {version:'0.1.0', backend:'/rest', transparent:false, enableGA:false, cookies:false, none:'_NONE_'};
	window.t41.core.status = {ok:'OK',nok:'NOK',err:'ERR'};

	/* t41 libraries declaration */
	window.t41.core.libs = {view:{depends:['google.jquery']},objectmodel:{}};
	
	window.t41.core.store = {actions:{}};
	
	/**
	 * Library loader
	 * @param moduleName
	 * @param moduleVersion
	 * @param options { version: lib version, namespace: lib namespace (default: t41)
	 * @returns {Boolean}
	 */
	window.t41.core.loader = function(moduleName, options) {

		var isUrl = (moduleName.substring(0,4) == 'http');
		var version = options && options.version ? options.version : null;
		var namespace = options && options.namespace ? options.namespace : 't41';
		
		if (moduleName.indexOf('.') != -1 && ! isUrl) {
			
			var parts = moduleName.split('.');
			switch (parts[0]) {
			
				case 'google':
				
					//load google lib
					if (! window['google']) {
						t41.core.loader('https://www.google.com/jsapi');
					}
				
					// trigger google.load();
					if (window['google'] && window['google']['load']) {
						try {
							google.load(parts[1], version, options);
						} catch (e) {
							alert("Google Loader Error: " + e);
							return false;
						}
					}
					break;
					
				case 'app':
					t41.core.loader(parts[1], {namespace:parts[0]});
					break;
			}
		} else {
			
			// t41 loader
			var script= document.createElement('script');
			script.setAttribute('type','text/javascript');
			var path = isUrl ? moduleName : '/js/' + namespace + '/' + moduleName + '.js';
			script.setAttribute('src', path);
			document.getElementsByTagName('head')[0].appendChild(script);
		}
	};
	
	
	window.t41.core.dump = function(obj, level) {
		
		var max = 10;
	    var level = level || 0;
		if (level > max) return;
		
		if (typeof obj != 'object') {
			console.log('"' + obj + '" is not an object');
			return false;
		}
		
	    var out = '';
	    for (var i in obj) {
	    	
	    	if (typeof obj[i] == 'function') continue;
	    	
	    	for (var a = 0 ; a < level ; a++) out += "\t";
	    	
	    	if (typeof obj[i] == 'object' || typeof obj[i] == 'array') {
	    		
	    		out += i + ":\n" + t41.core.dump(obj[i], level + 1);
	    	} else {
	    		
	    		out += i + ": " + obj[i];
	    	}
	    	
	    	out += "\n";
	    }
	    if (level == 0) {
	    	console.log(out);
	    } else {
	    	return out;
	    }
	};
	
	
	/**
	 * call: execute an ajax call
	 * 
	 * params:
	 * - context:	DOM element to which the call is related
	 * - action:	remote controller/action to call in /rest module
	 * - callback:	optional callback function to execute upon success/completion
	 * - data:		array of contextual data provided to the callback
	 */
	window.t41.core.call = function(p) {

		var url = '/rest/' + p.action;
		
		// call google analytics (@todo add preference)
		if (t41.core.enableGA == true && window._gaq) {
			_gaq.push(['_trackEvent', 'Ajax', p.action]);
			_gaq.push(['_trackPageview', url]);
		} else {
			//console.log('Google Analytics is not loaded');
		}
		
		
		// send ajax request to server
		t41.core.ajax({url:url,
				 	 data:p.data,
				 	 success:p.callback ? p.callback : arguments.callee.caller,
				 	 error:t41.core.defaultCallback,
				 	 method:p.method || 'post',
				 	 context:p.context ? p.context : null
				 	}); 
	};
	
	
	window.t41.core.ajaxSetup = function(value) {
		
		var url = value ? value.url ? value.url : t41.core.backend : t41.core.backend;
		var method = value.method || 'POST';
		jQuery.ajaxSetup({
							url:url, 
							global:false, 
							cache:true, 
							type:method,
							error:t41.core.defaultCallback,
							dataType:'json',
							 statusCode: {
								 404: function() {
								      alert(t41.lget('err:lbl_srv'));
								    },
								 500: function() {
								      alert(t41.lget('err:lbl_srv'));
								 }
							}
						});
	};

	
	window.t41.core.ajax = function(value) {
		try {
			t41.core.ajaxSetup(value);
			jQuery.ajax(value);
		} catch (e) {
			new t41.view.alert(e.message, {level:'error'});
		}
	};

	
	window.t41.core.defaultCallback = function(data) {

		switch (data.status) {
		
			case t41.core.status.ok:
				if (data.context.redirect) {
				//	console.log(data.context.redirect);
					document.location = data.context.redirect;
				} else {
					
					new t41.view.alert(data.context.message, {level:'info',timer:5});
				}
				break;
				
			case t41.core.status.nok:
				new t41.view.alert(data.context.message, {level:'warning'});
				break;
				
			case t41.core.status.err:
				new t41.view.alert(data.context.err, {level:'error'});
				break;
		}
	};
	
	
	window.t41.core.parameter = function(type,val) {
	
		this.type = type;
		this.val = val;
	};
	
	
	window.t41.core.setParameters = function(obj) {
		
		var params = {};
		for (var i in obj) {
			
			params[i] = new t41.core.parameter(obj[i].type,obj[i].val);
		}
		
		return params;
	};
	
	
	/**
	 * Function caller
	 * 
	 * @param funcName Function name
	 * @param param function parameter
	 */
	window.t41.core.funcCaller = function(funcName, param) {
		
		var context = window;
		var ns = funcName.split('.');
		
		for (var i in ns) {
			context = context[ns[i]] || {};
		}

		if (typeof context == 'function') {

			return context(param);
		} else {
			
			throw new exception(funcName + ' is not a function');
		}
	};
	
	
	/**
	 * Simple redirect function
	 */
	window.t41.core.redirect = function(url) {
		window.location.href = url;
	};
	
	
	/**
	 * Simple cookie generator
	 */
	window.t41.core.setCookie = function(name, value, ttl) {
		
		var exdate=new Date();
		exdate.setDate(exdate.getDate() + ttl);
		var c_value = escape(value) + '; expires=' + exdate.toUTCString() + '; path=/';
		document.cookie = name + "=" + c_value;
		if (t41.core.cookies != false) t41.core.cookies[name] = value;
	};
	
	
	window.t41.core.getCookie = function(name) {

		if (t41.core.cookies == false) {

			t41.core.cookies = {};
			var i,x,y,ARRcookies=document.cookie.split(";");
			for (i = 0 ; i < ARRcookies.length ; i++) {
				x = ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
				y = ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
				x = x.replace(/^\s+|\s+$/g,"");
				
				t41.core.cookies[x] = unescape(y);
			}
		}
		
		return t41.core.cookies[name] ? t41.core.cookies[name] : false;
	};
	
	
	window.t41.core.removeCookie = function(name) {

		t41.core.setCookie(name, '', -1);
		if (t41.core.cookies[name]) t41.core.cookies[name] = false;
	};
	
	
	/**
	 * Returns a YYYY-MM-DD HH:MM:SS formatted current date
	 */
	window.t41.core.date = function() {
		
		this.pad = function(n) {
			return n < 10 ? '0' + n : n;
		};
		  
		var date = new Date();

		return date.getUTCFullYear() + '-'
			+ this.pad(date.getUTCMonth()+1) + '-'
		    + this.pad(date.getUTCDate()) + ' '
		    + this.pad(date.getUTCHours()) + ':'
		    + this.pad(date.getUTCMinutes()) + ':'
		    + this.pad(date.getUTCSeconds());
	};

	
	
//	t41.core.loader('google.jsapi');
})();
}
