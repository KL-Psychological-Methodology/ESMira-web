import {SectionAlternative} from "../site/SectionContent";
import {Lang} from "../singletons/Lang";

export class UrlAlternatives {
	public static studyAlternatives(studyId: number, index: "studyEdit" | "messagesOverview" | "dataStatistics"): SectionAlternative[] {
		return [
			{
				title: Lang.get("edit_studies"),
				target: index != "studyEdit" && `studyEdit,id:${studyId}`
			},
			{
				title: Lang.get("messages"),
				target: index != "messagesOverview" && `messagesOverview,id:${studyId}`
			},
			{
				title: Lang.get("data"),
				target: index != "dataStatistics" && `dataStatistics,id:${studyId}`
			}
		]
	}
}