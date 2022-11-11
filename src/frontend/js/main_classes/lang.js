import {Requests} from "./requests";
import {Site} from "./site";
import {PromiseCache} from "./promise_cache";

const localFallback = {
	state_loading: "Loading…"
};
let vars = {};
let fallback = {};
let promise = Promise.resolve();

export const Lang = {
	code: "error",
	isInit: false,
	init: function(langCode) {
		let self = this;
		this.code = langCode;
		
		if(langCode === "en") {
			promise = Promise.all([
				Requests.load("locales/en.json?v="+PACKAGE_VERSION, true)
			]).then(function(langJson) {
				fallback = vars = JSON.parse(langJson);
			});
		}
		else {
			promise = Promise.all([
				Requests.load("locales/" + langCode + ".json?v="+PACKAGE_VERSION, true),
				Requests.load("locales/en.json?v="+PACKAGE_VERSION, true)
			]).then(function([langJson, fallbackJson]) {
				vars = JSON.parse(langJson);
				fallback = JSON.parse(fallbackJson);
			});
		}
		promise.then(function() {
			Site.init_lang();
			self.isInit = true;
		});
	},
	awaitPromise: function() {
		return promise;
	},
	get: function(key, ... replacers) {
		let s;
		if(vars.hasOwnProperty(key))
			s = vars[key];
		else if(fallback.hasOwnProperty(key))
			s = fallback[key];
		else
			return localFallback.hasOwnProperty(key) ? localFallback[key] : key;
		
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