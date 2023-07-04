import {PromiseCache} from "../main_classes/promise_cache";
import {URL_ABOUT_ESMIRA_JSON, URL_ABOUT_ESMIRA_STRUCTURE_JSON} from "../variables/urls";
import {Lang} from "../main_classes/lang";

export function get_aboutESMira_json() {
	let langUrl = URL_ABOUT_ESMIRA_JSON.replace("%s", Lang.code);
	let fallbackLangUrl = URL_ABOUT_ESMIRA_JSON.replace("%s", "en");
	
	return PromiseCache.loadText(URL_ABOUT_ESMIRA_STRUCTURE_JSON, function(structure) {
		return PromiseCache.loadText(langUrl, function(lang) {
			return {
				structure: JSON.parse(structure),
				translations: JSON.parse(lang)
			};
		}).catch(function() {
			return PromiseCache.loadText(fallbackLangUrl, function(lang) {
				return {
					structure: JSON.parse(structure),
					translations: JSON.parse(lang)
				};
			})
		});
	})
}