import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {BindObservable, OnBeforeChangeTransformer} from "../widgets/BindObservable";
import {TitleRow} from "../widgets/TitleRow";
import {ObservableArray} from "../observable/ObservableArray";
import {createUniqueName} from "../helpers/UniqueName";
import {StudyDataValues} from "../helpers/StudyDataValues";
import {Section} from "../site/Section";
import {BaseObservable} from "../observable/BaseObservable";
import {BtnTrash} from "../widgets/BtnWidgets";

export class Content extends SectionContent {
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}
	public title(): string {
		return this.getQuestionnaireOrThrow().sumScores.get()[this.getStaticInt("sumScoreI") ?? 0].name.get()
	}
	
	private addEntry(list: ObservableArray<string, BaseObservable<string>>, e: InputEvent): void {
		const element = e.target as HTMLSelectElement
		
		list.push(element.value)
		element.selectedIndex = 0
	}
	
	private removeEntry(list: ObservableArray<string, BaseObservable<string>>, index: number): void {
		list.remove(index)
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		const questionnaire = this.getQuestionnaireOrThrow()
		const variables = StudyDataValues.getQuestionnaireVariables(questionnaire)
		const sumScore = questionnaire.sumScores.get()[this.getStaticInt("sumScoreI") ?? 0]
		if(!sumScore)
			throw new Error(`SumScore does not exist!`)
		
		return <div>
			<div class="center">
				<label>
					<small>{Lang.get("variable_name")}</small>
					
					<input type="text" {... BindObservable(sumScore.name, new OnBeforeChangeTransformer<string>((before, after) => {
						return createUniqueName(study, after) ?? before
					}))}/>
				</label>
			</div>
			
			{TitleRow(Lang.getWithColon("sum"))}
			<div class="listParent">
				<div class="listChild">
					{sumScore.addList.get().map((entry, index) =>
						<div class="verticalPadding">
							<span>&#x271A;</span>
							<span>{entry.get()}</span>
							{BtnTrash(this.removeEntry.bind(this, sumScore.addList, index))}
						</div>
					)}
					{sumScore.subtractList.get().map((entry, index) =>
						<div class="verticalPadding">
							<span>&#9866;</span>
							<span>{entry.get()}</span>
							{BtnTrash(this.removeEntry.bind(this, sumScore.subtractList, index))}
						</div>
					)}
				</div>
				
				<div>
					<select class="smallText" onchange={this.addEntry.bind(this, sumScore.addList)}>
						<option>{Lang.get("select_to_add")}</option>
						{variables.map((variable) => <option>{variable}</option>)}
					</select>
					<br/>
					<select class="smallText " onchange={this.addEntry.bind(this, sumScore.subtractList)}>
						<option>{Lang.get("select_to_subtract")}</option>
						{variables.map((variable) => <option>{variable}</option>)}
					</select>
				</div>
			</div>
		</div>
	}
}