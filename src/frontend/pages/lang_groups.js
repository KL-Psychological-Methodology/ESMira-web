import html from "./lang_groups.html"
import {Studies} from "../js/main_classes/studies";
import {Lang} from "../js/main_classes/lang";

export function ViewModel(page) {
	this.html = html;
	this.promiseBundle = [
		Studies.init(page)
	];
	page.title(Lang.get("study_settings"));
	
	this.preInit = function({id}, studies) {
		this.dataObj = studies[id];
	};
}