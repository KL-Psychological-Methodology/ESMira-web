import {PrimitiveType} from "./types/PrimitiveType";
import {JsonTypes} from "./types/JsonTypes";
import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable, ObserverKeyType} from "./BaseObservable";
import {TranslatableJsonCreatorOptions} from "./interfaces/Translatable";

export type ObservableObjectDataType = Record<ObserverKeyType, JsonTypes>

/**
 * An observable that can hold values associated with a key.
 * Does not implement {@link set}, but provides {@link insert} instead.
 * Note: This observable is always initialized without any entries.
 * @see {@link BaseObservable}
 */
export class ObservableObject<T extends BaseObservable<ObservableTypes> = BaseObservable<ObservableTypes>> extends BaseObservable<Record<ObserverKeyType, T>> {
	constructor(parent: BaseObservable<ObservableTypes> | null, key: ObserverKeyType) {
		super(parent, key)
		this.sharedMemory.data = {}
		this.sharedMemory.default = {}
		this.cleanDanglingChildren();
	}
	
	/**
	 * Removes all children because ObservableObject is initialized without any entries
	 * This method mainly exists to be overridden in {@link DataStructure}
	 */
	protected cleanDanglingChildren(): void {
		this.empty();
	}
	
	protected getValueIndex(): Record<ObserverKeyType, T> {
		return this.sharedMemory.data
	}
	protected setValueIndex(key: ObserverKeyType, value: T): void {
		this.sharedMemory.data[key] = value
	}
	
	private getInitDefaultValues(): Record<ObserverKeyType, PrimitiveType | PrimitiveType[]> {
		return this.sharedMemory.default
	}
	protected setInitDefaultValue(key: ObserverKeyType, value: PrimitiveType | PrimitiveType[]): void {
		this.sharedMemory.default[key] = value
	}
	
	protected reCalcIsDifferent(forceIsDifferent: boolean = false): void {
		if(forceIsDifferent) {
			this.setIsDifferent(true, true)
			return
		}
		else {
			const valueIndex = this.getValueIndex()
			for(let key in valueIndex) {
				if(valueIndex[key].isDifferent()) {
					this.setIsDifferent(true, true)
					return
				}
			}
		}
		this.setIsDifferent(false, true)
	}
	
	public createJson(options?: TranslatableJsonCreatorOptions): Record<ObserverKeyType, JsonTypes> {
		const defaultValues = this.getInitDefaultValues()
		const record: Record<ObserverKeyType, JsonTypes> = {}
		const valueIndex = this.getValueIndex()
		
		options = options ?? {}
		for(const key in valueIndex) {
			const value = valueIndex[key].createJson(options)
			if(options?.dontFilterDefaults || !defaultValues.hasOwnProperty(key) || defaultValues[key] != value)
				record[key] = value
		}
		return record
	}
	
	public get(): Record<ObserverKeyType, T> {
		return this.getValueIndex()
	}
	public getEntry(key: ObserverKeyType): T | undefined {
		return (this as any)[key]
	}
	public getCount(): number {
		let count = 0
		for(const _ in this.getValueIndex()) {
			++count
		}
		return count
	}
	public getFirst(): T | undefined {
		const valueIndex = this.getValueIndex()
		for(const key in valueIndex) {
			return valueIndex[key]
		}
	}
	
	public contains(key: ObserverKeyType): boolean {
		return this.getValueIndex().hasOwnProperty(key)
	}
	
	
	/**
	 * Should not be used
	 * @deprecated
	 */
	public set(_value: Record<ObserverKeyType, T>): void {
		throw new Error("Changing the whole object structure is not supported. You probably want to use insert() instead")
	}
	
	/**
	 * Inserts an observer under the specified key.
	 * Can also be used to overwrite an existing entry.
	 * @param key - The key to associate with the object being inserted.
	 * @param obj - The object to be inserted.
	 * @param initialDefaultValue - An optional initial default value associated with the key.
	 * @param silently - If true, data is only removed. Otherwise {@link hasMutated} is called.
	 */
	public insert(key: ObserverKeyType, obj: T, initialDefaultValue?: PrimitiveType, silently: boolean = false): void {
		if(initialDefaultValue) {
			this.setInitDefaultValue(key, initialDefaultValue)
		}
		this.setValueIndex(key, obj);
		(this as any)[key] = obj;
		if(!silently) {
			this.hasMutated()
		}
	}
	
	/**
	 * Removes an entry from this object
	 * Also removes all connected observables and data from sharedMemory!
	 * @param key - The key of the entry being removed
	 * @param silently - If true, data is only removed. Otherwise {@link hasMutated} is called.
	 */
	public remove(key: ObserverKeyType, silently: boolean = false): void {
		delete this.getInitDefaultValues()[key]
		delete this.getValueIndex()[key]
		delete (this as any)[key]
		delete this.sharedMemory.children[key]
		--this.sharedMemory.childrenCount
		
		if(!silently) {
			this.hasMutated()
		}
	}
	
	public empty(silently: boolean = false): void {
		for(const key in this.getValueIndex()) {
			this.remove(key, true)
		}
		if(!silently) {
			this.hasMutated()
		}
	}
}