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
	let options = [];
	let addValues = function(questionnaire) {
		let pages = questionnaire.pages();
		for(let i=0, maxI=pages.length; i<maxI; ++i) {
			let inputs = pages[i].inputs();
			for(let j=0, maxJ=inputs.length; j<maxJ; ++j) {
				switch(inputs[j].responseType()) {
					case "text":
					case "video":
						continue;
				}
				options.push(inputs[j].name());
			}
		}
		
		if(questionnaire.hasOwnProperty("sumScores")) {
			let sumScores = questionnaire.sumScores();
			for(let i=0, max=sumScores.length; i<max; ++i) {
				options.push(sumScores[i].name());
			}
		}
	};
	if(questionnaire == null) {
		let questionnaires = Studies.get_current().questionnaires();
		for(let i=0,max=questionnaires.length; i<max; ++i) {
			addValues(questionnaires[i]);
		}
	}
	else
		addValues(questionnaire);
	
	return options;
}