import {SectionAlternative, SectionContent} from "../site/SectionContent";
import {Lang} from "../singletons/Lang";

export class SharedUrlAlternatives {
	public static studyAlternatives(sectionContent: SectionContent, indexName: "edit" | "msgs" | "data"): SectionAlternative[] {
		const study = sectionContent.getStudyOrThrow()
		const studyId = study.id.get()
		const depth = sectionContent.section.depth - 1
		return [
			{
				title: Lang.get("edit_study"),
				target: indexName != "edit" &&
					sectionContent
						.getUrl(`studyEdit,id:${studyId}`, depth)
						.replace(`allStudies:${indexName}`, "allStudies:edit")
			},
			{
				title: Lang.get("messages"),
				target: indexName != "msgs" &&
					sectionContent
						.getUrl(`messagesOverview,id:${studyId}`, depth)
						.replace(`allStudies:${indexName}`, "allStudies:msgs")
			},
			{
				title: Lang.get("data"),
				target: indexName != "data" &&
					sectionContent
						.getUrl(`dataStatistics,id:${studyId}`, depth)
						.replace(`allStudies:${indexName}`, "allStudies:data")
			}
		]
	}
}