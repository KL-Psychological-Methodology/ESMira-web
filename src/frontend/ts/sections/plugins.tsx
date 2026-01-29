import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {BtnAdd, BtnDownload, BtnEdit, BtnTrash} from "../components/Buttons";
import {SectionData} from "../site/SectionData";
import {FILE_ADMIN} from "../constants/urls";
import {makeUrlFriendly, safeConfirm} from "../constants/methods";
import {DropdownMenu} from "../components/DropdownMenu";
import {PluginListEntry, PluginMetadata} from "../plugin/PluginInterfaces";
import {PromiseCache} from "../singletons/PromiseCache";
import warnSvg from "../../imgs/icons/warn.svg?raw";

export class Content extends SectionContent {
	private pluginList: PluginListEntry[] = []
	
	constructor(sectionData: SectionData, pluginList: PluginListEntry[]) {
		super(sectionData)
		this.pluginList = pluginList
	}
	
	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [
			PromiseCache.get("plugins", () => sectionData.loader.loadJson(`${FILE_ADMIN}?type=ListPlugins`))
		]
	}
	
	public title(): string {
		return Lang.get("plugins")
	}
	
	private async deletePlugin(plugin: PluginMetadata): Promise<void> {
		if(!safeConfirm(Lang.get("confirm_delete_plugin", plugin.name))) {
			return;
		}
		
		await this.sectionData.loader.loadJson(`${FILE_ADMIN}?type=DeletePlugin`, "post", `pluginId=${plugin.pluginId}`)
		
		alert(Lang.get("info_successful"));
		window.location.reload();
	}
	
	private async updatePlugin(metadataUrl: string): Promise<void> {
		await this.sectionData.loader.loadJson(`${FILE_ADMIN}?type=InstallPlugin`, "post", `metadataUrl=${makeUrlFriendly(metadataUrl)}`)
		
		alert(Lang.get("info_successful"));
		window.location.reload();
	}
	
	public getView(): Vnode<any, any> {
		const packageVersion = this.sectionData.siteData.packageVersion
		
		return <div class="spacingTop center">
			<div class="vertical hAlignStart">
				{this.pluginList.map(
					entry => <div class="line">
						<a href={entry.newest.website ?? entry.current.website} target="_blank">{entry.current.name} ({entry.current.version})</a>
						{this.sectionData.siteData.pluginLoader.isNotCompatible(packageVersion, entry.current)
							&&
							<span class="inlineIcon middle" title={Lang.get("error_plugin_not_compatible")}>
								{m.trust(warnSvg)}
								&nbsp;
							</span>
						}
						{DropdownMenu("pluginSettings",
							BtnEdit(),
							(close) => <div>
								{this.sectionData.siteData.pluginLoader.sectionHasPluginFrontend(entry.current.pluginId, "pluginSettings") &&
									<>
										<div>
											<a href={this.getUrl(`pluginSettings:${entry.current.pluginId}`)} onclick={close}>{BtnEdit(undefined, Lang.get("settings"))}</a>
										</div>
										<br/>
									</>
								}
								
								<div>
									{BtnTrash(() => this.deletePlugin.bind(this, entry.current), Lang.get("uninstall"))}
								</div>
							</div>
						)}
						
						{entry.current.version != (entry.newest.version ?? entry.current.version) && entry.current.metadataUrl &&
							BtnDownload(() => this.updatePlugin.bind(this, entry.current.metadataUrl!), Lang.get("update_to_version", entry.newest.version ?? "0.0.0"))
						}
					
					</div>
				)}
				<br/>
				<a href={this.getUrl("installPlugin")}>{BtnAdd(undefined, Lang.get("install_new"))}</a>
			</div>
		</div>
	}
}