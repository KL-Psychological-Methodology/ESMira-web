import {FILE_ADMIN} from "../constants/urls";
import {PromiseCache} from "../singletons/PromiseCache";
import {Requests} from "../singletons/Requests";
import {ServerData} from "../data/serverDataTypes/ServerData";
import {JsonTypes} from "../observable/types/JsonTypes";


export class ServerSettingsLoader {
	private settings?: ServerData
	
	public async init(): Promise<ServerSettingsLoader> {
		await PromiseCache.get(`serverConfigs`, async () => {
			const json = await Requests.loadJson(`${FILE_ADMIN}?type=GetServerConfig`)
			this.settings = new ServerData(json)
		})
		return this
	}
	
	public getSettings(): ServerData {
		return this.settings!
	}
	
	public async saveSettings(): Promise<void> {
		const settings = this.settings
		if(settings == null)
			return Promise.resolve()
		
		const translationData: Record<string, JsonTypes> = {}
		const langCodes = settings.langCodes.get()
		
		const currentLang = settings.currentLangCode.get()
		for(const langCode of langCodes) {
			settings.currentLangCode.set(langCode.get())
			translationData[langCode.get()] = settings.siteTranslations.createJson({ dontFilterDefaults: true, dontIncludeAllLanguages: true })
		}
		settings.currentLangCode.set(currentLang)
		
		const exportData = settings.createJson({ dontFilterDefaults: true })
		exportData["translationData"] = translationData
		
		const json = await Requests.loadJson(
			FILE_ADMIN + "?type=SaveServerConfigs",
			"post",
			JSON.stringify(exportData)
		)
		
		
		this.settings = new ServerData(json, settings.defaultLang.get())
		this.settings.importObserverData(settings)
		this.settings.currentLangCode.set(settings.currentLangCode.get())
		this.settings.hasMutated()
		document.getElementById("headerServerName")!.innerText = this.settings.siteTranslations.serverName.get()
	}
}