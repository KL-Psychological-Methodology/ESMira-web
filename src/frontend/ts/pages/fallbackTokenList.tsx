import m, { Vnode } from "mithril";
import { FILE_ADMIN } from "../constants/urls";
import { InboundFallbackTokenInfo } from "../data/fallbackTokens/inboundFallbackToken";
import { Lang } from "../singletons/Lang";
import { Requests } from "../singletons/Requests";
import { Section } from "../site/Section";
import { SectionContent } from "../site/SectionContent";
import { BtnAdd, BtnTrash } from "../widgets/BtnWidgets";

export class Content extends SectionContent {
	private userInboundTokens: InboundFallbackTokenInfo[] = []

	public static preLoad(_section: Section): Promise<any>[] {
		return [
			Requests.loadJson(`${FILE_ADMIN}?type=GetInboundFallbackTokensForUser`)
		]
	}
	constructor(section: Section, inboundTokens: InboundFallbackTokenInfo[]) {
		super(section)

		this.userInboundTokens = this.sortInboundTokens(inboundTokens);
	}

	private isURLunique(url: string): boolean {
		return this.userInboundTokens.every((token) => url != token.otherServerUrl)
	}

	private async reloadInboundTokenList(): Promise<void> {
		const inboundFallbackTokens = await this.section.loader.loadJson(`${FILE_ADMIN}?type=GetInboundFallbackTokensForUser`)
		this.userInboundTokens = this.sortInboundTokens(inboundFallbackTokens)
	}

	private sortInboundTokens(inboundTokens: InboundFallbackTokenInfo[]): InboundFallbackTokenInfo[] {
		return inboundTokens.sort(function (a, b) {
			return a.otherServerUrl.localeCompare(b.otherServerUrl)
		})
	}

	private async deleteInboundToken(token: InboundFallbackTokenInfo) {
		if (!confirm(Lang.get("confirm_delete_inbound_fallback_token", token.otherServerUrl)))
			return

		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=DeleteInboundFallbackToken`,
			"post",
			`url=${btoa(token.otherServerUrl)}&user=${token.user}`
		)
		await this.reloadInboundTokenList()
	}

	private async issueInboundToken(): Promise<void> {
		let url: string | null = ""
		do {
			url = prompt(Lang.get("prompt_fallback_token_url"), url)
		} while (url && !this.isURLunique(url))

		if (!url)
			return

		url = btoa(url)

		const response = await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=IssueInboundFallbackToken`,
			"post",
			`url=${url}`
		)
		const token: string = response[0];
		await this.reloadInboundTokenList()
		// TODO add some way to display the current token
		alert(Lang.get("info_fallback_token", token))
	}

	public title(): string {
		return Lang.get("fallback_system")
	}

	public getView(): Vnode<any, any> {
		return <div>
			{this.getInboundTokenList()}
			<div class="spacingBottom center">
				{BtnAdd(this.issueInboundToken.bind(this), Lang.get("create_fallback_token"))}
			</div>
		</div>
	}

	private getInboundTokenList(): Vnode<any, any> {
		return <div class="listParent">
			<div class="listChild">
				{this.userInboundTokens.map((token: InboundFallbackTokenInfo) =>
					<div>
						{BtnTrash(this.deleteInboundToken.bind(this, token))}
						<span>{token.otherServerUrl}</span>
					</div>
				)}
			</div>
		</div>
	}

}