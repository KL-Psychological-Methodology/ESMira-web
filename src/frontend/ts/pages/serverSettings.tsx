import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {TabBar} from "../widgets/TabBar";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {FILE_ADMIN, URL_RELEASES_LIST} from "../constants/urls";
import {BindObservable} from "../widgets/BindObservable";
import {TitleRow} from "../widgets/TitleRow";
import {RichText} from "../widgets/RichText";
import {ObservableLangChooser} from "../widgets/ObservableLangChooser";
import {ChangeLanguageList} from "../widgets/ChangeLanguageList";
import {Section} from "../site/Section";
import {ObserverId} from "../observable/BaseObservable";
import {ServerSettingsLoader} from "../loader/ServerSettingsLoader";
import MarkdownIt from "markdown-it";
import {Requests} from "../singletons/Requests";
import {PackageVersionComparator} from "../singletons/PackageVersionComparator";
import { C } from "@fullcalendar/core/internal-common";

type ReleaseType = {version: string, date: Date, changeLog: string, downloadUrl: string}

interface SnapshotInfo {
	hasSnapshot: boolean,
	fileChanged: number,
	fileSize: number
}

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
	private snapshotInfo: SnapshotInfo

	public static preLoad(section: Section): Promise<any>[] {
		return [
			section.getTools().settingsLoader.init(),
			Requests.loadJson(`${FILE_ADMIN}?type=GetSnapshotInfo`)
		]
	}
	
	constructor(sitePage: Section, loader: ServerSettingsLoader, snapshotInfo: SnapshotInfo) {
		super(sitePage)
		this.loader = loader
		this.changeLanguageList = new ChangeLanguageList(() => {
			return this.loader.getSettings()
		})
		
		this.observerId = loader.getSettings().addObserver(this.updateSaveState.bind(this))
		this.section.siteData.dynamicCallbacks.save = this.saveServerSettings.bind(this)
		this.updateSaveState()
		this.snapshotInfo = snapshotInfo
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
		catch(e) {
			this.noConnection = true
			return
		}
		const releases = JSON.parse(jsonString)
		const stableReleases: ReleaseType[] = []
		const unstableReleases: ReleaseType[] = []
		
		
		//sort releases:
		
		let searchingForRelease = true;
		for(let i=releases.length-1; i>=0; --i) {
			let {tag_name: tagName, prerelease, body, published_at: publishedAt, assets} = releases[i];
			
			if(!PackageVersionComparator(this.section.siteData.packageVersion).isBelowThen(tagName))
				continue
			
			let data = {version: tagName, date: new Date(publishedAt), changeLog: body, downloadUrl: assets[0]["browser_download_url"]};
			if(prerelease)
				unstableReleases.push(data);
			else
				stableReleases.push(data);
		}
		
		//set release information:
		
		this.releaseData = this.loadPreReleases ? unstableReleases : stableReleases
		
		if(this.releaseData.length) {
			this.hasUpdates = true
			const lastRelease = this.releaseData[this.releaseData.length-1]
			this.newestVersionName = lastRelease.version
			this.newestVersionDownloadUrl = lastRelease.downloadUrl
		}
		else
			this.hasUpdates = false
	}
	private async updateNow(): Promise<void> {
		if(this.loadPreReleases) {
			if(!confirm(Lang.get("confirm_prerelease_update")))
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
			catch(e) {
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
			}
		])
	}
	
	private getGeneralView(): Vnode<any, any> {
		const settings = this.loader.getSettings()
		
		return <div>
			
			<div class="center">
				<label>
					<small>{Lang.get("server_name")}</small>
					<input type="text" {... BindObservable(settings.siteTranslations.serverName)}/>
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
						<input type="button" onclick={this.updateNow.bind(this)} value={Lang.get("update_now")}/>
					</div>
					{this.releaseData.map((data) =>
						<div class="spacingTop">
							<br/>
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
			<br/><br/>
			
			
			{TitleRow(Lang.getWithColon("snapshots"))}

			<div>
				<div>
				{this.snapshotInfo.hasSnapshot ?
					<span>{Lang.get("has_snapshot", (new Date(this.snapshotInfo.fileChanged * 1000)).toLocaleString())}</span> :
					<span>{Lang.get("no_snapshot")}</span>
				}
				</div>
				<div>
					<input type="button" onclick={this.createSnapshot.bind(this)} value={Lang.get("create_snapshot")}/>
					<span id="createSnapshotProgress"></span>
				</div>
				{this.snapshotInfo.hasSnapshot && <div>
					<input type="button" onclick={this.deleteSnapshot.bind(this)} value={Lang.get("delete_snapshot")}/>
					<input type="button" onclick={this.downloadSnapshot.bind(this)} value={Lang.get("get_snapshot")}/>
				</div>}
				<div>
					<form enctype="multipart/form-data">
						<input type="file" name="snapshotFileUpload" id="snapshotFileUpload"></input>
					</form>
					<input type="button" onclick={this.uploadSnapshot.bind(this)} value={Lang.get("upload_snapshot")}/>
					<span id="snapshotUploadProgress"></span>
				</div>
				{this.snapshotInfo.hasSnapshot && <div>
					<input type="button" onclick={this.restoreSnapshot.bind(this)} value={Lang.get("restore_snapshot")}/>
				</div>}
			</div>

			{TitleRow(Lang.getWithColon("additional_languages"))}
			{this.changeLanguageList.getView()}
			
			{this.debuggingOn &&
				<div>
					<br/><br/>
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

	private updateSaveState(): void {
		this.setDynamic("showSaveButton", this.loader.getSettings().isDifferent() ?? false)
		m.redraw()
	}
	
	private createSnapshot(): void {
		const eventSource = new EventSource(`${FILE_ADMIN}?type=CreateSnapshot`)
		const snapshotProgressSpan = document.getElementById("createSnapshotProgress")

		eventSource.addEventListener('progress', e => {
			console.log(e.data)
			if(snapshotProgressSpan != undefined) {
				const data = JSON.parse(e.data)
			 	const step: number = data['stage'] || 0
			 	const percent: number = data['progress'] || 0
			 	snapshotProgressSpan.innerText = Lang.get("creating_snaphsot", step, percent)
			}
		})
		eventSource.addEventListener('finished', e => {
			alert(Lang.get("created_snapshot"));
			eventSource.close();
			window.location.reload();
		})
		eventSource.addEventListener('failed', e => {
			console.log(e.data);
			if(snapshotProgressSpan != undefined)
				snapshotProgressSpan.innerText = Lang.get("creating_snapshot_failed")
			eventSource.close();
		})
	}

	private deleteSnapshot(): void {
		Requests.loadJson(`${FILE_ADMIN}?type=DeleteSnapshot`).then((value) => window.location.reload())
	}

	private downloadSnapshot(): void {
		let element = document.createElement('a')
		element.setAttribute('href', `${FILE_ADMIN}?type=GetSnapshot`)
		element.setAttribute('download', 'snapshot.zip')
		document.body.appendChild(element);
		element.click();
		document.body.removeChild(element);
	}

	private async uploadSnapshot(): Promise<void> {
		const inputElement: HTMLInputElement | null = document.querySelector('#snapshotFileUpload')
		const progressElement: HTMLSpanElement | null = document.querySelector('#snapshotUploadProgress')
		if(inputElement == null)
			return

		if(inputElement.files == null)
			return

		const file = inputElement.files[0]

		if(file == undefined)
			return

		console.log(file);
		const chunkSize = 1024 * 1024 * 5 // 5 MB chunks
		const timeStamp = new Date().getTime()

		for(let start = 0; start < file.size; start += chunkSize) {
			const progress = Math.round((start / file.size) * 100).toString() + " %"
			if(progressElement != null)
				progressElement.innerText = progress
			const chunk = file.slice(start, start + chunkSize)
			const formData = new FormData()
			console.log(formData)
			formData.set('file', chunk)
			formData.set('name', timeStamp.toString())
			if(start + chunkSize >= file.size)
				formData.set('complete', "1")

			await Requests.loadRaw(`${FILE_ADMIN}?type=UploadSnapshot`, "post", formData)
		}

		if(progressElement != null)
			progressElement.innerText = ""
		window.location.reload()
	}

	private restoreSnapshot() {
		Requests.loadJson(`${FILE_ADMIN}?type=RestoreSnapshot`).then((value) => window.location.reload())
	}

	public destroy(): void {
		this.observerId.removeObserver()
		this.setDynamic("showSaveButton", false)
		this.section.siteData.dynamicCallbacks.save = undefined
		super.destroy();
	}
}