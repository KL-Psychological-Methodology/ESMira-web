import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {Lang} from "../singletons/Lang";
import participateSvg from "../../imgs/dashIcons/participate.svg?raw"
import githubSvg from "../../imgs/dashIcons/github.svg?raw"
import questionSvg from "../../imgs/dashIcons/question.svg?raw"
import statisticsSvg from "../../imgs/dashIcons/statistics.svg?raw"
import serverStatisticsSvg from "../../imgs/dashIcons/serverStatistics.svg?raw"

export class Content extends SectionContent {
	public title(): string {
		return Lang.get("home")
	}
	
	public getView(): Vnode<any, any> {
		return DashRow(
			DashElement(null, {template: {title: Lang.get("participate_in_study"), icon: m.trust(participateSvg)}, href: this.getUrl("studies:attend")}),
			DashElement(null, {template: {title: Lang.get("what_is_esmira"), icon: m.trust(questionSvg)}, href: this.getUrl("about")}),
			DashElement(null, {template: {title: Lang.get("show_study_statistics"), icon: m.trust(statisticsSvg)}, href: this.getUrl("studies:statistics")}),
			DashElement(null, {template: {title: Lang.get("show_server_statistics"), icon: m.trust(serverStatisticsSvg)}, href: this.getUrl("serverStatistics")})
		)
	}
}