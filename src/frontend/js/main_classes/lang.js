import {Requests} from "./requests";
import {Site} from "./site";

export const Lang = {
	_promise: Promise.resolve(),
	_vars: {},
	_fallback: {
		state_loading: "Loading…"
	},
	code: "error",
	isInit: false,
	init: function(langCode) {
		let self = this;
		this.code = langCode;
		this._promise = Requests.load("locales/" + langCode + ".json?v="+PACKAGE_VERSION, true).then(function(obj) {
			self._vars = JSON.parse(obj);
			Site.init_lang();
			self.isInit = true;
		});
	},
	awaitPromise: function() {
		return this._promise;
	},
	get: function(key, ... replacers) {
		if(!Lang._vars.hasOwnProperty(key)) {
			 return Lang._fallback.hasOwnProperty(key) ? Lang._fallback[key] : key;
		}
		
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