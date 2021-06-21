import html from "./data_list.html"
import {Lang} from "../js/main_classes/lang";
import {FILE_ADMIN, FILE_RESPONSES} from "../js/variables/urls";
import {PromiseCache} from "../js/main_classes/promise_cache";
import * as ko from "knockout";
import {safe_confirm} from "../js/helpers/basics";
import {Studies} from "../js/main_classes/studies";
import {Admin} from "../js/main_classes/admin";

export function ViewModel(page) {
	let self = this;
	let init = function() {
		let study = Studies.get_current();
		
		if(study.version() === 0)
			return;
		
		page.loader.showLoader(Lang.get("state_loading"), PromiseCache.loadJson(FILE_ADMIN+"?type=list_data&study_id="+study.id(), function(backups) {
			let questionnaires = study.questionnaires();
			let questionnaireIndex = {};
			for(let i = questionnaires.length - 1; i >= 0; --i) {
				let questionnaire = questionnaires[i];
				questionnaireIndex[questionnaire.internalId()] = questionnaire.title();
			}
			
			let currentList = [];
			let backupsList = [];
			for(let i = backups.length - 1; i >= 0; --i) {
				let entry = backups[i];
				if(questionnaireIndex.hasOwnProperty(entry))
					currentList.push([questionnaireIndex[entry], entry]);
				else {
					let [date, internalId] = get_backupTitle(entry);
					if(internalId === -1 || !questionnaireIndex.hasOwnProperty(internalId))
						backupsList.push([entry, entry]);
					else
						backupsList.push([date + " " + questionnaireIndex[internalId], entry]);
				}
			}
			
			currentList.sort();
			backupsList.sort();
			
			return [currentList, backupsList];
		})).then(function(lists) {
			
			self.lists.current(lists[0]);
			self.lists.backups(lists[1]);
		});
	};
	let get_backupTitle = function(s) {
		let match = s.match(/^(\d{4}-\d{2}-\d{2})_(\d+)$/);
		
		if(match != null) {
			return [match[1], parseInt(match[2])];
		}
		return ["", -1];
	};
	
	page.title(Lang.get("data_table"));
	
	this.html = html;
	this.promiseBundle = [Studies.init(page), Admin.init(page)];
	
	this.preInit = function({id}, studies) {
		this.dataObj = studies[id];
		init();
	};
	
	this.fileResponsesUrl = FILE_RESPONSES;
	
	this.lists = {
		defaults: [
			[Lang.get("events_csv_title"), "events"],
			[Lang.get("web_access_csv_title"), "web_access"]
		],
		current: ko.observableArray(),
		backups: ko.observableArray(),
	};
	
	
	this.reload = function() {
		PromiseCache.remove(FILE_ADMIN+"?type=list_data");
		return init();
	};
	this.empty_data = function() {
		let study = Studies.get_current();
		
		if(!Admin.is_loggedIn() || !safe_confirm(Lang.get("confirm_delete_data", study.title())))
			return;
		
		page.loader.loadRequest(FILE_ADMIN + "?type=empty_data", false, "post", "study_id="+study.id())
			.then(self.reload)
			.then(function() {
				page.loader.info(Lang.get("info_successful"));
			});
	};
}