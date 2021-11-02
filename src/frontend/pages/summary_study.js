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
import {PromiseCache} from "../js/main_classes/promise_cache";
import {filter_box} from "../js/helpers/basics";
import {Studies} from "../js/main_classes/studies";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("summary"));
	this.extraContent = "<label class=\"no_title no_desc\"><span data-bind=\"text: Lang.colon_days\"></span><input type=\"number\" data-bind=\"value: $root.days, event: {change: $root.reload}\"/></label>";
	
	this.days = 3;
	this.modelsList = ko.observableArray();
	this.modelCount = ko.observable(0);
	
	this.filter_box = filter_box;
	this.reload = null;
	
	this.promiseBundle = [
		Studies.init(page),
		import("../js/dynamic_imports/statistic_tools")
	];
	this.preInit = function({id}, studies, {colors, setup_chart, listVariable}) {
		this.reload = function() {
			let study = studies[id];
			let url = FILE_RESPONSES.replace('%1', study.id()).replace('%2', 'events');
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
			
			
			let eventIndex, eventTypeNum;
			let setup_filteredChart = function(loader, chartName, filterBy, elId, dataType, days) {
				loader.filter_column(true, eventTypeNum, filterBy);
				return loader.index_data_async(false).then(function(loader) {
					let l = eventIndex.hasOwnProperty(filterBy) ? eventIndex[filterBy][0].length : 0;
					let chart = create_dayChartCode(chartName.replace("%", l), dataType, "userId", days);
					setup_chart(loader, elId, chart);
					loader.filter_column(false, eventTypeNum, filterBy);
					return loader;
				});
			}
			page.loader.showLoader(Lang.get("state_loading"), PromiseCache.loadCSV(url).then(function(loader) {
				return loader.index_data_async(false);
			}).then(function(loader) {
				eventIndex = loader.get_columnIndex("eventType");
				eventTypeNum = loader.get_columnNum("eventType");
				
				
				loader.set_columnVisibility(eventTypeNum, false);
				loader.filter_column(true, eventTypeNum, "questionnaire");
				
				return loader.index_data_async(false);
			}).then(function(loader) { //filtered by questionnaire
				
				//appType
				setup_chart(
					loader,
					"app_type_el",
					create_sumChartCode(Lang.get("app_type_per_questionnaire"), "appType", STATISTICS_CHARTTYPES_PIE)
				);
				
				//models-list:
				self.modelCount(listVariable(loader, "model", self.modelsList));
				
				//manufacturer
				setup_chart(
					loader,
					"manufacturer_el",
					create_sumChartCode(Lang.get("questionnaire_per_manufacturer"), "manufacturer", STATISTICS_CHARTTYPES_BARS)
				);
				
				
				let oneDay_ms = ONE_DAY * 1000;
				let day = Date.now() - (oneDay_ms * self.days);
				loader.filter_rows(false, "responseTime", function(value) {
					return value < day;
				});
				
				return loader.index_data_async(false);
			}).then(function(loader) { //filtered by questionnaire & days
				//questionnaires
				let l = eventIndex.hasOwnProperty("questionnaire") ? eventIndex["questionnaire"][0].length : 0;
				setup_chart(
					loader,
					"questionnaire_el",
					create_dayChartCode(Lang.get("questionnaires_with_total", l), STATISTICS_DATATYPES_FREQ_DISTR, "questionnaireName", self.days)
				);
				
				//questionnaires per user
				setup_chart(
					loader,
					"user_el",
					create_dayChartCode(Lang.get("questionnaires_per_user"), STATISTICS_DATATYPES_FREQ_DISTR, "userId", self.days),
					function(user) {
						Site.goto('sumUser,user:' + user);
					}
				);
				
				loader.filter_column(false, eventTypeNum, "questionnaire");
				
				return loader.index_data_async(false);
			}).then(function(loader) { //filtered by days with no events
				return setup_filteredChart(loader, Lang.get("joined_study_with_total"), "joined", "joined_el", STATISTICS_DATATYPES_SUM, self.days);
			}).then(function(loader) { //filtered by days with no events
				return setup_filteredChart(loader, Lang.get("quit_study_with_total"), "quit", "quit_el", STATISTICS_DATATYPES_SUM, self.days);
			}).then(function(loader) { //filtered by days with no events
				loader.set_columnVisibility(eventTypeNum, true);
				return loader.index_data_async(false);
			}).then(function(loader) { //filtered by days
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
			}));
		}
		this.reload();
	};
}