export const Lang = {
	_vars: {},
	code: "error",
	init: function(obj, langCode) {
		this._vars = obj;
		this.code = langCode;
	},
	get: function(key, ... replacers) {
		if(!Lang._vars.hasOwnProperty(key))
			return "Not translated";
		
		let s = Lang._vars[key];
		if(replacers.length) {
			for(let i=0, max=replacers.length; i<max; ++i) {
				let replace = replacers[i];
				let search;
				switch(typeof replace) {
					case "number":
						search = "%d";
						break;
					case "string":
						search = "%s";
						break;
				}
				s = s.replace(search, replacers[i]);
			}
			return s;
		}
		else
			return s;
	}
}