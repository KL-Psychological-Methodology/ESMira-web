import m, {Component, Vnode, VnodeDOM} from "mithril";
import {FILE_FALLBACK_APP_INSTALL_INSTRUCTIONS} from "../constants/urls";
import {AddJsToServerHtml} from "../helpers/AddJsToServerHtml";
import {Lang} from "../singletons/Lang";
import {Requests} from "../singletons/Requests";
import {SectionContent} from "../site/SectionContent";
import {SectionData} from "../site/SectionData";


interface FallbackAppInstallComponentOptions {
	sectionContent: SectionContent,
	html: string
}

class FallbackAppInstallComponent implements Component<FallbackAppInstallComponentOptions, any> {
	public oncreate(vNode: VnodeDOM<FallbackAppInstallComponentOptions, any>): void {
		const sectionContent = vNode.attrs.sectionContent
		try {
			AddJsToServerHtml.process(vNode.dom as HTMLElement, sectionContent)
		}
		catch (e: any) {
			sectionContent.sectionData.loader.error(e.message || e)
		}
	}

	public view(vNode: Vnode<FallbackAppInstallComponentOptions, any>): Vnode<any, any> {
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
				FILE_FALLBACK_APP_INSTALL_INSTRUCTIONS
					.replace("%d1", (sectionData.getStaticInt("id") ?? 0).toString())
					.replace("%s1", accessKey)
					.replace("%s2", Lang.code)
					.replace("%s3", sectionData.getStaticString("fromUrl") ?? "")
			)
		]
	}

	constructor(sectionData: SectionData, html: { pageHtml: string, pageTitle: string, forwarded: boolean }) {
		super(sectionData)
		this.pageHtml = html.pageHtml
		this.pageTitle = html.pageTitle
	}

	public title(): string {
		return this.pageTitle
	}

	public getView(): Vnode<any, any> {
		return m(FallbackAppInstallComponent, {
			html: this.pageHtml,
			sectionContent: this
		})
	}
}