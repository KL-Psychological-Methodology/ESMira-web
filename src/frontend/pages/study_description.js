import html from "./study_description.html"
import {Studies} from "../js/main_classes/studies";
import {Lang} from "../js/main_classes/lang";

export function ViewModel(page) {
	this.html = html;
	this.promiseBundle = [Studies.init(page)];
	page.title(Lang.get("study_description"));
	
	this.dataObj = null;
	
	this.preInit = function({id}, studies) {
		this.dataObj = studies[id];
	};
}