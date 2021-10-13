import {Lang} from "../main_classes/lang";
import {PromiseCache} from "../main_classes/promise_cache";
import {FILE_ADMIN} from "../variables/urls";
import {OwnMapping} from "../helpers/knockout_own_mapping";
import {Defaults} from "../variables/defaults";

export function load_langConfigs(study, page) {
	return page.loader.showLoader(Lang.get("state_loading"), PromiseCache.loadJson(FILE_ADMIN+"?type=load_langs&study_id="+study.id(), function(langObj) {
		for(let code in langObj) {
			if(langObj.hasOwnProperty(code))
				OwnMapping.add_lang(study, Defaults.studies, langObj[code], code);
		}
	}));
}