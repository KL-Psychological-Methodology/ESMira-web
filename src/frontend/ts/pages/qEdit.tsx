import { SectionContent } from "../site/SectionContent";
import m, { Vnode } from "mithril";
import { Lang } from "../singletons/Lang";
import upSvg from "../../imgs/icons/moveUp.svg?raw"
import copySvg from "../../imgs/icons/copy.svg?raw"
import downSvg from "../../imgs/icons/moveDown.svg?raw"
import dataTableSvg from "../../imgs/icons/table.svg?raw"
import deleteSvg from "../../imgs/icons/trash.svg?raw"
import warnSvg from "../../imgs/icons/warn.svg?raw"
import { TabBar } from "../widgets/TabBar";
import { ObservablePrimitive } from "../observable/ObservablePrimitive";
import { Section } from "../site/Section";
import { Study } from "../data/study/Study";
import { Questionnaire } from "../data/study/Questionnaire";
import { ObservableLangChooser } from "../widgets/ObservableLangChooser";
import { BindObservable } from "../widgets/BindObservable";
import { TitleRow } from "../widgets/TitleRow";
import { Page } from "../data/study/Page";
import { safeConfirm } from "../constants/methods";
import { Input } from "../data/study/Input";
import { createUniqueName } from "../helpers/UniqueName";
import { DragContainer, DragTools } from "../widgets/DragContainer";
import { DashRow } from "../widgets/DashRow";
import { DashElement } from "../widgets/DashElement";
import { ObservableArray } from "../observable/ObservableArray";
import { ObservableStructureDataType } from "../observable/ObservableStructure";
import { BtnLikeSpacer } from "../widgets/BtnLikeSpacer";
import { DropdownMenu } from "../widgets/DropdownMenu";
import { AddDropdownMenus } from "../helpers/AddDropdownMenus";
import { BtnAdd, BtnCopy, BtnCustom, BtnEdit, BtnTransfer, BtnTrash } from "../widgets/BtnWidgets";
import { CodeEditor } from "../widgets/CodeEditor";
import { NotCompatibleIcon } from "../widgets/NotCompatibleIcon";

export class Content extends SectionContent {
	private readonly questionnaireIndex: ObservablePrimitive<number>
	private readonly addDropdownMenus = new AddDropdownMenus(this)

	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}

	constructor(section: Section) {
		super(section)
		this.questionnaireIndex = section.siteData.dynamicValues.getOrCreateObs("questionnaireIndex", 0)
	}

	public title(): string {
		return Lang.get("questionnaires")
	}
	public titleExtra(): Vnode<any, any> | null {
		if (this.getStudyOrThrow().questionnaires.get().length == 0)
			return null
		return <a href={this.getUrl("demo")}>
			{BtnCustom(m.trust(dataTableSvg), undefined, Lang.get("preview"))}
		</a>
	}


	private addQuestionnaire(e: MouseEvent): Promise<void> {
		const study = this.getStudyOrThrow()
		return this.addDropdownMenus.addQuestionnaire(study, e.target as Element)
	}
	private copyQuestionnaire(study: Study, questionnaire: Questionnaire, index: number): void {
		const newQuestionnaire = study.questionnaires.addCopy(questionnaire, index)
		this.section.siteData.studyLoader.autoValidateQuestionnaire(study, newQuestionnaire)
	}
	private deleteQuestionnaire(study: Study, questionnaire: Questionnaire, index: number): void {
		if (!safeConfirm(Lang.get("confirm_delete_questionnaire", questionnaire.getTitle())))
			return

		let internalId = questionnaire.internalId.get();

		study.questionnaires.remove(index)

		const questionnairesArray = study.questionnaires.get()
		//remove specificGroupInternalId in eventTriggers:
		for (let qI = questionnairesArray.length - 1; qI >= 0; --qI) {
			let triggers = questionnairesArray[qI].actionTriggers.get();
			for (let triggerI = triggers.length - 1; triggerI >= 0; --triggerI) {
				let eventTriggers = triggers[triggerI].eventTriggers.get();
				for (let cueI = eventTriggers.length - 1; cueI >= 0; --cueI) {
					let cue = eventTriggers[cueI];
					if (cue.specificQuestionnaireInternalId.get() === internalId) {
						cue.specificQuestionnaireEnabled.set(false);
						cue.specificQuestionnaireInternalId.set(-1);
					}
				}
			}
		}
	}


	private addPage(questionnaire: Questionnaire): void {
		questionnaire.pages.push({})
	}
	private copyPage(page: Page, index: number): void {
		const study = this.getStudyOrThrow()
		const newPage = (page.parent as ObservableArray<ObservableStructureDataType, Page>).addCopy(page, index)

		this.section.siteData.studyLoader.autoValidatePage(study, newPage)
	}
	private transferPage(oldQuestionnaire: Questionnaire, oldPageIndex: number, newQuestionnaire: Questionnaire, close: () => void): void {
		newQuestionnaire.pages.moveFromOtherList(oldQuestionnaire.pages, oldPageIndex, newQuestionnaire.pages.get().length)
		close()
	}
	private deletePage(questionnaire: Questionnaire, index: number): void {
		if (!safeConfirm(Lang.get("confirm_delete_inputPage")))
			return

		questionnaire.pages.remove(index)
		window.location.hash = `${this.section.getHash(this.section.depth)}`
	}
	private movePage(questionnaire: Questionnaire, pIndex: number, direction: number): void {
		const pagesObs = questionnaire.pages;
		const pages = pagesObs.get()
		const temp = pages[pIndex]
		pages[pIndex] = pages[pIndex + direction]
		pages[pIndex + direction] = temp
		pagesObs.hasMutated()

		window.setTimeout(function () { //wait until changes took effect
			const parentBox = document.getElementById("questionnaireEditBox")
			parentBox?.children[pIndex + direction].scrollIntoView({ behavior: "smooth", block: "start" });
		}, 100);
	}
	private movePageUp(questionnaire: Questionnaire, pIndex: number): void {
		this.movePage(questionnaire, pIndex, -1)
	}
	private movePageDown(questionnaire: Questionnaire, pIndex: number): void {
		this.movePage(questionnaire, pIndex, +1)
	}

	private hasPages(questionnaire: Questionnaire): boolean {
		return questionnaire.pages.get().length > 0
	}

	private hasInputs(questionnaire: Questionnaire): boolean {
		return questionnaire.pages.get().some(value => value.inputs.get().length > 0)
	}

	private addInput(questionnaire: Questionnaire, pageI: number): void {
		const name = createUniqueName(this.getStudyOrThrow())
		if (!name)
			return

		questionnaire.pages.get()[pageI].inputs.push({ name: name })
		this.newSection(`inputEdit,input:${btoa(name)}`)
	}
	private copyInput(input: Input, index: number): void {
		const newName = createUniqueName(this.getStudyOrThrow(), input.name.get())
		if (!newName)
			return
		const newInput = (input.parent as ObservableArray<ObservableStructureDataType, Input>).addCopy(input, index)
		newInput.name.set(newName)
	}
	private deleteInput(page: Page, index: number): void {
		if (!safeConfirm(Lang.get("confirm_delete_input")))
			return

		page.inputs.remove(index)
		window.location.hash = `${this.section.getHash(this.section.depth)}`
	}


	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		if (study.questionnaires.get().length == 0)
			return <div class="center spacingTop">{BtnAdd(this.addQuestionnaire.bind(this), Lang.get("create"))}</div>

		return TabBar(this.questionnaireIndex, study.questionnaires.get().map((questionnaire, index) => {
			return {
				title: <div>{this.hasInputs(questionnaire) ? <div>{questionnaire.getTitle()}</div> : <div><div class="inlineIcon">{m.trust(warnSvg)}</div>{questionnaire.getTitle()}</div>}</div>,
				draggableList: study.questionnaires,
				view: () => this.getQuestionnaireView(study, questionnaire, index)
			}
		}), false, this.addQuestionnaire.bind(this))
	}

	private getQuestionnaireView(study: Study, questionnaire: Questionnaire, qIndex: number): Vnode<any, any> {
		return <div>
			{DashRow(
				DashElement("stretched",
					{ floating: true, template: { title: Lang.get("copy"), icon: m.trust(copySvg) }, onclick: this.copyQuestionnaire.bind(this, study, questionnaire, qIndex + 1) },
					{ floatingRight: true, highlight: true, template: { title: Lang.get("delete"), icon: m.trust(deleteSvg) }, onclick: this.deleteQuestionnaire.bind(this, study, questionnaire, qIndex) },
					{
						content:
							<div class="listParent verticalPadding floatingSpaceLeft floatingSpaceRight">
								<div>
									<label class="line">
										<small>{Lang.get("questionnaire_name")}</small>
										<input class="big" type="text" {...BindObservable(questionnaire.title)} />
										{ObservableLangChooser(study)}
									</label>
								</div>
								<div class="listChild">
									<label>
										<input type="checkbox" {...BindObservable(questionnaire.isBackEnabled)} />
										<span>{Lang.get("allow_back_button")}</span>
									</label>
									<label>
										<input type="checkbox" {...BindObservable(questionnaire.showInDisabledList)} />
										<span>{Lang.get("show_in_disabled_list")}</span>
										<small>{Lang.get("show_in_disabled_list_desc")}</small>
									</label>
									{!this.hasInputs(questionnaire) ?
										<label>
											<div class="inlineIcon">{m.trust(warnSvg)}</div>
											<span>{Lang.get("questionnaire_no_inputs")}</span>
										</label> : <div></div>
									}
								</div>
							</div>
					}),
				DashElement("stretched", {
					content:
						<div>
							<div class="fakeLabel line">
								<small>{Lang.get("questionnaire_end_script")}{NotCompatibleIcon("Web")}</small>
								{CodeEditor(questionnaire.endScriptBlock)}
							</div>
						</div>
				})
			)}

			{
				DragContainer((dragTools) => {
					return <div id="questionnaireEditBox">
						{questionnaire.pages.get().map((page, pageIndex) => this.getPageView(dragTools, study, questionnaire, qIndex, page, pageIndex))}
					</div>
				})
			}
			<hr />
			<div class="dragHidden spacingBottom center">
				{BtnAdd(this.addPage.bind(this, questionnaire), Lang.get("add_page"))}
			</div>
		</div>
	}

	private getPageView(dragTools: DragTools, study: Study, questionnaire: Questionnaire, qIndex: number, page: Page, pageIndex: number): Vnode<any, any> {
		return <div class="spacingTop">
			{TitleRow(
				<div class="spacingTop">
					<div class="title flexGrow flexCenter">{Lang.get("questionnaire_edit_title", (pageIndex + 1), page.inputs.get().length)}</div>

					<div class="nowrap flexCenter">
						{BtnTrash(this.deletePage.bind(this, questionnaire, pageIndex))}
						{BtnCopy(this.copyPage.bind(this, page, pageIndex + 1))}
						{
							DropdownMenu("transferPage",
								BtnTransfer(),
								(close) => <ul>
									<h2 class="nowrap">{Lang.getWithColon("transfer_to_other_questionnaire")}</h2>
									{study.questionnaires.get().map((selectedQuestionnaire) =>
										<li class="clickable nowrap" onclick={this.transferPage.bind(this, questionnaire, pageIndex, selectedQuestionnaire, close)}>{selectedQuestionnaire.getTitle()}</li>
									)}
								</ul>
							)
						}

						{pageIndex < questionnaire.pages.get().length - 1 ?
							<div class="btn horizontal clickable" onclick={this.movePageDown.bind(this, questionnaire, pageIndex)}>
								{m.trust(downSvg)}
							</div> : BtnLikeSpacer()
						}
						{pageIndex > 0 ?
							<div class="btn horizontal clickable" onclick={this.movePageUp.bind(this, questionnaire, pageIndex)}>
								{m.trust(upSvg)}
							</div> : BtnLikeSpacer()
						}
						<a class="spacingLeft" href={this.getUrl(`pageSettings,qId:${questionnaire.internalId.get()},pageI:${pageIndex}`)}>
							{BtnEdit()}
						</a>
					</div>
				</div>
			)}

			{page.inputs.get().length == 0 && dragTools.getDragTarget(0, page.inputs)}


			<div class="coloredLines spacingTop">
				{page.inputs.get().map((input, inputIndex) => {
					return dragTools.getDragTarget(inputIndex, page.inputs,
						<div class="line horizontalPadding verticalPadding flexHorizontal">
							<div className="flexCenter spacingRight">
								{dragTools.getDragStarter(inputIndex, page.inputs)}
							</div>

							<div class="flexGrow">
								<div class="verticalPadding highlight smallText">{`${Lang.getDynamic("input_" + input.responseType.get())} ${input.required.get() ? '*' : ''} (${input.name.get()})`}</div>

								<div class="verticalPadding">{m.trust(input.text.get())}</div>
							</div>

							<div class="nowrap flexCenter">
								{BtnTrash(this.deleteInput.bind(this, page, inputIndex))}
								{BtnCopy(this.copyInput.bind(this, input, inputIndex + 1))}
								<a class="spacingLeft" href={this.getUrl(`inputEdit,input:${btoa(input.name.get())}`)}>
									{BtnEdit()}
								</a>
							</div>
						</div>
					)
				})}
			</div>
			<div class="dragHidden verticalPadding spacingLeft">
				{BtnLikeSpacer()}
				{BtnAdd(this.addInput.bind(this, questionnaire, pageIndex), Lang.get("add_item"))}
			</div>
		</div>
	}
}