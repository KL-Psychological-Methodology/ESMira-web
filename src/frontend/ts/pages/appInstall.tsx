import {SectionContent} from "../site/SectionContent";
import m, {Component, Vnode, VnodeDOM} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {AddJsToServerHtml} from "../helpers/AddJsToServerHtml";
import {Requests} from "../singletons/Requests";
import {FILE_APP_INSTALL_INSTRUCTIONS} from "../constants/urls";
interface AppInstallComponentOptions {
	sectionContent: SectionContent
	html: string
}

class AppInstallComponent implements Component<AppInstallComponentOptions, any> {
	public oncreate(vNode: VnodeDOM<AppInstallComponentOptions, any>): void {
		AddJsToServerHtml.process(vNode.dom as HTMLElement, vNode.attrs.sectionContent)
	}
	
	public view(vNode: Vnode<AppInstallComponentOptions, any>): Vnode<any, any> {
		return <div>{m.trust(vNode.attrs.html)}</div>
	}
}

export class Content extends SectionContent {
	private pageHtml: string
	private pageTitle: string
	
	public static preLoad(section: Section): Promise<any>[] {
		return [
			Requests.loadJson(
				FILE_APP_INSTALL_INSTRUCTIONS
					.replace("%d1", (section.getStaticInt("id") ?? -1).toString())
					.replace("%s1", section.getDynamic("accessKey", "")?.get())
					.replace("%s2", Lang.code)
			),
			section.getStudyPromise()
		]
	}
	
	constructor(section: Section, html: { pageHtml: string, pageTitle: string }) {
		super(section)
		this.pageHtml = html.pageHtml
		this.pageTitle = html.pageTitle
		
	}
	
	public title(): string {
		return this.pageTitle
	}
	
	public getView(): Vnode<any, any> {
		return m(AppInstallComponent, {
			html: this.pageHtml,
			sectionContent: this
		})
	}
}