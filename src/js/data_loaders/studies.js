import ko from 'knockout';
import {FILE_ADMIN, FILE_STUDIES} from "../variables/urls.js";
import {repairStudy} from "../helpers/updater.js";
import {OwnMapping} from "../knockout_own_mapping.js";
import {Defaults} from "../variables/defaults.js";
import {DetectChange} from "../detect_change.js";
import * as Basics from "../helpers/basics.js";
import {Site} from "../classes/site";
import {Lang} from "../classes/lang";
import {add_to_list} from "../helpers/knockout_functions";
import {save_confirm} from "../helpers/basics.js";
import {PromiseCache} from "../classes/promise_cache";

function clone(obj) {
	return JSON.parse(JSON.stringify(obj));
}

export const Studies = {
	// _promiseState: null,
	// _promiseFinished: false,
	box: ko.observable(),
	timeOfLoad: 0,
	accessKey: ko.observable(""),
	all_accessKeys: ko.observableArray([]),
	newMessages: ko.observable(),
	needsBackup: ko.observableArray([]),
	lastActivities: ko.observableArray([]),
	changed_state: {},
	
	// init: function() {
	// 	let accessKey = this.accessKey();
	// 	let isAdmin = Site.admin.is_loggedIn();
	//
	// 	return this._promiseState || (this._promiseState = Site.load_withState(FILE_STUDIES + (isAdmin ? "?is_loggedIn&" : "?") + "access_key="+accessKey).then((studies) => {
	// 		if(!isAdmin && !studies.length && accessKey.length) {
	// 			Site.error(Lang.get("error_wrong_accessKey"));
	// 			return;
	// 		}
	// 		this.timeOfLoad = Date.now();
	//
	// 		studies.sort( function(a, b) {
	// 			if(a.title > b.title)
	// 				return 1;
	// 			else if(a.title < b.title)
	// 				return -1;
	// 			else
	// 				return 0;
	// 		});
	//
	// 		//we create a fake array so it still works with our framework and we can also use ids in the url
	// 		// (so updating study.name will not lead to a different url because of its changed position in the list):
	// 		let list = {};
	// 		let accessKey_index = {};
	// 		let accessKeyList = [];
	// 		for(let i = studies.length - 1; i >= 0; --i) {
	// 			let study = studies[i];
	//
	// 			if(!repairStudy(study) && isAdmin)
	// 				Site.error(Lang.get("error_study_not_compatible").replace("%s", study.title));
	//
	// 			let id = study.id;
	// 			let o = OwnMapping.fromJS(study, Defaults.studies);
	// 			list[i] = list[id] = o;
	//
	// 			this.changed_state[id] = new DetectChange(this, o);
	//
	// 			let studyAccessKeys = study.accessKeys;
	// 			if(study.published) {
	// 				for(let j = studyAccessKeys.length - 1; j >= 0; --j) {
	// 					let accessKey = studyAccessKeys[j];
	// 					if(!accessKey_index.hasOwnProperty(accessKey)) {
	// 						accessKey_index[accessKey] = true;
	// 						accessKeyList.push(accessKey);
	// 					}
	// 				}
	// 			}
	// 		}
	// 		accessKeyList.sort();
	// 		this.all_accessKeys(accessKeyList);
	//
	//
	// 		list.length = studies.length;
	// 		this.box(list);
	// 		this.box.push = function(study) {
	// 			list[list.length] = study;
	// 			list[study.id()] = study;
	// 			++list.length;
	//
	// 			this.valueHasMutated();
	// 		};
	// 		this.box.remove = function(study) {
	// 			let id = study.id();
	// 			delete list[id];
	//
	// 			for(let i = list.length - 1; i >= 0; --i) {
	// 				if(list[i].id() === id) {
	// 					++i;
	// 					for(; i < list.length; ++i) {
	// 						list[i - 1] = list[i];
	// 					}
	// 					break;
	// 				}
	// 			}
	// 			--list.length;
	// 			delete list[list.length];
	//
	// 			this.valueHasMutated();
	// 		};
	//
	// 		this._promiseFinished = true;
	// 		return this.box();
	// 	}));
	// },
	// reload: function() {
	// 	if(this._promiseFinished) {
	// 		this._promiseFinished = false;
	// 		this._promiseState = null;
	// 		return this.init();
	// 	}
	// 	else
	// 		return this._promiseState;
	// },
	
	init: function() {
		return PromiseCache.loadJson(this.getUrl(), (studies) => {
			let accessKey = this.accessKey();
			let isAdmin = Site.admin.is_loggedIn();
			if(!isAdmin && !studies.length && accessKey.length) {
				Site.error(Lang.get("error_wrong_accessKey"));
				return;
			}
			this.timeOfLoad = Date.now();

			studies.sort( function(a, b) {
				if(a.title > b.title)
					return 1;
				else if(a.title < b.title)
					return -1;
				else
					return 0;
			});

			//we create a fake array so it still works with our framework and we can also use ids in the url
			// (so updating study.name will not lead to a different url because of its changed position in the list):
			let list = {};
			let accessKey_index = {};
			let accessKeyList = [];
			for(let i = studies.length - 1; i >= 0; --i) {
				let study = studies[i];

				if(!repairStudy(study) && isAdmin)
					Site.error(Lang.get("error_study_not_compatible").replace("%s", study.title));

				let id = study.id;
				let o = OwnMapping.fromJS(study, Defaults.studies);
				list[i] = list[id] = o;

				this.changed_state[id] = new DetectChange(this, o);

				let studyAccessKeys = study.accessKeys;
				if(study.published) {
					for(let j = studyAccessKeys.length - 1; j >= 0; --j) {
						let accessKey = studyAccessKeys[j];
						if(!accessKey_index.hasOwnProperty(accessKey)) {
							accessKey_index[accessKey] = true;
							accessKeyList.push(accessKey);
						}
					}
				}
			}
			accessKeyList.sort();
			this.all_accessKeys(accessKeyList);


			list.length = studies.length;
			this.box(list);
			this.box.push = function(study) {
				list[list.length] = study;
				list[study.id()] = study;
				++list.length;

				this.valueHasMutated();
			};
			this.box.remove = function(study) {
				let id = study.id();
				delete list[id];

				for(let i = list.length - 1; i >= 0; --i) {
					if(list[i].id() === id) {
						++i;
						for(; i < list.length; ++i) {
							list[i - 1] = list[i];
						}
						break;
					}
				}
				--list.length;
				delete list[list.length];

				this.valueHasMutated();
			};

			// this._promiseFinished = true;
			return this.box();
		});
	},
	getUrl: function() {
		return FILE_STUDIES + (Site.admin.enable_adminFeatures ? "?is_loggedIn" : "?access_key="+this.accessKey())
	},

	reload: function() {
		PromiseCache.remove(this.getUrl());
		return this.init();
	},
	
	change_accessKey: function() {
		let accessKey = document.getElementById("accessKey_el").value;
		Basics.save_cookie("access_key", accessKey);
		this.accessKey(accessKey);
		this.reload();
	},
	set_initAgain: function() {
		this._promiseState = null;
	},
	
	add_study: function(study) {
		this.init().then(() => {
			Site.load_withState(FILE_ADMIN+"?type=get_new_id").then((id) => {
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
				this.changed_state[id] = new DetectChange(this, new_study_mapped);
				this.changed_state[id].setDirty(true);
				
				this.box.push(new_study_mapped);
				
				// Page.goto(["nav", id], Page.get_lastBox());
				Site.goto("admin/studies/nav,studies:"+id, 0);
			});
		});
		
	},
	add_group: function(study, depth) {
		let groups = study.groups();
		
		let newGroup = add_to_list('groups', 'groupsQ/q_edit', depth)(study);
		
		let internalId = -1;
		let retry = false;
		do {
			internalId = Math.round(Math.random() * 9999) + 1000;
			
			for(let i in groups) {
				if(!groups.hasOwnProperty(i))
					continue;
				if(groups[i].internalId() === internalId) {
					retry = true;
					break;
				}
			}
		} while(retry);
		newGroup.internalId(internalId);
	},
	save_study: function() {//this will publish the study for all new participants
		if(!Site.admin.is_loggedIn())
			return;
		
		let id = this.get_currentId();
		let study = this.get_current();
		Site.load_withState(
			FILE_ADMIN+"?type=save_study&study_id="+id+"&timeOfLoad="+this.timeOfLoad,
			false,
			"post",
			OwnMapping.toJSON(study)
		).then((json) => {
			OwnMapping.update(study, json, Defaults.studies);
			this.set_study_unchanged(study);
			this.timeOfLoad = Date.now();
			
			if(study.published()) {
				let studyAccessKeys = study.accessKeys();
				let all_accessKeys = this.all_accessKeys();
				for(let i = studyAccessKeys.length - 1; i >= 0; --i) {
					let accessKey = studyAccessKeys[i]();
					if(all_accessKeys.indexOf(accessKey) === -1) {
						all_accessKeys.push(accessKey);
					}
				}
				all_accessKeys.sort();
				this.all_accessKeys(all_accessKeys);
				this.all_accessKeys.valueHasMutated()
			}
		});
	},
	delete_study: function() {
		let study = this.get_current();
		if(!study || !Site.admin.is_loggedIn() || !save_confirm(Lang.get("confirm_delete_study").replace("%", study.title())))
			return;
		let id = study.id();
		
		let complete_fu = function() {
			this.box.remove(study);
			delete this.changed_state[id];
			Site.goto("admin/sEdit");
		};
		
		if(study.version() === 0)
			complete_fu();
		else
			Site.load_withState(FILE_ADMIN + "?type=delete_study", false, "post", "study_id="+id).then(complete_fu);
	},
	mark_study_as_updated: function() {//this will mark the study as updated for already existing participants
		if(!Site.admin.is_loggedIn())
			return;
		
		let study = this.get_current();
		
		Site.load_withState(FILE_ADMIN+"?type=mark_study_as_updated", false, "post", "study_id="+study.id()).then(() => {
			study.version(study.version() + 1);
			study.subVersion(0);
			study.new_changes(false);
			this.set_study_unchanged(study);
			this.timeOfLoad = Date.now();
		});
	},
	
	backup_study: function(study) {
		let studyId = study.id();
		if(!confirm(Lang.get("confirm_backup").replace("%", study.title())))
			return;
		
		let needsBackup = this.needsBackup;
		Site.load_withState(FILE_ADMIN+"?type=backup_study", false, "post", "study_id="+studyId).then((data) => {
			let index = needsBackup.indexOf(studyId);
			if(index > -1)
				needsBackup.splice(index, 1);
			
			Site.data_list.init(study.id());
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
	
	group_isActive: function(group) {
		return group.publishedWeb() && (!group.durationStart() || Date.now() >= group.durationStart()) && (!group.durationEnd() || Date.now() <= group.durationEnd()) && group.pages().length
	},
	
	check_isLocked: function(study, el) {
		Site.load_withState(FILE_ADMIN + "?type=is_frozen&study_id="+study.id()).then((frozen) => {
			el.checked = frozen;
			el.disabled = false;
		});
	},
	
	lock: function(study, el) {
		Site.load_withState(FILE_ADMIN + "?type=freeze_study" + (el.checked ? "&frozen" : "") + "&study_id="+study.id()).then((frozen) => {
			el.checked = frozen;
			alert(frozen ? Lang.get("info_study_frozen") : Lang.get("info_study_unfrozen"));
		});
	},
	
	check_studyVersion: function(study) {
		if(study.serverVersion() > Site.serverVersion)
			Site.error(Lang.get("error_study_not_compatible"));
		else if(study.serverVersion() !== Site.serverVersion) {
			repairStudy(study);
			if(Site.admin.is_loggedIn())
				console.log(Lang.get("info_study_updated").replace("%s", study.title()).replace("%d", study.serverVersion()).replace("%d", Site.serverVersion));
		}
	},
	
	get_current: function() {
		return this.box()[this.get_currentId()];
	},
	get_currentId: function() {
		return Site.valueIndex.studies;
	},
	get_currentGroup: function() { //TODO: group
		return this.get_current().groups()[Site.valueIndex.groups];
	}
};