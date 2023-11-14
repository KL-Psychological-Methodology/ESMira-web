import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {Lang} from "../singletons/Lang";
import participateSvg from "../../imgs/dashIcons/participate.svg?raw"
import questionSvg from "../../imgs/dashIcons/question.svg?raw"
import statisticsSvg from "../../imgs/icons/statistics.svg?raw"
import serverStatisticsSvg from "../../imgs/dashIcons/serverStatistics.svg?raw"
import {Section} from "../site/Section";
import {PromiseCache} from "../singletons/PromiseCache";
import {Requests} from "../singletons/Requests";
import {FILE_SETTINGS} from "../constants/urls";

export class Content extends SectionContent {
	private homeMessage: string
	
	public title(): string {
		return Lang.get("home")
	}
	public static preLoad(_section: Section): Promise<any>[] {
		return [
			PromiseCache.get("legal", () => {
				return Requests.loadJson(FILE_SETTINGS)
			})
		]
	}
	constructor(section: Section, settings: {homeMessage: string}) {
		super(section)
		this.homeMessage = settings.homeMessage
	}
	public getView(): Vnode<any, any> {
		return DashRow(
			DashElement(null, {template: {title: Lang.get("participate_in_study"), icon: m.trust(participateSvg)}, href: this.getUrl("studies:attend")}),
			DashElement(null, {template: {title: Lang.get("what_is_esmira"), icon: m.trust(questionSvg)}, href: this.getUrl("about")}),
			DashElement(null, {template: {title: Lang.get("show_study_statistics"), icon: m.trust(statisticsSvg)}, href: this.getUrl("studies:statistics")}),
			DashElement(null, {template: {title: Lang.get("show_server_statistics"), icon: m.trust(serverStatisticsSvg)}, href: this.getUrl("serverStatistics")}),
		this.homeMessage.length > 0 && DashElement("stretched", {content: <div>{m.trust(this.homeMessage)}</div>}),
		)
	}
}