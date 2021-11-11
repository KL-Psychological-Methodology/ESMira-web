import {Site} from "../main_classes/site";
import {OwnMapping} from "../helpers/knockout_own_mapping";
import {bindEvent, check_string, get_cookie, safe_confirm, save_cookie} from "../helpers/basics";
import {FILE_ADMIN} from "../variables/urls";
import ko from "knockout";
import btn_add from '../../widgets/btn_add.html';
import btn_delete from '../../widgets/btn_delete.html';
import btn_ok from '../../widgets/btn_ok.html';
import rich_text from '../../widgets/rich_text.html';
import {Requests} from "../main_classes/requests";
import {Admin} from "../main_classes/admin";
import {Lang} from "../main_classes/lang";
import {ChangeUser_viewModel} from "../../widgets/change_user";
import changeUser from '../../widgets/change_user.html';
import only_icon from '../../widgets/only_icon.html';
import {RichText} from "../../widgets/rich_text";
import {Studies} from "../main_classes/studies";
import {Defaults} from "../variables/defaults";
import {add_default} from "../helpers/list_functions";
import "../../css/style_admin.css";
import {LangOptions} from "../../widgets/lang_options";
import lang_options from "../../widgets/lang_options.html";

function DetectChange(obj, changedFu) {
	this.isDirty = ko.observable(false);
	let subscriptions = OwnMapping.subscribe(obj, this.isDirty, changedFu);
	
	this.setDirty = function(state) {
		OwnMapping.unsetDirty(obj);
		this.isDirty(state);
	};
	this.set_enabled = function(enabled) {
		if(enabled) {
			if(!subscriptions.length)
				subscriptions = OwnMapping.subscribe(obj, this.isDirty, changedFu);
		}
		else if(subscriptions.length)
			this.destroy();
	};
	this.destroy = function() {
		for(let i=subscriptions.length-1; i>=0; --i) {
			subscriptions[i].dispose();
		}
		subscriptions = [];
	};
}

function ListTools(page) {
	let self = this;
	this.add_obj = function(list, obj, pageCode) {
		let item = OwnMapping.fromJS(obj, obj);
		list.push(item);
		
		if(pageCode)
			Site.add_page(pageCode.replace("%", list().length-1), page.depth);
		return item;
	};
	
	this.add_prompted = function(obs, checkFu) {
		let s = prompt();
		while(true) {
			if(s == null || !s.length)
				return;
			else if(!check_string(s))
				s = prompt(Lang.get("error_forbidden_characters"), s);
			else if(checkFu && !checkFu(s))
				return;
			else
				break;
		}
		obs.push(ko.observable(s));
	}
	this.remove_from_list = function(list, index, confirm_msg, important) {
		if(confirm_msg && (important &&!safe_confirm(confirm_msg) || (!important &&!confirm(confirm_msg))))
			return false;

		list.splice(index, 1);
		return true;
	};
	
	this.ko__add_default = function(key, pageCode) {
		return function(data) {
			self.add_obj(data[key], Defaults[key], pageCode);
		}
	};
	this.ko__remove_from_list = function(list, index, confirm_msg, important) {
		return function() {
			self.remove_from_list(list, index, confirm_msg, important);
		}
	};
}

function clone(obj) {
	return JSON.parse(JSON.stringify(obj));
}

export const AdminTools = {
	username: ko.observable(),
	is_rootAdmin: ko.observable(false),
	publish: ko.observableArray(),
	write: ko.observableArray(),
	msg: ko.observableArray(),
	read: ko.observableArray(),
	has_newErrors: ko.observable(),
	loginTime: 0,
	_observedDetector: null,
	_observedSave: null,
	_observedPublish: null,
	_saveFu: null,
	_publishFu: null,
	
	
	init: function() {
		let self = this;
		window.onbeforeunload = function() {
			return Studies.tools.any_study_changed() || (self._observedDetector && self._observedDetector.isDirty())
				? Lang.get("confirm_leave_page_unsaved_changes")
				: undefined;
		};
		
		ko.bindingHandlers.numericValue = {
			init : function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let interceptor = ko.computed({
					read: function() {
						return ko.unwrap(valueAccessor());
					},
					write: function(value) {
						let num = parseInt(value);
						valueAccessor()(num || 0);
					},
					disposeWhenNodeIsRemoved: el
				});
				
				ko.applyBindingsToNode(el, {value: interceptor}, bindingContext);
			}
		};
		ko.bindingHandlers.indexValue = {
			init : function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let options = allBindings().options;
				let interceptor = ko.computed({
					read: function() {
						return options[ko.unwrap(valueAccessor())];
					},
					write: function(value) {
						valueAccessor()(options.indexOf(value));
					},
					disposeWhenNodeIsRemoved: el
				});
				
				ko.applyBindingsToNode(el, {value: interceptor}, bindingContext);
			}
		};
		ko.bindingHandlers.keyValue = {
			init : function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let options = allBindings().options;
				let interceptor = ko.computed({
					read: function() {
						let value = ko.unwrap(valueAccessor()),
							i = options.length-1;
						
						for(; i>=0; --i) {
							if(options[i].key === value)
								break;
						}
						
						return options[i];
					},
					write: function(value) {
						valueAccessor()(value.key);
					},
					disposeWhenNodeIsRemoved: el
				});
				
				ko.applyBindingsToNode(el, {value: interceptor}, bindingContext);
			}
		};
		ko.bindingHandlers.dateValue = {
			init : function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let interceptor = ko.computed({
					read: function() {
						let value = ko.unwrap(valueAccessor());
						if(value === 0)
							return "";
						return (new Date(value)).toISOString().split("T")[0];
					},
					write: function(value) {
						if(value === "")
							valueAccessor()(0);
						else
							valueAccessor()((new Date(value)).getTime());
					},
					disposeWhenNodeIsRemoved: el
				});
				
				ko.applyBindingsToNode(el, {value: interceptor}, bindingContext);
			}
		};
		ko.bindingHandlers.timeValue = {
			init : function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				bindEvent(el, "focusout", function () {
					let value = el.value;
					
					if(value === "")
						valueAccessor()(-1);
					else {
						let parts = value.split(":");
						
						let date = new Date();
						date.setHours(0);
						date.setMinutes(0);
						date.setSeconds(0);
						date.setMilliseconds(0);
						let midnight = date.getTime();
						
						date.setHours(parseInt(parts[0]));
						date.setMinutes(parseInt(parts[1]));
						
						valueAccessor()(date.getTime() - midnight);
					}
					
				});
			},
			update: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let value = ko.unwrap(valueAccessor());
				
				if(value === -1) {
					el.value = "";
				}
				else {
					let date = new Date();
					date.setHours(0);
					date.setMinutes(0);
					date.setSeconds(0);
					date.setMilliseconds(0);
					let midnight = date.getTime();
					
					
					//we cant use toLocaleTimeString() because it includes seconds which will make Firefox display them:
					let d = new Date(midnight + value),
						hour = d.getHours(),
						min = d.getMinutes();
					
					if(hour <= 9)
						hour = "0"+hour;
					if(min <= 9)
						min = "0"+min;
					
					el.value = hour+":"+min;
				}
			}
		};
		ko.bindingHandlers.combinedValue = {
			init : function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				// let index = parseInt(el.getAttribute("tag-index"));
				let index = 1 << parseInt(el.getAttribute("tag-index"));
				// let index = 1 << Array.from(el.parentElement.children).indexOf(el);
				
				let interceptor = ko.computed({
					read: function() {
						return (ko.unwrap(valueAccessor()) & index) === index;
					},
					write: function(value) {
						if(value)
							valueAccessor()(valueAccessor()() | index);
						else
							valueAccessor()(valueAccessor()() - index);
					},
					disposeWhenNodeIsRemoved: el
				});
				
				ko.applyBindingsToNode(el, {checked: interceptor}, bindingContext);
			}
		};
		
		ko.components.register('widget-add', {
			viewModel: function(params) {
				this.title = params.title;
			},
			template: btn_add
		});
		ko.components.register('widget-delete', {
			viewModel: function(params) {
				this.title = params.title;
			},
			template: btn_delete
		});
		ko.components.register('widget-ok', {
			viewModel: function(params) {
				this.title = params.title;
			},
			template: btn_ok
		});
		ko.components.register('widget-only', {
			viewModel: function(params) {
				this.android = params.hasOwnProperty("android");
				this.web = params.hasOwnProperty("web");
				this.ios = params.hasOwnProperty("ios");
			},
			template: only_icon
		});
		ko.components.register('change-user', {
			viewModel: {
				createViewModel: function(params, componentInfo) {
					return new ChangeUser_viewModel(ko.contextFor(componentInfo.element).$root.page, componentInfo.element, params);
				}
			},
			template: changeUser
		});
		ko.components.register('rich-text', {
			viewModel: {
				createViewModel: function(params, componentInfo) {
					return new RichText(componentInfo.element, params);
				}
			},
			template: rich_text
		});
		ko.components.register('lang-options', {
			viewModel: {
				createViewModel: function(params, componentInfo) {
					return new LangOptions(ko.contextFor(componentInfo.element).$root.page, params);
				}
			},
			template: lang_options
		});
		
		
		let el_saveBtn = Site.el_saveBtn;
		el_saveBtn.innerText = Lang.get("save");
		bindEvent(el_saveBtn, "click", function() {
			if(this._saveFu) {
				let detector = this._observedDetector;
				let r = this._saveFu();
				if(r)
					r.then(function() {
						detector.setDirty(false);
					});
			}
		}.bind(this));
		
		
		let el_publishBtn = Site.el_publishBtn;
		el_publishBtn.title = Lang.get("info_publish");
		bindEvent(el_publishBtn, "click", function() {
			if(this._publishFu)
				this._publishFu();
		}.bind(this));
	},
	
	has_readPermission: function(studyId) {
		return this.is_rootAdmin() || this.read.indexOf(studyId) !== -1
	},
	has_publishPermission: function(studyId) {
		return this.is_rootAdmin() || this.publish.indexOf(studyId) !== -1
	},
	has_writePermission: function(studyId) {
		return this.is_rootAdmin() || this.write.indexOf(studyId) !== -1
	},
	has_msgPermission: function(studyId) {
		return this.is_rootAdmin() || this.msg.indexOf(studyId) !== -1
	},
	
	set_loginStatus: function({username, isLoggedIn, loginTime, lastActivities, permissions, new_messages, needsBackup_list, is_admin, has_errors}) {
		if(!isLoggedIn) {
			this.is_rootAdmin(false);
			Admin.is_loggedIn(false);
		}
		else {
			this.has_newErrors(has_errors);
			Studies.tools.newMessages(OwnMapping.fromJS(new_messages || {}));
			
			Admin.is_loggedIn(true);
			Studies.tools.needsBackup(needsBackup_list || []);
			
			lastActivities = lastActivities.sort(function(a, b) {
				return b[1] - a[1];
			});
			Studies.tools.lastActivities(lastActivities);
			
			this.loginTime = loginTime;
			this.username(username);
			
			if(is_admin)
				this.is_rootAdmin(true);
			else {
				this.is_rootAdmin(false);
				this.publish(permissions.publish ? permissions.publish : []);
				this.msg(permissions.msg ? permissions.msg : []);
				this.write(permissions.write ? permissions.write : []);
				this.read(permissions.read ? permissions.read : []);
			}
		}
		
		Site.reload_allPages();
		Studies.set_initAgain();
	},
	
	change_password: function(page, username, password) {
		let self = this;
		return page.loader.loadRequest(
			FILE_ADMIN + "?type=change_password",
			false,
			"post",
			"user="+username + "&pass="+password
		).then(function() {
			page.loader.info(Lang.get("info_successful"));
		});
	},
	change_username : function(page, username) {
		let newUsername = prompt(Lang.get("prompt_choice"), username);
		if(!newUsername)
			return;
		
		return page.loader.loadRequest(
			FILE_ADMIN + "?type=change_username",
			false,
			"post",
			"user="+username + "&new_user="+newUsername
		).then(function() {
			if(username === Admin.tools.username())
				Admin.tools.username(newUsername);
			
			page.loader.info(Lang.get("info_successful"));
			return newUsername;
		});
	},
	
	get_changeDetector: function(obj, changeFu) {
		return new DetectChange(obj, changeFu);
	},
	get_listTools: function(page) {
		return new ListTools(page);
	},
	
	change_observed: function(detector, saveFu, newChanges_obj, publishFu) {
		this.remove_observed();
		
		if(detector) {
			this._observedDetector = detector
			const showSave_fu = function(b) {
				if(b)
					Site.el_saveBtn.classList.add("visible");
				else
					Site.el_saveBtn.classList.remove("visible");
			};
			
			this._observedSave = detector.isDirty.subscribe(showSave_fu);
			showSave_fu(detector.isDirty());
		}
		if(saveFu)
			this._saveFu = saveFu;
		
		if(newChanges_obj) {
			const showPublish_fu = function(b) {
				if(b)
					Site.el_publishBtn.classList.add("visible");
				else
					Site.el_publishBtn.classList.remove("visible");
			};
			
			this._observedPublish = newChanges_obj.subscribe(showPublish_fu);
			showPublish_fu(newChanges_obj());
		}
		if(publishFu)
			this._publishFu = publishFu;
	},
	remove_observed: function() {
		if(this._observedSave) {
			this._observedSave.dispose();
			Site.el_saveBtn.classList.remove("visible");
			this._observedSave = null;
		}
		if(this._observedPublish) {
			this._observedPublish.dispose();
			Site.el_publishBtn.classList.remove("visible");
			this._observedPublish = null;
		}
	}
}
export const Studies_tools = {
	changed_state: {},
	newMessages: ko.observable(),
	needsBackup: ko.observableArray([]),
	lastActivities: ko.observableArray([]),
	lastChanged: {},
	_observedSave: null,
	_observedPublish: null,
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
			Studies.init(page).then(this.change_observed.bind(this, (study_id)));
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
	
	change_observed: function(study_id) {
		Admin.tools.change_observed(
			this.changed_state[study_id],
			this.save_study.bind(this),
			Studies.list()[study_id].new_changes,
			this.mark_study_as_updated
		);
	}
}