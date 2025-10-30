import {SectionAlternative} from "./SectionContent";
import {SECTION_DELIMITER} from "./HashData";
import {Study} from "../data/study/Study";
import {SiteData} from "./SiteData";
import {AdminToolsInterface} from "../admin/AdminToolsInterface";
import {Admin} from "../admin/Admin";
import {StaticValues} from "./StaticValues";
import {PrimitiveType} from "../observable/types/PrimitiveType";
import {DynamicValues} from "./DynamicValues";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {StudiesDataType} from "../loader/StudyLoader";
import {LoaderState} from "./LoaderState";

interface SectionCallbacks {
	hasAlternatives(): boolean
	getAlternatives(): SectionAlternative[] | Promise<SectionAlternative[]> | null
	getSectionTitle(): string
	getSectionCallback(): Promise<any>
	setMarked(isMarked: boolean): void
	reload: () => Promise<void>
}

/**
 * Stores data for {@link Section} and is shared with other site elements (e.g. {@link NavigationRow})
 * Is held and defined iny {@link HashData}
 * @see {@link Section}
 */
export class SectionData {
	public readonly depth: number
	public readonly sectionName: string
	public readonly sectionValue: string
	public readonly dataCode: string
	public readonly fullDataCode: string
	public readonly staticValues: Record<string, string>
	public readonly cssRules: { aHeader: string, dashHeader: string, svgHeader: string }
	public readonly allSections: SectionData[]
	public readonly siteData: SiteData
	public readonly loader: LoaderState = new LoaderState()
	public callbacks?: SectionCallbacks
	
	constructor(depth: number, dataCode: string, allSections: SectionData[], siteData: SiteData) {
		this.depth = depth
		this.dataCode = dataCode
		this.allSections = allSections
		this.siteData = siteData
		
		const lastSectionData = depth ? allSections[depth - 1] : null
		const lastStaticValues: Record<string, any> = lastSectionData?.staticValues || {}
		this.staticValues = {...lastStaticValues} //creates a copy
		const [sectionName, sectionValue] = this.getValues(this.staticValues)
		this.sectionName = sectionName
		this.sectionValue = sectionValue
		this.fullDataCode = lastSectionData ? lastSectionData.fullDataCode + SECTION_DELIMITER + dataCode : dataCode
		
		if(lastSectionData) {
			const aRule = `.section.${lastSectionData.sectionName} a[href="#${this.fullDataCode}"]`
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
	
	private getValues(existingValues: Record<string, string>): [string, string] {
		const variables = this.dataCode.split(",")
		
		for(let i = variables.length - 1; i >= 1; --i) {
			const [key, value] = variables[i].split(":")
			existingValues[key] = value ?? "1"
		}
		
		return variables[0].split(":") as [string, string]
	}
	
	public getHash(depth: number = this.depth): string {
		if(!this.allSections.length) {
			return "#"
		}
		const allowedDepth = Math.min(this.allSections.length - 1, Math.max(depth, 0))
		return `#${this.allSections[allowedDepth].fullDataCode}`
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
	
	public getAvailableStudiesPromise(accessKey: string, filterOver: boolean = true): Promise<StudiesDataType> {
		return this.siteData.studyLoader.loadAvailableStudies(accessKey, filterOver)
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
	
	
	public backHash(): string {
		return this.getHash(this.depth-1)
	}
	
	/**
	 * Creates the hash url to a provided section. The returned hash also includes the path to the sections to the left of the current section.
	 * By default, the new section is added to the right of the current section (current depth + 1).
	 * By providing a depth, the new section is added to the specified depth instead.
	 *
	 * @see {@link HashData}
	 * @param name - The name (including its data code) of the target section.
	 * @param depth - Optional. The depth to add the new section to. Defaults to the current depth + 1.
	 * @returns The full url hash.
	 */
	public getUrl(name: string, depth: number = this.depth): string {
		return `${this.getHash(depth)}${SECTION_DELIMITER}${name}`
	}
	
	/**
	 * Returns the study associated with this section (or a second before this) or null if no study is associated.
	 * @param id - the study id. If not provided uses the hash url variable `id` instead.
	 * @returns the study or null if no study is associated.
	 */
	public getStudyOrNull(id: number = this.getStaticInt("id") ?? -1): Study | null {
		const studies = this.siteData.studyLoader.getStudies()
		if(id == -1)
			return studies.getCount() == 1 ? (studies.getFirst() || null) : null
		
		return studies.getEntry(id) ?? null
	}
	
	
	
	public getDynamic<T extends PrimitiveType>(key: keyof DynamicValues, defaultValue: T): ObservablePrimitive<T> {
		return this.siteData.dynamicValues.getOrCreateObs(key, defaultValue)
	}
}