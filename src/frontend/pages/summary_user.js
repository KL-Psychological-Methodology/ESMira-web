import html from "./summary_user.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {FILE_RESPONSES} from "../js/variables/urls";
import {Studies} from "../js/main_classes/studies";
import {Admin} from "../js/main_classes/admin";
import {CsvLoader} from "../js/dynamic_imports/csv_loader";
import {drawCharts, setup_chart, create_perDayChartCode, load_statisticsFromFiles} from "../js/dynamic_imports/statistic_tools";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("participants"));
	this.promiseBundle = [
		Studies.init(page),
		Admin.init(page)
	];
	
	
	this.joined_time = ko.observable("");
	this.quit_time = ko.observable("");
	this.timezones = ko.observable("");
	this.appType = ko.observable("");
	this.model = ko.observable();
	
	this.participantList = ko.observableArray();
	this.participantCount = ko.observable(0);
	this.currentParticipant = ko.observable("");
	this.isLoading = ko.observable(false);
	this.showData = ko.computed(function() {
		// return !!self.currentParticipant();
		return !self.isLoading() && !!self.currentParticipant();
	});
	
	
	let study, loader;
	
	this.postInit = function({id}, studies) {
		study = studies[id];
		
		
		let url = FILE_RESPONSES.replace('%1', study.id()).replace('%2', 'events');
		loader = new CsvLoader(url, page);
		loader.waitUntilReady().then(function() {
			loader.filter_column(false, "eventType");
			loader.filter(true, "eventType", "questionnaire");
			loader.get_valueList("userId", true)
				.then(function(valueList) {
					self.participantList(valueList);
					self.participantCount(valueList.length);
				});
		});
	};
	
	this.selectUser = function(userData) {
		if(self.isLoading())
			return;
		self.isLoading(true);
		let user = userData.name;
		self.currentParticipant(user);
		let charts = study.personalStatistics.charts();
		
		loader.reset();
		loader.waitUntilReady()
			.then(function() {
				loader.filter_column(false, "userId");
				loader.filter(true, "userId", user);
				loader.get_valueList("timezone").then(function(valueList) {
					self.timezones(valueList);
				});
				loader.get_valueList("appType").then(function(valueList) {
					self.appType(valueList);
				});
				loader.get_valueList("model").then(function(valueList) {
					self.model(valueList);
				});
				
				loader.filter_column(false, "eventType");
				loader.filter(true, "eventType", "questionnaire");
				
				//we need to wo wait until chart is created (to call setup_chart() ) before we can do another filter:
				return create_perDayChartCode(loader, Lang.get("questionnaires"), "questionnaireName")
			})
			.then(function(chartCode) {
				setup_chart(loader, "day_questionnaire_el", chartCode);
				
				loader.filter(false, "eventType", "questionnaire");
				loader.filter(true, "eventType", "joined");
				loader.get_valueList("responseTime").then(function(valueList) {
					self.joined_time(valueList);
				});
				loader.filter(false, "eventType", "joined");
				
				loader.filter(true, "eventType", "quit");
				loader.get_valueList("responseTime").then(function(valueList) {
					self.quit_time(valueList);
				});
				
				return loader.waitUntilReady();
			})
			.then(function() {
				page.loader.update(Lang.get("state_loading_file", Lang.get("statistics")));
				
				self.isLoading(false);
				return load_statisticsFromFiles(
					page,
					study,
					charts,
					user
				);
			})
			.then(function([statistics, publicStatistics]) {
				let el = document.getElementById("user_personalStatistics");
				while(el.hasChildNodes()) {
					el.removeChild(el.firstChild);
				}
				self.isLoading(false);
				drawCharts(
					el,
					charts,
					statistics,
					publicStatistics,
					Admin.tools.has_readPermission(study.id())
				);
		});
	};
}