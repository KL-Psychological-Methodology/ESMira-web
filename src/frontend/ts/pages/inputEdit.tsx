import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Study} from "../data/study/Study";
import {BindObservable, OnBeforeChangeTransformer} from "../widgets/BindObservable";
import {TitleRow} from "../widgets/TitleRow";
import {Input} from "../data/study/Input";
import {createUniqueName} from "../helpers/UniqueName";
import {DashRow} from "../widgets/DashRow";
import {InputOptionDesigner} from "../helpers/InputOptionDesigner";
import {DashElement} from "../widgets/DashElement";
import {Section} from "../site/Section";
import {SearchWidget} from "../widgets/SearchWidget";
import {NotCompatibleIcon} from "../widgets/NotCompatibleIcon";

type IndexContainer = { qIndex: number, pIndex: number, iIndex: number } | null

export class Content extends SectionContent {
	private indexContainer: IndexContainer = null
	
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}
	
	public title(): string {
		return this.getStaticString("input") ?? "Error"
	}

	private getInputIndices(study: Study, inputName: string): IndexContainer {
		let pIndex = -1
		let iIndex = -1
		const qIndex = study.questionnaires.get().findIndex((questionnaire) => {
			pIndex = questionnaire.pages.get().findIndex((page) => {
				iIndex = page.inputs.get().findIndex((input) => {
					return input.name.get() == inputName
				})
				return iIndex != -1
			})
			return pIndex != -1
		})

		return qIndex == -1 ? null : { qIndex: qIndex, pIndex: pIndex, iIndex: iIndex }
	}

	private getInputFromIndices(study: Study): Input | null {
		return this.indexContainer && study.questionnaires.get()[this.indexContainer.qIndex]?.pages.get()[this.indexContainer.pIndex]?.inputs.get()[this.indexContainer.iIndex]
	}

	private getInput(study: Study, inputName: string): Input | null {
		const input = this.getInputFromIndices(study)
		if(input) {
			if(input.name.get() != inputName)
				this.newSection(`inputEdit,input:${input.name.get()}`, this.section.depth-1)
			return input
		}
		
		this.indexContainer = this.getInputIndices(study, inputName)
		return this.getInputFromIndices(study)
	}

	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		let input = this.getInput(study, this.getStaticString("input") ?? "")
		if(!input)
			throw new Error(`Input ${this.getStaticString("input")} does not exist`)
		const subItemI = this.getStaticInt("subInput")
		if(subItemI != null) {
			input = input.subInputs.get()[subItemI]
			if(!input)
				throw new Error(`SubItem ${subItemI} in Input ${this.getStaticString("input")} does not exist`)
		}
		
		const inputDesigner = new InputOptionDesigner(study, input, this.getUrl.bind(this), this.newSection.bind(this))

		return <div>
			{subItemI == null &&
				<div class="center">
					<label class="horizontal">
						<small>{Lang.get("variable_name")}</small>
						<input type="text" {... BindObservable(input.name, new OnBeforeChangeTransformer<string>((before, after) => {
							return createUniqueName(study, after) ?? before
						}))}/>
					</label>
				</div>
			}
			
			
			{TitleRow(Lang.getWithColon("type"))}
			{SearchWidget((tools) =>
				<div class="inputSelector">
					<input placeholder={Lang.get("search")} class="search small vertical" type="text" onkeyup={tools.updateSearchFromEvent.bind(tools)}/>
					<div class="scrollBox noBorder">
						{
							inputDesigner.createTypesView(
								(title, inputViews)=> {
									return <div>
										<h2 class="center">{title}</h2>
										{DashRow(...inputViews)}
									</div>
								},
								(entry, isActive, onclick) => {
									return tools.searchView(
										entry.title,
										DashElement("cramped", {
											small: true,
											highlight: isActive,
											content:
												<div class="smallText">
													{entry.title}
													{entry.notCompatible && NotCompatibleIcon(... entry.notCompatible)}
												</div>,
											onclick: onclick
										})
									)
								})
						}
					</div>
				</div>
			)}
			
			{TitleRow(Lang.getWithColon("settings"))}
			{inputDesigner.getView()}
		</div>
	}
}