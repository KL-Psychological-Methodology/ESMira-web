import {OwnMapping} from "../helpers/knockout_own_mapping";
import {Defaults} from "../variables/defaults";
import {Site} from "../main_classes/site";
import {Studies} from "../main_classes/studies";

let chart = null;

export function get_chart() {
	if(chart !== null)
		return chart;
	
	let index = Site.valueIndex;
	
	if(index.hasOwnProperty("public"))
		chart = Studies.list()[index.id].publicStatistics.charts()[index.public];
	else if(index.hasOwnProperty("personal"))
		chart = Studies.list()[index.id].personalStatistics.charts()[index.personal];
	else
		chart = OwnMapping.fromJS(index.chart ? JSON.parse(atob(index.chart)) : Defaults.charts, Defaults.charts);
	
	return chart;
}

export function remove_chart() {
	chart = null;
}

export function get_pageType() {
	let index = Site.valueIndex;
	if(index.hasOwnProperty("public"))
		return "public";
	else if(index.hasOwnProperty("personal"))
		return "personal";
	else
		return "calc";
}

export function create_axisValues(questionnaire) {
	if(questionnaire == null)
		return Studies.tools.get_studyVariables(Studies.get_current());
	else
		return Studies.tools.get_questionnaireVariables(questionnaire);
}