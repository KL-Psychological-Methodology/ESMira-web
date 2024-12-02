import m, { Vnode } from "mithril";
import { Lang } from "../singletons/Lang";
import { SectionContent } from "../site/SectionContent";
import { FILE_ADMIN } from "../constants/urls";

export class Content extends SectionContent {
	public title(): string {
		const fallbackServerUrl = this.getStaticString("fallbackUrl")
		return fallbackServerUrl ? Lang.get("fallback_server_title", atob(fallbackServerUrl)) : "Error"
	}

	public getView(): Vnode<any, any> {
		return <div>
			<a onclick={this.ping.bind(this)}>{Lang.get("fallback_ping")}</a>
		</div>
	}

	private async ping(): Promise<void> {
		const url = this.getStaticString("fallbackUrl")
		if (url === null) {
			alert(Lang.get("fallback_url_parameter_error"))
			return
		}
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=PingFallbackServer`,
			"post",
			`url=${url}`
		)
		alert(Lang.get("fallback_ping_success", atob(url)))
	}
}