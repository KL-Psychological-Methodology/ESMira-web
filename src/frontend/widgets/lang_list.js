import {Studies} from "../js/main_classes/studies";
import {addLang, changeLang, currentLangCode, loadStudyLangConfigs} from "../js/shared/lang_configs";
import {Site} from "../js/main_classes/site";
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";


export function LangList(page, {langObj, translations}) {
	let self = this;
	let obj;
	this.langCodeNames = ko.observableArray([]);
	
	import("../lang_codes/" + Lang.code + ".json").then(function(langCollection) {
		let list = [];
		for(let code in langCollection) {
			list.push({name: langCollection[code], langCode: code})
		}
		self.langCodeNames(list)
	});
	if(langObj)
		obj = langObj;
	else {
		obj = Studies.get_current();
		loadStudyLangConfigs(obj, page).then(function(loadedLanguages) {
			translations = loadedLanguages;
		});
	}
	this.langCodes = obj.langCodes;
	this.defaultLang = obj.defaultLang
	
	this.langChanged = function(langCode, e) {
		let el = e.target;
		let valueBefore = el.getAttribute("value-before");
		
		translations[langCode] = translations[valueBefore];
		delete translations[valueBefore];
		
		if(currentLangCode === valueBefore) {
			changeLang(obj, translations, langCode);
			Site.reload_allPages();
		}
		
		if(obj.defaultLang() === valueBefore)
			obj.defaultLang(langCode);
	}
	
	this.addLang = function(_, e) {
		let el = e.target;
		addLang(obj, translations, el.value);
		el.selectedIndex = 0;
	};
	
	this.deleteLang = function(langCode) {
		if(obj.langCodes().length <= 1)
			return;
		let index = obj.langCodes.indexOf(langCode);
		obj.langCodes.splice(index, 1);
		delete translations[langCode]; //this will leave some subscriptions dangling. But we don't care
		
		if(langCode === obj.defaultLang())
			obj.defaultLang(obj.langCodes()[0]());
		
		console.log(langCode, currentLangCode , obj.defaultLang());
		if(currentLangCode === langCode) {
			changeLang(obj, translations, obj.langCodes()[0]());
			Site.reload_allPages();
		}
	};
	
	this.setDefault = function(langCode) {
		console.log(langCode)
		obj.defaultLang(langCode)
	};
}