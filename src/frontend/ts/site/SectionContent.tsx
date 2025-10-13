import {Vnode} from "mithril";
import {Study} from "../data/study/Study";
import {StaticValues} from "./StaticValues";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {Questionnaire} from "../data/study/Questionnaire";
import {DynamicValues} from "./DynamicValues";
import {PrimitiveType} from "../observable/types/PrimitiveType";
import {AccountPermissions} from "../admin/AccountPermissions";
import {AdminToolsInterface} from "../admin/AdminToolsInterface";
import {Admin} from "../admin/Admin";
import {SECTION_DELIMITER} from "./HashData";
import {SectionData} from "./SectionData";

export interface SectionAlternative {
	title: string
	target: string | false
	header?: boolean
}

/**
 * The dynamic content class of each section. Each section extends this class. SectionContent is a child of {@link Section} which takes care of loading and
 * displaying SectionContent.
 * Important: Changes to Observables in data.dataTypes will lead to {@link m.redraw()} (see {@link StudyLoader.constructor()}).
 * BUT, changes to the dom will happen asynchronously. In other words: The philosophy is, that {@link getView()} always works with fresh (not cached) data
 * and always constructs the full section (instead of just updating it). Mithril will take care of keeping track of changes and updates the dom when necessary.
 *
 * Method calls are guaranteed to be in this order:
 * 1. {@link preLoad()}
 * 2. {@link preInit()}
 * 3. {@link title()}, {@link titleExtra()}, {@link getView()}
 * Have a look at the implementation in {@link Section.load()} for more information
 */
export abstract class SectionContent {
	public readonly sectionData: SectionData
	
	constructor(section: SectionData) {
		this.sectionData = section
	}
	
	/**
	 * Is always called before anything else.
	 * @returns Promise array. The section will be in loading state and not other methods will be called as long as these Promises are loading
	 */
	public static preLoad(_sectionData: SectionData): Promise<any>[] {
		return []
	}
	
	/**
	 * Is guaranteed to run AFTER all promises in {@link preLoad()} are finished
	 * @param _responses Holds the return values of each Promise from {@link preLoad()}
	 */
	public preInit(... _responses: any): Promise<any> {
		return Promise.resolve()
	}
	public getSectionCallback(): any {
		return null
	}
	public hasAlternatives(): boolean {
		return false
	}
	public getAlternatives(): SectionAlternative[] | Promise<SectionAlternative[]> | null {
		return null
	}
	
	public abstract title(): string
	
	public titleExtra(): Vnode<any, any> | null {
		return null
	}
	
	
	public getDynamic<T extends PrimitiveType>(key: keyof DynamicValues, defaultValue: T): ObservablePrimitive<T> {
		return this.sectionData.getDynamic(key, defaultValue)
	}
	public setDynamic<T extends PrimitiveType>(key: keyof DynamicValues, newValue: T) {
		this.sectionData.siteData.dynamicValues.setChild(key, newValue)
	}
	public getStaticInt<T extends StaticValues>(key: T): number | null {
		return this.sectionData.getStaticInt(key)
	}
	public getStaticString<T extends StaticValues>(key: T): string | null {
		return this.sectionData.getStaticString(key)
	}
	
	/**
	 * @see {@link SectionData.getStudyOrNull()}
	 */
	public getStudyOrNull(id: number = this.getStaticInt("id") ?? -1): Study | null {
		return this.sectionData.getStudyOrNull(id)
	}
	public getStudyOrThrow(id: number = this.getStaticInt("id") ?? -1): Study {
		const study = this.getStudyOrNull(id)
		if(!study)
			throw new Error(`Study ${id} does not exist!`)
		return study
	}
	public getQuestionnaireOrNull(qId: number = this.getStaticInt("qId") ?? -1, study: Study | null = this.getStudyOrNull()): Questionnaire | null {
		if(!study)
			return null
		const questionnaires = this.getStudyOrThrow().questionnaires.get()
		for(const questionnaire of questionnaires) {
			if(questionnaire.internalId.get() == qId)
				return questionnaire
		}
		return null
	}
	public getQuestionnaireOrThrow(qId: number = this.getStaticInt("qId") ?? -1): Questionnaire {
		const questionnaire = this.getQuestionnaireOrNull(qId, this.getStudyOrThrow())
		if(!questionnaire)
			throw new Error(`Questionnaire ${qId} does not exist!`)
		else
			return questionnaire
	}
	
	public getTools(): AdminToolsInterface {
		return this.sectionData.getTools()
	}
	public getAdmin(): Admin {
		return this.sectionData.getAdmin()
	}
	
	public hasPermission(name: keyof AccountPermissions, studyId: number): boolean {
		return this.getAdmin().isLoggedIn() && (this.getTools().hasPermission(name, studyId) ?? false)
	}
	
	/**
	 * @see {@link SectionData.getUrl()}
	 */
	public getUrl(name: string, depth: number = this.sectionData.depth): string {
		return this.sectionData.getUrl(name, depth)
	}
	public goTo(target: string): void {
		window.location.hash = "#"+target;
	}
	public newSection(target: string, depth: number = this.sectionData.depth): void {
		window.location.hash = depth == -1 ? target : `${this.sectionData.getHash(depth)}${SECTION_DELIMITER}${target}`
	}
	
	public abstract getView(): Vnode<any, any>
	
	public destroy(): void {
		//do nothing
	}
}