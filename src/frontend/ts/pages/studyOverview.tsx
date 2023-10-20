import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {TitleRow} from "../widgets/TitleRow";
import {FILE_SAVE_ACCESS} from "../constants/urls";
import {Requests} from "../singletons/Requests";

export class Content extends SectionContent {
	public static preLoad(section: Section): Promise<any>[] {
		return [
			section.getStudyPromise(),
			Requests.loadJson(FILE_SAVE_ACCESS, "post", `study_id=${section.getStaticInt("id") ?? -1}&page_name=${section.depth ? "study" : "navigatedFromHome"}`)
		]
	}
	public title(): string {
		return Lang.get("study_description")
	}
	
	public getView(): Vnode<any, any> {
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