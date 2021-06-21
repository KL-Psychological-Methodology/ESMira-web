import {PromiseCache} from "../main_classes/promise_cache";
import {Requests} from "../main_classes/requests";
import {URL_ABOUT_ESMIRA_JSON} from "../variables/urls";
import {Lang} from "../main_classes/lang";

export function get_aboutESMira_json() {
	let url = URL_ABOUT_ESMIRA_JSON.replace("%s", Lang.code);
	
	return PromiseCache.loadText(url, function(response) {
		return JSON.parse(response);
	}).catch(function() {
		let promise = Requests.load(URL_ABOUT_ESMIRA_JSON.replace("%s", "en"), true).then(function(response) {
			return JSON.parse(response);
		});
		
		return PromiseCache.save(url, promise);
	});
}