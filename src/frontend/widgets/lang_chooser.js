import {Studies} from "../js/main_classes/studies";
import {currentLangCode, changeLang, loadStudyLangConfigs, setCurrentLangCodeSilently} from "../js/shared/lang_configs";
import {Site} from "../js/main_classes/site";
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";


export function LangChooser(page, {langObj, translations, onChange, hasTitle}) {
	let self = this;
	let obj;
	let promise;
	if(langObj) {
		obj = langObj;
		promise = Promise.resolve();
	}
	else {
		obj = Studies.get_current();
		promise = loadStudyLangConfigs(obj, page).then(function(loadedLanguages) {
			translations = loadedLanguages;
		});
	}
	this.langCodes = obj.langCodes;
	this.currentLang = currentLangCode;
	this.langNames = ko.observable({});
	
	import("../lang_codes/" + Lang.code + ".json").then(function(langNames) {
		self.langNames(langNames);
	});
	
	this.hasTitle = !!hasTitle;
	
	this.changeLang = function(langCode) {
		changeLang(obj, translations, langCode);
		Site.reload_allPages();
		if(onChange)
			onChange();
	}
	promise.then(function() {
		if(self.langCodes().length && self.langCodes.indexOf(currentLangCode) === -1) {
			if(self.langCodes.indexOf(obj.defaultLang()) !== -1)
				self.changeLang(obj.defaultLang());
			else
				self.changeLang(self.langCodes[0]);
		}
	});
}