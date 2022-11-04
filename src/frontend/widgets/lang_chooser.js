import {Studies} from "../js/main_classes/studies";
import {currentLangCode, add_lang, changeLang, loadStudyLangConfigs} from "../js/shared/lang_configs";
import {Site} from "../js/main_classes/site";


export function LangChooser(page, {langObj, enableAdd, onChange, alwaysVisible, hasTitle, langDefaults}) {
	let self = this;
	let obj;
	let languages = {};
	
	if(langObj)
		obj = langObj;
	else {
		obj = Studies.get_current();
		loadStudyLangConfigs(obj, page).then(function(loadedLanguages) {
			languages = loadedLanguages;
		});
	}
	this.langCodes = obj.langCodes;
	this.currentLang = currentLangCode;
	if(this.langCodes.indexOf(currentLangCode) === -1)
		currentLangCode(obj.defaultLang());
	
	
	this.enableAdd = !!enableAdd;
	this.alwaysVisible = !!alwaysVisible;
	this.hasTitle = !!hasTitle;
	
	this.add_lang = add_lang.bind(this, obj, languages);
	
	this.changeLang = function(langCode) {
		changeLang(obj, languages, langCode);
		Site.reload_allPages();
		if(onChange)
			onChange();
	}
}