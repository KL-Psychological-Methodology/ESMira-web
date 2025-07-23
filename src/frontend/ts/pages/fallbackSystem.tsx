import m, { Vnode } from "mithril";
import { getBaseUrl } from "../constants/methods";
import { FILE_ADMIN } from "../constants/urls";
import { InboundFallbackTokenInfo } from "../data/fallbackTokens/inboundFallbackToken";
import { OutboundFallbackToken } from "../data/fallbackTokens/outboundFallbackToken";
import { ObservableArray } from "../observable/ObservableArray";
import { ObservablePrimitive } from "../observable/ObservablePrimitive";
import { ObservableStructureDataType } from "../observable/ObservableStructure";
import { Lang } from "../singletons/Lang";
import { Requests } from "../singletons/Requests";
import { Section } from "../site/Section";
import { SectionContent } from "../site/SectionContent";
import { AddOutboundToken } from "../widgets/AddOutboundToken";
import { BtnAdd, BtnTrash, BtnCopy, BtnEdit } from "../widgets/BtnWidgets";
import { DragContainer } from "../widgets/DragContainer";
import { TabBar } from "../widgets/TabBar";
import { TitleRow } from "../widgets/TitleRow";

// noinspection JSUnusedGlobalSymbols
export class Content extends SectionContent {
	private inboundTokens: Map<string, InboundFallbackTokenInfo[]> = new Map()
	private outboundTokenUrls: ObservableArray<ObservableStructureDataType, OutboundFallbackToken>
	private selectedIndex = new ObservablePrimitive(0, null, "fallbackSystem")

	private recentToken: string | null = null
	public static preLoad(_section: Section): Promise<any>[] {
		return [
			Requests.loadJson(`${FILE_ADMIN}?type=GetInboundFallbackTokens`),
			Requests.loadJson(`${FILE_ADMIN}?type=GetOutboundFallbackTokensInfo`),
		]
	}
	constructor(section: Section, inboundTokens: InboundFallbackTokenInfo[], outboundTokens: ObservableStructureDataType[]) {
		super(section)

		this.inboundTokens = this.sortInboundTokens(inboundTokens);
		this.outboundTokenUrls = new ObservableArray<ObservableStructureDataType, OutboundFallbackToken>(
			outboundTokens,
			null,
			"outboundTokenUrls",
			(data, parent, key) => { return new OutboundFallbackToken(data, parent, key) }
		)
		this.outboundTokenUrls.addObserver(this.updateOutpboundFallbackTokenList.bind(this))
	}

	private async reloadInboundTokenList(): Promise<void> {
		const inboundFallbackTokens = await this.section.loader.loadJson(`${FILE_ADMIN}?type=GetInboundFallbackTokens`)
		this.inboundTokens = this.sortInboundTokens(inboundFallbackTokens)
	}

	private sortInboundTokens(inboundTokens: InboundFallbackTokenInfo[]): Map<string, InboundFallbackTokenInfo[]> {
		let map: Map<string, InboundFallbackTokenInfo[]> = new Map()
		for (var token of inboundTokens) {
			if (!map.has(token.user)) {
				map.set(token.user, [])
			}
			map.get(token.user)!.push(token)
		}
		return map
	}

	private async deleteInboundToken(token: InboundFallbackTokenInfo) {
		if (!confirm(Lang.get("confirm_delete_inbound_fallback_token", atob(token.otherServerUrl))))
			return

		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=DeleteInboundFallbackToken`,
			"post",
			`url=${token.otherServerUrl}&user=${token.user}`
		)
		await this.reloadInboundTokenList()
	}

	private async issueInboundToken(): Promise<void> {
		const response = await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=IssueFallbackSetupToken`,
			"post",
			""
		)
		this.recentToken = response[0]
		await this.reloadInboundTokenList()
	}

	private async addOutboundToken(url: string, token: string): Promise<any> {
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=SetupFallbackSystem`,
			"post",
			`otherUrl=${btoa(url)}&ownUrl=${btoa(getBaseUrl())}&setupToken=${token}`
		)
		this.outboundTokenUrls.push({ "url": url })
		return true
	}

	private async deleteOutboundToken(index: number): Promise<any> {
		this.outboundTokenUrls.remove(index)
		window.location.hash = `${this.section.getHash(this.section.depth)}`
	}

	private async updateOutpboundFallbackTokenList(): Promise<void> {
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=SetOutboundFallbackTokensList`,
			"post",
			`urlList=${JSON.stringify(this.outboundTokenUrls.get().map((token) => btoa(token.url.get())))}`
		)
	}

	public title(): string {
		return Lang.get("fallback_token_list")
	}

	public getView(): Vnode<any, any> {
		if (this.getTools().isAdmin) {
			return TabBar(this.selectedIndex, [
				{
					title: Lang.get("inbound_fallback_tab"),
					view: this.getInboundView.bind(this)
				},
				{
					title: Lang.get("outbound_fallback_tab"),
					view: this.getOutboundView.bind(this)
				}
			])
		} else {
			return this.getInboundView()
		}
	}

	private getInboundView(): Vnode<any, any> {
		const isAdmin = this.section.getTools().isAdmin
		return <div>
			<span>{Lang.get("inbound_fallback_token_info")}</span>

			{isAdmin && this.getInboundTokenListAdmin()}
			{!isAdmin && this.getInboundTokenListUser()}
			{TitleRow(Lang.get("add_fallback_token"))}
			<div class="spacingTop spacingBottom center">
				{BtnAdd(this.issueInboundToken.bind(this), Lang.get("create_fallback_setup_token"))}
			</div>
			{this.getNewInboundTokenView()}
		</div >
	}

	private getOutboundView(): Vnode<any, any> {
		return <div>

			<span>{Lang.get("outbound_fallback_token_info")}</span>
			<div class="listParent spacingTop">
				{DragContainer((dragTools) =>
					<div class="listChild">
						{this.outboundTokenUrls.get().map((token: OutboundFallbackToken, index) =>
							dragTools.getDragTarget(index, this.outboundTokenUrls,
								<div class="verticalPadding">
									{dragTools.getDragStarter(index, this.outboundTokenUrls)}
									{BtnTrash(this.deleteOutboundToken.bind(this, index))}
									<a href={token.url.get()}>{token.url.get()}</a>
									<a class="spacingLeft" href={this.getUrl(`fallbackServerView,fallbackUrl:${btoa(token.url.get())}`)}>{BtnEdit()}</a>
								</div>
							)
						)}
					</div>
				)}
			</div>

			{TitleRow(Lang.get("add_fallback_token"))}
			{AddOutboundToken(this.addOutboundToken.bind(this), (msg) => { this.section.loader.error(msg) })}

		</div>
	}

	private getInboundTokenListUser(): Vnode<any, any> {
		const userList = this.inboundTokens.get(this.getTools().accountName)
		if (userList == null) {
			return <div></div>
		}
		return <div class="listParent">
			<div class="listChild">
				{userList.map((token) =>
					<div>
						{BtnTrash(this.deleteInboundToken.bind(this, token))}
						<a href={atob(token.otherServerUrl)}>{atob(token.otherServerUrl)}</a>
					</div>
				)}
			</div>
		</div>
	}

	private getInboundTokenListAdmin(): Vnode<any, any> {
		return <div class="listParent">
			<div class="listChild">
				{Array.from(this.inboundTokens.keys()).map((user: string) =>
					<div>
						<div class="spacingTop spacingBottom"><h2>{user}</h2></div>
						<div>{this.inboundTokens.get(user)?.map((token) =>
							<div>
								{BtnTrash(this.deleteInboundToken.bind(this, token))}
								<a href={atob(token.otherServerUrl)}>{atob(token.otherServerUrl)}</a>
							</div>
						)}</div>
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
			<div class="verticalPadding center spacingTop"><span>{url}</span>{BtnCopy(() => navigator.clipboard.writeText(url))}</div>
			<div class="center"><span>{token}</span>{BtnCopy(() => navigator.clipboard.writeText(token))}</div>
		</div>
	}
}