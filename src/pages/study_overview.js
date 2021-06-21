import html from "./study_overview.html"
import {Site} from "../js/main_classes/site";
import {Lang} from "../js/main_classes/lang";
import {Studies} from "../js/main_classes/studies";

export function ViewModel(page) {
	this.html = html;
	
	this.promiseBundle = [Studies.init(page)];
	
	this.preInit = function() {
		let study = Studies.get_current();
		if(!study) {
			page.replace("studies,attend");
			this.dataObj = null;
			return;
		}
		
		this.dataObj = study;
		page.title(study.title);
		
		Site.save_access(page, study.id(), !page.depth ? "study" : "navigatedFromHome");
		
		if(!study.publishedWeb())
			page.replace("appInstall");
		else if(study.contactEmail)
			this.extraContent = "<a class=\"small_text\" href=\"mailto:"+study.contactEmail()+"\">"+Lang.get('contactEmail')+"</a>";
	}
}