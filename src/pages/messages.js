import html from "./messages.html"
import {Site} from "../js/main_classes/site";
import {Studies} from "../js/main_classes/studies";
import {reloadMessages} from "../js/shared/messages";

export function ViewModel(page) {
	this.html = html;
	this.promiseBundle = [Studies.init(page), reloadMessages(Site.valueIndex.id)];
	
	this.userWithMessages = null;
	
	this.preInit = function({id}, studies, messages) {
		page.title(studies[id].title);
		
		this.userWithMessages = messages;
	};
}