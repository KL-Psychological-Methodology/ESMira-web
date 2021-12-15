import html from "./summary_user.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {FILE_RESPONSES, FILE_STATISTICS} from "../js/variables/urls";
import {Studies} from "../js/main_classes/studies";
import {Admin} from "../js/main_classes/admin";
import {CsvLoader} from "../js/dynamic_imports/csv_loader";
import {
	drawCharts,
	setup_chart,
	create_perDayChartCode,
	load_statisticsFromFiles,
	create_loaderForNeededFiles
} from "../js/dynamic_imports/statistic_tools";
import {Requests} from "../js/main_classes/requests";

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
	
	
	let study, eventsLoader, questionnaireLoaderList, publicStatisticsCache = false;
	
	this.postInit = function({id}, studies) {
		study = studies[id];
		
		
		let url = FILE_RESPONSES.replace('%1', study.id()).replace('%2', 'events');
		eventsLoader = new CsvLoader(url, page);
		eventsLoader.waitUntilReady().then(function() {
			eventsLoader.filter_column(false, "eventType");
			eventsLoader.filter(true, "eventType", "questionnaire");
			eventsLoader.get_valueList("userId", true)
				.then(function(valueList) {
					self.participantList(valueList);
					self.participantCount(valueList.length);
				});
		});
		
		questionnaireLoaderList = create_loaderForNeededFiles(page, study, study.personalStatistics.charts());
	};
	
	this.selectUser = function(userData) {
		if(self.isLoading())
			return;
		self.isLoading(true);
		let user = userData.name;
		self.currentParticipant(user);
		let charts = study.personalStatistics.charts();
		
		eventsLoader.reset();
		eventsLoader.waitUntilReady()
			.then(function() {
				eventsLoader.filter_column(false, "userId");
				eventsLoader.filter(true, "userId", user);
				eventsLoader.get_valueList("timezone").then(function(valueList) {
					self.timezones(valueList);
				});
				eventsLoader.get_valueList("appType").then(function(valueList) {
					self.appType(valueList);
				});
				eventsLoader.get_valueList("model").then(function(valueList) {
					self.model(valueList);
				});
				
				eventsLoader.filter_column(false, "eventType");
				eventsLoader.filter(true, "eventType", "questionnaire");
				
				//we need to wo wait until chart is created (to call setup_chart() ) before we can do another filter:
				return create_perDayChartCode(eventsLoader, Lang.get("questionnaires"), "questionnaireName")
			})
			.then(function(chartCode) {
				setup_chart(eventsLoader, "day_questionnaire_el", chartCode);
				
				eventsLoader.filter(false, "eventType", "questionnaire");
				eventsLoader.filter(true, "eventType", "joined");
				eventsLoader.get_valueList("responseTime").then(function(valueList) {
					self.joined_time(valueList);
				});
				eventsLoader.filter(false, "eventType", "joined");
				
				eventsLoader.filter(true, "eventType", "quit");
				eventsLoader.get_valueList("responseTime").then(function(valueList) {
					self.quit_time(valueList);
				});
				
				return eventsLoader.waitUntilReady();
			})
			.then(function() {
				page.loader.update(Lang.get("state_loading_file", Lang.get("statistics")));
				
				return load_statisticsFromFiles(
					questionnaireLoaderList,
					study,
					charts,
					user,
					!!publicStatisticsCache
				);
			})
			.then(function([statistics, publicStatistics]) {
				if(!publicStatisticsCache)
					publicStatisticsCache = publicStatistics;
				let el = document.getElementById("user_personalStatistics");
				while(el.hasChildNodes()) {
					el.removeChild(el.firstChild);
				}
				self.isLoading(false);
				drawCharts(
					el,
					charts,
					statistics,
					publicStatisticsCache,
					Admin.tools.has_readPermission(study.id())
				);
		});
	};
	
	this.destroy = function() {
		eventsLoader.close();
		for(let i=questionnaireLoaderList.length-1; i>=0; --i) {
			if(publicStatisticsCache[i])
				publicStatisticsCache[i].close();
		}
	}
}