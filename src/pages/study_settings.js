import html from "./study_settings.html"
import {Studies} from "../js/main_classes/studies";
import {Lang} from "../js/main_classes/lang";
import {FILE_ADMIN} from "../js/variables/urls";
import ko from "knockout";
import {Site} from "../js/main_classes/site";
import {load_langConfigs} from "../js/shared/lang_configs";
import {OwnMapping} from "../js/helpers/knockout_own_mapping";
import {Defaults} from "../js/variables/defaults";

export function ViewModel(page) {
	let self = this;
	let id = Site.valueIndex.id;
	this.html = html;
	this.promiseBundle = [
		Studies.init(page),
		page.loader.loadRequest(FILE_ADMIN + "?type=is_frozen&study_id="+id).then(function(frozen) {
			self.locked_enabled(frozen);
		}),
	];
	page.title(Lang.get("study_settings"));
	this.locked_enabled = ko.observable(false);
	
	let study;
	this.preInit = function({id}, studies) {
		study = studies[id];
		this.dataObj = study
		load_langConfigs(study, page);
		
		this.locked_enabled.subscribe(function() {
			page.loader.loadRequest(FILE_ADMIN + "?type=freeze_study" + (self.locked_enabled() ? "&frozen" : "") + "&study_id="+id).then(function(frozen) {
				self.locked_enabled(frozen);
				page.loader.info(frozen ? Lang.get("info_study_frozen") : Lang.get("info_study_unfrozen"));
			});
		});
	};
	
	
	this.add_lang = function() {
		let code = prompt(Lang.get("prompt_languageCode"));
		if(!code)
			return;
		let langObj = OwnMapping.toLangJs(study, "_");
		OwnMapping.add_lang(study, Defaults.studies, langObj, code);
		study.langCodes.push(ko.observable(code));
	}
	
	this.delete_lang = function(code) {
		let index = study.langCodes.indexOf(code);
		study.langCodes.splice(index, 1);
	}
}