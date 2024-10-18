import {BaseObservable, JsonCreatorOptions} from "./BaseObservable";
import {ObservableTypes} from "./types/ObservableTypes";
import {ObservablePrimitive} from "./ObservablePrimitive";

export type TranslatableJsonCreatorOptions = { dontIncludeAllLanguages?: boolean } & JsonCreatorOptions

/**
 * A {@link BaseObservable} that can store multiple versions under a langCode
 */
export abstract class BaseTranslatable<T extends ObservableTypes> extends BaseObservable<T> {
	public readonly currentLangCode: ObservablePrimitive<string>
	
	protected constructor(parent: BaseObservable<ObservableTypes> | null, key: string, newLang?: string) {
		super(parent, key)
		
		const parentTranslatable = parent && this.isTranslatable(parent) ? parent : null
		
		if(newLang)
			this.currentLangCode = new ObservablePrimitive<string>(newLang, null, "currentLang")
		else
			this.currentLangCode = parentTranslatable?.currentLangCode ?? new ObservablePrimitive<string>("en", null, "currentLang")
	}
	
	
	protected isTranslatable(value: BaseObservable<any>): value is BaseTranslatable<any> {
		return (value as BaseTranslatable<any>).addLanguage !== undefined;
	}
	
	abstract addLanguage(langCode: string, langData?: any): void
	abstract renameLanguage(oldLangCode: string, newLangCode: string): void
	abstract removeLanguage(langCode: string): void
}