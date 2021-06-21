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

function DetectChange(obj) {
	//Thanks to http://www.knockmeout.net/2011/05/creating-smart-dirty-flag-in-knockoutjs.html
	
	let result = function() {},
		_initialState = ko.observable(OwnMapping.toJSON(obj)),
		_isInitiallyDirty = ko.observable(false);
	
	result.isDirty = ko.pureComputed(function() {
		return _isInitiallyDirty() || _initialState() !== OwnMapping.toJSON(obj);
	});
	
	result.setDirty = function(state) {
		_initialState(OwnMapping.toJSON(obj));
		_isInitiallyDirty(state);
	};
	
	result.set_onChangeListener = function(fu) {
		result.onChange = ko.pureComputed(function() {
			OwnMapping.toJSON(obj);
			return Date.now();
		});
		result.onChange.subscribe(fu);
	};
	result.remove_onChangeListener = function() {
		if(result.onChange)
			result.onChange.dispose();
	}
	
	return result;
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
	username: ko.observable(get_cookie("user")),
	is_rootAdmin: ko.observable(false),
	publish: ko.observableArray(),
	write: ko.observableArray(),
	msg: ko.observableArray(),
	read: ko.observableArray(),
	has_newErrors: ko.observable(),
	
	
	init: function() {
		window.onbeforeunload = function(){
			return Studies.tools.any_study_changed() ? Lang.get("confirm_leave_page_unsaved_changes") : undefined;
		};
		
		ko.bindingHandlers.numericValue = {
			init : function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let interceptor = ko.computed({
					read: function() {
						return ko.unwrap(valueAccessor());
					},
					write: function(value) {
						valueAccessor()(parseInt(value));
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
			// viewModel: ChangeUser_viewModel,
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
	
	set_loginStatus: function(data) {
		if(!data || !data["isLoggedIn"]) {
			this.is_rootAdmin(false);
			Admin.is_loggedIn(false);
		}
		else {
			this.has_newErrors(!!data.errors);
			Studies.tools.newMessages(OwnMapping.fromJS(data["new_messages"] || {}));
			
			Admin.is_loggedIn(true);
			Studies.tools.needsBackup(data["needsBackup"] || []);
			
			data.lastActivities = data.lastActivities.sort(function(a, b) {
				return b[1] - a[1];
			});
			Studies.tools.lastActivities(data.lastActivities);
			
			// if(data.needsBackup.length && Page.get_pagesCount() === 1)
			// 	Page.goto("#studies,backups");
			
			if(data.admin)
				this.is_rootAdmin(true);
			else {
				this.is_rootAdmin(false);
				this.publish(data.publish ? data.publish : []);
				this.msg(data.msg ? data.msg : []);
				this.write(data.write ? data.write : []);
				this.read(data.read ? data.read : []);
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
		).then(function(hashed_pass) {
			if(username === self.username())
				save_cookie("pass", hashed_pass);
		});
	},
	
	get_changeDetector: function(obj) {
		return new DetectChange(obj);
	},
	get_listTools: function(page) {
		return new ListTools(page);
	}
}
export const Studies_tools = {
	changed_state: {},
	newMessages: ko.observable(),
	needsBackup: ko.observableArray([]),
	lastActivities: ko.observableArray([]),
	_observedSave: null,
	_observedPublish: null,
	
	init: function(page) {
		let studies = Studies.list();
		for(let i = studies.length - 1; i >= 0; --i) {
			let study = studies[i];
			this.initStudy(study);
		}
		
		let el_saveBtn = Site.el_saveBtn;
		el_saveBtn.innerText = Lang.get("save");
		bindEvent(el_saveBtn, "click", this.save_study.bind(this));
		
		let el_publishBtn = Site.el_publishBtn;
		el_publishBtn.title = Lang.get("info_publish");
		bindEvent(el_publishBtn, "click", this.mark_study_as_updated.bind(this));
		
		let study_id = Site.valueIndex.id;
		
		if(study_id !== undefined) {
			Studies.init(page).then(this.change_observed.bind(this, (study_id)));
		}
	},
	initStudy: function(study) {
		this.changed_state[study.id()] = new DetectChange(study);
	},
	
	add_study: function(page, study) {
		let self = this;
		page.loader.showLoader(Lang.get("state_loading"),
			Promise.all([
				Requests.load(FILE_ADMIN+"?type=get_new_id&for=study"),
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
			Requests.load(FILE_ADMIN+"?type=get_new_id&for=questionnaire", false, "post", JSON.stringify(filtered)).then(function(internalId) {
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
		let page = Site.get_lastPage();
		page.loader.loadRequest(
			FILE_ADMIN+"?type=save_study&study_id="+study.id()+"&timeOfLoad="+Studies.timeOfLoad,
			false,
			"post",
			OwnMapping.toJSON(study)
		).then(function(json) {
			OwnMapping.update(study, json, Defaults.studies);
			self.set_study_unchanged(study);
			Studies.timeOfLoad = Date.now();
			
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
		}).catch(function() {
			Studies.timeOfLoad = Date.now();
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
		let page = Site.get_lastPage();
		page.loader.loadRequest(FILE_ADMIN+"?type=mark_study_as_updated", false, "post", "study_id="+study.id()).then(function() {
			study.version(study.version() + 1);
			study.subVersion(0);
			study.new_changes(false);
			self.set_study_unchanged(study);
			Studies.timeOfLoad = Date.now();
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
	
	lock: function(page, study, el) {
		page.loader.loadRequest(FILE_ADMIN + "?type=freeze_study" + (el.checked ? "&frozen" : "") + "&study_id="+study.id()).then(function(frozen) {
			el.checked = frozen;
			alert(frozen ? Lang.get("info_study_frozen") : Lang.get("info_study_unfrozen"));
		});
	},
	
	change_observed: function(id) {
		let study = Studies.list()[id];
		let changed_state = this.changed_state[id];
		
		if(!study || !changed_state)
			return;
		
		this.remove_observed();
		const save_fu = function(b) {
			if(b)
				Site.el_saveBtn.classList.add("visible");
			else
				Site.el_saveBtn.classList.remove("visible");
		};
		
		this._observedSave = changed_state.isDirty.subscribe(save_fu);
		save_fu(changed_state.isDirty());
		
		
		let new_changes = study.new_changes;
		const publish_fu = function(b) {
			if(b)
				Site.el_publishBtn.classList.add("visible");
			else
				Site.el_publishBtn.classList.remove("visible");
		};
		
		this._observedPublish = new_changes.subscribe(publish_fu);
		publish_fu(new_changes());
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