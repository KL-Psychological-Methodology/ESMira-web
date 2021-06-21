import html from "./summary_user.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {FILE_RESPONSES} from "../js/variables/urls";
import {PromiseCache} from "../js/main_classes/promise_cache";
import {Studies} from "../js/main_classes/studies";
import {Admin} from "../js/main_classes/admin";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	this.promiseBundle = [
		Studies.init(page),
		import("../js/dynamic_imports/statistic_tools"),
		Admin.init(page)
	];
	
	this.joined_time = ko.observable("");
	this.quit_time = ko.observable("");
	this.timezones = ko.observable("");
	this.appType = ko.observable("");
	this.model = ko.observable();
	
	
	this.postInit = function({id, user}, studies, {drawCharts, setup_chart, create_perDayChartCode, load_statisticsFromFiles}) {
		page.title(user);
		
		let study = studies[id];
		let charts = study.personalStatistics.charts();
		let url = FILE_RESPONSES.replace('%1', study.id()).replace('%2', 'events');
		
		
		let get_arrayFromIndex = function(loader, columnName, contextVar) {
			let index = loader.get_visible_columnIndex(loader.get_columnNum(columnName));
			let data = [];
			for(let key in index) {
				if(index.hasOwnProperty(key))
					data.push(key);
			}
			contextVar(data.join("<br/>"));
		};
		
		page.loader.showLoader(Lang.get("state_loading"), PromiseCache.loadCSV(url).then(function(loader) {
			return loader.index_data_async(false);
		}).then(function(loader) {
			let userNum = loader.get_columnNum("userId");
			
			loader.set_columnVisibility(userNum, false);
			loader.filter_column(true, userNum, user);
			
			return loader.index_data_async(false);
		}).then(function(loader) {
			let eventTypeNum = loader.get_columnNum("eventType");
			
			get_arrayFromIndex(loader, "timezone", self.timezones);
			get_arrayFromIndex(loader, "appType", self.appType);
			get_arrayFromIndex(loader, "model", self.model);
			
			// let pageIndex = loader.get_visible_columnIndex(eventTypeNum);
			loader.set_columnVisibility(eventTypeNum, false);
			loader.filter_column(true, eventTypeNum, "questionnaire");
			
			return loader.index_data_async(false);
		}).then(function(loader) {
			let eventTypeNum = loader.get_columnNum("eventType");
			
			setup_chart(loader, "day_questionnaire_el", create_perDayChartCode(loader, Lang.get("questionnaires"), "questionnaireName"));
			
			//list quit and joined events:
			
			// loader.set_columnVisibility(eventTypeNum, false);
			loader.filter_column(false, eventTypeNum, "questionnaire");
			loader.filter_column(true, eventTypeNum, "joined");
			
			return loader.index_data_async(false);
		}).then(function(loader) {
			let eventTypeNum = loader.get_columnNum("eventType");
			
			get_arrayFromIndex(loader, "responseTime", self.joined_time);
			loader.filter_column(false, eventTypeNum, "joined");
			loader.filter_column(true, eventTypeNum, "quit");
			
			return loader.index_data_async(false);
		}).then(function(loader) {
			get_arrayFromIndex(loader, "responseTime", self.quit_time);
			
			page.loader.update(Lang.get("state_loading_file", Lang.get("statistics")));
			
			return load_statisticsFromFiles(
				study,
				charts,
				user
			);
		}).then(function([statistics, publicStatistics]) {
			drawCharts(document.getElementById("user_personalStatistics"), charts, statistics, publicStatistics, Admin.tools.has_readPermission(study.id()));
		}));
	};
}