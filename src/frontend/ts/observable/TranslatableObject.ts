import {PrimitiveType} from "./types/PrimitiveType";
import {ObservablePrimitive} from "./ObservablePrimitive";
import {JsonTypes} from "./types/JsonTypes";
import {ObservableTypes} from "./types/ObservableTypes";
import {TranslatablePrimitive} from "./TranslatablePrimitive";
import {BaseObservable} from "./BaseObservable";
import {BaseTranslatable, TranslatableJsonCreatorOptions} from "./BaseTranslatable";
import {TranslatableArray} from "./TranslatableArray";
import {ObservableArray} from "./ObservableArray";

export type TranslatableObjectDataType = Record<string, JsonTypes>

export class TranslatableObject extends BaseTranslatable<ObservableTypes> {
	private _isDifferent = false
	private alwaysDifferent = false
	
	private readonly initJson: TranslatableObjectDataType
	
	private readonly generalDefaultValues: Record<string, PrimitiveType | PrimitiveType[]> = {}
	private readonly valueIndex: Record<string, BaseObservable<ObservableTypes>> = {}
	
	
	constructor(data: TranslatableObjectDataType, parent: BaseObservable<ObservableTypes> | null, key: string, newLang?: string) {
		super(parent, key, newLang)
		this.initJson = data
	}
	
	private calcIsDifferent(overrideIsDifferent: boolean = false): void {
		if(overrideIsDifferent || this.alwaysDifferent) {
			this._isDifferent = true
			return
		}
		else {
			for(let key in this.valueIndex) {
				if(this.valueIndex[key].isDifferent()) {
					this._isDifferent = true
					return
				}
			}
		}
		this._isDifferent = false
	}
	
	public setDifferent(isDifferent: boolean): void {
		this.alwaysDifferent = isDifferent
		this.hasMutated(!this._isDifferent)
	}
	public isDifferent(): boolean {
		return this._isDifferent
	}
	
	public hasMutated(turnedDifferent: boolean = false, forceIsDifferent: boolean = false, target: BaseObservable<ObservableTypes> = this): void {
		const wasDifferent = this._isDifferent
		this.calcIsDifferent(forceIsDifferent)
		this.runObservers(turnedDifferent, target)
		if(this.parent)
			this.parent.hasMutated(!wasDifferent && turnedDifferent, this._isDifferent, target)
	}
	
	public createJson(options?: TranslatableJsonCreatorOptions): Record<string, JsonTypes> {
		const record: Record<string, JsonTypes> = {}
		
		options = options ?? {}
		for(let key in this.valueIndex) {
			const value = this.valueIndex[key].createJson(options)
			if(options?.dontFilterDefaults || !this.generalDefaultValues.hasOwnProperty(key) || this.generalDefaultValues[key] != value)
				record[key] = value
		}
		return record
	}
	
	/**
	 * @deprecated
	 */
	get(): any {
		return this
	}
	/**
	 * @deprecated
	 */
	public set(_value: any): void {
		throw new Error("Not supported")
	}
	
	public addLanguage(langCode: string, langData?: Record<string, JsonTypes>): void {
		for(let key in this.valueIndex) {
			const obs = this.valueIndex[key]
			if(this.isTranslatable(obs))
				obs.addLanguage(langCode, langData && langData.hasOwnProperty(key) ? langData[key] : undefined)
		}
	}
	public renameLanguage(oldLangCode: string, newLangCode: string): void {
		for(let key in this.valueIndex) {
			const obs = this.valueIndex[key]
			if(this.isTranslatable(obs))
				obs.renameLanguage(oldLangCode, newLangCode)
		}
	}
	public removeLanguage(langCode: string): void {
		for(let key in this.valueIndex) {
			const obs = this.valueIndex[key]
			if(this.isTranslatable(obs))
				obs.removeLanguage(langCode)
		}
		this.calcIsDifferent()
	}
	
	public updateKeyName(keyName?: string, parent?: BaseObservable<ObservableTypes> | undefined): void {
		super.updateKeyName(keyName, parent)
		for(let key in this.valueIndex) {
			this.valueIndex[key].updateKeyName()
		}
	}
	
	protected primitive<T extends PrimitiveType>(key: string, defaultValue: T): BaseObservable<T> {
		const obs = new ObservablePrimitive(this.initJson.hasOwnProperty(key) ? this.initJson[key] as T : defaultValue, this, key)
		
		this.generalDefaultValues[key] = defaultValue
		this.valueIndex[key] = obs
		return obs
	}
	protected primitiveArray<T extends PrimitiveType>(key: string, defaultValue: T[] = []): ObservableArray<T, BaseObservable<T>> {
		const value = this.initJson.hasOwnProperty(key) ? this.initJson[key] as T[] : defaultValue
		const obs = new ObservableArray<T, BaseObservable<T>>(value, this, key, (data, parent, childKey) => {
			return new ObservablePrimitive<T>(data, parent, childKey)
		})
		
		this.generalDefaultValues[key] = defaultValue
		this.valueIndex[key] = obs
		return obs
	}
	
	protected translatable(key: string, defaultValue: string): BaseObservable<string> {
		const obs = new TranslatablePrimitive(this.initJson.hasOwnProperty(key) ? this.initJson[key] as string : defaultValue, this, key)
		
		this.generalDefaultValues[key] = defaultValue
		this.valueIndex[key] = obs
		return obs
	}
	protected translatableArray(key: string, defaultValue: string[] = []): TranslatableArray<string, BaseTranslatable<string>> {
		const value = this.initJson.hasOwnProperty(key) ? this.initJson[key] as string[] : defaultValue
		const obs = new TranslatableArray<string, TranslatablePrimitive<string>>(value, this, key, (data, parent, childKey) => {
			return new TranslatablePrimitive<string>(data, parent, childKey)
		})
		this.generalDefaultValues[key] = defaultValue
		this.valueIndex[key] = obs
		return obs
	}
	
	protected objectArray<T extends TranslatableObject>(
		key: string,
		typeConstructor: { new(data: TranslatableObjectDataType, parent: BaseObservable<ObservableTypes> | null, key: string, newLang?: string): T ;}
	): TranslatableArray<TranslatableObjectDataType, T> {
		const obs = new TranslatableArray<TranslatableObjectDataType, T>(
			this.initJson.hasOwnProperty(key) ? this.initJson[key] as TranslatableObjectDataType[] : [],
			this,
			key,
			(data, parent, childKey) => {
				return new typeConstructor(data, parent, childKey)
			}
		)
		this.valueIndex[key] = obs
		return obs
	}
	protected object<T extends TranslatableObject>(
		key: string,
		typeConstructor: { new(data: TranslatableObjectDataType, parent: BaseObservable<ObservableTypes> | null, key: string, newLang?: string): T ;}
	): T {
		const obs = new typeConstructor(this.initJson.hasOwnProperty(key) ? this.initJson[key] as TranslatableObjectDataType : {}, this, key)
		this.valueIndex[key] = obs
		return obs
	}
}