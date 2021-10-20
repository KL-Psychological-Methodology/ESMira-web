import ko from 'knockout';
import {FILE_STUDIES} from "../variables/urls.js";
import {repairStudy} from "../helpers/updater.js";
import {OwnMapping} from "../helpers/knockout_own_mapping.js";
import {Defaults} from "../variables/defaults.js";
import {Site} from "./site";
import {Lang} from "./lang";
import {PromiseCache} from "./promise_cache";
import {Admin} from "./admin";


export const Studies = {
	list: ko.observable([]),
	accessKey: ko.observable(""),
	all_accessKeys: ko.observableArray([]),
	
	tools: null,
	
	init: function(page) {
		return PromiseCache.loadJson(this.getUrl(), function(studies) {
			studies.sort( function(a, b) {
				if(a.title > b.title)
					return 1;
				else if(a.title < b.title)
					return -1;
				else
					return 0;
			});
			
			//we create a fake array so it still works with our framework and we can also use ids in the url
			// (if we would use index instead, updating study.title would lead to a different url because of its changed position in the list when sorted):
			let list = {};
			let accessKey_index = {};
			let accessKeyList = [];
			for(let i = studies.length - 1; i >= 0; --i) {
				let study = studies[i];
				
				if(!repairStudy(study))
					console.error(Lang.get("error_study_not_compatible", study.title));
				
				let id = study.id;
				let o = OwnMapping.fromJS(study, Defaults.studies);
				list[i] = list[id] = o;
				
				if(self.tools)
					self.tools.initStudy(o);
				// self.changed_state[id] = new DetectChange(self, o);
				
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
			self.all_accessKeys(accessKeyList);


			list.length = studies.length;
			self.list(list);
			self.list.push = function(study) {
				list[list.length] = study;
				list[study.id()] = study;
				++list.length;
				
				this.valueHasMutated();
			};
			self.list.remove = function(study) {
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

			return self.list();
		}).then(function(studies) {
			if(page && !Admin.is_loggedIn() && !studies.length && self.accessKey().length)
				page.loader.info(Lang.get("error_wrong_accessKey"));
			
			return studies;
		});
	},
	getUrl: function() {
		return FILE_STUDIES + (Admin.enable_adminFeatures ? "?is_loggedIn" : "?access_key="+this.accessKey())
	},

	reload: function(page) {
		PromiseCache.remove(this.getUrl());
		return this.init(page);
	},
	
	set_initAgain: function() {
		PromiseCache.remove(this.getUrl());
	},
	
	questionnaire_isActive: function(questionnaire) {
		return questionnaire.publishedWeb() && (!questionnaire.durationStart() || Date.now() >= questionnaire.durationStart()) && (!questionnaire.durationEnd() || Date.now() <= questionnaire.durationEnd()) && questionnaire.pages().length
	},
	
	set_current: function(study) {
		Site.valueIndex.id = study.id();
	},
	get_current: function() {
		let index = Site.valueIndex;
		let studies = this.list();
		
		if(index.hasOwnProperty("id"))
			return studies[index.id];
		else if(studies.length === 1)
			return studies[0];
		else if(index.hasOwnProperty("qId")) {
			let [study, questionnaire] = this.get_studyByInternalId(index.qId);
			this.set_current(study);
			return study;
		}
	},
	
	get_questionnaireByInternalId: function(study, internalId) {
		let questionnaires = study.questionnaires();
		
		internalId = parseInt(internalId);
		for(let i = questionnaires.length - 1; i >= 0; --i) {
			let questionnaire = questionnaires[i];
			if(internalId === parseInt(questionnaire.internalId()))
				return questionnaire;
		}
		return null;
	},
	get_studyByInternalId: function(internalId) {
		let studies = this.list();
		for(let i = studies.length - 1; i >= 0; --i) {
			let study = studies[i];
			
			let questionnaire = this.get_questionnaireByInternalId(study, internalId);
			if(questionnaire)
				return [study, questionnaire];
		}
	}
};
let self = Studies;