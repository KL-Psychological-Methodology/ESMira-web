import { SectionContent } from "../site/SectionContent";
import m, { Vnode } from "mithril";
import { Lang } from "../singletons/Lang";
import { TabBar } from "../widgets/TabBar";
import { ObservablePrimitive } from "../observable/ObservablePrimitive";
import { FILE_ADMIN, URL_RELEASES_LIST } from "../constants/urls";
import { BindObservable } from "../widgets/BindObservable";
import { TitleRow } from "../widgets/TitleRow";
import { RichText } from "../widgets/RichText";
import { ObservableLangChooser } from "../widgets/ObservableLangChooser";
import { ChangeLanguageList } from "../widgets/ChangeLanguageList";
import { Section } from "../site/Section";
import { BaseObservable, ObserverId } from "../observable/BaseObservable";
import { ServerSettingsLoader } from "../loader/ServerSettingsLoader";
import MarkdownIt from "markdown-it";
import { Requests } from "../singletons/Requests";
import { PackageVersionComparator } from "../singletons/PackageVersionComparator";
import { BtnAdd, BtnCopy, BtnEdit, BtnTrash } from "../widgets/BtnWidgets";
import { AddOutboundToken } from "../widgets/AddOutboundToken";
import { ObservableArray } from "../observable/ObservableArray";
import { ObservableStructureDataType } from "../observable/ObservableStructure";
import { OutboundFallbackToken } from "../data/fallbackTokens/outboundFallbackToken";
import { DragContainer } from "../widgets/DragContainer";
import { callback } from "chart.js/dist/helpers/helpers.core";
import { getBaseUrl } from "../constants/methods";
import { InboundFallbackTokenInfo } from "../data/fallbackTokens/inboundFallbackToken";

type ReleaseType = { version: string, date: Date, changeLog: string, downloadUrl: string }

export class Content extends SectionContent {
	private loader: ServerSettingsLoader
	private markdownRenderer = new MarkdownIt()
	private noConnection = false
	private loadPreReleases = false
	private selectedIndex = new ObservablePrimitive(0, null, "serverSettings")
	private readonly observerId: ObserverId
	private hasUpdates: boolean = false
	private newestVersionName: string = ""
	private newestVersionDownloadUrl: string = ""
	private releaseData: ReleaseType[] = []
	private debuggingOn = process.env.NODE_ENV !== "production"
	private changeLanguageList: ChangeLanguageList
	private outboundTokenUrls: ObservableArray<ObservableStructureDataType, OutboundFallbackToken>
	private inboundTokens: Map<string, InboundFallbackTokenInfo[]> = new Map()

	public static preLoad(section: Section): Promise<any>[] {
		return [
			section.getTools().settingsLoader.init(),
			Requests.loadJson(`${FILE_ADMIN}?type=GetOutboundFallbackTokensInfo`).catch(() => { return [] }),
			Requests.loadJson(`${FILE_ADMIN}?type=GetInboundFallbackTokens`).catch(() => { return [] })
		]
	}

	constructor(sitePage: Section, loader: ServerSettingsLoader, tokenInfos: ObservableStructureDataType[], inboundTokens: InboundFallbackTokenInfo[]) {
		super(sitePage)
		this.loader = loader
		this.changeLanguageList = new ChangeLanguageList(() => {
			return this.loader.getSettings()
		})

		this.observerId = loader.getSettings().addObserver(this.updateSaveState.bind(this))
		this.section.siteData.dynamicCallbacks.save = this.saveServerSettings.bind(this)
		this.updateSaveState()
		this.outboundTokenUrls = new ObservableArray<ObservableStructureDataType, OutboundFallbackToken>(
			tokenInfos,
			null,
			"outboundTokenUrls",
			(data, parent, key) => { return new OutboundFallbackToken(data, parent, key) }
		)
		this.outboundTokenUrls.addObserver(this.updateOutboundFallbackTokenList.bind(this))
		this.sortInboundTokens(inboundTokens)
	}

	private async reloadInboundTokenList(): Promise<void> {
		const inboundTokens = await this.section.loader.loadJson(`${FILE_ADMIN}?type=GetInboundFallbackTokens`)
		this.sortInboundTokens(inboundTokens)
	}

	private sortInboundTokens(inboundTokens: InboundFallbackTokenInfo[]) {
		this.inboundTokens = new Map()
		for (var token of inboundTokens) {
			if (!this.inboundTokens.has(token.user)) {
				this.inboundTokens.set(token.user, [])
			}
			this.inboundTokens.get(token.user)!.push(token)
		}
	}

	private async deleteInboundToken(token: InboundFallbackTokenInfo) {
		if (!confirm(Lang.get("confirm_delete_inbound_fallback_token", atob(token.otherServerUrl))))
			return

		await this.section.loader.loadJson(`${FILE_ADMIN}?type=DeleteInboundFallbackToken`,
			"post",
			`url=${token.otherServerUrl}&user=${token.user}`)
		await this.reloadInboundTokenList()
	}

	public async preInit(): Promise<any> {
		await this.changeLanguageList.promise
		await this.loadUpdates()
	}

	public title(): string {
		return Lang.get("server_settings")
	}
	public titleExtra(): Vnode<any, any> {
		return ObservableLangChooser(this.loader.getSettings())
	}

	private getUpdateUrl(): string {
		return `${FILE_ADMIN}?type=UpdateVersion&fromVersion=${this.section.siteData.packageVersion}`
	}

	private async saveServerSettings(): Promise<void> {
		await this.section.loader.showLoader(
			this.getTools().settingsLoader.saveSettings()
		)
	}

	private async loadUpdates(): Promise<void> {
		let jsonString: string = ""
		try {
			jsonString = await this.section.loader.loadRaw(URL_RELEASES_LIST)
			this.noConnection = false
		}
		catch (e) {
			this.noConnection = true
			return
		}
		const releases = JSON.parse(jsonString)
		const stableReleases: ReleaseType[] = []
		const unstableReleases: ReleaseType[] = []


		//sort releases:

		let searchingForRelease = true;
		for (let i = releases.length - 1; i >= 0; --i) {
			let { tag_name: tagName, prerelease, body, published_at: publishedAt, assets } = releases[i];

			if (!PackageVersionComparator(this.section.siteData.packageVersion).isBelowThen(tagName))
				continue

			let data = { version: tagName, date: new Date(publishedAt), changeLog: body, downloadUrl: assets[0]["browser_download_url"] };
			if (prerelease)
				unstableReleases.push(data);
			else
				stableReleases.push(data);
		}

		//set release information:

		this.releaseData = this.loadPreReleases ? unstableReleases : stableReleases

		if (this.releaseData.length) {
			this.hasUpdates = true
			const lastRelease = this.releaseData[this.releaseData.length - 1]
			this.newestVersionName = lastRelease.version
			this.newestVersionDownloadUrl = lastRelease.downloadUrl
		}
		else
			this.hasUpdates = false
	}
	private async updateNow(): Promise<void> {
		if (this.loadPreReleases) {
			if (!confirm(Lang.get("confirm_prerelease_update")))
				return;
		}
		const loader = this.section.loader
		await loader.showLoader(new Promise(async (resolve, reject) => {
			try {
				await Requests.loadJson(`${FILE_ADMIN}?type=DownloadUpdate`, "post", `url=${this.newestVersionDownloadUrl}`)

				loader.update(Lang.get("state_installing"))
				await Requests.loadJson(`${FILE_ADMIN}?type=DoUpdate`)

				loader.update(Lang.get("state_finish_installing"))
				await Requests.loadJson(`${FILE_ADMIN}?type=UpdateVersion&fromVersion=${this.section.siteData.packageVersion}`)
				resolve(null)
			}
			catch (e) {
				reject(e)
			}
		}), Lang.get("state_downloading"))

		alert(Lang.get("info_web_update_complete"));
		window.location.reload();
	}
	private async changeRelease(e: InputEvent): Promise<void> {
		this.loadPreReleases = (e.target as HTMLSelectElement).selectedIndex == 1
		await this.loadUpdates()
	}

	public getView(): Vnode<any, any> {
		return TabBar(this.selectedIndex, [
			{
				title: Lang.get("general"),
				view: this.getGeneralView.bind(this)
			},
			{
				title: Lang.get("home_message"),
				view: this.getHomeMessageView.bind(this)
			},
			{
				title: Lang.get("impressum"),
				view: this.getImpressumView.bind(this)
			},
			{
				title: Lang.get("privacyPolicy"),
				view: this.getPrivacyPolicyView.bind(this)
			},
			{
				title: Lang.get("fallback_system"),
				view: this.getFallbackSystemView.bind(this)
			}
		])
	}

	private getGeneralView(): Vnode<any, any> {
		const settings = this.loader.getSettings()

		return <div>

			<div class="center">
				<label>
					<small>{Lang.get("server_name")}</small>
					<input type="text" {...BindObservable(settings.siteTranslations.serverName)} />
					{ObservableLangChooser(settings)}
				</label>
			</div>

			{TitleRow(Lang.getWithColon("server_update", this.section.siteData.packageVersion))}

			<label class="right">
				<small>{Lang.get("update_version")}</small>
				<select class="small" onchange={this.changeRelease.bind(this)}>
					<option>{Lang.get("stable")}</option>
					<option>{Lang.get("unstable")}</option>
				</select>
			</label>

			{this.hasUpdates
				? <div class="spacingTop">
					<div class="center">
						<span>{Lang.get("info_newVersionAvailable")}</span>
						&nbsp;
						<span class="highlight">{this.newestVersionName}</span>
					</div>
					<div class="center spacingTop">
						<input type="button" onclick={this.updateNow.bind(this)} value={Lang.get("update_now")} />
					</div>
					{this.releaseData.map((data) =>
						<div class="spacingTop">
							<br />
							<h1>
								<span>{data.version}</span>
								<span class="spacingLeft smallText middle">({data.date.toLocaleString()})</span>
							</h1>
							<div>{m.trust(this.markdownRenderer.render(data.changeLog))}</div>
						</div>
					)}

				</div>
				: <div class="center highlight">{this.noConnection ? Lang.get('info_no_connection_to_update_server') : Lang.get('info_ESMira_is_up_to_date')}</div>
			}
			<br /><br />


			{TitleRow(Lang.getWithColon("additional_languages"))}
			{this.changeLanguageList.getView()}

			{this.debuggingOn &&
				<div>
					<br /><br />
					{TitleRow("Debugging:")}
					<a href={this.getUpdateUrl()}>Run update script</a>
				</div>
			}

		</div>
	}
	private getHomeMessageView(): Vnode<any, any> {
		const settings = this.loader.getSettings()
		return <div>
			<div class="line fakeLabel spacingBottom">
				{RichText(settings.siteTranslations.homeMessage)}
				{ObservableLangChooser(settings)}
			</div>
		</div>
	}
	private getImpressumView(): Vnode<any, any> {
		const settings = this.loader.getSettings()
		return <div>
			<div class="line fakeLabel spacingBottom">
				{RichText(settings.siteTranslations.impressum)}
				{ObservableLangChooser(settings)}
			</div>
		</div>
	}
	private getPrivacyPolicyView(): Vnode<any, any> {
		const settings = this.loader.getSettings()
		return <div>
			<div class="line fakeLabel spacingBottom">
				{RichText(settings.siteTranslations.privacyPolicy)}
				{ObservableLangChooser(settings)}
			</div>
		</div>
	}

	private getFallbackSystemView(): Vnode<any, any> {
		const url = getBaseUrl()
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
			{TitleRow(Lang.get("add_outbound_fallback_token"))}

			{AddOutboundToken(this.addOutboundToken.bind(this), (msg) => { this.section.loader.error(msg) })}

			{this.getFallbackInboundView()}

		</div>

	}

	private getFallbackInboundView(): Vnode<any, any> {
		return <div>
			{TitleRow(Lang.get("admin_inbound_fallback_list"))}
			<span>{Lang.get("inbound_fallback_token_info")}</span>
			<div class="listParent">
				<div class="listChild">
					{Array.from(this.inboundTokens.keys()).map((user: string) =>
						<div>
							<div class="spacingTop spacingBottom"><h2>{user}</h2></div>
							<div>{this.inboundTokens.get(user)?.map((token) =>
								<div>
									{BtnTrash(this.deleteInboundToken.bind(this, token))}
									<span>{atob(token.otherServerUrl)}</span>
								</div>
							)}</div>
						</div>
					)}
				</div>
			</div>
		</div>
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

	private async updateOutboundFallbackTokenList(): Promise<void> {
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=SetOutboundFallbackTokensList`,
			"post",
			`urlList=${JSON.stringify(this.outboundTokenUrls.get().map((token) => btoa(token.url.get())))}`
		)
	}

	private updateSaveState(): void {
		this.setDynamic("showSaveButton", this.loader.getSettings().isDifferent() ?? false)
		m.redraw()
	}

	public destroy(): void {
		this.observerId.removeObserver()
		this.setDynamic("showSaveButton", false)
		this.section.siteData.dynamicCallbacks.save = undefined
		super.destroy();
	}
}