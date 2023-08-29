import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {TitleRow} from "../widgets/TitleRow";
import {Questionnaire} from "../data/study/Questionnaire";
import {Section} from "../site/Section";
import {BtnAdd, BtnTrash} from "../widgets/BtnWidgets";

export class Content extends SectionContent {
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}
	public title(): string {
		return Lang.get("edit_sumScores")
	}
	
	private addSumScore(questionnaire: Questionnaire): void {
		questionnaire.sumScores.push({})
	}
	private removeSumScore(questionnaire: Questionnaire, index: number): void {
		questionnaire.sumScores.remove(index)
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		if(!study.questionnaires.get().length)
			return <div class="center spacingTop">{Lang.get("info_no_questionnaires_created")}</div>
		
		return <div>
			{study.questionnaires.get().map((questionnaire) =>
				<div>
					{TitleRow(questionnaire.getTitle())}
					<div class="listParent">
						<div class="listChild">
							{questionnaire.sumScores.get().map((sumScore, index) =>
								<div class="verticalPadding">
									{BtnTrash(this.removeSumScore.bind(this, questionnaire, index))}
									<a href={this.getUrl(`sumScoreEdit,qId:${questionnaire.internalId.get()},sumScoreI:${index}`)}>
										<span>{sumScore.name.get()}</span>
									</a>
								</div>
							)}
						</div>
						
						<div>
							{BtnAdd(this.addSumScore.bind(this, questionnaire), Lang.get("add"))}
						</div>
					</div>
				</div>
			)}
			
		</div>
	}
}