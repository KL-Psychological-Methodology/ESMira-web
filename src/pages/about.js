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
	this.preInit = function(index, {page_about, contact_text, about_text, file_googleStore, file_appleStore, use_for_own_studies, esmira_own_server_get_started, repository_link}) {
		this.page_about = page_about;
		this.contact_text = contact_text;
		this.about_text = about_text;
		this.file_googleStore = file_googleStore;
		this.file_appleStore = file_appleStore;
		this.use_for_own_studies = use_for_own_studies;
		this.esmira_own_server_get_started = esmira_own_server_get_started;
		this.repository_link = repository_link;
	}
}