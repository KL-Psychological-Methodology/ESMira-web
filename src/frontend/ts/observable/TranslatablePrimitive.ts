import {ObservablePrimitive} from "./ObservablePrimitive";
import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable} from "./BaseObservable";
import {PrimitiveType} from "./types/PrimitiveType";
import {JsonTypes} from "./types/JsonTypes";
import {BaseTranslatable, TranslatableJsonCreatorOptions} from "./BaseTranslatable";

type LanguageData<T> = Record<string, T>

export class TranslatablePrimitive<T extends PrimitiveType> extends BaseTranslatable<T> {
	private readonly observables: Record<string, ObservablePrimitive<T>> = {}
	private langCount: number = 0
	private readonly defaultValue: T
	
	constructor(value: T | LanguageData<T>, parent: BaseObservable<ObservableTypes> | null, key: string, newLang?: string) {
		super(parent, key, newLang)
		
		if(typeof value == "object") {
			for(let langCode in value) {
				this.observables[langCode] = new ObservablePrimitive<T>(value[langCode], this.parent, this.keyName)
				++this.langCount
			}
			this.defaultValue = value[this.currentLangCode.get()]
		}
		else {
			this.defaultValue = value
			this.observables[this.currentLangCode.get()] = new ObservablePrimitive<T>(value, this.parent, this.keyName)
			this.langCount = 1
		}
	}
	
	public updateKeyName(keyName: string, parent?: BaseObservable<ObservableTypes> | undefined): void {
		super.updateKeyName(keyName, parent)
		for(let key in this.observables) {
			this.observables[key].updateKeyName(keyName, parent)
		}
    }
	
	public createJson(options?: TranslatableJsonCreatorOptions): JsonTypes {
		if(options?.dontIncludeAllLanguages || this.langCount <= 1)
			return this.get()
		else {
			let lastValue: T | null = null
			let hasDifferentValues = false
			const r: LanguageData<T> = {}
			for(const langCode in this.observables) {
				const value = this.observables[langCode].get()
				r[langCode] = value
				if(lastValue != null && value != lastValue)
					hasDifferentValues = true
				lastValue = value
			}
			return hasDifferentValues ?  r : (lastValue ?? this.defaultValue)
		}
    }
	
	public addLanguage(langCode: string, value: T = this.defaultValue): void {
		if(this.observables.hasOwnProperty(langCode)) {
			console.log(`Language "${langCode}" already exists in ${this.keyName}`)
			return
		}
		this.observables[langCode] = new ObservablePrimitive<T>(value, this.parent, this.keyName)
		++this.langCount
	}
	public renameLanguage(oldLangCode: string, newLangCode: string): void {
		if(!this.observables.hasOwnProperty(oldLangCode))
			return
		this.observables[newLangCode] = this.observables[oldLangCode]
		delete this.observables[oldLangCode]
		if(this.currentLangCode.get() == oldLangCode)
			this.currentLangCode.set(newLangCode)
	}
	public removeLanguage(langCode: string): void {
		delete this.observables[langCode]
		--this.langCount
		
		if(this.currentLangCode.get() == langCode) {
			let firstLangKey = "en"
			for(let observablesKey in this.observables) {
				firstLangKey = observablesKey
				break
			}
			this.currentLangCode.set(firstLangKey)
		}
	}
	
	public hasMutated(forceIsDifferent: boolean = false): void {
		this.getObs()?.hasMutated(forceIsDifferent)
	}
	public isDifferent(): boolean {
		let isDifferent = false
		for(let key in this.observables) {
			if(this.observables[key].isDifferent()) {
				isDifferent = true
				break;
			}
		}
		return isDifferent
	}
	
	private getObs(): ObservablePrimitive<T> | null {
		const langCode = this.currentLangCode.get()
		if(!this.observables.hasOwnProperty(langCode)) {
			this.observables[langCode] = new ObservablePrimitive<T>(this.defaultValue, this.parent, this.keyName)
			++this.langCount
		}
		return this.observables[langCode]
	}
	
	public get(): T {
		return this.getObs()!.get()
	}
	public set(value: T): void {
		this.getObs()?.set(value)
	}
}