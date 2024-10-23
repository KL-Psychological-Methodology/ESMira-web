import {ObservableArray} from "../ObservableArray";
import {BaseObservable} from "../BaseObservable";
import {ObservablePrimitive} from "../ObservablePrimitive";

/**
 * This interface signals that it is the root of an observable structure and needs to be in charge of translations for all its children
 * Each {@link BaseTranslatable} that is implemented as a container ({@link TranslatableArray} or {@link ObservableStructure})
 * is able to handle translations for itself and its children (but NOT its parent).
 * Additionally, this interface makes sure that the root also has additional settings ({@link defaultLang} and {@link langCodes})
 * that are important for the whole structure (and are usually stored in the backend).
 * I also find it more clear when you can define a "head class" so it is obvious that this class and all its children will have the
 * same languages and are handled together.
 */
export interface TranslationRootInterface {
	currentLangCode: ObservablePrimitive<string>
	defaultLang: BaseObservable<string>
	langCodes: ObservableArray<string, BaseObservable<string>>
	
	addLanguage(langCode: string, langData?: any): void
	renameLanguage(oldLangCode: string, newLangCode: string): void
	removeLanguage(langCode: string): void
}