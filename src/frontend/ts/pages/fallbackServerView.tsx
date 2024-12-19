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
			<div><a onclick={this.ping.bind(this)}>{Lang.get("fallback_ping")}</a></div>
			<div><a onclick={this.forceSync.bind(this)}>{Lang.get("fallback_full_synch")}</a></div>
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

	private async forceSync(): Promise<void> {
		const url = this.getStaticString("fallbackUrl")
		if (url === null) {
			alert(Lang.get("fallback_url_parameter_error"))
			return
		}
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=SynchAllStudiesToFallback`,
			"post",
			`url=${url}`
		)
		this.section.loader.showMessage(Lang.get("info_successful"))
	}
}