import {Studies} from "../js/main_classes/studies";
import {add_lang, load_langConfigs} from "../js/shared/lang_configs";

export function LangOptions(page, {langObj, enableAdd, onChange, alwaysVisible, hasTitle, langDefaults}) {
	let self = this;
	let obj;
	
	if(langObj)
		obj = langObj;
	else {
		obj = Studies.get_current();
		load_langConfigs(obj, page);
	}
	this.langCodes = obj.langCodes;
	
	this.enableAdd = !!enableAdd;
	this.alwaysVisible = !!alwaysVisible;
	this.hasTitle = !!hasTitle;
	
	this.add_lang = add_lang.bind(this, obj, langDefaults);
	
	this.changeLang = function(index) {
		index = parseInt(index);
		if(index === -1)
			Studies.tools.currentLang("_");
		else
			Studies.tools.currentLang(self.langCodes()[index]());
		if(onChange)
			onChange();
	}
}