import m, {Vnode} from "mithril";
import {Content as AttendContent} from "../pages/attend";
import {FILE_GET_QUESTIONNAIRE} from "../constants/urls";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {ObserverId} from "../observable/BaseObservable";
import {Questionnaire} from "../data/study/Questionnaire";
import {Study} from "../data/study/Study";

export class Content extends AttendContent {
	private readonly questionnaireIndex: ObservablePrimitive<number>
	private readonly indexObserverId: ObserverId
	private langObserverId: ObserverId
	
	constructor(section: Section, study: Study) {
		super(section)
		this.questionnaireIndex = this.getDynamic("questionnaireIndex", 0)
		this.indexObserverId = this.questionnaireIndex.addObserver(() => {
			this.loadQuestionnaire()
		})
		this.langObserverId = study.currentLangCode.addObserver(() => {
			this.loadQuestionnaire()
		})
	}
	
	protected createUrl(): string {
		const studyId = this.getStaticInt("id") ?? -1
		const questionnaire = this.getAttendQuestionnaire()
		const accessKey = this.getDynamic("accessKey", "").get()

		return FILE_GET_QUESTIONNAIRE
			.replace("%d1", studyId.toString())
			.replace("%d2", questionnaire.internalId.get().toString())
			.replace("%s1", accessKey)
			.replace("%s2", Lang.code)
			.replace("%s3", `demo=1&${this.noCookieSID}`)
	}
	
	protected getAttendQuestionnaire(): Questionnaire {
		if(this.section.sectionValue == "static") {
			return this.getQuestionnaireOrThrow()
		}
		else {
			const study = this.getStudyOrThrow()
			const questionnaireIndex = this.questionnaireIndex.get()
			return study.questionnaires.get()[questionnaireIndex]
		}
	}
	
	public getView(): Vnode<any, any> {
		
		const questionnaire = this.getAttendQuestionnaire()
		
		return <div>
			{questionnaire.isDifferent() &&
				<div class="highlight center">
					{Lang.get("questionnaire_outdated")}
				</div>
			}
			{this.getQuestionnaireView(questionnaire)}
		</div>
	}
	
	destroy(): void {
		super.destroy()
		this.indexObserverId.removeObserver()
		this.langObserverId.removeObserver()
	}
}