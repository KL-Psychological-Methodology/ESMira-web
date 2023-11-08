import m, {Vnode} from "mithril";
import {LoaderState} from "./LoaderState";
import {SiteData} from "./SiteData";
import {Lang} from "../singletons/Lang";
import {SectionContent} from "./SectionContent";
import backSvg from "../../imgs/icons/back.svg?raw";
import {StaticValues} from "./StaticValues";
import {Study} from "../data/study/Study";
import {StudiesDataType} from "../loader/StudyLoader";
import {PrimitiveType} from "../observable/types/PrimitiveType";
import {DynamicValues} from "./DynamicValues";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {AdminToolsInterface} from "../admin/AdminToolsInterface";
import {Admin} from "../admin/Admin";

export class Section {
	public readonly depth: number
	public readonly dataCode: string
	private readonly staticValues: Record<string, string>
	
	public readonly allSections: Array<Section>
	public readonly sectionName: string
	public readonly sectionValue: string
	public readonly loader: LoaderState
	public readonly siteData: SiteData
	public sectionContent: SectionContent | null = null
	public readonly initPromise: Promise<Section>
	private setInitDone: (section: Section) => void = () => null
	
	public isMarked = false
	
	constructor(dataCode: string, siteData: SiteData, allSections: Array<Section>, depth: number = allSections.length) {
		this.depth = depth
		this.dataCode = dataCode
		this.allSections = allSections
		this.siteData = siteData
		this.initPromise = new Promise<Section>((resolve) => {
			this.setInitDone = resolve
		})
		
		this.loader = new LoaderState()
		
		const variables = dataCode.split(",")
		const [pageName, pageValue] = variables[0].split(":")
		
		const lastPage: Section | null = this.depth ? allSections[this.depth - 1] : null
		const lastStaticValues: Record<string, any> = lastPage?.staticValues || {}
		this.staticValues = { ... lastStaticValues } //creates a copy
		
		// additional values are variables:
		for (let i = variables.length - 1; i >= 1; --i) {
			const [key, value] = variables[i].split(":")
			this.staticValues[key] = value == undefined ? "1" : value
		}
		
		this.sectionName = pageName
		this.sectionValue = pageValue
	}
	
	public async load(): Promise<void> {
		return this.loader.showLoader(new Promise<void>(async (resolve, reject) => {
			try {
				await Lang.awaitPromise()
				
				const adminSuccessOrNotNeeded = await this.getAdmin().init()
				let actualPageName
				if(!adminSuccessOrNotNeeded) {
					this.siteData.onlyShowLastSection = true
					actualPageName = "login"
				}
				else
					actualPageName = this.sectionName
				
				const { Content } = await import(`../pages/${actualPageName}.tsx`)
				
				const loadResponses = await Promise.all(Content.preLoad(this))
				
				const sectionContent = new Content(this, ...loadResponses)
				await sectionContent.preInit(... loadResponses)
				this.sectionContent = sectionContent
				m.redraw()
				resolve()
				this.setInitDone(this)
			}
			catch(e) {
				reject(e)
			}
		}))
	}
	public reload(): Promise<void> {
		this.destroy()
		const section = new Section(this.dataCode, this.siteData, this.allSections, this.depth)
		this.allSections[this.depth] = section
		return section.load()
	}
	
	private async getQuestionnairePromise(): Promise<Study> {
		const studyLoader = this.siteData.studyLoader
		const isLoggedIn = this.siteData.admin.isLoggedIn()
		const qId = this.getStaticInt("qId")
		if(qId == null)
			throw new Error("Cannot load study. Missing id or qId")
		
		if(isLoggedIn) {
			const studyId = await studyLoader.getStudyIdFromQuestionnaireId(qId)
			this.staticValues["id"] = studyId.toString()
			return studyLoader.loadFullStudy(studyId)
		}
		
		const accessKey = this.siteData.dynamicValues.getOrCreateObs("accessKey", "").get()
		const studies = await studyLoader.loadAvailableStudies(accessKey)
		
		for(const currentStudyId in studies.get()) {
			const study = studies.getEntry(parseInt(currentStudyId))!
			const questionnaires = study.questionnaires.get()
			
			for(const questionnaire of questionnaires) {
				if(questionnaire.internalId.get() == qId) {
					const studyId = study.id.get()
					this.staticValues["id"] = studyId.toString()
					return isLoggedIn ? studyLoader.loadFullStudy(studyId) : study
				}
			}
		}
		throw new Error(`Cannot find questionnaire ${qId}`)
	}
	public async getStudyPromise(id: number = this.getStaticInt("id") ?? -1): Promise<Study> {
		const isLoggedIn = this.siteData.admin.isLoggedIn()
		
		if(id == -1)
			return this.getQuestionnairePromise()
		else {
			if(isLoggedIn)
				return this.siteData.studyLoader.loadFullStudy(id)
			else {
				const accessKey = this.siteData.dynamicValues.getOrCreateObs("accessKey", "").get()
				const studies = await this.siteData.studyLoader.loadAvailableStudies(accessKey)
				if(!studies.contains(id))
					throw new Error(`Study ${id} does not exist`)
				return studies.getEntry(id)!
			}
		}
	}
	
	public getAvailableStudiesPromise(accessKey: string): Promise<StudiesDataType> {
		return this.siteData.studyLoader.loadAvailableStudies(accessKey)
	}
	public getStrippedStudyListPromise(): Promise<StudiesDataType> {
		return this.siteData.studyLoader.loadStrippedStudyList()
	}
	
	
	public getTools(): AdminToolsInterface {
		return this.getAdmin().getTools()
	}
	public getAdmin(): Admin {
		return this.siteData.admin
	}
	
	public getStaticInt<T extends StaticValues>(key: T): number | null {
		return this.staticValues.hasOwnProperty(key) ? parseInt(this.staticValues[key]) : null
	}
	public getStaticString<T extends StaticValues>(key: T): string {
		return this.staticValues[key]?.toString()
	}
	
	public getDynamic<T extends PrimitiveType>(key: keyof DynamicValues, defaultValue: T): ObservablePrimitive<T> {
		return this.siteData.dynamicValues.getOrCreateObs(key, defaultValue)
	}
	
	private getSectionContentView(): Vnode<any, any> | undefined {
		try {
			return this.sectionContent?.getView()
		}
		catch(e: any) {
			this.loader.error(e.message || e)
			console.error(e)
		}
	}
	public getSectionTitle(): string {
		try {
			return this.sectionContent?.title() || Lang.get("state_loading")
		}
		catch(e: any) {
			console.error(e)
			this.loader.error(e.message || e)
			return Lang.get("error_unknown")
		}
	}
	private getSectionExtras(): Vnode<any, any> | string {
		try {
			return this.sectionContent?.titleExtra() || ""
		}
		catch(e) {
			return ""
		}
	}
	
	public getView(): Vnode<any, any> {
		return <div class={`section ${this.sectionName} fadeIn ${this.isMarked ? "pointOut" : ""}`}>
			<div class="sectionTop">
				<a href={this.backHash()} class="back">{m.trust(backSvg)}</a>
				<div class="sectionTitle">
					<div class="title" onclick={this.eventClick.bind(this)}>{this.getSectionTitle()}</div>
					<div class="extra">{this.getSectionExtras()}</div>
				</div>
			</div>
			<div class={`sectionContent ${this.sectionName}`}>{this.getSectionContentView()}</div>
			{this.loader.getView()}
		</div>
	}
	
	public getHash(depth: number = this.depth): string {
		const sections = this.allSections;
		const dataCodes: string[] = []
		for(let i = 0, max = Math.min(sections.length, depth + 1); i < max; ++i) {
			dataCodes.push(sections[i].dataCode)
		}
		return `#${dataCodes.join("/")}`;
	}
	public backHash(): string {
		return this.getHash(this.depth-1)
	}
	
	
	private eventClick(): void {
		this.siteData.currentSection = this.depth
		m.redraw()
	}
	
	public destroy(): void {
		if(this.sectionContent)
			this.sectionContent.destroy()
	}
}