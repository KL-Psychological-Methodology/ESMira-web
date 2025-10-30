import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../components/DashRow";
import {DashElement} from "../components/DashElement";
import {Lang} from "../singletons/Lang";
import {SectionData} from "../site/SectionData";
import {BtnDownload} from "../components/Buttons";
import {FILE_ADMIN} from "../constants/urls";
import {makeUrlFriendly} from "../constants/methods";
import {AboutESMiraLoader} from "../loader/AboutESMiraLoader";
import {SimplifiedPluginMetadata} from "../plugin/PluginInterfaces";

export class Content extends SectionContent {
	private knownPlugins: SimplifiedPluginMetadata[]
	
	public title(): string {
		return Lang.get("install_plugin")
	}
	public static preLoad(_section: SectionData): Promise<any>[] {
		return [
			AboutESMiraLoader.loadPlugins()
		]
	}
	constructor(sectionData: SectionData, plugins: SimplifiedPluginMetadata[]) {
		super(sectionData)
		this.knownPlugins = plugins
	}
	private async installFromFile(event: InputEvent): Promise<void> {
		const target = event.target as HTMLInputElement
		if(!target || !target.files) {
			return
		}
		
		const file = target.files[0]
		let formData = new FormData()
		formData.append("plugin", file)
		
		await this.sectionData.loader.loadJson(`${FILE_ADMIN}?type=InstallPlugin`, "file", formData)
		
		alert(Lang.get("info_successful"))
		this.goTo("admin/serverSettings")
		window.location.reload()
	}
	private async installFromUrl(url: string): Promise<void> {
		if(!url) {
			return;
		}
		
		await this.sectionData.loader.loadJson(`${FILE_ADMIN}?type=InstallPlugin`, "post", `metadataUrl=${makeUrlFriendly(url)}`)
		
		alert(Lang.get("info_successful"))
		this.goTo("admin/serverSettings")
		window.location.reload()
	}
	
	public getView(): Vnode<any, any> {
		return DashRow(
			DashElement("stretched", {
				content: <form
					onsubmit={async (event: SubmitEvent) => {
						event.preventDefault()
						const formData = new FormData(event.target as HTMLFormElement);
						await this.installFromUrl(formData.get("url")?.toString() ?? "")
					}}>
					<label class="line">
						<small>{Lang.get("install_from_url")}</small>
						<input name="url" class="big" type="url"/>
						<small>{Lang.get("info_install_plugin_from_url")}</small>
					</label>
					<input type="submit" value={Lang.get("install")}/>
				</form>
			}),
			<h1 class="center">{Lang.get("or")}</h1>,
			DashElement("stretched", {
				content: <div>
					<label>
						<small>{Lang.get("install_from_file")}</small>
						<input type="file" accept=".zip" onchange={this.installFromFile.bind(this)}/>
					</label>
				</div>
			}),
			!!this.knownPlugins.length && <h1 class="center">{Lang.get("or")}</h1>,
			... this.knownPlugins.map(entry => DashElement("stretched", {
				content: <div>
					<div>
						<div>
							<a href={entry.website} target="_blank">{entry.name}</a>
							<div class="right">
								{BtnDownload(() => this.installFromUrl(entry.metadataUrl), Lang.get("install"))}
							</div>
						</div>
						<small>{entry.description}</small>
					</div>
				</div>
			}))
		)
	}
}