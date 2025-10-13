import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import downloadSvg from "../../imgs/icons/download.svg?raw"
import {DataStructureInputType} from "../data/DataStructure";
import {BtnCustom} from "../components/Buttons";
import {JsonSourceComponent} from "../helpers/JsonSourceComponent";
import {SectionData} from "../site/SectionData";

export class Content extends SectionContent {
	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [sectionData.getStudyPromise()]
	}
	public title(): string {
		return Lang.get("study_source")
	}
	public titleExtra(): Vnode<any, any> | null {
		const study = this.getStudyOrThrow()
		return <a href={window.URL.createObjectURL(new Blob([JSON.stringify(study.createJson())], {type: 'text/json'}))} download={`${study.title.get()}.json`}>
			{BtnCustom(m.trust(downloadSvg), undefined, Lang.get("download"))}
		</a>
	}
	
	public getView(): Vnode<any, any> {
		return m(JsonSourceComponent, {
			getStudy: () => this.getStudyOrThrow(),
			setJson: (json: DataStructureInputType) => {
				const study = this.getStudyOrThrow()
				if(JSON.stringify(json) == JSON.stringify(study.createJson()))
					return
				
				const newStudy = this.sectionData.siteData.studyLoader.updateStudyJson(study, json)
				newStudy.setIsDifferent(true)
			}
		})
	}
}