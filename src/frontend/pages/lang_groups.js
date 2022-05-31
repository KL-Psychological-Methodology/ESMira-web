import html from "./lang_groups.html"
import {Studies} from "../js/main_classes/studies";
import {Lang} from "../js/main_classes/lang";
import {FILE_ADMIN} from "../js/variables/urls";
import ko from "knockout";
import {Site} from "../js/main_classes/site";
import {load_langConfigs, add_lang} from "../js/shared/lang_configs";
import {Defaults} from "../js/variables/defaults";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	this.promiseBundle = [
		Studies.init(page)
	];
	page.title(Lang.get("study_settings"));
	
	let study;
	this.preInit = function({id}, studies) {
		study = studies[id];
		this.dataObj = study;
		this.add_lang = add_lang.bind(this, study, Defaults.studies);
		load_langConfigs(study, page);
	};
	
	
	
	this.delete_lang = function(code) {
		let index = study.langCodes.indexOf(code);
		study.langCodes.splice(index, 1);
	}
}