import {Studies} from "../js/main_classes/studies";
import {currentLangCode, add_lang, changeLang, loadStudyLangConfigs} from "../js/shared/lang_configs";
import {Site} from "../js/main_classes/site";
import {Lang} from "../js/main_classes/lang";
import ko from 'knockout';
import {Requests} from "../js/main_classes/requests";
import langCodesNames from "../js/langCodes.json";


export function LangList(page, {langObj}) {
	let self = this;
	let obj;
	let languages = {};
	
	this.langCodesNames = langCodesNames
	
	
	if(langObj)
		obj = langObj;
	else {
		obj = Studies.get_current();
		loadStudyLangConfigs(obj, page).then(function(loadedLanguages) {
			languages = loadedLanguages;
		});
	}
	this.langCodes = obj.langCodes;
	this.defaultLang = obj.defaultLang
	
	this.addLang = add_lang.bind(this, obj, languages);
	
	this.deleteLang = function(langCode) {
		let index = obj.langCodes.indexOf(langCode);
		obj.langCodes.splice(index, 1);
	}
	
	this.setDefault = function(langCode) {
		console.log(langCode)
		obj.defaultLang(langCode)
	}
}