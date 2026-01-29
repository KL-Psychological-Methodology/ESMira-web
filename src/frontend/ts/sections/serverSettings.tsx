import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {TabBar} from "../components/TabBar";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {FILE_ADMIN, URL_RELEASES_LIST} from "../constants/urls";
import {BindObservable} from "../components/BindObservable";
import {TitleRow} from "../components/TitleRow";
import {RichText} from "../components/RichText";
import {ObservableLangChooser} from "../components/ObservableLangChooser";
import {ChangeLanguageList} from "../components/ChangeLanguageList";
import {ObserverId} from "../observable/BaseObservable";
import {ServerSettingsLoader} from "../loader/ServerSettingsLoader";
import MarkdownIt from "markdown-it";
import {Requests} from "../singletons/Requests";
import {SectionData} from "../site/SectionData";
import {compareSemVersion} from "../constants/methods";
import {PluginListEntry} from "../plugin/PluginInterfaces";
import {PromiseCache} from "../singletons/PromiseCache";
import {DashRow} from "../components/DashRow";
import {DashElement} from "../components/DashElement";

type ReleaseType = {
	version: string,
	date: Date,
	changeLog: string,
	downloadUrl: string
}

export class Content extends SectionContent {
	private settingsLoader: ServerSettingsLoader
	private readonly pluginsNeedsAttention: boolean
	private markdownRenderer = new MarkdownIt()
	private noConnection = false
	private loadPreReleases = false
	private selectedIndex = new ObservablePrimitive(0, null, "serverSettings")
	private readonly observerId: ObserverId
	private hasUpdates: boolean = false
	private newestVersionName: string = ""
	private newestVersionDownloadUrl: string = ""
	private releaseData: ReleaseType[] = []
	private changeLanguageList: ChangeLanguageList

	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [
			sectionData.getTools().settingsLoader.init(),
			PromiseCache.get("plugins", () => sectionData.loader.loadJson(`${FILE_ADMIN}?type=ListPlugins`))
		]
	}

	constructor(sectionData: SectionData, loader: ServerSettingsLoader, pluginListData: PluginListEntry[]) {
		super(sectionData)
		this.settingsLoader = loader
		this.changeLanguageList = new ChangeLanguageList(() => {
			return this.settingsLoader.getSettings()
		})
		
		const packageVersion = this.sectionData.siteData.packageVersion
		this.pluginsNeedsAttention = !!pluginListData.find(entry =>
			(entry.current.version != (entry.newest.version ?? entry.current.version)) || this.sectionData.siteData.pluginLoader.isNotCompatible(packageVersion, entry.current)
		)
		
		this.observerId = loader.getSettings().addObserver(this.updateSaveState.bind(this))
		this.sectionData.siteData.dynamicCallbacks.save = this.saveServerSettings.bind(this)
		this.updateSaveState()
	}

	public async preInit(): Promise<any> {
		await this.changeLanguageList.promise
		await this.loadUpdates()
	}

	public title(): string {
		return Lang.get("server_settings")
	}
	public titleExtra(): Vnode<any, any> {
		return ObservableLangChooser(this.settingsLoader.getSettings())
	}

	private getUpdateUrl(): string {
		return `${FILE_ADMIN}?type=UpdateVersion&fromVersion=${this.sectionData.siteData.packageVersion}`
	}

	private async saveServerSettings(): Promise<void> {
		await this.sectionData.loader.showLoader(
			this.getTools().settingsLoader.saveSettings()
		)
	}

	private async loadUpdates(): Promise<void> {
		let jsonString: string = ""
		try {
			jsonString = await this.sectionData.loader.loadRaw(URL_RELEASES_LIST)
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

		for (let i = releases.length - 1; i >= 0; --i) {
			let { tag_name: tagName, prerelease, body, published_at: publishedAt, assets } = releases[i];

			if(!compareSemVersion(this.sectionData.siteData.packageVersion, tagName)) {
				continue
			}

			let data = { version: tagName, date: new Date(publishedAt), changeLog: body, downloadUrl: assets[0]["browser_download_url"] };
			if (prerelease) {
				unstableReleases.push(data);
			}
			else {
				stableReleases.push(data);
			}
		}

		//set release information:

		this.releaseData = this.loadPreReleases ? unstableReleases : stableReleases

		if (this.releaseData.length) {
			this.hasUpdates = true
			const lastRelease = this.releaseData[this.releaseData.length - 1]
			this.newestVersionName = lastRelease.version
			this.newestVersionDownloadUrl = lastRelease.downloadUrl
		}
		else {
			this.hasUpdates = false
		}
	}
	private async updateNow(): Promise<void> {
		if (this.loadPreReleases) {
			if (!confirm(Lang.get("confirm_prerelease_update"))) {
				return
			}
		}
		const loader = this.sectionData.loader
		await loader.showLoader(new Promise(async (resolve, reject) => {
			try {
				await Requests.loadJson(`${FILE_ADMIN}?type=UpdateStepDownload`, "post", `url=${this.newestVersionDownloadUrl}`)
				loader.update(Lang.get("state_preparing"))
				await Requests.loadJson(`${FILE_ADMIN}?type=UpdateStepPrepare`)
				await Requests.loadWithSSE(`${FILE_ADMIN}?type=UpdateStepReplace`, percent => loader.update(Lang.get("state_updating", percent)))
				loader.update(Lang.get("state_migrating"))
				await Requests.loadJson(`${FILE_ADMIN}?type=UpdateVersion&fromVersion=${this.sectionData.siteData.packageVersion}`)
				
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
				title: Lang.get("maintenance"),
				view: this.getMaintenanceView.bind(this)
			}
		])
	}

	private getGeneralView(): Vnode<any, any> {
		const settings = this.settingsLoader.getSettings()

		return <div>

			<div class="center">
				<label>
					<small>{Lang.get("server_name")}</small>
					<input type="text" {...BindObservable(settings.siteTranslations.serverName)} />
					{ObservableLangChooser(settings)}
				</label>
			</div>

			{TitleRow(Lang.getWithColon("server_update", this.sectionData.siteData.packageVersion))}
			
			{DashRow(
				DashElement(null, {
					template: {
						title: Lang.get("snapshots"),
					},
					href: this.getUrl("snapshots")
				}),
				DashElement(null, {
					template: {
						title: Lang.get("plugins"),
					},
					highlight: this.pluginsNeedsAttention,
					href: this.getUrl("plugins")
				})
			)}
			
			<div class="horizontal flexBlock">
				{this.hasUpdates
					? <div class="spacingTop fillFlexSpace">
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
					: <div class="center highlight fillFlexSpace">{this.noConnection ? Lang.get('info_no_connection_to_update_server') : Lang.get('info_ESMira_is_up_to_date')}</div>
				}
				
				<label>
					<small>{Lang.get("update_version")}</small>
					<select class="small" onchange={this.changeRelease.bind(this)}>
						<option>{Lang.get("stable")}</option>
						<option>{Lang.get("unstable")}</option>
					</select>
				</label>
			</div>
			<br /><br />


			{TitleRow(Lang.getWithColon("additional_languages"))}
			{this.changeLanguageList.getView()}

		</div>
	}
	private getHomeMessageView(): Vnode<any, any> {
		const settings = this.settingsLoader.getSettings()
		return <div>
			<div class="line fakeLabel spacingBottom">
				{RichText(settings.siteTranslations.homeMessage)}
				{ObservableLangChooser(settings)}
			</div>
		</div>
	}
	private getImpressumView(): Vnode<any, any> {
		const settings = this.settingsLoader.getSettings()
		return <div>
			<div class="line fakeLabel spacingBottom">
				{RichText(settings.siteTranslations.impressum)}
				{ObservableLangChooser(settings)}
			</div>
		</div>
	}
	private getPrivacyPolicyView(): Vnode<any, any> {
		const settings = this.settingsLoader.getSettings()
		return <div>
			<div class="line fakeLabel spacingBottom">
				{RichText(settings.siteTranslations.privacyPolicy)}
				{ObservableLangChooser(settings)}
			</div>
		</div>
	}
	
	private getMaintenanceView(): Vnode<any, any> {
		return <div>
			<div class="center spacingTop spacingBottom">
				<input type="button" onclick={async () => {
					await this.sectionData.loader.loadJson(`${FILE_ADMIN}?type=RebuildStudyIndex`)
					this.sectionData.loader.info(Lang.get("info_successful"))
				}} value={Lang.get("rebuild_study_index")} />
			</div>
			
			<div class="center spacingTop spacingBottom">
				<input type="button" onclick={async () => {
					await this.sectionData.loader.loadJson(this.getUpdateUrl())
					this.sectionData.loader.info(Lang.get("info_successful"))
				}} value={Lang.get("run_version_migration_script")} />
			</div>
		</div>
	}

	private updateSaveState(): void {
		this.setDynamic("showSaveButton", this.settingsLoader.getSettings().isDifferent() ?? false)
		m.redraw()
	}

	public destroy(): void {
		this.observerId.removeObserver()
		this.setDynamic("showSaveButton", false)
		this.sectionData.siteData.dynamicCallbacks.save = undefined
		super.destroy();
	}
}