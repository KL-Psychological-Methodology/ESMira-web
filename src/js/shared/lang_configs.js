import {Lang} from "../main_classes/lang";
import {PromiseCache} from "../main_classes/promise_cache";
import {FILE_ADMIN} from "../variables/urls";
import {OwnMapping} from "../helpers/knockout_own_mapping";
import {Defaults} from "../variables/defaults";
import ko from "knockout";
import {Studies} from "../main_classes/studies";

export function load_langConfigs(study, page) {
	return page.loader.showLoader(Lang.get("state_loading"), PromiseCache.loadJson(FILE_ADMIN+"?type=load_langs&study_id="+study.id(), function(langObj) {
		for(let code in langObj) {
			if(langObj.hasOwnProperty(code))
				OwnMapping.add_lang(study, Defaults.studies, langObj[code], code);
		}
	}));
}

export function add_lang(obj, defaults = Defaults.studies) {
	let code = prompt(Lang.get("prompt_languageCode"));
	if(!code)
		return;
	let langObj = OwnMapping.toLangJs(obj, "_");
	OwnMapping.add_lang(obj, defaults, langObj, code);
	obj.langCodes.push(ko.observable(code));
	Studies.tools.currentLang(code);
}