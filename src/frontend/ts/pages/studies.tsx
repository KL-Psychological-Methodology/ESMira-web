import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {Study} from "../data/study/Study";
import {StudiesDataType} from "../loader/StudyLoader";

export class Content extends SectionContent {
	protected targetPage: string
	protected titleString: string
	private readonly accessKey: ObservablePrimitive<string>
	
	protected studies: Study[] = []
	private readonly destroyImpl: (() => void)
	
	public static preLoad(section: Section): Promise<any>[] {
		return [
			section.getAvailableStudiesPromise(section.siteData.dynamicValues.getOrCreateObs("accessKey", "").get())
		]
	}
	
	constructor(section: Section, studiesObs: StudiesDataType) {
		super(section)
		this.accessKey = this.getDynamic("accessKey", "")
		
		switch(section.sectionValue) {
			case "appInstall":
				this.targetPage = "appInstall"
				this.titleString = Lang.get("select_a_study")
				break
			case "statistics":
				this.targetPage = "publicStatistics"
				this.titleString = Lang.get("statistics")
				break
			case "attend":
			default:
				this.targetPage = "studyOverview"
				this.titleString = Lang.get("select_a_study")
				break
		}
		
		this.updateSortedStudies(studiesObs)
		
		const studyObserverId = studiesObs.addObserver((_origin, bubbled) => {
			if(!bubbled)
				this.updateSortedStudies(studiesObs)
		})
		
		this.destroyImpl = () => {
			studyObserverId.removeObserver()
		}
	}
	
	public title(): string {
		const allSections = this.section.allSections
		const depth = this.section.depth
		if(allSections.length > depth+1) {
			const id = allSections[depth+1].getStaticInt("id")
			if(id)
				return this.getStudyOrNull(id)?.title.get() ?? this.titleString
		}
		return this.titleString
	}
	
	protected updateSortedStudies(studiesObs: StudiesDataType): void {
		const studies = this.section.siteData.studyLoader.getSortedStudyList(Object.values(studiesObs.get()))
		switch(this.section.sectionValue) {
			case "statistics":
				this.studies = studies.filter((study) => study.publicStatistics.charts.get().length != 0)
				break
			case "attend":
				this.studies = studies.filter((study) => study.version.get() != 0 && study.published.get())
				break
			case "appInstall":
			default:
				this.studies = studies
				break
		}
	}
	
	private async reloadAccessKey(e: SubmitEvent): Promise<void> {
		e.preventDefault()
		const formData = new FormData(e.target as HTMLFormElement)
		this.accessKey.set(formData.get("accessKeyInput")?.toString() ?? "")
		const studiesObs = await this.section.loader.showLoader(this.section.siteData.studyLoader.loadAvailableStudies(this.accessKey.get(), true))
		this.section.loader.closeLoader() //this will not be run if there is an error
		this.updateSortedStudies(studiesObs)
	}
	
	public getView(): Vnode<any, any> {
		
		return (
			<div>
				<div class="accessKeyBox">{
					<form method="post" action="" onsubmit={this.reloadAccessKey.bind(this)}>
						<label class="noDesc">
							<small>{Lang.get("accessKey")}</small>
							<input name="accessKeyInput" type="text" value={this.accessKey.get()}/>
							<input type="submit" value={Lang.get('send')}/>
						</label>
					</form>
				}</div>
				
				<div class="stickerList">{
					this.studies.map((study) =>
						<div class="line">
							<span class="title">{this.getStudyLinkView(study)}</span>
						</div>
					)
				}</div>
			</div>
		)
	}
	
	protected getStudyLinkView(study: Study): Vnode<any, any> {
		return <a href={this.getUrl(`${this.targetPage},id:${study.id.get()}`)}>{study.title.get()}</a>
	}
	
	public destroy(): void {
		super.destroy()
		this.destroyImpl()
	}
}