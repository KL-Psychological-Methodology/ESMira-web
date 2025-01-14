import m, { Vnode } from "mithril";
import { getBaseUrl } from "../constants/methods";
import { FILE_ADMIN } from "../constants/urls";
import { InboundFallbackTokenInfo } from "../data/fallbackTokens/inboundFallbackToken";
import { Lang } from "../singletons/Lang";
import { Requests } from "../singletons/Requests";
import { Section } from "../site/Section";
import { SectionContent } from "../site/SectionContent";
import { BtnAdd, BtnTrash, BtnCopy } from "../widgets/BtnWidgets";
import { TitleRow } from "../widgets/TitleRow";

export class Content extends SectionContent {
	private userInboundTokens: InboundFallbackTokenInfo[] = []
	private recentToken: string | null = null
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
		const response = await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=IssueFallbackSetupToken`,
			"post",
			""
		)
		const token: string = response[0];
		this.recentToken = token
		await this.reloadInboundTokenList()
	}

	public title(): string {
		return Lang.get("fallback_token_list")
	}

	public getView(): Vnode<any, any> {
		return <div>
			<span>{Lang.get("inbound_fallback_token_info")}</span>

			{this.getInboundTokenList()}
			<div class="spacingBottom center">
				{BtnAdd(this.issueInboundToken.bind(this), Lang.get("create_fallback_setup_token"))}
			</div>
			{this.getNewInboundTokenView()}
		</div>
	}

	private getInboundTokenList(): Vnode<any, any> {
		return <div class="listParent spacingTop">
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

	private getNewInboundTokenView(): Vnode<any, any> {
		if (this.recentToken == null)
			return <div></div>
		const url = getBaseUrl()
		let token = this.recentToken
		return <div>
			{TitleRow(Lang.get("new_fallback_setup_token"))}
			<div>{Lang.get("info_fallback_setup_token")}</div>
			<div class="verticalPadding center spacingTop"><span>{token}</span>{BtnCopy(() => navigator.clipboard.writeText(token))}</div>
			<div class="center"><span>{url}</span>{BtnCopy(() => navigator.clipboard.writeText(url))}</div>
		</div>
	}
}