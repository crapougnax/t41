if (! window.t41) {
	window.t41 = [];
}

if (! window.t41.locale) {
	
	window.t41.locale = {lang:'en'};

	/**
	 * Function that returns the locale version of the given key for the given lang or default
	 * ex: t41.locale.get('yes'), t41.locale.get('no','fr'), t41.locale.get('wd:sunday')
	 * 
	 * @param key
	 * @param lang
	 * @returns string
	 */
	window.t41.locale.get = function(key, params) {
		params = params || {};
		lang = params.lang || t41.locale.lang;
		obj = params.obj || t41.locale[lang];
		vars = params.vars || null;

		if (key.indexOf(':') != -1) {
			
			var parts = key.split(':');
			params.obj = t41.locale[lang][parts[0]];
			return t41.locale.get(parts[1], params);
		}
		
		if (obj && obj[key]) {
			
			if (vars==null) {
				return obj[key];
			} else {
				var str = obj[key];
				for (i in vars) {
					str = str.replace('%'+i, vars[i]);
				}
				return str;
			}
			
		} else if (lang != 'en') {

			params.lang = 'en';
			
			return t41.locale.get(key, params);
			
		}
		
		return key;
	};
	
	//short form: t41.lget('yes'); 
	window.t41.lget = t41.locale.get;
	window.t41.lget.fr = function (k, p) {
		p = p || {};
		p.lang = 'fr';
		return t41.lget(k, p);
	};
	
	// LOCALES

	
	/**
	 * English messages
	 */
	window.t41.locale.en = {
			
		yes:'yes',
		no:'no',
		cancel:'Cancel',
		back:'Back',
		confirm:{
			button:'Confirm',
			title:'Confirmation Request',
			message:"Please confirm the demanded action"
		},
		wd:{ // weekdays
			sunday:'Sunday',
			monday:'Monday',
			tuesday:'Tuesday',
			wednesday:'Wednesday',
			thursday:'Thursday',
			friday:'Friday',
			saturday:'Saturday'
		},
		ac:{ // autocompleter
			noresult:'No result to display',
			oneresult:'One result only',
			manyresults:'%0 results',
			moreresults:'%0 results displayed in a set of %1',
			extend:'More results'
		},
		err:{
			lbl_srv:'Server Error',
			bkd:'Server error, plrease refresh the page'
		}
	};
	
	
	/**
	 * French messages
	 */
	window.t41.locale.fr = {
			
		yes:'oui',
		no:'non',
		cancel:'Annuler',
		back:'Retour',
		confirm:{
			button:'Confirmer',
			title:'Demande de confirmation',
			message:"Veuillez confirmer l'action demandée"
		},
		wd:{
			sunday:'Dimanche',
			monday:'Lundi',
			tuesday:'Mardi',
			wednesday:'Mercredi',
			thursday:'Jeudi',
			friday:'Vendredi',
			saturday:'Samedi'
		},
		ac:{
			noresult:'Aucun résultat à afficher',
			oneresult:'Un seul résultat',
			manyresults:'%0 résultats',
			moreresults:'%0 résultats affichés sur un total de %1',
			extend:'Plus de résultats'
		},
		err:{
			lbl_srv:'Une erreur a été signalée par le serveur',
			bkd:'Erreur serveur, veuillez rafraîchir la page'
		}
	};
}