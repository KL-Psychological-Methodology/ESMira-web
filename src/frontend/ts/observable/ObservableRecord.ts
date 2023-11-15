import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable, JsonCreatorOptions} from "./BaseObservable";
import {TranslatableObject, TranslatableObjectDataType} from "./TranslatableObject";
import { JsonTypes } from "./types/JsonTypes";

export class ObservableRecord<T extends TranslatableObject> extends BaseObservable<Record<number, T>> {
	protected _isDifferent = false
	private backingField: Record<number, T>
	private defaultKeys: string[]
	
	constructor(data: Record<number, T>, key: string = "") {
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
	
	public contains(key: number): boolean {
		return this.backingField.hasOwnProperty(key)
	}
	public getEntry(key: number): T | undefined {
		return this.backingField[key]
	}
	
	public get(): Record<number, T> {
		return this.backingField
	}
	public filter(callback: (id: number, entry: T) => boolean): Record<number, T> {
		const entries :Record<number, T> = {}
		for(const id in this.backingField) {
			if(callback(parseInt(id), this.backingField[id]))
				entries[id] = this.backingField[id]
		}
		return entries
	}
	
	public exists(key: number): boolean {
		return this.backingField.hasOwnProperty(key)
	}
	public set(data: Record<number, T>, _silently?: boolean): void {
		this.backingField = data
		this.defaultKeys = Object.keys(data)
		this.hasMutated(!this._isDifferent)
	}
	
	public add(key: number, value: T): void {
		if(this.exists(key))
			delete this.backingField[key]
		
		this.backingField[key] = value
		this.hasMutated(!this._isDifferent)
	}
	public remove(key: number): void {
		if(this.backingField.hasOwnProperty(key)) {
			delete this.backingField[key]
			this.hasMutated(!this._isDifferent)
		}
	}
	
	public createJson(options?: JsonCreatorOptions): JsonTypes {
		const json: Record<number, TranslatableObjectDataType> = {}
		for(let key in this.backingField) {
			json[key] = this.backingField[key].createJson(options)
		}
		return json
	}
}