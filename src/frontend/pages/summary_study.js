import html from "./summary_study.html"
import {Lang} from "../js/main_classes/lang";
import {Site} from "../js/main_classes/site";
import ko from "knockout";
import {FILE_RESPONSES} from "../js/variables/urls";
import {ONE_DAY} from "../js/variables/constants";
import {OwnMapping} from "../js/helpers/knockout_own_mapping";
import {Defaults} from "../js/variables/defaults";
import {
	CONDITION_OPERATOR_GREATER,
	CONDITION_OPERATOR_LESS, CONDITION_TYPE_AND,
	STATISTICS_CHARTTYPES_BARS, STATISTICS_CHARTTYPES_PIE,
	STATISTICS_DATATYPES_FREQ_DISTR, STATISTICS_DATATYPES_SUM,
	STATISTICS_VALUETYPES_COUNT
} from "../js/variables/statistics";
import {Studies} from "../js/main_classes/studies";
import {CsvLoader} from "../js/dynamic_imports/csv_loader";
import {colors, setup_chart} from "../js/dynamic_imports/statistic_tools";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("summary"));
	this.extraContent = "<label class=\"no_title no_desc\"><span data-bind=\"text: Lang.colon_days\"></span><input type=\"number\" data-bind=\"value: $root.days, event: {change: $root.reload}\"/></label>";
	
	this.days = 3;
	this.modelsList = ko.observableArray();
	this.modelCount = ko.observable(0);
	this.showData = ko.observable(true);
	
	let loader;
	this.reload = function() {
		let create_dayChartCode = function(title, dataType, variableName, days) {
			let oneDay_ms = ONE_DAY * 1000;
			let day = Math.ceil(Date.now() / oneDay_ms) * oneDay_ms + (new Date).getTimezoneOffset();
			
			let axis = [];
			for(let i = 0; i < days; ++i) {
				axis.push({
					xAxis: {
						conditions: []
					},
					yAxis: {
						conditions: [
							{
								key: "responseTime",
								value: day.toString(),
								operator: CONDITION_OPERATOR_LESS
							},
							{
								key: "responseTime",
								value: (day -= oneDay_ms).toString(),
								operator: CONDITION_OPERATOR_GREATER
							}
						],
						variableName: variableName,
						observedVariableIndex: i,
						conditionType: CONDITION_TYPE_AND
					},
					label: !i ? Lang.get("today") : (i === 1 ? Lang.get("yesterday") : Lang.get("x_days_ago", i)),
					color: colors[i % colors.length]
				});
			}
			return OwnMapping.fromJS({
				title: title,
				publicVariables: [],
				axisContainer: axis,
				valueType: STATISTICS_VALUETYPES_COUNT,
				dataType: dataType,
				chartType: STATISTICS_CHARTTYPES_BARS
			}, Defaults.charts);
		};
		let create_sumChartCode = function(title, variableName, chartType) {
			return OwnMapping.fromJS({
				title: title,
				publicVariables: [],
				axisContainer: [{
					xAxis: {
						conditions: []
					},
					yAxis: {
						conditions: [],
						variableName: variableName,
						observedVariableIndex: 0
					},
					label: variableName
				}],
				valueType: STATISTICS_VALUETYPES_COUNT,
				dataType: STATISTICS_DATATYPES_FREQ_DISTR,
				chartType: chartType
			}, Defaults.charts);
		};
		
		this.showData(false);
		let count;
		loader.waitUntilReady()
			.then(function() {
				loader.reset();
				return loader.waitUntilReady();
			})
			.then(function() {
				return loader.get_valueCount("eventType", ["questionnaire", "joined", "quit"])
					.then(function(countLoaded) {
						count = countLoaded;
					});
			})
			.then(function() {
				loader.filter_column(false, "eventType");
				loader.filter(true, "eventType", "questionnaire");
				setup_chart(
					loader,
					"app_type_el",
					create_sumChartCode(Lang.get("app_type_per_questionnaire"), "appType", STATISTICS_CHARTTYPES_PIE)
				);
				setup_chart(
					loader,
					"manufacturer_el",
					create_sumChartCode(Lang.get("questionnaire_per_manufacturer"), "manufacturer", STATISTICS_CHARTTYPES_BARS)
				);
				
				loader.get_valueList("model", true)
					.then(function(valueList) {
						self.modelsList(valueList);
						self.modelCount(valueList.length);
					});
				
				let oneDay_ms = ONE_DAY * 1000;
				let day = Date.now() - (oneDay_ms * self.days);
				loader.filter_rowsByResponseTime(false, day);
				setup_chart(
					loader,
					"questionnaire_el",
					create_dayChartCode(Lang.get("questionnaires_with_total", count.questionnaire), STATISTICS_DATATYPES_FREQ_DISTR, "questionnaireName", self.days)
				);
				setup_chart(
					loader,
					"user_el",
					create_dayChartCode(Lang.get("questionnaires_per_user"), STATISTICS_DATATYPES_FREQ_DISTR, "userId", self.days),
					function(user) {
						Site.goto('sumUser,user:' + user);
					}
				);
				
				
				loader.filter(false, "eventType", "questionnaire");
				loader.filter(true, "eventType", "joined");
				setup_chart(
					loader,
					"joined_el",
					create_dayChartCode(Lang.get("joined_study_with_total").replace("%", count.joined), STATISTICS_DATATYPES_SUM, "userId", self.days)
				);
				loader.filter(false, "eventType", "joined");
				
				
				loader.filter(true, "eventType", "quit");
				setup_chart(
					loader,
					"quit_el",
					create_dayChartCode(Lang.get("quit_study_with_total").replace("%", count.quit), STATISTICS_DATATYPES_SUM, "userId", self.days)
				);
				loader.filter(false, "eventType", "quit");
				
				
				loader.filter_column(true, "eventType");
				setup_chart(
					loader,
					"app_version_el",
					create_dayChartCode(Lang.get("used_app_version"), STATISTICS_DATATYPES_FREQ_DISTR, "appVersion", self.days)
				);
				setup_chart(
					loader,
					"study_version_el",
					create_dayChartCode(Lang.get("used_study_version"), STATISTICS_DATATYPES_FREQ_DISTR, "studyVersion", self.days)
				);
				
				self.showData(true);
			});
	};
	
	this.promiseBundle = [
		Studies.init(page)
	];
	this.preInit = function({id}, studies) {
		let study = studies[id];
		let url = FILE_RESPONSES.replace('%1', study.id()).replace('%2', 'events');
		
		loader = new CsvLoader(url, page);
		this.reload();
	};
}