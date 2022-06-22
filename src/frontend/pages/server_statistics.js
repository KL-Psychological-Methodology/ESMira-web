import html from "./server_statistics.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {FILE_ADMIN, FILE_SERVER_STATISTICS} from "../js/variables/urls";
import {SMALLEST_TIMED_DISTANCE} from "../js/variables/constants";
import {
	STATISTICS_CHARTTYPES_BARS,
	STATISTICS_CHARTTYPES_LINE, STATISTICS_CHARTTYPES_LINE_FILLED, STATISTICS_CHARTTYPES_PIE,
	STATISTICS_DATATYPES_DAILY, STATISTICS_DATATYPES_FREQ_DISTR,
	STATISTICS_STORAGE_TYPE_FREQ_DISTR,
	STATISTICS_STORAGE_TYPE_TIMED, STATISTICS_VALUETYPES_COUNT,
	STATISTICS_VALUETYPES_SUM
} from "../js/variables/statistics";
import {OwnMapping} from "../js/helpers/knockout_own_mapping";
import {Defaults} from "../js/variables/defaults";
import {Requests} from "../js/main_classes/requests";
import {Admin} from "../js/main_classes/admin";
import {Studies} from "../js/main_classes/studies";
import {colors} from "../js/dynamic_imports/statistic_tools";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("server_statistics"));
	if(Admin.enable_adminFeatures)
		this.promiseBundle = [
			import("../js/dynamic_imports/chart_box.js"),
			Requests.load(FILE_SERVER_STATISTICS),
			Requests.load(FILE_ADMIN+"?type=get_last_activities"),
			Studies.init(page),
			Admin.init(page)
		];
	else
		this.promiseBundle = [
			import("../js/dynamic_imports/chart_box.js"),
			Requests.load(FILE_SERVER_STATISTICS)
		];
	
	this.total_studies = ko.observable(0);
	this.total_questionnaires = ko.observable(0);
	this.total_users = ko.observable(0);
	this.created = ko.observable(0);
	this.lastActivities = ko.observableArray();
	
	this.postInit = function(index, {ChartBox}, serverStatistics, lastActivities) {
		if(lastActivities) {
			for(let studyId in lastActivities) {
				if(!lastActivities.hasOwnProperty(studyId))
					continue;
				
				self.lastActivities.push({id: studyId, timestamp: lastActivities[studyId]});
			}
		}
		let is_loggedIn = Admin.is_loggedIn();
		this.total_studies(serverStatistics.total.studies);
		this.total_questionnaires(serverStatistics.total.questionnaire);
		this.total_users(serverStatistics.total.users);
		this.created(serverStatistics.created);
		
		let create_dataSet = function(type, data, count) {
			return {
				storageType: type,
				data: data,
				entryCount: count,
				timeInterval: SMALLEST_TIMED_DISTANCE
			};
		}
		
		let data_to_dailyData = function(variable, subVariable) {
			let statisticsData = {};
			let count = 0;
			let data = serverStatistics.days;
			
			
			for(let key in data) {
				if(!data.hasOwnProperty(key))
					continue;
				
				if(data[key].hasOwnProperty(variable)) {
					if(subVariable) {
						statisticsData[key] = {sum: data[key][variable].hasOwnProperty(subVariable) ? data[key][variable][subVariable] : 0, count: 1};
					}
					else
						statisticsData[key] = {sum: data[key][variable], count: 1};
				}
				else
					statisticsData[key] = {sum: 0, count: 1};
				++count;
			}
			
			return create_dataSet(STATISTICS_STORAGE_TYPE_TIMED, statisticsData, count);
		}
		let data_to_weekdayData = function(type) {
			let weekdays_data = {};
			weekdays_data[Lang.get("weekday_mon")] = serverStatistics["week"][type][1];
			weekdays_data[Lang.get("weekday_tue")] = serverStatistics["week"][type][2];
			weekdays_data[Lang.get("weekday_wed")] = serverStatistics["week"][type][3];
			weekdays_data[Lang.get("weekday_thu")] = serverStatistics["week"][type][4];
			weekdays_data[Lang.get("weekday_fri")] = serverStatistics["week"][type][5];
			weekdays_data[Lang.get("weekday_sat")] = serverStatistics["week"][type][6];
			weekdays_data[Lang.get("weekday_sun")] = serverStatistics["week"][type][0];
			
			return create_dataSet(STATISTICS_STORAGE_TYPE_FREQ_DISTR, weekdays_data, 7)
		}
		
		let appType_data = {};
		appType_data[Lang.get("Android")] = serverStatistics.total.android;
		appType_data[Lang.get("iOS")] = serverStatistics.total.ios;
		appType_data[Lang.get("Web")] = serverStatistics.total.web;
		
		
		//
		//App version
		//
		let appVersion_axisContainer = [];
		let appVersion_statistics = [];
		
		if(is_loggedIn) {
			let appVersion_labels = {};
			let days = serverStatistics.days;
			for(let timestamp in days) {
				if(!days.hasOwnProperty(timestamp))
					continue;
				let appVersion = days[timestamp].appVersion;
				if(appVersion) {
					for(let key in appVersion) {
						if(!appVersion.hasOwnProperty(key))
							continue;
						if(!appVersion_labels.hasOwnProperty(key))
							appVersion_labels[key] = true;
					}
				}
			}
			let keys = Object.keys(appVersion_labels).sort();
			let maxColors = colors.length;
			for(let i=0, max=keys.length, realI=0; i<max; ++i) {
				let key = keys[i];
				if(key.indexOf("_dev") !== -1 || key.indexOf("wasDev") !== -1)
					continue;
				appVersion_statistics.push(data_to_dailyData("appVersion", key));
				
				appVersion_axisContainer.push({
					xAxis: {
						conditions: []
					},
					yAxis: {
						conditions: [],
						variableName: "appVersion",
						observedVariableIndex: realI
					},
					label: key,
					color: colors[realI%maxColors]
				});
				++realI;
			}
		}
		
		
		//
		//statistics
		//
		
		let statistics = {
			questionnaire: [data_to_dailyData("questionnaire")],
			joined: [data_to_dailyData("joined")],
			appType: [create_dataSet(STATISTICS_STORAGE_TYPE_FREQ_DISTR, appType_data, 3)],
			appVersion: appVersion_statistics,
			weekdays_questionnaire: [data_to_weekdayData("questionnaire")],
			weekdays_join: [data_to_weekdayData("joined")]
		};
		
		
		//
		//ChartBox
		//
		if(is_loggedIn) {
			new ChartBox(
				document.getElementById("daily_appVersion_graph"),
				statistics,
				false,
				OwnMapping.fromJS({
					title: Lang.get("app_version"),
					publicVariables: [],
					axisContainer: appVersion_axisContainer,
					valueType: STATISTICS_VALUETYPES_SUM,
					dataType: STATISTICS_DATATYPES_DAILY,
					chartType: STATISTICS_CHARTTYPES_LINE
				}, Defaults.charts)
			);
		}
		else
			document.getElementById("daily_appVersion_graph").classList.add("hidden");
		
		new ChartBox(
			document.getElementById("daily_questionnaire_graph"),
			statistics,
			false,
			OwnMapping.fromJS({
				title: Lang.get("per_day"),
				publicVariables: [],
				axisContainer: [
					{
						xAxis: {
							conditions: []
						},
						yAxis: {
							conditions: [],
							variableName: "questionnaire",
							observedVariableIndex: 0
						},
						label: Lang.get("questionnaires"),
						color: "#00ffff"
					}
				],
				valueType: STATISTICS_VALUETYPES_SUM,
				dataType: STATISTICS_DATATYPES_DAILY,
				chartType: STATISTICS_CHARTTYPES_LINE_FILLED
			}, Defaults.charts)
		);
		
		new ChartBox(
			document.getElementById("daily_joined_graph"),
			statistics,
			false,
			OwnMapping.fromJS({
				title: Lang.get("per_day"),
				publicVariables: [],
				axisContainer: [
					{
						xAxis: {
							conditions: []
						},
						yAxis: {
							conditions: [],
							variableName: "joined",
							observedVariableIndex: 0
						},
						label: Lang.get("joined_study"),
						color: "#80ff80"
					}
				],
				valueType: STATISTICS_VALUETYPES_SUM,
				dataType: STATISTICS_DATATYPES_DAILY,
				chartType: STATISTICS_CHARTTYPES_LINE_FILLED
			}, Defaults.charts)
		);
		
		new ChartBox(
			document.getElementById("weekdays_questionnaire_graph"),
			statistics,
			false,
			OwnMapping.fromJS({
				title: Lang.get("total_count_per_weekday"),
				publicVariables: [],
				axisContainer: [
					{
						xAxis: {
							conditions: []
						},
						yAxis: {
							conditions: [],
							variableName: "weekdays_questionnaire",
							observedVariableIndex: 0
						},
						label: Lang.get("questionnaires"),
						color: "#00ffff"
					},
				],
				valueType: STATISTICS_VALUETYPES_SUM,
				dataType: STATISTICS_DATATYPES_FREQ_DISTR,
				chartType: STATISTICS_CHARTTYPES_BARS
			}, Defaults.charts),
			false, true
		);
		
		new ChartBox(
			document.getElementById("weekdays_joined_graph"),
			statistics,
			false,
			OwnMapping.fromJS({
				title: Lang.get("total_count_per_weekday"),
				publicVariables: [],
				axisContainer: [
					{
						xAxis: {
							conditions: []
						},
						yAxis: {
							conditions: [],
							variableName: "weekdays_join",
							observedVariableIndex: 0
						},
						label: Lang.get("joined_study"),
						color: "#80ff80"
					}
				],
				valueType: STATISTICS_VALUETYPES_SUM,
				dataType: STATISTICS_DATATYPES_FREQ_DISTR,
				chartType: STATISTICS_CHARTTYPES_BARS
			}, Defaults.charts),
			false, true
		);
		
		new ChartBox(
			document.getElementById("app_type_graph"),
			statistics,
			false,
			OwnMapping.fromJS({
				title: Lang.get("app_type"),
				publicVariables: [],
				axisContainer: [
					{
						xAxis: {
							conditions: []
						},
						yAxis: {
							conditions: [],
							variableName: "appType",
							observedVariableIndex: 0
						}
					}
				],
				valueType: STATISTICS_VALUETYPES_COUNT,
				dataType: STATISTICS_DATATYPES_FREQ_DISTR,
				chartType: STATISTICS_CHARTTYPES_PIE
			}, Defaults.charts)
		);
	}
}