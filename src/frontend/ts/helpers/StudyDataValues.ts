import {Study} from "../data/study/Study";
import {Questionnaire} from "../data/study/Questionnaire";
import {Input} from "../data/study/Input";

function setResponseTypeValues(input: Input, variables: string[]): void {
	const name = input.name.get()
	switch(input.responseType.get()) {
		case "text":
			return
		case "app_usage":
			variables.push(name+"~usageTimeYesterday")
			variables.push(name+"~usageCountYesterday")
			variables.push(name+"~usageTimeToday")
			variables.push(name+"~usageCountToday")
			variables.push(name);
			return
		case "bluetooth_devices":
			variables.push(name+"~devices")
			variables.push(name)
			return
		case "dynamic_input":
			variables.push(name+"~index")
			variables.push(name)
			return
		case "list_multiple":
			variables.push(name)
			input.listChoices.get().forEach((listChoice) => {
				variables.push(name + "~" + listChoice.get())
			})
			return
		default:
			variables.push(name)
			return
	}
}

export const StudyDataValues = {
	getQuestionnaireVariableNames(questionnaire: Questionnaire): string[] {
		const variables: string[] = []
		for(const page of questionnaire.pages.get()) {
			for(const input of page.inputs.get()) {
				variables.push(input.name.get())
			}
		}
		
		for(const sumScore of questionnaire.sumScores.get()) {
			variables.push(sumScore.name.get());
		}
		
		return variables
	},
	
	getQuestionnaireVariables(questionnaire: Questionnaire): string[] {
		const variables: string[] = []
		for(const page of questionnaire.pages.get()) {
			for(const input of page.inputs.get()) {
				setResponseTypeValues(input, variables)
			}
		}
		
		questionnaire.sumScores.get().forEach((sumScore) => {
			variables.push(sumScore.name.get());
		})
		
		return variables
	},
	
	getStudyVariables(study: Study): string[] {
		let variables: string[] = [];
		let questionnaires = study.questionnaires.get();
		questionnaires.forEach((questionnaire) => {
			variables = variables.concat(this.getQuestionnaireVariables(questionnaire))
		})
		
		return variables;
	}
}