import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable} from "./BaseObservable";
import {PrimitiveType} from "./types/PrimitiveType";
import { JsonTypes } from "./types/JsonTypes";

export class ObservablePrimitive<T extends PrimitiveType> extends BaseObservable<T> {
	protected _isDifferent = false
	protected backingField: T
	protected defaultField: T
	
	constructor(value: T, parent: BaseObservable<ObservableTypes> | null, key: string) {
		super(parent, key)
		this.keyName = key
		this.backingField = value
		this.defaultField = value
	}
	
	public createJson(): JsonTypes {
        return this.get();
    }
	
	public hasMutated(forceIsDifferent: boolean = false): void {
		const wasDifferent = this._isDifferent
		this._isDifferent = forceIsDifferent || this.backingField != this.defaultField
		this.runObservers(this._isDifferent, this)
		if(this.parent)
			this.parent.hasMutated(!wasDifferent && this._isDifferent, this._isDifferent, this)
	}
	
	public isDifferent(): boolean {
		return this._isDifferent
	}
	
	public get(): T {
		return this.backingField
	}
	public set(value: T, silently: boolean = false) {
		if(silently) {
			this.defaultField = value
			this.backingField = value
		}
		else if(this.backingField != value) {
			this.backingField = value
			this.hasMutated()
		}
	}
}