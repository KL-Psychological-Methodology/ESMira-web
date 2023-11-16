import m, {Vnode} from "mithril";
import {Section} from "./Section";
import {Study} from "../data/study/Study";
import {StaticValues} from "./StaticValues";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {Questionnaire} from "../data/study/Questionnaire";
import {DynamicValues} from "./DynamicValues";
import {PrimitiveType} from "../observable/types/PrimitiveType";
import {AccountPermissions} from "../admin/AccountPermissions";
import {AdminToolsInterface} from "../admin/AdminToolsInterface";
import {Admin} from "../admin/Admin";

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
	public readonly section: Section
	
	constructor(section: Section) {
		this.section = section
	}
	
	/**
	 * Is always called before anything else.
	 * @returns Promise array. The section will be in loading state and not other methods will be called as long as these Promises are loading
	 */
	public static preLoad(_section: Section): Promise<any>[] {
		return []
	}
	
	/**
	 * Is guaranteed to run AFTER all promises in {@link preLoad()} are finished
	 * @param _responses Holds the return values of each Promise from {@link preLoad()}
	 */
	public preInit(... _responses: any): Promise<any> {
		return Promise.resolve()
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
		return this.section.getDynamic(key, defaultValue)
	}
	public setDynamic<T extends PrimitiveType>(key: keyof DynamicValues, newValue: T) {
		this.section.siteData.dynamicValues.set(key, newValue)
	}
	public getStaticInt<T extends StaticValues>(key: T): number | null {
		return this.section.getStaticInt(key)
	}
	public getStaticString<T extends StaticValues>(key: T): string | null {
		return this.section.getStaticString(key)
	}
	protected getStudyOrNull(id: number = this.getStaticInt("id") ?? -1): Study | null {
		return this.section.siteData.studyLoader.getStudies().getEntry(id) ?? null
	}
	public getStudyOrThrow(id: number = this.getStaticInt("id") ?? -1): Study {
		const study = this.getStudyOrNull(id)
		if(!study)
			throw new Error(`Study ${id} does not exist!`)
		return study
	}
	protected getQuestionnaireOrNull(qId: number = this.getStaticInt("qId") ?? -1, study: Study | null = this.getStudyOrNull()): Questionnaire | null {
		if(!study)
			return null
		const questionnaires = this.getStudyOrThrow().questionnaires.get()
		for(const questionnaire of questionnaires) {
			if(questionnaire.internalId.get() == qId)
				return questionnaire
		}
		return null
	}
	protected getQuestionnaireOrThrow(qId: number = this.getStaticInt("qId") ?? -1): Questionnaire {
		const questionnaire = this.getQuestionnaireOrNull(qId, this.getStudyOrThrow())
		if(!questionnaire)
			throw new Error(`Questionnaire ${qId} does not exist!`)
		else
			return questionnaire
	}
	
	public getTools(): AdminToolsInterface {
		return this.section.getTools()
	}
	public getAdmin(): Admin {
		return this.section.getAdmin()
	}
	
	public hasPermission(name: keyof AccountPermissions, studyId: number): boolean {
		return this.getAdmin().isLoggedIn() && (this.getTools().hasPermission(name, studyId) ?? false)
	}
	
	public getUrl(name: string, depth: number = this.section.depth): string {
		return `${this.section.getHash(depth)}/${name}`
	}
	public goTo(target: string): void {
		window.location.hash = "#"+target;
	}
	public newSection(target: string, depth: number = this.section.depth): void {
		window.location.hash = depth == -1 ? target : `${this.section.getHash(depth)}/${target}`
	}
	
	
	/**
	 * Remember: Values or references of observables should NOT be cached (also when the value is an observable itself)
	 * It would lead to new values not being updated properly on {@link m.redraw()}
	 *
	 * Examples:
	 * You can cache: {@link StudyLoader.studyCache}. Because this observable is readonly and will never be replaced
	 * You can NOT cache {@link StudyLoader.studyCache.get()} or {@link getStudyOrThrow()} or {@link getStudy().questionnaires.get()[2]}.
	 * 		Because all study entries in StudyLoader might have been replaced or removed between {@link m.redraw()}
	 *
	 * You can cache: {@link SiteData.dynamicValues["accessKey"]} because {@link Container} uses Singletons (see {@link Container.getOrCreateObs()})
	 * You can not cache: {@link SiteData.dynamicValues["accessKey"].get()} because its value might change
	 *
	 * In conclusion: {@link getView()} always needs to work with fresh values. Starting, for example, with {@link getStudyOrThrow()}
	 */
	public abstract getView(): Vnode<any, any>
	
	public destroy(): void {
		//do nothing
	}
}