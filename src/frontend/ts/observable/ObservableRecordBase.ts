import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable, JsonCreatorOptions} from "./BaseObservable";
import {TranslatableObject, TranslatableObjectDataType} from "./TranslatableObject";
import { JsonTypes } from "./types/JsonTypes";
import {PrimitiveType} from "./types/PrimitiveType";

/**
 * Main logic for {@link ObservableRecord} and {@link TranslatableObjectRecord}
 */
export abstract class ObservableRecordBase<
	K extends number | string,
	ObsT extends BaseObservable<ObservableTypes> | BaseObservable<PrimitiveType>
> extends BaseObservable<Record<number, ObsT>> {
	protected _isDifferent = false
	protected backingField: Record<K, ObsT>
	protected count: number = 0
	protected defaultKeys: string[] = []
	
	protected constructor(
		key: string = ""
	) {
		super(null, key)
		this.keyName = key
		this.backingField = {} as Record<K, ObsT>
	}
	
	public reCalcIsDifferent(forceIsDifferent: boolean = false): void {
		this._isDifferent = forceIsDifferent || this.defaultKeys != Object.keys(this.backingField)
	}
	
	public isDifferent(): boolean {
		return this._isDifferent
	}
	
	public contains(key: K): boolean {
		return this.backingField.hasOwnProperty(key)
	}
	public getEntry(key: K): ObsT | undefined {
		return this.backingField[key]
	}
	public getFirst(): ObsT | undefined {
		for(const key in this.backingField) {
			return this.backingField[key]
		}
		return undefined
	}
	
	public get(): Record<K, ObsT> {
		return this.backingField
	}
	public filter(callback: (id: K, entry: ObsT) => boolean): Record<K, ObsT> {
		const entries :Record<K, ObsT> = {} as Record<K, ObsT>
		for(const id in this.backingField) {
			if(callback(id, this.backingField[id]))
				entries[id] = this.backingField[id]
		}
		return entries
	}
	
	public exists(key: K): boolean {
		return this.backingField.hasOwnProperty(key)
	}
	public setBase(data: Record<K, ObsT>, _silently?: boolean): void {
		this.backingField = data
		this.defaultKeys = Object.keys(data)
		this.hasMutated(!this._isDifferent)
		
		let count = 0
		for(const id in data) {
			++count
		}
		this.count = count
	}
	
	public addBase(key: K, value: ObsT): void {
		if(this.exists(key))
			delete this.backingField[key] // existing entry will essentially be overwritten
		else
			++this.count
		
		this.backingField[key] = value
		this.hasMutated(!this._isDifferent)
	}
	
	public remove(key: K): void {
		if(this.backingField.hasOwnProperty(key)) {
			delete this.backingField[key]
			--this.count
			this.hasMutated(!this._isDifferent)
		}
	}
	public getCount(): number {
		return this.count
	}
	
	public createJson(options?: JsonCreatorOptions): JsonTypes {
		const json: Record<K, TranslatableObjectDataType | JsonTypes> = {} as Record<K, TranslatableObjectDataType>
		for(const key in this.backingField) {
			json[key] = this.backingField[key].createJson(options)
		}
		return json
	}
}