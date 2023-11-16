import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {TitleRow} from "../widgets/TitleRow";
import {FILE_SAVE_ACCESS} from "../constants/urls";
import {Requests} from "../singletons/Requests";
import {StudiesDataType} from "../loader/StudyLoader";

export class Content extends SectionContent {
	private readonly isRedirected: boolean = false
	
	public static preLoad(section: Section): Promise<any>[] {
		return [
			section.siteData.studyLoader.loadAvailableStudies(section.getDynamic("accessKey", "").get())
		]
	}
	constructor(section: Section, studies: StudiesDataType) {
		super(section)
		const count = studies.getCount()
		
		if(count == 0)
			throw new Error(`Could not find study`)
		else if(count == 1) {
			const study = studies.getFirst()
			if(study) {
				this.section.setStatic("id", study.id.get())
				Requests.loadJson(FILE_SAVE_ACCESS, "post", `study_id=${study.id.get()}&page_name=${this.section.depth ? "study" : "navigatedFromHome"}`)
			}
		}
		else {
			const study = this.getStudyOrNull()
			if(study)
				Requests.loadJson(FILE_SAVE_ACCESS, "post", `study_id=${study.id.get()}&page_name=${this.section.depth ? "study" : "navigatedFromHome"}`)
			else {
				this.newSection("studies:studyOverview", this.section.depth - 1)
				this.isRedirected = true
			}
		}
	}
	
	public title(): string {
		return Lang.get("study_description")
	}
	
	public getView(): Vnode<any, any> {
		if(this.isRedirected)
			return <div></div>
		
		const study = this.getStudyOrThrow()
		return <div>
			{study.studyDescription.get() &&
				<div class="scrollBox spacingBottom">{m.trust(study.studyDescription.get())}</div>
			}
			
			{TitleRow(Lang.getWithColon("questionnaires"))}
			{study.questionnaires.get().map((questionnaire) =>
				questionnaire.isActive(Date.now(), Date.now()) &&
					<a class="vertical verticalPadding" href={this.getUrl(`attend,qId:${questionnaire.internalId.get()}`)}>{questionnaire.getTitle()}</a>
			)}
		</div>
	}
}