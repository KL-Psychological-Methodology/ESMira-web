import {ObservableStructure} from "../../observable/ObservableStructure";

export class EventTrigger extends ObservableStructure {
	public label								= this.primitive<string>(		"label",							"Event")
	public cueCode								= this.primitive<string>(		"cueCode",							"joined")
	public skipThisQuestionnaire				= this.primitive<boolean>(		"skipThisQuestionnaire",			false)
	public specificQuestionnaireEnabled			= this.primitive<boolean>(		"specificQuestionnaireEnabled",	false)
	public specificQuestionnaireInternalId		= this.primitive<number>(		"specificQuestionnaireInternalId",	-1)
	public randomDelay							= this.primitive<boolean>(		"randomDelay",						false)
	public delaySec								= this.primitive<number>(		"delaySec",						0)
	public delayMinimumSec						= this.primitive<number>(		"delayMinimumSec",					0)
}