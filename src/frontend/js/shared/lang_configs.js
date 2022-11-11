import {Lang} from "../main_classes/lang";
import {PromiseCache} from "../main_classes/promise_cache";
import {FILE_ADMIN} from "../variables/urls";
import {OwnMapping} from "../helpers/knockout_own_mapping";
import {Defaults} from "../variables/defaults";
import ko from "knockout";
import {Studies} from "../main_classes/studies";
import {Site} from "../main_classes/site";

export let currentLangCode = "unnamed";

export function loadStudyLangConfigs(study, page) {
	// Rationale:
	// We detect changes in study (for general structure changes) and languages (for translation changes)
	// Default language will be empty, but that is ok, because its entries will be added from study which has subscriptions
	// When structure changes: the new entries in language won't be subscribed to. But isDirty will be true either way because the structure changed
	// When a language is added: it won't be subscribed to. So we need to add it
	
	return page.loader.showLoader(Lang.get("state_loading"), PromiseCache.loadJson(FILE_ADMIN+"?type=load_langs&study_id="+study.id(), function(langObj) {
		let translations = {};
		translations[study.defaultLang()] = {}; // will be filled as soon as we switch languages
		
		let detector = Studies.tools.getStudyChangedDetector(study.id());
		for(let code in langObj) {
			if(langObj.hasOwnProperty(code)) {
				let lang = OwnMapping.bindNewLanguageContainer(study, langObj[code]);
				translations[code] = lang;
				detector.addMonitored(lang);
			}
		}
		translations.__detector = detector;
		return translations;
	}));
}

export function changeLang(obs, translations, toLangCode) {
	if(!translations.hasOwnProperty(toLangCode))
		return;
	OwnMapping.switchLanguage(obs, translations[currentLangCode], translations[toLangCode]);
	translations.__detector.reload();
	currentLangCode = toLangCode;
}

export function addLang(obs, translations, langCode) {
	if(translations.hasOwnProperty(langCode))
		return;
	let lang = OwnMapping.bindNewLanguageContainer(obs);
	translations[langCode] = lang;
	obs.langCodes.push(ko.observable(langCode));
	
	translations.__detector.addMonitored(lang); //technically, we don't need this because when we add a new language, detector should be dirty anyway.
}