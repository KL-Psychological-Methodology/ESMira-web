import {SectionContent} from "../site/SectionContent";
import m, {Component, Vnode, VnodeDOM} from "mithril";
import {Lang} from "../singletons/Lang";
import {Study} from "../data/study/Study";
import {Questionnaire} from "../data/study/Questionnaire";
import {Input} from "../data/study/Input";
import {FILE_GET_QUESTIONNAIRE} from "../constants/urls";
import {Section} from "../site/Section";
import {AddJsToServerHtml} from "../helpers/AddJsToServerHtml";

interface ServerResponse {
	dataType: "questionnaire" | "finished" | "forwarded"
	sid: string
	currentPageInt: number
	pageHtml: string
	pageTitle: string
	missingInput: string
}

export class Content extends SectionContent {
	private pageTitle: string = ""
	private currentPageHtml: string = ""
	private currentPageInt: number = 0
	protected noCookieSID: string = ""
	private isFinished: boolean = false
	
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}
	
	public preInit(_study: Study): Promise<void> {
		return this.loadQuestionnaire()
	}
	
	public title(): string {
		return this.pageTitle
	}
	public titleExtra(): Vnode<any, any> | null {
		return <a class="smallText" href={"mailto: " + this.getStudyOrThrow().contactEmail.get()}>{Lang.get("contactEmail")}</a>
	}
	
	protected getAttendQuestionnaire(): Questionnaire {
		return this.getQuestionnaireOrThrow()
	}
	
	protected createUrl(): string {
		const studyId = this.getStaticInt("id") ?? -1
		const questionnaireId = this.getStaticInt("qId") ?? -1
		const accessKey = this.getDynamic("accessKey", "")
		
		return FILE_GET_QUESTIONNAIRE
			.replace("%d1", studyId.toString())
			.replace("%d2", questionnaireId.toString())
			.replace("%s1", accessKey.get())
			.replace("%s2", Lang.code)
			.replace("%s3", this.noCookieSID)
	}
	
	public async loadQuestionnaire(formData: string = "load"): Promise<void> {
		const response: ServerResponse = await this.section.loader.loadJson(this.createUrl(), "post", formData)
		this.noCookieSID = response.sid
		this.pageTitle = response.pageTitle
		
		switch(response.dataType) {
			case "questionnaire":
				this.currentPageHtml = response.pageHtml
				this.currentPageInt = response.currentPageInt
				
				if(response.missingInput) {
					const missingElement = document.getElementById(`item-${response.missingInput}`)
					if(!missingElement)
						return
					missingElement.scrollIntoView({behavior: 'smooth'})
					missingElement.style.outline = "5px solid red"
					window.setTimeout(() => {
						missingElement.style.outline = "unset"
					}, 3000)
				}
				break
			case "finished":
				this.isFinished = true
				break
			case "forwarded":
				this.currentPageHtml = response.pageHtml
		}
		
		m.redraw()
	}
	
	protected getQuestionnaireView(questionnaire: Questionnaire): Vnode<any, any> {
		return m(QuestionnaireComponent, {
			questionnaire: questionnaire,
			currentPageHtml: this.currentPageHtml,
			currentPageInt: this.currentPageInt,
			sectionContent: this
		})
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		const questionnaire = this.getAttendQuestionnaire()
		
		return this.isFinished
			? <div class="center spacingTop highlight">{study.webQuestionnaireCompletedInstructions.get() || Lang.get("default_webQuestionnaireCompletedInstructions")}</div>
			: this.getQuestionnaireView(questionnaire)
	}
}

interface QuestionnaireComponentOptions {
	questionnaire: Questionnaire
	currentPageHtml: string
	currentPageInt: number
	sectionContent: Content
}
class QuestionnaireComponent implements Component<QuestionnaireComponentOptions, any> {
	private rootView?: HTMLElement
	private handleInputs(input: Input): void {
		const form = document.forms.namedItem("sectionContent")
		if(!form)
			return
		const formElements = form.elements
		const child = formElements.namedItem(`responses[${input.name.get()}]`) as HTMLInputElement
		
		if(!child)
			return
		if(child.hasAttribute("was-processed"))
			return
		if(child instanceof Element)
			child.setAttribute("was-processed", "true")
		if(child instanceof RadioNodeList) {
			const entry =  (child[0] as HTMLInputElement)
			entry.setAttribute("was-processed", "true")
		}
		
		switch(input.responseType.get()) {
			case "va_scale":
				if(child?.getAttribute("no-value")) {
					child.classList.add("not-clicked")
					const wasClicked = () => {
						child.classList.remove("not-clicked")
						return true
					}
					child.addEventListener("mousedown", wasClicked)
					child.addEventListener("touchstart", wasClicked)
				}
				if(input.showValue.get()) {
					child.addEventListener("change", () => {
						if(child.previousElementSibling)
							(child.previousElementSibling as HTMLElement).innerText = child.value
					})
				}
				break
			default:
				break
		}
	}
	
	public oncreate(vNode: VnodeDOM<QuestionnaireComponentOptions, any>): void {
		this.rootView = vNode.dom as HTMLElement
	}
	public onupdate(vNode: VnodeDOM<QuestionnaireComponentOptions, any>): void {
		//update inputs:
		
		const inputs = vNode.attrs.questionnaire.pages.get()[vNode.attrs.currentPageInt].inputs.get()
		inputs.forEach(this.handleInputs)
		
		
		//add event to form:
		
		const forms = vNode.dom.getElementsByTagName("form")
		for(let form of forms as any) {
			if(form.getAttribute("was-processed"))
				continue
			form.setAttribute("was-processed", "1")
			form.addEventListener("submit", this.onFormSubmit.bind(this, vNode.attrs.sectionContent.loadQuestionnaire.bind(vNode.attrs.sectionContent)))
		}
		
		
		// add javascript content:
		
		AddJsToServerHtml.process(vNode.dom as HTMLElement, vNode.attrs.sectionContent)
	}
	
	private async onFormSubmit(loadQuestionnaire: (type: string) => Promise<void>, e: SubmitEvent): Promise<void> {
		e.preventDefault()
		e.stopPropagation()
		const formData = new FormData(e.target as HTMLFormElement)
		
		let data = (e.submitter as HTMLInputElement).name
		formData.forEach((value, key) => {
			data += `&${key}=${value}`
		})
		
		await loadQuestionnaire(data)
		window.setTimeout(() => this.rootView?.scrollIntoView({block: "start", behavior: 'smooth'}), 300)
	}
	
	public view(vNode: Vnode<QuestionnaireComponentOptions, any>): Vnode<any, any> {
		return <div class="questionnaireBox">
			{m.trust(vNode.attrs.currentPageHtml)}
		</div>
	}
}