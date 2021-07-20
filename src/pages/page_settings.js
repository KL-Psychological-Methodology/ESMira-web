import html from "./page_settings.html"
import {Lang} from "../js/main_classes/lang";
import {Studies} from "../js/main_classes/studies";

export function ViewModel(page) {
	this.html = html;
	page.title(Lang.get("questionnaires_edit"));
	this.promiseBundle = [Studies.init(page)];
	
	this.preInit = function({id, q, page}, studies) {
		this.dataObj = studies[id].questionnaires()[q].pages()[page];
	};
}