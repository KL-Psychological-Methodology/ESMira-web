import html from "./consent.html"
import {Studies} from "../js/main_classes/studies";
import {Lang} from "../js/main_classes/lang";
import {save_cookie} from "../js/helpers/basics";

export function ViewModel(page) {
	this.html = html;
	this.promiseBundle = [Studies.init(page)];
	page.title(Lang.get("informed_consent"));
	
	this.dataObj = null;
	
	let study;
	this.preInit = function() {
		study = Studies.get_current();
		if(!study) {
			page.replace("studies,consent");
			this.dataObj = null;
			return;
		}
		this.dataObj = study;
	};
	
	this.saveConsent = function() {
		let id = study.id();
		save_cookie('informed_consent'+id, 1);
		page.replace('attend');
	};
}