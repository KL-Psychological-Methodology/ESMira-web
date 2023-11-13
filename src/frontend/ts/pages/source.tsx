import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import downloadSvg from "../../imgs/icons/download.svg?raw"
import {TranslatableObjectDataType} from "../observable/TranslatableObject";
import {Section} from "../site/Section";
import {BtnCustom} from "../widgets/BtnWidgets";
import {JsonSourceComponent} from "../helpers/JsonSourceComponent";

export class Content extends SectionContent {
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
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
			setJson: (json: TranslatableObjectDataType) => {
				const study = this.getStudyOrThrow()
				if(JSON.stringify(json) == JSON.stringify(study.createJson()))
					return
				
				const newStudy = this.section.siteData.studyLoader.updateStudyJson(study, json)
				newStudy.setDifferent(true)
			}
		})
	}
}