import html from "./backups.html"
import {Lang} from "../js/main_classes/lang";
import {Admin} from "../js/main_classes/admin";
import {Studies} from "../js/main_classes/studies";

export function ViewModel(page) {
	this.html = html;
	page.title(Lang.get("backup_needed"));
	this.promiseBundle = [Studies.init(page), Admin.init(page)];
	
	this.studies = null;
	
	this.preInit = function(index, studies, admin) {
		this.studies = studies;
	};
}