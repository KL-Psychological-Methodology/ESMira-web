import {ObservableArray} from "../ObservableArray";
import {BaseObservable} from "../BaseObservable";
import {ObservablePrimitive} from "../ObservablePrimitive";

export interface TranslationRootInterface {
	currentLangCode: ObservablePrimitive<string>
	defaultLang: BaseObservable<string>
	langCodes: ObservableArray<string, BaseObservable<string>>
	
	addLanguage(langCode: string, langData?: any): void
	renameLanguage(oldLangCode: string, newLangCode: string): void
	removeLanguage(langCode: string): void
}