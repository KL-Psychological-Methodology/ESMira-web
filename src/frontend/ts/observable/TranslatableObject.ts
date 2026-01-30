import {ObservablePrimitive} from "./ObservablePrimitive";
import {JsonTypes} from "./types/JsonTypes";
import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable, ObserverKeyType} from "./BaseObservable";
import {ObservableObject} from "./ObservableObject";
import {defineCurrentLangCode, isTranslatable, Translatable, TranslatableJsonCreatorOptions} from "./interfaces/Translatable";

export type ObservableStructureDataType = Record<string, JsonTypes>

/**
 * An observable that can hold translatable values associated with a key.
 * @see {@link ObservableObject}
 * @see {@link Translatable}
 * @see {@link BaseObservable}
 */
export class TranslatableObject<T extends BaseObservable<ObservableTypes> = BaseObservable<ObservableTypes>> extends ObservableObject<T> implements Translatable<ObservableTypes> {
	public readonly currentLangCode: ObservablePrimitive<string>
	
	constructor(parent: BaseObservable<ObservableTypes> | null, key: ObserverKeyType, newLang?: string) {
		super(parent, key)
		this.currentLangCode = defineCurrentLangCode(this, newLang)
	}
	
	public createJson(options?: TranslatableJsonCreatorOptions): Record<ObserverKeyType, JsonTypes> {
		return super.createJson(options)
	}
	
	public addLanguage(langCode: string, langData?: Record<ObserverKeyType, JsonTypes>): void {
		const valueIndex = this.getValueIndex()
		for(const key in valueIndex) {
			const obs = valueIndex[key]
			if(isTranslatable(obs)) {
				obs.addLanguage(langCode, langData && langData.hasOwnProperty(key) ? langData[key] : undefined)
			}
		}
	}
	public renameLanguage(oldLangCode: string, newLangCode: string): void {
		const valueIndex = this.getValueIndex()
		for(const key in valueIndex) {
			const obs = valueIndex[key]
			if(isTranslatable(obs)) {
				obs.renameLanguage(oldLangCode, newLangCode)
			}
		}
	}
	public removeLanguage(langCode: string): void {
		const valueIndex = this.getValueIndex()
		for(const key in valueIndex) {
			const obs = valueIndex[key]
			if(isTranslatable(obs)) {
				obs.removeLanguage(langCode)
			}
		}
		if(!this.sharedMemory.preventIsDifferentRecalculations) {
			this.reCalcIsDifferent()
		}
	}
}