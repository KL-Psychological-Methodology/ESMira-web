import {SectionContent} from "../site/SectionContent";
import m, {Component, Vnode, VnodeDOM} from "mithril";
import {Lang} from "../singletons/Lang";
import {AddJsToServerHtml} from "../helpers/AddJsToServerHtml";
import {Requests} from "../singletons/Requests";
import {FILE_APP_INSTALL_INSTRUCTIONS} from "../constants/urls";
import {SectionData} from "../site/SectionData";
interface AppInstallComponentOptions {
	sectionContent: SectionContent
	html: string
}

class AppInstallComponent implements Component<AppInstallComponentOptions, any> {
	public oncreate(vNode: VnodeDOM<AppInstallComponentOptions, any>): void {
		const sectionContent = vNode.attrs.sectionContent
		try {
			AddJsToServerHtml.process(vNode.dom as HTMLElement, sectionContent)
		}
		catch(e: any) {
			sectionContent.sectionData.loader.error(e.message || e)
		}
	}
	
	public view(vNode: Vnode<AppInstallComponentOptions, any>): Vnode<any, any> {
		return <div>{m.trust(vNode.attrs.html)}</div>
	}
}

export class Content extends SectionContent {
	private readonly pageHtml: string
	private readonly pageTitle: string
	
	public static preLoad(sectionData: SectionData): Promise<any>[] {
		const accessKey = sectionData.getDynamic("accessKey", "").get()
		return [
			Requests.loadJson(
				FILE_APP_INSTALL_INSTRUCTIONS
					.replace("%d1", (sectionData.getStaticInt("id") ?? 0).toString())
					.replace("%s1", accessKey)
					.replace("%s2", Lang.code)
			),
			sectionData.getAvailableStudiesPromise(accessKey, true)
		]
	}
	
	constructor(sectionData: SectionData, html: { pageHtml: string, pageTitle: string, forwarded: boolean }) {
		super(sectionData)
		if(html.forwarded)
			this.newSection("studies:appInstall", this.sectionData.depth - 1)
		
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