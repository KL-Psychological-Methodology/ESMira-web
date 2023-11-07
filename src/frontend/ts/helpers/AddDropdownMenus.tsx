import {DropdownMenu, openDropdown} from "../widgets/DropdownMenu";
import {TranslatableObjectDataType} from "../observable/TranslatableObject";
import m, {Vnode} from "mithril";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {Lang} from "../singletons/Lang";
import editSvg from "../../imgs/icons/change.svg?raw"
import copySvg from "../../imgs/icons/copy.svg?raw"
import {SectionContent} from "../site/SectionContent";
import {Study} from "../data/study/Study";

export class AddDropdownMenus {
	private sectionContent: SectionContent
	
	constructor(sectionContent: SectionContent) {
		this.sectionContent = sectionContent
	}
	
	public async addStudy(target: Element): Promise<void> {
		const loaderState = this.sectionContent.section.loader
		const studyLoader = this.sectionContent.section.siteData.studyLoader
		await this.sectionContent.section.getStrippedStudyListPromise()
		await loaderState.showLoader(studyLoader.loadStrippedStudyList())
		
		openDropdown(
			"newDialog",
			target,
			(close) =>
				this.addStudyView(async (studyData) => {
					close()
					const id = await loaderState.showLoader(studyLoader.addStudy(studyData))
					if(id)
						this.sectionContent.newSection(`allStudies:edit/studyEdit,id:${id}`)
				}),
			{
				connectedDropdowns: ["studyList"]
			}
		)
	}
	
	private addStudyView(addStudy: (studyData: TranslatableObjectDataType) => any): Vnode<any, any> {
		const loaderState = this.sectionContent.section.loader
		const studyLoader = this.sectionContent.section.siteData.studyLoader
		
		return <div style="min-width: 500px">
			{DashRow(
				this.newButton(Lang.get("empty_study"), Lang.get("prompt_studyName"),
					(title) => addStudy({ title: title })
				),
				this.duplicateButton(Lang.get("duplicate_study"), (study, close) =>
					<li class={`clickable ${study.published.get() ? "" : "unPublishedStudy"}`} onclick={
						async () => {
							close()
							const fullStudy = await loaderState.showLoader(studyLoader.loadFullStudy(study.id.get()))
							addStudy(fullStudy.createJson())
						}
					}>{study.title.get()}</li>
				)
			)}
		</div>
	}
	
	public async addQuestionnaire(study: Study, target: Element): Promise<void> {
		const loaderState = this.sectionContent.section.loader
		const studyLoader = this.sectionContent.section.siteData.studyLoader
		await this.sectionContent.section.getStrippedStudyListPromise()
		await loaderState.showLoader(studyLoader.loadStrippedStudyList())
		
		openDropdown(
			"newDialog",
			target,
			(close) =>
				this.addQuestionnaireView(async (studyData) => {
					close()
					const questionnaire = await loaderState.showLoader(studyLoader.addQuestionnaire(study, studyData))
					
					if(this.sectionContent.section.sectionName == "qEdit")
						this.sectionContent.newSection(`qEdit,q:${questionnaire.internalId.get()}`, this.sectionContent.section.depth - 1)
					else
						this.sectionContent.newSection(`qEdit,q:${questionnaire.internalId.get()}`)
					this.sectionContent.setDynamic("questionnaireIndex", study.questionnaires.get().length - 1)
				}),
			{
				connectedDropdowns: ["studyList"]
			}
		)
	}
	private addQuestionnaireView(addSQuestionnaire: (questionnaireData: TranslatableObjectDataType) => any): Vnode<any, any> {
		const loaderState = this.sectionContent.section.loader
		const studyLoader = this.sectionContent.section.siteData.studyLoader
		const openedStudies: Record<number, boolean> = {}
		
		return <div style="min-width: 500px">
			{DashRow(
				this.newButton(Lang.get("empty_questionnaire"), Lang.get("prompt_newQuestionnaire"),
					(title) => addSQuestionnaire({ title: title, pages: [{}]})
				),
				this.duplicateButton(Lang.get("duplicate_questionnaire"), (study, close) => {
					if(study.questionnaires.get().length == 0)
						return
					const id = study.id.get()
					return <li class={`clickable ${study.published.get() ? "" : "unPublishedStudy"}`} onclick={() => {
						openedStudies[id] = !openedStudies[id]
					}}>
						{study.title.get()}
						{openedStudies[id] &&
							<ul>
								{study.questionnaires.get().map((questionnaire, index) =>
									<li class="clickable" onclick={
										async () => {
											close()
											const fullStudy = await loaderState.showLoader(studyLoader.loadFullStudy(id))
											const fullQuestionnaire = fullStudy.questionnaires.get()[index]
											addSQuestionnaire({
												title: fullQuestionnaire.getTitle(),
												pages: fullQuestionnaire.pages.createJson()
											})
										}
									}>{questionnaire.getTitle()}</li>
								)}
							</ul>
						}
					</li>
				})
			)}
		</div>
	}
	
	private newButton(btnTitle: string, promptString: string, add: (title: string) => void): Vnode<any, any> {
		return DashElement(null, {
			template: { title: btnTitle, icon: m.trust(editSvg) },
			onclick: () => {
				const title = prompt(promptString)
				if(!title || title.length < 3)
					return null
				add(title)
			}
		})
	}
	
	private duplicateButton(title: string, drawContent: (study: Study, close: () => void) => Vnode<any, any> | void): Vnode<any, any> {
		const tools = this.sectionContent.getTools()
		return DropdownMenu("studyList",
			DashElement(null, {
				template: { title: title, icon: m.trust(copySvg) },
				showAsClickable: true
			}),
			(close) => <ul style="min-width: 300px">
				<h2>{Lang.get("select_a_study")}</h2>
				{ this.sectionContent.section.siteData.studyLoader.getSortedStudyList()
					.filter((study) =>
						tools.isAdmin || tools.permissions.write.indexOf(study.id.get()) != -1
					)
					.map((study) =>
						drawContent(study, close)
					)}
			</ul>
		)
	}
}