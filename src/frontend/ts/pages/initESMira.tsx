import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {Requests} from "../singletons/Requests";
import {FILE_ADMIN, FILE_CHECK_HTACCESS} from "../constants/urls";
import {TitleRow} from "../widgets/TitleRow";
import {ChangeAccount} from "../widgets/ChangeAccount";

interface FolderData {
	dirBase: string
	dataFolderExists: boolean
}

export class Content extends SectionContent {
	private readonly htaccessEnabled: boolean
	private readonly modRewriteEnabled: boolean
	private folderData: FolderData
	private reuseFolder: boolean = false
	
	public static preLoad(_section: Section): Promise<any>[] {
		return [
			Requests.loadJson(`${FILE_ADMIN}?type=InitESMiraPrep`),
			Requests.loadJson(FILE_CHECK_HTACCESS,)
		]
	}
	
	constructor(section: Section, folderData: FolderData, serverData: {htaccess: boolean, modRewrite: boolean}) {
		super(section)
		this.folderData = folderData
		this.htaccessEnabled = serverData.htaccess
		this.modRewriteEnabled = serverData.modRewrite
	}
	
	public title(): string {
		return Lang.get("init_esmira")
	}
	
	private async changeDataLocation(e: InputEvent): Promise<void> {
		const value = (e.target as HTMLInputElement).value
		this.folderData.dirBase = value
		
		const folderData = await this.section.loader.loadJson(`${FILE_ADMIN}?type=DataFolderExists`, "post", `data_location=${value}`)
		this.folderData.dataFolderExists = folderData.dataFolderExists
		m.redraw()
	}
	private async createESMira(accountName: string, password: string): Promise<boolean> {
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=InitESMira`,
			"post",
			`new_account=${accountName}&pass=${password}&data_location=${this.folderData.dirBase}&reuseFolder=${this.reuseFolder ? "1" : "0"}`
		)
		window.location.reload()
		return true
	}
	
	public getView(): Vnode<any, any> {
		return <div>
			{(!this.htaccessEnabled || !this.modRewriteEnabled) &&
				<div>
					{TitleRow(Lang.getWithColon("additional_server_settings"))}
					<div class="spacingBottom">{m.trust(Lang.get("info_init_additional_steps"))}</div>
					<table class="spacingBottom">
						<tr>
							<td>.htaccess:</td>
							<td style={`color:${this.htaccessEnabled ? 'lightgreen' : 'red'}`}>{this.htaccessEnabled ? Lang.get("enabled") : Lang.get("disabled")}</td>
						</tr>
						<tr>
							<td>mod rewrite:</td>
							<td style={`color:${this.modRewriteEnabled ? 'lightgreen' : 'red'}`}>{this.modRewriteEnabled ? Lang.get("enabled") : Lang.get("disabled")}</td>
						</tr>
					</table>
				</div>
			}
			
			{TitleRow(Lang.getWithColon("installation_settings"))}
			<div class="spacingBottom">{Lang.get("info_path_to_data_directory")}</div>
			<label class="line spacingBottom">
				<small>{Lang.get("path_to_data_directory")}</small>
				<input class="big" type="text" value={this.folderData.dirBase} onchange={this.changeDataLocation.bind(this)}/>
			</label>
			
			{this.folderData.dataFolderExists &&
				<div>
					<div class="spacingBottom">{Lang.get("info_dataFolder_already_exists")}</div>
					<div class="center">
						<label>
							<small>{Lang.get("no")}</small>
							<input type="radio" checked={this.reuseFolder} onchange={() => {this.reuseFolder = true}}/>
						</label>
						<label class="spacingLeft">
							<small>{Lang.get("yes")}</small>
							<input type="radio" checked={!this.reuseFolder} onchange={() => {this.reuseFolder = false}}/>
						</label>
					</div>
				</div>
			}
			
			{TitleRow(Lang.getWithColon("admin_account"))}
			<div>{Lang.get("info_no_password_forget")}</div>
			<br/>
			<br/>
			<br/>
			
			<div class="center">
				{ChangeAccount(
					this.createESMira.bind(this),
					this.section.loader.error.bind(this.section.loader)
				)}
			</div>
		</div>
	}
}