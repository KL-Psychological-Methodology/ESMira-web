import {TranslatableObject, TranslatableObjectDataType} from "../../observable/TranslatableObject";
import {BaseObservable} from "../../observable/BaseObservable";
import {ObservableTypes} from "../../observable/types/ObservableTypes";

export class SiteTranslations extends TranslatableObject {
	public serverName = this.translatable("serverName", "")
	public impressum = this.translatable("impressum", "")
	public privacyPolicy = this.translatable("privacyPolicy", "")
	public homeMessage = this.translatable("homeMessage", "")
	
	constructor(data: TranslatableObjectDataType, parent: BaseObservable<ObservableTypes> | null, key: string, newLang?: string) {
		const defaultLang = newLang ?? (parent as TranslatableObject)?.currentLangCode.get() ?? "en"
		const translationData = data as  Record<string, Record<string, string>>
		super(translationData[defaultLang], parent, key, newLang)
		
		for(const langCode in translationData) {
			if(langCode != defaultLang)
				this.addLanguage(langCode, translationData[langCode])
		}
	}
}