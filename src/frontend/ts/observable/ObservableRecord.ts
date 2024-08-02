import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable, JsonCreatorOptions} from "./BaseObservable";
import {TranslatableObject, TranslatableObjectDataType} from "./TranslatableObject";
import { JsonTypes } from "./types/JsonTypes";

export class ObservableRecord<K extends number | string, T extends TranslatableObject> extends BaseObservable<Record<number, T>> {
	protected _isDifferent = false
	private backingField: Record<K, T>
	private count: number = 0
	private defaultKeys: string[]
	
	constructor(data: Record<K, T>, key: string = "") {
		super(null, key)
		this.keyName = key
		this.backingField = data
		this.defaultKeys = Object.keys(data)
	}
	
	public hasMutated(turnedDifferent: boolean, forceIsDifferent: boolean = false, target: BaseObservable<ObservableTypes> = this): void {
		this._isDifferent = forceIsDifferent || this.defaultKeys != Object.keys(this.backingField)
		this.runObservers(turnedDifferent, target)
	}
	
	public isDifferent(): boolean {
		return this._isDifferent
	}
	
	public contains(key: K): boolean {
		return this.backingField.hasOwnProperty(key)
	}
	public getEntry(key: K): T | undefined {
		return this.backingField[key]
	}
	public getFirst(): T | undefined {
		for(const key in this.backingField) {
			return this.backingField[key]
		}
		return undefined
	}
	
	public get(): Record<K, T> {
		return this.backingField
	}
	public filter(callback: (id: K, entry: T) => boolean): Record<K, T> {
		const entries :Record<K, T> = {} as Record<K, T>
		for(const id in this.backingField) {
			if(callback(id, this.backingField[id]))
				entries[id] = this.backingField[id]
		}
		return entries
	}
	
	public exists(key: K): boolean {
		return this.backingField.hasOwnProperty(key)
	}
	public set(data: Record<K, T>, _silently?: boolean): void {
		this.backingField = data
		this.defaultKeys = Object.keys(data)
		this.hasMutated(!this._isDifferent)
		
		let count = 0
		for(const id in data) {
			++count
		}
		this.count = count
	}
	
	public add(key: K, value: T): void {
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
		const json: Record<K, TranslatableObjectDataType> = {} as Record<K, TranslatableObjectDataType>
		for(const key in this.backingField) {
			json[key] = this.backingField[key].createJson(options)
		}
		return json
	}
}