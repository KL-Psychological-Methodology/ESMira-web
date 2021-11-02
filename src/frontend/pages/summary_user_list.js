import html from "./summary_user_list.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {FILE_RESPONSES} from "../js/variables/urls";
import {PromiseCache} from "../js/main_classes/promise_cache";
import {Studies} from "../js/main_classes/studies";
import {Admin} from "../js/main_classes/admin";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	
	this.userList = ko.observableArray();
	this.userCount = ko.observable(0);
	
	
	this.promiseBundle = [
		Studies.init(page),
		import("../js/dynamic_imports/statistic_tools"),
		Admin.init(page)
	];
	this.preInit = function({id}, studies, {listVariable}) {
		let study = studies[id];
		let url = FILE_RESPONSES.replace('%1', study.id()).replace('%2', 'events');
		
		page.loader.showLoader(Lang.get("state_loading"), PromiseCache.loadCSV(url).then(function(loader) {
			return loader.index_data_async(false);
		}).then(function(loader) {
			let eventTypeNum = loader.get_columnNum("eventType");
			
			loader.set_columnVisibility(eventTypeNum, false);
			loader.filter_column(true, eventTypeNum, "questionnaire");
			return loader.index_data_async(false);
		}).then(function(loader) {
			let num = listVariable(loader, "userId", self.userList);
			page.title(Lang.get("participants_with_count", num));
		}));
	};
}