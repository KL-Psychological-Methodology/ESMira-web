import {ObservablePrimitive} from "./ObservablePrimitive";
import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable, ObserverKeyType} from "./BaseObservable";
import {PrimitiveType} from "./types/PrimitiveType";
import {JsonTypes} from "./types/JsonTypes";
import {defineCurrentLangCode, Translatable, TranslatableJsonCreatorOptions} from "./interfaces/Translatable";

type LanguageData<T> = Record<string, T>

/**
 * An observable that is translatable and can hold any primitive (string, number, boolean).
 * @see {@link ObservablePrimitive}
 * @see {@link Translatable}
 * @see {@link BaseObservable}
 */
export class TranslatablePrimitive<T extends PrimitiveType> extends BaseObservable<T> implements Translatable<T> {
	public readonly currentLangCode: ObservablePrimitive<string>
	
	constructor(value: T | LanguageData<T>, parent: BaseObservable<ObservableTypes> | null, key: ObserverKeyType) {
		super(parent, key)
		this.currentLangCode = defineCurrentLangCode(this)
		
		if(value != null && typeof value == "object") { // value will be null when it has the wrong type (source was faulty)
			this.sharedMemory.data = value //we cannot use set() because it does not expect a LanguageData type
			this.setDefaultRecord({...value})
		}
		else {
			const langCode = this.currentLangCode.get()
			const record: LanguageData<T> = {
				[langCode]: value,
			}
			this.sharedMemory.data = record //we cannot use set() because it does not expect a LanguageData type
			this.setDefaultRecord({...record})
		}
	}
	
	private getDataRecord(): LanguageData<T> {
		return this.sharedMemory.data
	}
	private getDefaultRecord(): LanguageData<T> {
		return this.sharedMemory.default
	}
	
	private setDefaultRecord(value: Record<string, T>): void {
		this.sharedMemory.default = value
	}
	
	public createJson(options?: TranslatableJsonCreatorOptions): JsonTypes {
		if(options?.dontIncludeAllLanguages)
			return this.get()
		else {
			const record = this.getDataRecord()
			let lastValue: T | null = null
			let hasDifferentValues = false
			for(const langCode in record) {
				const value = record[langCode]
				if(lastValue != null && value != lastValue) {
					hasDifferentValues = true
					break;
				}
				lastValue = value
			}
			return hasDifferentValues ? record : (lastValue ?? this.getDefault())
		}
    }
	
	public addLanguage(langCode: string, value: T = this.getDefault()): void {
		const record = this.getDataRecord()
		if(record.hasOwnProperty(langCode)) {
			console.log(`Language "${langCode}" already exists in ${this.keyName}`)
			return
		}
		record[langCode] = value
		this.getDefaultRecord()[langCode] = value
	}
	public renameLanguage(oldLangCode: string, newLangCode: string): void {
		const record = this.getDataRecord()
		const defaultRecord = this.getDefaultRecord()
		if(record.hasOwnProperty(oldLangCode)) {
			record[newLangCode] = record[oldLangCode]
			delete record[oldLangCode]
		}
		if(defaultRecord.hasOwnProperty(oldLangCode)) {
			defaultRecord[newLangCode] = defaultRecord[oldLangCode]
			delete defaultRecord[oldLangCode]
		}
		if(this.currentLangCode.get() == oldLangCode)
			this.currentLangCode.set(newLangCode)
	}
	public removeLanguage(langCode: string): void {
		const record = this.getDataRecord()
		delete record[langCode]
		
		if(this.currentLangCode.get() == langCode) {
			let firstLangKey = "en"
			for(let observablesKey in record) {
				firstLangKey = observablesKey
				break
			}
			this.currentLangCode.set(firstLangKey)
		}
		if(!this.sharedMemory.preventIsDifferentRecalculations) {
			this.reCalcIsDifferent()
		}
	}
	
	protected reCalcIsDifferent(forceIsDifferent: boolean = false): void {
		if(forceIsDifferent) {
			this.setIsDifferent(true, true)
			return
		}
		const record = this.getDataRecord()
		const defaultRecord = this.getDefaultRecord()
		let isDifferent = false
		for(const key in record) {
			const defaultValue = defaultRecord[key as keyof typeof defaultRecord]
			if(defaultValue != record[key]) {
				isDifferent = true
				break;
			}
		}
		this.setIsDifferent(isDifferent, true)
	}
	
	protected getDefault(): T {
		const record = this.getDefaultRecord()
		return record[this.currentLangCode.get() as keyof typeof record]
	}
	protected setDefault(value: T): void {
		const record = this.getDefaultRecord()
		record[this.currentLangCode.get() as keyof typeof record] = value
	}
	public get(): T {
		const record = this.getDataRecord()
		const langCode = this.currentLangCode.get() as keyof typeof record
		if(!record.hasOwnProperty(langCode)) {
			record[langCode] = this.getDefault()
		}
		return record[langCode]
	}
	public set(value: T, silently: boolean = false): void {
		const record = this.getDataRecord()
		record[this.currentLangCode.get() as keyof typeof record] = value
		
		if(!silently) {
			this.hasMutated()
		}
	}
}