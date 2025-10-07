import m, {Vnode} from "mithril";
import {LoaderState} from "./LoaderState";
import {SiteData} from "./SiteData";
import {Lang} from "../singletons/Lang";
import {SectionContent} from "./SectionContent";
import backSvg from "../../imgs/icons/back.svg?raw";
import starEmptySvg from "../../imgs/icons/star_empty.svg?raw";
import starFilledSvg from "../../imgs/icons/star_filled.svg?raw";
import {SECTION_DELIMITER, SectionData} from "../singletons/HashData";
import {StaticValues} from "./StaticValues";
import {Study} from "../data/study/Study";
import {StudiesDataType} from "../loader/StudyLoader";
import {PrimitiveType} from "../observable/types/PrimitiveType";
import {DynamicValues} from "./DynamicValues";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {AdminToolsInterface} from "../admin/AdminToolsInterface";
import {Admin} from "../admin/Admin";
import { BookmarkLoader } from "../loader/BookmarkLoader";

/**
 * {@link Section} is responsible for displaying the {@link LoaderState} and loading the content.
 * The content is dynamically loaded and inherits from {@link SectionContent}.
 * Each {@link Section} is fully independent of each other. Their main source of data comes from
 * - {@link SiteData}: Holds the {@link StudyLoader} and other data and convenience methods. It is shared between all sections.
 * - {@link DynamicValues}: A Record saved in {@link SiteData} with observables that are shared between all sections - the only means of communicating between sections.
 * 		Should not be cached. That means the value needs to be reloaded every time the view gets recreated
 * - {@link StaticValues}: Values that are not changed (for the section) after initialisation of a section.
 * 		Each section has their own copy which includes copies of values from all previous sections.
 * 		It is not meant to store data but only as a way of accessing variables from the url hash
 *
 */
export class Section {
	public readonly depth: number
	public readonly dataCode: string
	public readonly fullDataCode: string
	private readonly staticValues: Record<string, string>
	public readonly cssRules: {aHeader: string, dashHeader: string, svgHeader: string}
	
	public readonly allSections: Array<Section>
	public readonly sectionName: string
	public readonly sectionValue: string
	public readonly loader: LoaderState
	public readonly siteData: SiteData
	public sectionContent: SectionContent | null = null
	public readonly initPromise: Promise<Section>
	private setInitDone: (section: Section) => void = () => null
	
	public isMarked = false
	
	constructor(sectionData: SectionData, siteData: SiteData, allSections: Array<Section>, depth: number = allSections.length) {
		const lastSection: Section | null = depth ? allSections[depth - 1] : null
		
		this.depth = depth
		this.dataCode = sectionData.dataCode
		this.fullDataCode = lastSection ? lastSection.fullDataCode + SECTION_DELIMITER + sectionData.dataCode : sectionData.dataCode
		this.allSections = allSections
		this.siteData = siteData
		this.initPromise = new Promise<Section>((resolve) => {
			this.setInitDone = resolve
		})
		
		this.loader = new LoaderState()
		
		const lastStaticValues: Record<string, any> = lastSection?.staticValues || {}
		this.staticValues = { ... lastStaticValues } //creates a copy
		const [sectionName, sectionValue] = sectionData.getValues(this.staticValues)
		
		this.sectionName = sectionName
		this.sectionValue = sectionValue
		
		if(lastSection) {
			const aRule = `.section.${lastSection.sectionName} a[href="#${this.fullDataCode}"]`
			this.cssRules = {
				aHeader: `${aRule}, ${aRule} span`,
				dashHeader: `${aRule}.dashLink`,
				svgHeader: `${aRule} svg`
			}
		}
		else {
			this.cssRules = {
				aHeader: "",
				dashHeader: "",
				svgHeader: ""
			}
		}
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
				
				let Content
				try {
					const importedContent = await import(`../pages/${actualPageName}.tsx`)
					Content = importedContent.Content
				}
				catch(e: any) {
					reject(Lang.get("error_pageNotFound", actualPageName))
					return
				}
				
				const loadResponses = await Promise.all(Content.preLoad(this))
				const sectionContent = new Content(this, ...loadResponses) as SectionContent
				await sectionContent.preInit(... loadResponses)
				this.sectionContent = sectionContent
				
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
		const section = new Section(new SectionData(this.dataCode), this.siteData, this.allSections, this.depth)
		this.allSections[this.depth] = section
		return section.load()
	}
	
	private async getStudyPromiseFromQuestionnaire(): Promise<Study> {
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
	public async getStudyPromiseFromAccessKey(id: number): Promise<Study> {
		const accessKey = this.getDynamic("accessKey", "").get()
		const studies = await this.siteData.studyLoader.loadAvailableStudies(accessKey)
		if(id != -1) {
			if(!studies.contains(id))
				throw new Error(`Study ${id} does not exist`)
			return studies.getEntry(id)!
		}
		else {
			if(studies.getCount() == 1) {
				const study = studies.getFirst()
				if(study) {
					this.staticValues["id"] = study.id.get().toString()
					return study
				}
			}
			
			throw new Error("Could not find study")
		}
	}
	public async getStudyPromise(id: number = this.getStaticInt("id") ?? -1): Promise<Study> {
		const isLoggedIn = this.siteData.admin.isLoggedIn()
		
		if(id == -1 && this.getStaticInt("qId") != null)
			return this.getStudyPromiseFromQuestionnaire()
		else if(isLoggedIn && id != -1)
			return this.siteData.studyLoader.loadFullStudy(id)
		else
			return this.getStudyPromiseFromAccessKey(id)
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
	public setStatic(key: string, value: number | string): void {
		this.staticValues[key] = value.toString()
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
					<div>
						<div class="extra">{this.getSectionExtras()}{this.getBookmark()}</div>
					</div>
				</div>
			</div>
			<div class={`sectionContent ${this.sectionName}`}>{this.getSectionContentView()}</div>
			{this.loader.getView()}
		</div>
	}
	
	private getBookmark(): Vnode<any, any> {
		const isLoggedIn = this.siteData.admin.isLoggedIn()
		if(!isLoggedIn)
			return <div></div>
		const isBookmarked = this.getAdmin().getTools().bookmarksLoader.hasBookmark(this.getHash())
		return <a 
			class={isBookmarked ? "bookmarkActive" : "bookmarkInactive"}
			title={Lang.get(isBookmarked ? "remove_bookmark" : "create_bookmark")}
			onclick={this.toggleBookmark.bind(this)}>
			{isBookmarked ? m.trust(starFilledSvg) : m.trust(starEmptySvg)}
		</a>
	}

	private toggleBookmark(): void {
		const bookmarksLoader: BookmarkLoader = this.getAdmin().getTools().bookmarksLoader
		const hash = this.getHash()
		if(bookmarksLoader.hasBookmark(hash)){
			bookmarksLoader.deleteBookmark(hash)
		} else {
			const defaultName = this.allSections.slice(1).map((section) => section.getSectionTitle()).join(" > ")
			const bookmarkName = prompt(Lang.get("prompt_bookmark_name"), defaultName)
			if(!bookmarkName)
				return
			bookmarksLoader.setBookmark(hash, bookmarkName)
		}
		
	}

	public getHash(depth: number = this.depth): string {
		if(!this.allSections.length) {
			return "#"
		}
		const allowedDepth = Math.min(this.allSections.length - 1, Math.max(depth, 0))
		return `#${this.allSections[allowedDepth].fullDataCode}`
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