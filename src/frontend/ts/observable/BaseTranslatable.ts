import {BaseObservable, JsonCreatorOptions} from "./BaseObservable";
import {ObservableTypes} from "./types/ObservableTypes";
import {ObservablePrimitive} from "./ObservablePrimitive";

export type TranslatableJsonCreatorOptions = { dontIncludeAllLanguages?: boolean } & JsonCreatorOptions

/**
 * A {@link BaseObservable} that can also store multiple versions under a langCode.
 * The way it works is that each translatable member internally uses a Record<langKey, BaseObservable> only returns the
 * BaseObservable that is active at the moment.
 *
 * Important: Each Container implementation need to ensure that it either uses {@link isTranslatable} to check if their
 * children are BaseTranslatable (and not only BaseObservable) or that only BaseTranslatable children can be added!
 *
 * {@link currentLangCode} is stored the same way as {@link BaseObservable.shared}: It is only defined in the root object of its structure and
 * its reference is then shared between all its children.
 * So in each structure, only a single object of {@link currentLangCode}, that is shared between all its members, exists.
 * The root of a structure is meant to always implement {@link TranslationRootInterface} when translations are be used.
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
	
	/**
	 * Returns true if a BaseObservable is also a BaseTranslatable
	 * @param value the BaseObservable to be checked
	 */
	protected isTranslatable(value: BaseObservable<any>): value is BaseTranslatable<any> {
		return (value as BaseTranslatable<any>).addLanguage !== undefined;
	}
	
	abstract addLanguage(langCode: string, langData?: any): void
	abstract renameLanguage(oldLangCode: string, newLangCode: string): void
	abstract removeLanguage(langCode: string): void
}