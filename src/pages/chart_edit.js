import html from "./chart_edit.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {DATA_MAIN_KEYS} from "../js/variables/constants";
import {
	CONDITION_TYPE_ALL,
	CONDITION_TYPE_AND,
	CONDITION_TYPE_OR, STATISTICS_CHARTTYPES_PIE, STATISTICS_CHARTTYPES_SCATTER, STATISTICS_DATATYPES_FREQ_DISTR,
	STATISTICS_DATATYPES_XY
} from "../js/variables/statistics";
import {OwnMapping} from "../js/helpers/knockout_own_mapping";
import {delete_cookie, get_cookie, save_cookie} from "../js/helpers/basics";
import {Studies} from "../js/main_classes/studies";
import {create_axisValues, get_chart, get_pageType, remove_chart} from "../js/shared/charts";
import {Admin} from "../js/main_classes/admin";
import {Defaults} from "../js/variables/defaults";

function get_chartCache() {
	let cookieName_content = "chartCache_content";
	let cookieName_title = "chartCache_title";
	
	let r = [];
	for(let i=0, cookie; (cookie = get_cookie(cookieName_content+i)); ++i) {
		r.push({
			title: get_cookie(cookieName_title+i) || "Cookie",
			content: cookie
		});
	}
	return r;
}

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("edit_chart"));
	this.promiseBundle = [Studies.init(page)];
	
	this.preInit = function() {
		this.dataObj = get_chart();
		this.pageType = get_pageType();
		this.extraContent = "<a class=\"right highlight\" data-bind=\"navi: 'chart', text: '"+Lang.get(this.pageType === 'calc' ? 'calculate' : 'preview')+"'\"></a>";
	}
	this.destroy = function() {
		remove_chart();
	}
	
	let axisCount = 0;
	
	
	this.chartCache = ko.observableArray(get_chartCache());
	
	this.condition_types = [
		"=",
		"≠",
		"⋝",
		"⋜"
	];
	this.statistics_chartTypes = [
		Lang.get("statisticsChart_line"),
		Lang.get("statisticsChart_line_filled"),
		Lang.get("statisticsChart_bars"),
		Lang.get("statisticsChart_scatter"),
		Lang.get("statisticsChart_pie")
	];
	this.statistics_dataTypes = [
		Lang.get("statisticsDataType_daily"),
		Lang.get("statisticsDataType_frequencyDistr"),
		Lang.get("statisticsDataType_sum"),
		Lang.get("statisticsDataType_xy")
	];
	this.statistics_valueTypes = [
		Lang.get("statisticsValueType_mean"),
		Lang.get("statisticsValueType_sum"),
		Lang.get("statisticsValueType_count")
	];
	
	this.CONDITION_TYPE_AND = CONDITION_TYPE_AND;
	this.CONDITION_TYPE_OR = CONDITION_TYPE_OR;
	this.STATISTICS_DATATYPES_XY = STATISTICS_DATATYPES_XY;
	this.STATISTICS_DATATYPES_FREQ_DISTR = STATISTICS_DATATYPES_FREQ_DISTR;
	this.STATISTICS_CHARTTYPES_SCATTER = STATISTICS_CHARTTYPES_SCATTER;
	this.STATISTICS_CHARTTYPES_PIE = STATISTICS_CHARTTYPES_PIE;
	
	let listTools = Admin.tools.get_listTools(page);
	this.ko__remove_from_list = listTools.ko__remove_from_list;
	
	this.addCondition = function(data, e) {
		let axis = data.axis;
		let el = e.target;
		
		let condition = listTools.add_obj(axis.conditions, Defaults.conditions);
		condition.key(el.value);
		if(axis.conditionType() === CONDITION_TYPE_ALL)
			axis.conditionType(CONDITION_TYPE_AND);
		el.selectedIndex = 0;
	}
	this.removeCondition = function(axis, index) {
		return function() {
			listTools.remove_from_list(axis.conditions, index);
			if(!axis.conditions().length)
				axis.conditionType(CONDITION_TYPE_ALL);
		}
	}
	
	this.addPublicVariable = function(chart) {
		listTools.add_obj(chart.publicVariables, Defaults.axisContainer);
	};
	this.addVariable = function(chart) {
		let item = listTools.add_obj(chart.axisContainer, Defaults.axisContainer);
		item.xAxis.observedVariableIndex(axisCount++);
		item.yAxis.observedVariableIndex(axisCount++);
	}
	
	this.get_conditionValues = function(axisValue) {
		let questionnaires = Studies.get_current().questionnaires();
		let get_questionnaire = function() {
			for(let i=questionnaires.length-1; i>=0; --i) {
				let questionnaire = questionnaires[i];
				
				if(questionnaire.hasOwnProperty("sumScores")) {
					let sumScores = questionnaire.sumScores();
					for(let i = sumScores.length - 1; i >= 0; --i) {
						if(sumScores[i].name() === axisValue)
							return questionnaire;
					}
				}
				
				let pages = questionnaire.pages();
				for(let j=pages.length-1; j>=0; --j) {
					let page = pages[j].inputs();
					for(let k = page.length - 1; k >= 0; --k) {
						if(page[k].name() === axisValue)
							return questionnaire
					}
				}
			}
		};
		
		let questionnaire = axisValue === "" ?questionnaires[0] : get_questionnaire();
		
		return questionnaire == null ? DATA_MAIN_KEYS : self.create_axisValues(questionnaire).concat(DATA_MAIN_KEYS);
	}
	
	this.save_chartCache = function(chart) {
		let title = prompt(Lang.get("prompt_title"));
		
		if(!title)
			return;
		
		let cookieName_content = "chartCache_content";
		let cookieName_title = "chartCache_title";
		let i = 0
		while(get_cookie(cookieName_content+i)) {
			if(++i > 50) {
				i = 0;
				break;
			}
		}
		save_cookie(cookieName_content+i, btoa(OwnMapping.toJSON(chart)));
		save_cookie(cookieName_title+i, title);
		
		self.chartCache(get_chartCache());
	};
	this.delete_chartCache = function(index) {
		let cookieName_content = "chartCache_content";
		let cookieName_title = "chartCache_title";
		
		for(let nextIndex = index+1, cookie; (cookie = get_cookie(cookieName_content+nextIndex)); ++index, ++nextIndex) {
			save_cookie(cookieName_content+index, get_cookie(cookieName_content+nextIndex));
			save_cookie(cookieName_title+index, get_cookie(cookieName_title+nextIndex) || "Cookie");
		}
		
		delete_cookie(cookieName_content+index);
		delete_cookie(cookieName_title+index);
		
		self.chartCache(get_chartCache());
	}
	
	this.create_axisValues = create_axisValues;
}
