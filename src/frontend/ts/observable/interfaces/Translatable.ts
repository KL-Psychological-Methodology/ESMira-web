import {ObservablePrimitive} from "../ObservablePrimitive";
import {BaseObservable, JsonCreatorOptions} from "../BaseObservable";
import {ObservableTypes} from "../types/ObservableTypes";

/**
 * An interface to ensure that an observable is translatable.
 * A Translatable returns its value based on the language set in {@link currentLangCode}.
 * The value of {@link currentLangCode} is (and should only be) set by the {@link TranslatableRootInterface}.
 * A Translatable must implement {@link currentLangCode} by using {@link defineCurrentLangCode()}.
 * @see {@link BaseObservable}
 * @see {@link TranslatableRootInterface}
 * @see {@link defineCurrentLangCode()}
 */
export interface Translatable<T extends ObservableTypes, DefaultT extends ObservableTypes = T> extends BaseObservable<T, DefaultT> {
	readonly currentLangCode: ObservablePrimitive<string>
	addLanguage(langCode: string, langData?: any): void
	renameLanguage(oldLangCode: string, newLangCode: string): void
	removeLanguage(langCode: string): void
}

export function isTranslatable(value: BaseObservable<any>): value is Translatable<any> {
	return (value as Translatable<any>).currentLangCode !== undefined;
}

export function defineCurrentLangCode(parent: BaseObservable<ObservableTypes> | null, newLang?: string) {
	const parentTranslatable = parent && isTranslatable(parent) ? parent : null
	
	if(newLang)
		return new ObservablePrimitive<string>(newLang, null, "currentLang")
	else
		return parentTranslatable?.currentLangCode ?? new ObservablePrimitive<string>("en", null, "currentLang")
}

export type TranslatableJsonCreatorOptions = { dontIncludeAllLanguages?: boolean } & JsonCreatorOptions