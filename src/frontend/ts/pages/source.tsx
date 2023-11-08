import {SectionContent} from "../site/SectionContent";
import m, {Component, Vnode, VnodeDOM} from "mithril";
import {Lang} from "../singletons/Lang";
import downloadSvg from "../../imgs/icons/download.svg?raw"
import {Study} from "../data/study/Study";
import {ObserverId} from "../observable/BaseObservable";
import {TranslatableObjectDataType} from "../observable/TranslatableObject";
import {JSONContent, JSONEditor, MenuButton, MenuItem, Mode, TextContent} from "vanilla-jsoneditor";
import {Section} from "../site/Section";
import {JsonTypes} from "../observable/types/JsonTypes";
import {BtnCustom} from "../widgets/BtnWidgets";

interface SourceComponentOptions {
	getStudy: () => Study
	setJson: (json: TranslatableObjectDataType) => void
}
class SourceComponent implements Component<SourceComponentOptions, any> {
	private hasChanged: boolean = false
	private editor?: JSONEditor
	private setJson?: (json: TranslatableObjectDataType) => void
	private getStudy?: () => Study
	private studyObserveId?: ObserverId
	
	private getJson(): JsonTypes {
		if(!this.getStudy)
			return {}
		const r = this.getStudy().createJson()
		return r ?? {}
	}
	
	public oncreate(vNode: VnodeDOM<SourceComponentOptions, any>): void {
		this.setJson = vNode.attrs.setJson
		this.getStudy = vNode.attrs.getStudy
		
		this.editor = new JSONEditor(
			{
				target: vNode.dom,
				props: {
					content: {json: this.getJson()},
					mode: Mode.tree,
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
		
		this.studyObserveId = this.getStudy().addObserver(() => {
			this.editor?.set({json: this.getJson()})
		})
	}
	
	public onremove(): void {
		this.studyObserveId?.removeObserver()
	}
	
	private clickApply(): void {
		if(!this.setJson || !this.getStudy)
			return
		let json
		try {
			json = (this.editor?.get() as JSONContent).json ?? JSON.parse((this.editor?.get() as TextContent).text)
		}
		catch(e) {
			console.error(e)
			return
		}
		json.id = this.getStudy().id.get()
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
			{BtnCustom(m.trust(downloadSvg), undefined, Lang.get("download"))}
		</a>
	}
	
	public getView(): Vnode<any, any> {
		return m(SourceComponent, {
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