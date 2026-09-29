import m, {Vnode} from "mithril";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {Questionnaire} from "../data/study/Questionnaire";
import {Study} from "../data/study/Study";
import {SectionData} from "../site/SectionData";
import { SectionContent } from "../site/SectionContent";
import { Lang } from "../singletons/Lang";
import { previewPage } from "../components/Preview";

export class Content extends SectionContent {
	private readonly questionnaireIndex: ObservablePrimitive<number>
	private currentPage: number
	
	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [sectionData.getStudyPromise()]
	}
	
	constructor(sectionData: SectionData, study: Study) {
		super(sectionData)
		this.questionnaireIndex = this.getDynamic("questionnaireIndex", 0)
		this.currentPage = this.getStaticInt("pageI") ?? 0
	}
	
	public title(): string {
		return Lang.get("preview")
	}
	protected getAttendQuestionnaire(): Questionnaire {
		if(this.sectionData.sectionValue == "static") {
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
		const pages = questionnaire.pages.get()
		const page = pages[this.currentPage]
		
		const prevDisabled = this.currentPage <= 0
		const nextDisabled = this.currentPage >= pages.length - 1
		
		return <div>
			<small class="previewInfo line center">{Lang.get("preview_info")}</small>
			<hr/>
			{previewPage(page)}
			<div class="line horizontal hAlignSpaced spacingTop">
				<input type="button" disabled={prevDisabled} onclick={() => --this.currentPage} value={Lang.get("previous")}/>
				<div>{this.currentPage + 1} / {pages.length}</div>
				<input type="button" disabled={nextDisabled} onclick={() => ++this.currentPage} value={Lang.get("continue")}/>
			</div>
		</div>
	}
}