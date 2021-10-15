import * as ko from "knockout";
import {Lang} from "../js/main_classes/lang";
import {PromiseCache} from "../js/main_classes/promise_cache";
import {FILE_ADMIN} from "../js/variables/urls";
import {OwnMapping} from "../js/helpers/knockout_own_mapping";
import {Defaults} from "../js/variables/defaults";
import {Site} from "../js/main_classes/site";
import {Studies} from "../js/main_classes/studies";
import {add_lang, load_langConfigs} from "../js/shared/lang_configs";

export function LangOptions(page, {enableAdd, onChange, alwaysVisible, hasTitle}) {
	let self = this;
	let study = Studies.get_current();
	this.langCodes = study.langCodes;
	this.enableAdd = !!enableAdd;
	this.alwaysVisible = !!alwaysVisible;
	this.hasTitle = !!hasTitle;
	
	load_langConfigs(study, page);
	
	this.add_lang = add_lang.bind(this, study);
	
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