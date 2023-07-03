import {Site} from "../main_classes/site";
import {OwnMapping} from "../helpers/knockout_own_mapping";
import {safe_confirm} from "../helpers/basics";
import {FILE_ADMIN} from "../variables/urls";
import ko from "knockout";
import {Requests} from "../main_classes/requests";
import {Admin} from "../main_classes/admin";
import {Lang} from "../main_classes/lang";
import {Studies} from "../main_classes/studies";
import {Defaults} from "../variables/defaults";
import {add_default} from "../helpers/list_functions";
import "../../css/style_admin.css";
import {DetectChange} from "../main_classes/detect_change";
import {NavigationRow} from "../main_classes/navigation_row";
import {PromiseCache} from "../main_classes/promise_cache";
import {changeLang, currentLangCode, loadStudyLangConfigs} from "../shared/lang_configs";


function clone(obj) {
	return JSON.parse(JSON.stringify(obj));
}

export const Studies_tools = {
	changed_state: {},
	newMessages: ko.observable(),
	lastChanged: {},
	
	init: function(page) {
		ko.options.deferUpdates = true;
		let studies = Studies.list();
		for(let i = studies.length - 1; i >= 0; --i) {
			let study = studies[i];
			this.initStudy(study);
		}
		
		let study_id = Site.valueIndex.id;
		
		if(study_id !== undefined)
			Studies.init(page).then(this.change_observedStudy.bind(this, (study_id)));
	},
	initStudy: function(study) {
		// At this point, langauges are not loaded yet.
		// They will be loaded as soon as the user opens the study.
		let self = this;
		let studyId = study.id();
		let detector = new DetectChange(study);
		if(this.changed_state[studyId])
			this.changed_state[studyId].destroy();
			
		this.changed_state[studyId] = detector;
		
		detector.isDirty.subscribe(function(newValue) {
			if(newValue && study.version()) {
				let localLastChanged = self.lastChanged[studyId] || Admin.tools.loginTime;
				Requests.load(
					FILE_ADMIN+"?type=check_changed&study_id="+studyId+"&lastChanged="+localLastChanged
				).then(function({lastChanged, json}) {
					if(lastChanged > localLastChanged) {
						self.lastChanged[studyId] = lastChanged;
						OwnMapping.update(Studies.list()[studyId], json, Defaults.studies);
						detector.setDirty(false);
						alert(Lang.get("error_study_was_changed", study.title()));
					}
				});
			}
		});
	},
	
	add_study: function(page, study) {
		let self = this;
		
		page.loader.showLoader(Lang.get("state_loading"),
			Promise.all([
				Requests.load(FILE_ADMIN+"?type=get_new_id&for=study&study_id=-1"),
				Studies.init(page)
			])
			.then(function([id]) {
				let new_study;
				if(study)
					new_study = OwnMapping.toJS(study);
				else {
					new_study = clone(Defaults.studies);
					new_study.title = Lang.get("default_study_name");
				}
				
				new_study.id = id;
				new_study.serverVersion = Site.serverVersion;
				new_study.packageVersion = PACKAGE_VERSION;
				new_study.version = 0;
				new_study.subVersion = 0;
				new_study.published = false;
				new_study.title = prompt(Lang.get("prompt_studyName"), new_study.title);
				
				if(new_study.title == null || new_study.title.length < 3 || !isNaN(new_study.title))
					return;
				
				let new_study_mapped = OwnMapping.fromJS(new_study, Defaults.studies);
				//dirty-state:
				self.initStudy(new_study_mapped);
				self.changed_state[id].setDirty(true);
				
				Studies.list.push(new_study_mapped);
				
				Site.goto("admin/studies,edit/studyEdit,id:"+id, 0);
			}));
	},
	
	update_study: function(studyObj, updates) {
		OwnMapping.update(studyObj, updates, Defaults.studies);
		// this.initStudy(studyObj);
		// this.change_observedStudy(studyObj.id());
		// this.set_study_changedState(studyObj, true);
	},
	
	add_questionnaire: function(page, study, pageCode) {
		let questionnaires = study.questionnaires();
		
		let filtered = [];
		for(let i=questionnaires.length-1; i>=0; --i) {
			filtered.push(questionnaires[i].internalId());
		}
		
		return page.loader.showLoader(Lang.get("state_loading"),
			Requests.load(FILE_ADMIN+"?type=get_new_id&for=questionnaire&study_id="+study.id(), false, "post", JSON.stringify(filtered)).then(function(internalId) {
				let newQuestionnaire = add_default(study.questionnaires, "questionnaires");
				newQuestionnaire.internalId(internalId);
				
				if(pageCode)
					Site.add_page(pageCode.replace("%", study.questionnaires().length-1), page.depth);
				return newQuestionnaire;
			}));
	},
	save_study: function() {//this will publish the study for all new participants
		let self = this;
		if(!Admin.is_loggedIn())
			return;
		
		let study = Studies.get_current();
		let studyId = study.id();
		let page = Site.get_lastPage();
		
		let currentLang = currentLangCode;
		let translations;
		let defaultLang = study.defaultLang();
		
		page.loader.showLoader(
			Lang.get("state_loading"),
			loadStudyLangConfigs(study, page).then(function(loadedTranslations) {
				translations = loadedTranslations;
				let studies = {};
				let langCodes = study.langCodes();
				for(let i=langCodes.length-1; i>=0; --i) {
					let code = langCodes[i]();
					changeLang(study, translations, code);
					let langStudy = OwnMapping.toJS(study);
					langStudy.lang = code;
					studies[code === defaultLang ? "_" : code] = langStudy;
				}
				let saveType = study.version() === 0 ? "create_study" : "save_study";
				return Requests.load(
					FILE_ADMIN+"?type=" + saveType + "&study_id="+studyId+"&lastChanged="+(self.lastChanged[studyId] || Admin.tools.loginTime),
					false,
					"post",
					JSON.stringify(studies)
				);
			}).then(function({lastChanged, json}) {
				self.lastChanged[studyId] = lastChanged;
				
				//only default settings were changed:
				changeLang(study, translations, defaultLang);
				self.update_study(study, json._);
				changeLang(study, translations, currentLang);
				
				Site.reload_allPages();
				self.set_study_changedState(study, false);
			})
		);
	},
	backup_study: function(page, study) {
		let studyId = study.id();
		if(!confirm(Lang.get("confirm_backup", study.title())))
			return;
		
		return page.loader.loadRequest(FILE_ADMIN+"?type=backup_study", false, "post", "study_id="+studyId).then(function() {
			page.loader.info(Lang.get("info_successful"));
		});
	},
	delete_study: function(page) {
		let self = this;
		let study = Studies.get_current();
		if(!study || !Admin.is_loggedIn() || !safe_confirm(Lang.get("confirm_delete_study", study.title())))
			return;
		let id = study.id();
		
		let complete_fu = function() {
			Studies.list.remove(study);
			delete self.changed_state[id];
			Site.goto("admin/studies,edit");
		};
		
		if(study.version() === 0)
			complete_fu();
		else
			page.loader.loadRequest(FILE_ADMIN + "?type=delete_study", false, "post", "study_id="+id).then(complete_fu);
	},
	mark_study_as_updated: function() {//this will mark the study as updated for already existing participants
		let self = this;
		if(!Admin.is_loggedIn())
			return;
		
		let study = Studies.get_current();
		let studyId = study.id();
		let page = Site.get_lastPage();
		page.loader.loadRequest(FILE_ADMIN+"?type=mark_study_as_updated", false, "post", "study_id="+studyId).then(function({lastChanged}) {
			self.lastChanged[studyId] = lastChanged;
			self.set_studyDetector_enabled(study, false);
			study.version(study.version() + 1);
			study.subVersion(0);
			study.new_changes(false);
			self.set_study_changedState(study);
			self.set_studyDetector_enabled(study, true);
		});
	},
	
	any_study_changed: function() {
		for(let id in this.changed_state) {
			if(!this.changed_state.hasOwnProperty(id))
				continue;
			if(this.changed_state[id].isDirty())
				return true;
		}
		return false;
	},
	getStudyChangedDetector(studyId) {
		return this.changed_state[studyId];
	},
	set_study_changedState: function(study, changed) {
		this.getStudyChangedDetector(study.id()).setDirty(changed || false);
	},
	set_studyDetector_enabled: function(study, enabled) {
		this.getStudyChangedDetector(study.id()).set_enabled(enabled);
	},
	
	lock: function(page, study, el) {
		page.loader.loadRequest(FILE_ADMIN + "?type=freeze_study" + (el.checked ? "&frozen" : "") + "&study_id="+study.id()).then(function(frozen) {
			el.checked = frozen;
			alert(frozen ? Lang.get("info_study_frozen") : Lang.get("info_study_unfrozen"));
		});
	},
	
	change_observedStudy: function(study_id) {
		NavigationRow.admin.change_observed(
			this.getStudyChangedDetector(study_id),
			this.save_study.bind(this),
			Studies.list()[study_id].new_changes,
			this.mark_study_as_updated.bind(this)
		);
	},
	
	get_studyVariables: function(study) {
		let variables = [];
		let questionnaires = study.questionnaires();
		for(let i=0, max=questionnaires.length; i<max; ++i) {
			variables = variables.concat(this.get_questionnaireVariables(questionnaires[i]));
		}
		return variables;
	},
	get_questionnaireVariables: function(questionnaire) {
		let variables = [];
		let pages = questionnaire.pages();
		for(let i=0, maxI=pages.length; i<maxI; ++i) {
			let inputs = pages[i].inputs();
			for(let j=0, maxJ=inputs.length; j<maxJ; ++j) {
				let input = inputs[j];
				let name = input.name();
				switch(input.responseType()) {
					case "text":
						continue;
					case "app_usage":
						variables.push(name+"~usageTimeYesterday");
						variables.push(name+"~usageCountYesterday");
						variables.push(name+"~usageTimeToday");
						variables.push(name+"~usageCountToday");
						variables.push(name);
						break;
					case "dynamic_input":
						variables.push(name+"~index");
						variables.push(name);
						break;
					case "list_multiple":
						variables.push(name);
						let listChoices = input.listChoices();
						for(let k=0, maxK=listChoices.length; k<maxK; ++k) {
							variables.push(name + "~" + listChoices[k]());
						}
						break;
					default:
						variables.push(name);
				}
			}
		}
		
		if(questionnaire.hasOwnProperty("sumScores")) {
			let sumScores = questionnaire.sumScores();
			for(let i=0, max=sumScores.length; i<max; ++i) {
				variables.push(sumScores[i].name());
			}
		}
		return variables;
	},
	
	is_media: function(input) {
		let responseType = input.responseType();
		return responseType === 'photo' || responseType === 'record_audio';
	},
	for_each_input: function(study, fu) {
		let questionnaires = study.questionnaires();
		for(let iQ=questionnaires.length-1; iQ>=0; iQ--) {
			let pages = questionnaires[iQ].pages();
			for(let iP=pages.length-1; iP>=0; iP--) {
				let inputs = pages[iP].inputs();
				for(let iI=inputs.length-1; iI>=0; iI--) {
					if(fu(inputs[iI]) === false)
						return;
				}
			}
		}
	},
	has_media: function(study) {
		let self = this;
		let has_media = false;
		this.for_each_input(study, function(input) {
			if(self.is_media(input)) {
				has_media = true;
				return false;
			}
		});
		return has_media;
	},
	list_specialDataColumns: function(study) {
		let columns = [];
		this.for_each_input(study, function(input) {
			switch(input.responseType()) {
				case 'photo':
					columns.push({key: input.name(), type: 'image'});
					break;
				case 'record_audio':
					columns.push({key: input.name(), type: 'audio'});
					break;
			}
		});
		
		return columns;
	}
}