import html from "./summary_web.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {FILE_RESPONSES} from "../js/variables/urls";
import {OwnMapping} from "../js/helpers/knockout_own_mapping";
import {Defaults} from "../js/variables/defaults";
import {
	CONDITION_OPERATOR_GREATER,
	CONDITION_OPERATOR_LESS, CONDITION_TYPE_AND,
	STATISTICS_CHARTTYPES_BARS, STATISTICS_CHARTTYPES_PIE,
	STATISTICS_DATATYPES_FREQ_DISTR,
	STATISTICS_VALUETYPES_COUNT
} from "../js/variables/statistics";
import {PromiseCache} from "../js/main_classes/promise_cache";
import {filter_box} from "../js/helpers/basics";
import {Studies} from "../js/main_classes/studies";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("web_access"));
	this.extraContent = "<label class=\"no_title no_desc\"><span data-bind=\"text: Lang.colon_months\"></span><input type=\"number\" data-bind=\"value: $root.months, event: {change: $root.reload}\"/></label>";
	
	this.months = 3;
	this.refererList = ko.observableArray();
	this.refererCount = ko.observable(0);
	this.user_agentList = ko.observableArray();
	this.user_agentCount = ko.observable(0);
	
	this.filter_box = filter_box;
	this.reload = null;
	
	this.promiseBundle = [
		Studies.init(page),
		import("../js/dynamic_imports/statistic_tools")
	];
	this.preInit = function({id}, studies, {colors, setup_chart, listVariable, create_perDayChartCode}) {
		this.reload = function() {
			let study = studies[id];
			let url = FILE_RESPONSES.replace('%1', study.id()).replace('%2', 'web_access');
			let create_perMonthChartCode = function(title, monthsMax) {
				let date = new Date();
				let currentDate = date.getTime();
				let month = date.getMonth();
				let year = date.getFullYear();
				date.setHours(0, 0, 0, 0);
				date.setFullYear(year, month, 1);
				
				let axis = [];
				for(let i=0; i<monthsMax; ++i) {
					axis.push({
						xAxis: {
							conditions: []
						},
						yAxis: {
							conditions: [
								{
									key: "responseTime",
									value: currentDate,
									operator: CONDITION_OPERATOR_LESS
								},
								{
									key: "responseTime",
									value: currentDate = date.getTime(),
									operator: CONDITION_OPERATOR_GREATER
								}
							],
							variableName: "page",
							observedVariableIndex: i,
							conditionType: CONDITION_TYPE_AND
						},
						label: date.toLocaleString('default', { month: 'long' }),
						color: colors[i%colors.length]
					});
					
					if(--month <0) {
						date.setFullYear(year -= 1);
						date.setMonth(month = 11);
					}
					else
						date.setMonth(month);
				}
				return OwnMapping.fromJS({
					title: title,
					publicVariables: [],
					axisContainer: axis,
					valueType: STATISTICS_VALUETYPES_COUNT,
					dataType: STATISTICS_DATATYPES_FREQ_DISTR,
					chartType: STATISTICS_CHARTTYPES_BARS
				}, Defaults.charts);
			};
			let create_pieChartCode = function(title) {
				return OwnMapping.fromJS({
					title: title,
					publicVariables: [],
					axisContainer: [{
						xAxis: {
							conditions: []
						},
						yAxis: {
							conditions: [],
							variableName: "page",
							observedVariableIndex: 0
						},
						label: "page"
					}],
					valueType: STATISTICS_VALUETYPES_COUNT,
					dataType: STATISTICS_DATATYPES_FREQ_DISTR,
					chartType: STATISTICS_CHARTTYPES_PIE
				}, Defaults.charts);
			};
			
			page.loader.showLoader(Lang.get("state_loading"), PromiseCache.loadCSV(url).then(function(loader) {
				return loader.index_data_async(false);
			}).then(function(loader) {
				setup_chart(loader, "days_el", create_perDayChartCode(loader, Lang.get("daily_pageViews"), "page"));
				setup_chart(loader, "total_el", create_pieChartCode(Lang.get("total_pageViews")));
				setup_chart(loader, "months_el", create_perMonthChartCode(Lang.get("monthly_pageViews"), self.months));
				
				self.refererCount(listVariable(loader, "referer", self.refererList));
				self.user_agentCount(listVariable(loader, "user_agent", self.user_agentList));
			}));
		}
		this.reload();
	};
}