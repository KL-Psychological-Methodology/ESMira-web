import {Lang} from "../main_classes/lang";
import {Studies} from "../main_classes/studies";
import {check_string} from "../helpers/basics";


export function get_uniqueName(existingName= null) {
	let sameValuesAllowedNum = 0,
		isNew = existingName == null,
		prompt_text, get_array;
	
	
	prompt_text = Lang.get("prompt_input_name");
	get_array = function(questionnaire) {
		let r = [];
		if(questionnaire.hasOwnProperty("inputs")) {
			let pages = questionnaire.pages();
			for(let i = pages.length - 1; i >= 0; --i) {
				let inputs = pages[i].inputs();
				for(let j = inputs.length - 1; j >= 0; --j) {
					r.push(inputs[j].name());
				}
			}
		}
		if(questionnaire.hasOwnProperty("sumScores")) {
			let sumScores = questionnaire.sumScores();
			for(let i = sumScores.length - 1; i >= 0; --i) {
				r.push(sumScores[i].name());
			}
		}
		return r;
	};
	
	let returnName;
	if(isNew) {
		returnName = prompt(prompt_text);
		if(returnName == null)
			return null;
	}
	else {
		returnName = existingName;
		sameValuesAllowedNum = 1; //we will iterate also over the original item - which will have the same name
	}
	
	
	let is_unique = function(array) {
		for(let i = array.length; i--;) {
			if(array[i] === returnName && --sameValuesAllowedNum < 0)
				return false;
		}
		return true;
	};
	
	let is_unique_in_all_questionnaires = function() {
		let questionnaires = Studies.get_current().questionnaires();
		for(let i=questionnaires.length-1; i>=0; --i) {
			if(!is_unique(get_array(questionnaires[i])))
				return false;
		}
		return true;
	};
	
	let formatError;
	while((formatError=(returnName.length < 3 || !check_string(returnName))) || !is_unique_in_all_questionnaires(get_array, returnName)) {
		let oldName = returnName;
		returnName = prompt(formatError ? Lang.get("error_name_wrongFormat") : prompt_text, returnName);
		
		if(returnName == null) {
			if(isNew)
				return null;
			else
				returnName = oldName;
		}
	}
	return returnName;
}