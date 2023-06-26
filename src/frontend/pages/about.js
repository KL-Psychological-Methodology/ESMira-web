import html from "./about.html"
import {URL_ABOUT_ESMIRA_SOURCE} from "../js/variables/urls";
import {Lang} from "../js/main_classes/lang";
import {get_aboutESMira_json} from "../js/shared/about_esmira";

export function ViewModel(page) {
	this.html = html;
	page.title(Lang.get("about_esmira"));
	
	this.URL_ABOUT_ESMIRA_SOURCE = URL_ABOUT_ESMIRA_SOURCE;
	
	this.promiseBundle = [
		get_aboutESMira_json()
	];
	this.preInit = function(index, {structure, translations}) {
		this.structure = structure;
		this.translations = translations;
	}
}