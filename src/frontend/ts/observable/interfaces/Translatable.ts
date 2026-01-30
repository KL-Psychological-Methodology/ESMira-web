import {ObservablePrimitive} from "../ObservablePrimitive";
import {BaseObservable, JsonCreatorOptions} from "../BaseObservable";
import {ObservableTypes} from "../types/ObservableTypes";
import {TranslatableRootInterface} from "./TranslatableRootInterface";
import {LangCodeObservable} from "../LangCodeObservable";

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

/**
 * Either returns the currentLangCode of the parent {@link TranslatableRootInterface}
 * or, if newLang is provided, creates a new {@link ObservablePrimitive} with the provided language code.
 * or, if parentTranslatable does not exist or is not a translatable, creates a new {@link ObservablePrimitive} with the default value "en".
 * @param obs the observable for which currentLangCode should be defined.
 * @param newLang the language code that should be used to create a new currentLangCode (is usually only provided by a {@link TranslatableRootInterface}).
 */
export function defineCurrentLangCode(obs: Translatable<ObservableTypes>, newLang?: string): ObservablePrimitive<string> {
	const parentTranslatable = obs.parent && isTranslatable(obs.parent) ? obs.parent : null
	
	if(newLang) {
		return new LangCodeObservable(newLang ?? "en", obs, "currentLangCode")
	}
	else {
		return parentTranslatable?.currentLangCode ?? new LangCodeObservable(newLang ?? "en", obs, "currentLangCode")
	}
}

export type TranslatableJsonCreatorOptions = { dontIncludeAllLanguages?: boolean } & JsonCreatorOptions