import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {TitleRow} from "../widgets/TitleRow";
import {Questionnaire} from "../data/study/Questionnaire";
import {Section} from "../site/Section";
import {BtnAdd, BtnTrash} from "../widgets/BtnWidgets";
import {createUniqueName} from "../helpers/UniqueName";
import { SumScore } from "../data/study/SumScore";
import { DashRow } from "../widgets/DashRow";
import { DashElement } from "../widgets/DashElement";

export class Content extends SectionContent {
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}
	public title(): string {
		return Lang.get("edit_sumScores")
	}
	
	private addSumScore(questionnaire: Questionnaire): void {
		const name = createUniqueName(this.getStudyOrThrow())
		if(!name)
			return
		questionnaire.sumScores.push({name: name})
		this.newSection(`sumScoreEdit,qId:${questionnaire.internalId.get()},sumScoreI:${questionnaire.sumScores.get().length - 1}`)
	}
	private removeSumScore(questionnaire: Questionnaire, index: number): void {
		questionnaire.sumScores.remove(index)
		window.location.hash = `${this.section.getHash(this.section.depth)}`
	}

	private addVirtualInput(questionnaire: Questionnaire): void {
		const name = createUniqueName(this.getStudyOrThrow())
		if(!name)
			return
		questionnaire.virtualInputs.push(name)
	}

	private removeVirtualInput(questionnaire: Questionnaire, index: number): void {
		questionnaire.virtualInputs.remove(index)
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		if(!study.questionnaires.get().length)
			return <div class="center spacingTop">{Lang.get("info_no_questionnaires_created")}</div>
		
		return <div>
			{study.questionnaires.get().map((questionnaire) =>
				<div>
					{TitleRow(questionnaire.getTitle())}
					<div class="spacingTop spacingBottom">
						{DashRow(
							DashElement(null, {
								content: this.getSumScoresEntryView(questionnaire)
							}),
							DashElement(null, {
								content: this.getVirtualInputsEntryView(questionnaire)
							})
						)}
					</div>
				</div>
			)}
			
		</div>
	}

	private getSumScoresEntryView(questionnaire: Questionnaire): Vnode<any, any> {
		return <div>
			<h2 class="center">
				{Lang.getWithColon("sum_scores")}
			</h2>
			<div class="listParent">
				<div class="lastChild">
					{questionnaire.sumScores.get().map((SumScore, index) =>
						<div class="verticalPadding">
							{BtnTrash(this.removeSumScore.bind(this, questionnaire, index))}
							<a href={this.getUrl(`sumScoreEdit,qId:${questionnaire.internalId.get()},sumScoreI:${index}`)}>
								<span>{SumScore.name.get()}</span>
							</a>
						</div>
					)}
				</div>

				<div>
					{BtnAdd(this.addSumScore.bind(this, questionnaire), Lang.get("add"))}
				</div>
			</div>
		</div>
	}

	private getVirtualInputsEntryView(questionnaire: Questionnaire): Vnode<any, any> {
		return <div>
			<h2 class="center">
				{Lang.getWithColon("virtual_inputs")}
			</h2>
			<div class="listParent">
				<div class="lastChild">
					{questionnaire.virtualInputs.get().map((VirtualInput, index) =>
						<div class="verticalPadding">
							{BtnTrash(this.removeVirtualInput.bind(this, questionnaire, index))}
							<span>{VirtualInput.get()}</span>
						</div>
					)}
				</div>

				<div>
					{BtnAdd(this.addVirtualInput.bind(this, questionnaire), Lang.get("add"))}
				</div>
			</div>
		</div>
	}
}