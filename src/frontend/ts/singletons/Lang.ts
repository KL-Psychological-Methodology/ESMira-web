import fallbackLang from "../../../locales/en.json";
import {Requests} from "./Requests";

let promise: Promise<boolean> = Promise.resolve(false)
let langRecord: Record<string, string> = {}

export type LangKey = keyof typeof fallbackLang

export const Lang = {
	code: "en",
	isInit: false,
	init(langCode: string, packageVersion: string): void {
		this.code = langCode
		if(langCode == "en") {
			promise = Promise.resolve(true)
			return
		}
		promise = Requests.loadRaw(`locales/${langCode}.json?v=${packageVersion}`)
			.then((jsonString) => {
				this.isInit = true
				langRecord = JSON.parse(jsonString)
				return true
			})
			.catch(e => {
				console.error(e)
				return false
			});
	},
	awaitPromise(): Promise<boolean> {
		return promise
	},
	getDynamic: function(key: string): string {
		return this.get(key as keyof typeof fallbackLang)
	},
	get: function(key: LangKey, ... replacers: Array<string | number>): string {
		let value
		if(langRecord.hasOwnProperty(key))
			value = langRecord[key]
		else if(fallbackLang.hasOwnProperty(key))
			value = fallbackLang[key]
		else {
			langRecord[key] = key
			console.error(`Lang: ${key} does not exist`)
			return key
		}
		
		if(replacers.length) {
			for(const replace of replacers) {
				let search
				switch(typeof replace) {
					case "number":
						search = "%d"
						break;
					case "string":
						search = "%s"
						break;
				}
				value = value.replace(search, replace?.toString() ?? "")
			}
			return value
		}
		else
			return value
	},
	getWithColon: function(key: LangKey, ... replacers: Array<string | number>): string {
		return Lang.get("colon", Lang.get(key, ...replacers))
	}
}