import {Questionnaire} from "../data/study/Questionnaire";
import {Lang} from "../singletons/Lang";
import {Study} from "../data/study/Study";
import {checkString} from "../constants/methods";
import {StudyDataValues} from "./StudyDataValues";

function isUnique(questionnaires: Questionnaire[], name: string): boolean {
	for(let qI=questionnaires.length-1; qI>=0; --qI) {
		const questionnaire = questionnaires[qI]
		const names = StudyDataValues.getQuestionnaireVariableNames(questionnaire)
		
		let start = -1
		do {
			start = names.indexOf(name, start + 1)
			if(start != -1)
				return false
			
		} while(start != -1)
	}
	return true
}

export function createUniqueName(
	study: Study,
	originalName: string | null = null,
	createNewName: (oldName: string) => string | null = (oldName: string) => prompt(Lang.get("prompt_input_name"), oldName)
): string | null {
	let defaultName: string | null
	if(originalName == null) {
		defaultName = createNewName("")
		if(defaultName == null)
			return null
	}
	else
		defaultName = originalName
	
	let checkedName: string | null = defaultName
	const questionnaires = study.questionnaires.get()
	
	do {
		const formatError = (checkedName.length < 3 || !checkString(checkedName))
		
		if(formatError)
			checkedName = prompt(Lang.get("error_name_wrongFormat"))
		else if(isUnique(questionnaires, checkedName))
			return checkedName
		else
			checkedName = createNewName(checkedName)
		
	} while(checkedName)
	
	return checkedName
}