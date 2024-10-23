import {Study} from "../data/study/Study";
import {ObservableStructureDataType} from "../observable/ObservableStructure";
import m, {Component, Vnode, VnodeDOM} from "mithril";
import {JSONContent, JSONEditor, MenuButton, MenuItem, Mode, TextContent} from "vanilla-jsoneditor";
import {ObserverId} from "../observable/BaseObservable";
import {JsonTypes} from "../observable/types/JsonTypes";
import {Lang} from "../singletons/Lang";

interface JsonSourceComponentOptions {
	getStudy?: () => Study
	setJson: (json: ObservableStructureDataType) => void
	showMainMenuBar?: boolean
	mode?: Mode
	saveBtnLabel?: string
}
export class JsonSourceComponent implements Component<JsonSourceComponentOptions, any> {
	private hasChanged: boolean = false
	private editor?: JSONEditor
	private setJson?: (json: ObservableStructureDataType) => void
	private getStudy?: () => Study
	private studyObserveId?: ObserverId
	private saveBtnLabel?: string
	
	private getJson(): JsonTypes {
		if(!this.getStudy)
			return {}
		return this.getStudy().createJson()
	}
	private getJsonContent(): JSONContent | TextContent {
		if(this.getStudy)
			return {json: this.getStudy().createJson()}
		return {text: ""}
	}
	
	public oncreate(vNode: VnodeDOM<JsonSourceComponentOptions, any>): void {
		this.setJson = vNode.attrs.setJson
		this.getStudy = vNode.attrs.getStudy
		this.saveBtnLabel = vNode.attrs.saveBtnLabel
		
		this.editor = new JSONEditor(
			{
				target: vNode.dom,
				props: {
					content: this.getJsonContent(),
					mode: vNode.attrs.mode ?? Mode.tree,
					mainMenuBar: vNode.attrs.showMainMenuBar ?? true,
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
		
		if(this.getStudy) {
			this.studyObserveId = this.getStudy().addObserver(() => {
				this.editor?.set(this.getJsonContent())
			})
		}
	}
	
	public onremove(): void {
		this.studyObserveId?.removeObserver()
	}
	
	private clickApply(): void {
		if(!this.setJson)
			return
		let json
		try {
			json = (this.editor?.get() as JSONContent).json ?? JSON.parse((this.editor?.get() as TextContent).text)
		}
		catch(e) {
			console.error(e)
			return
		}
		if(this.getStudy)
			json.id = this.getStudy().id.get()
		this.setJson(json)
		this.hasChanged = false
	}
	
	public view(): Vnode<any, any> {
		return <div class="source">
			{this.hasChanged &&
				<div class="applyBtn" onclick={this.clickApply.bind(this)}>{this.saveBtnLabel ?? Lang.get("apply_changes")}</div>
			}
			</div>
	}
}