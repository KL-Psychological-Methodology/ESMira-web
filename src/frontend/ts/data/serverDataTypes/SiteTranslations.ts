import {TranslatableObject, TranslatableObjectDataType} from "../../observable/TranslatableObject";
import {BaseObservable} from "../../observable/BaseObservable";
import {ObservableTypes} from "../../observable/types/ObservableTypes";

export class SiteTranslations extends TranslatableObject {
	public serverName = this.translatable("serverName", "")
	public impressum = this.translatable("impressum", "")
	public privacyPolicy = this.translatable("privacyPolicy", "")
	
	constructor(data: TranslatableObjectDataType, parent: BaseObservable<ObservableTypes> | null, key: string, newLang?: string) {
		const defaultLang = newLang ?? "en"
		const translationData = data as  Record<string, Record<string, string>>
		super(translationData[defaultLang], parent, key, newLang)
		
		for(let langCode in translationData) {
			if(langCode != defaultLang)
				this.addLanguage(langCode, translationData[langCode])
		}
	}
}