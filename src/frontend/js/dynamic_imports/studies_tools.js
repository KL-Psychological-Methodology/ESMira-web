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


function clone(obj) {
	return JSON.parse(JSON.stringify(obj));
}

export const Studies_tools = {
	changed_state: {},
	newMessages: ko.observable(),
	needsBackup: ko.observableArray([]),
	lastActivities: ko.observableArray([]),
	lastChanged: {},
	currentLang: ko.observable("_"),
	
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
		let self = this;
		let studyId = study.id();
		let detector = new DetectChange(study);
		this.changed_state[study.id()] = detector;
		
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
		let study_id = study ? study.id() : 0;
		
		page.loader.showLoader(Lang.get("state_loading"),
			Promise.all([
				Requests.load(FILE_ADMIN+"?type=get_new_id&for=study&study_id="+study_id),
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
				
				// Page.goto(["nav", id], Page.get_lastBox());
				Site.goto("admin/studies,edit/studyEdit,id:"+id, 0);
			}));
	},
	
	add_questionnaire: function(page, study, pageCode) {
		let questionnaires = study.questionnaires();
		
		let filtered = [];
		for(let i=questionnaires.length-1; i>=0; --i) {
			filtered.push(questionnaires[i].internalId());
		}
		
		return page.loader.showLoader(Lang.get("state_loading"),
			Requests.load(FILE_ADMIN+"?type=get_new_id&for=questionnaire&study_id="+study.id(), false, "post", JSON.stringify(filtered)).then(function(internalId) {
				let newQuestionnaire = add_default(study.questionnaires, "questionnaires", pageCode);
				newQuestionnaire.internalId(internalId);
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
		
		let studies = {
			_: OwnMapping.toLangJs(study, "_")
		};
		
		let langCodes = study.langCodes();
		for(let i=langCodes.length-1; i>=0; --i) {
			let code = langCodes[i]();
			let langStudy = OwnMapping.toLangJs(study, code);
			langStudy.lang = code;
			studies[code] = langStudy;
		}
		
		return page.loader.loadRequest(
			FILE_ADMIN+"?type=save_study&study_id="+studyId+"&lastChanged="+(self.lastChanged[studyId] || Admin.tools.loginTime),
			false,
			"post",
			JSON.stringify(studies)
		).then(function({lastChanged, json}) {
			self.lastChanged[studyId] = lastChanged;
			
			
			OwnMapping.update(study, json._, Defaults.studies); //language fields were not changed
			
			// self.set_study_unchanged(study);
			
			if(study.published()) {
				let studyAccessKeys = study.accessKeys();
				let all_accessKeys = Studies.all_accessKeys();
				for(let i = studyAccessKeys.length - 1; i >= 0; --i) {
					let accessKey = studyAccessKeys[i]();
					if(all_accessKeys.indexOf(accessKey) === -1) {
						all_accessKeys.push(accessKey);
					}
				}
				all_accessKeys.sort();
				Studies.all_accessKeys(all_accessKeys);
				Studies.all_accessKeys.valueHasMutated()
			}
		});
	},
	backup_study: function(page, study) {
		let studyId = study.id();
		if(!confirm(Lang.get("confirm_backup", study.title())))
			return;
		
		let needsBackup = this.needsBackup;
		page.loader.loadRequest(FILE_ADMIN+"?type=backup_study", false, "post", "study_id="+studyId).then(function() {
			let index = needsBackup.indexOf(studyId);
			if(index > -1)
				needsBackup.splice(index, 1);
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
			self.set_study_unchanged(study);
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
	set_study_unchanged: function(study) {
		this.changed_state[study.id()].setDirty(false);
	},
	set_studyDetector_enabled: function(study, enabled) {
		this.changed_state[study.id()].set_enabled(enabled);
	},
	
	lock: function(page, study, el) {
		page.loader.loadRequest(FILE_ADMIN + "?type=freeze_study" + (el.checked ? "&frozen" : "") + "&study_id="+study.id()).then(function(frozen) {
			el.checked = frozen;
			alert(frozen ? Lang.get("info_study_frozen") : Lang.get("info_study_unfrozen"));
		});
	},
	
	change_observedStudy: function(study_id) {
		NavigationRow.admin.change_observed(
			this.changed_state[study_id],
			this.save_study.bind(this),
			Studies.list()[study_id].new_changes,
			this.mark_study_as_updated.bind(this)
		);
	}
}