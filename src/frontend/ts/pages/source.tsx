import {SectionContent} from "../site/SectionContent";
import m, {Component, Vnode, VnodeDOM} from "mithril";
import {Lang} from "../singletons/Lang";
import downloadSvg from "../../imgs/icons/download.svg?raw"
import {Study} from "../data/study/Study";
import {ObserverId} from "../observable/BaseObservable";
import {TranslatableObjectDataType} from "../observable/TranslatableObject";
import {JSONEditor, MenuButton, MenuItem, Mode, TextContent} from "vanilla-jsoneditor";
import {Section} from "../site/Section";
import {JsonTypes} from "../observable/types/JsonTypes";

interface SourceComponentOptions {
	study: Study
	setJson: (json: TranslatableObjectDataType) => void
}
class SourceComponent implements Component<SourceComponentOptions, any> {
	private hasChanged: boolean = false
	private editor?: JSONEditor
	private setJson?: (json: TranslatableObjectDataType) => void
	private study?: Study
	private studyObserveId?: ObserverId
	private langObserveId?: ObserverId
	
	private getJson(): JsonTypes {
		const study = this.study
		if(!study)
			return {}
		const r = study.createJson()
		return r ?? {}
	}
	
	public oncreate(vNode: VnodeDOM<SourceComponentOptions, any>): void {
		this.setJson = vNode.attrs.setJson
		this.study = vNode.attrs.study
		
		this.editor = new JSONEditor(
			{
				target: vNode.dom,
				props: {
					content: {json: this.getJson()},
					mode: Mode.text,
					onChange: () => {
						this.hasChanged = true
						m.redraw()
					},
					onRenderMenu: (items: MenuItem[], _context: { mode: 'tree' | 'text' | 'table', modal: boolean }) => {
						const index = items.findIndex((item) => {return (item as MenuButton)?.text == "table"})
						items.splice(index, 1)
						return items
					}
				}
				
			})
		
		this.studyObserveId = this.study?.addObserver(() => {
			this.editor?.set({json: this.getJson()})
		})
		this.langObserveId = this.study?.currentLangCode.addObserver(() => {
			this.editor?.set({json: this.getJson()})
		})
	}
	
	public onremove(): void {
		this.studyObserveId?.removeObserver()
		this.langObserveId?.removeObserver()
	}
	
	private clickApply(): void {
		if(!this.setJson)
			return
		let json;
		try {
			json = JSON.parse((this.editor?.get() as TextContent).text);
		}
		catch(e) {
			console.error(e);
			return;
		}
		json.id = this.study?.id.get() ?? -1
		this.setJson(json)
		this.hasChanged = false
	}
	
	public view(): Vnode<any, any> {
		return <div class="source">
			{this.hasChanged &&
				<div class="applyBtn" onclick={this.clickApply.bind(this)}>{Lang.get("apply_changes")}</div>
			}
		</div>
	}
}

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
			{m.trust(downloadSvg)}
			<span class="spacingLeft">{Lang.get("download")}</span>
		</a>
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		return m(SourceComponent, {
			study: study,
			setJson: (json: TranslatableObjectDataType) => {
				if(JSON.stringify(json) == JSON.stringify(study.createJson()))
					return
				
				const newStudy = this.section.siteData.studyLoader.updateStudyJson(study, json)
				console.log(study, newStudy)
				// newStudy.setDifferent(true)
			}
		})
	}
}