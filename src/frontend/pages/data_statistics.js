import html from "./data_statistics.html"
import {Admin} from "../js/main_classes/admin";
import {Studies} from "../js/main_classes/studies";

export function ViewModel(page) {
	this.html = html;
	
	this.promiseBundle = [Studies.init(page), Admin.init(page)];
	
	this.preInit = function({id}, studies) {
		page.title(studies[id].title);
		this.dataObj = studies[id];
	};
}