import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable} from "./BaseObservable";
import { JsonTypes } from "./types/JsonTypes";

export class ObservablePromise<T> extends BaseObservable<Promise<T>> {
	protected backingField: Promise<T>
	
	constructor(value: Promise<T>, parent: BaseObservable<ObservableTypes> | null, key: string) {
		super(parent, key)
		this.keyName = key
		this.backingField = value
	}
	
	public createJson(): JsonTypes {
        return ""
    }
	
	public hasMutated(forceIsDifferent: boolean = false): void {
		this.runObservers(false, this)
		if(this.parent)
			this.parent.hasMutated(false, forceIsDifferent, this)
	}
	
	public isDifferent(): boolean {
		return false
	}
	
	public get(): Promise<any> {
		return this.backingField
	}
	public set(value: Promise<T>, silently: boolean = false): void {
		this.backingField = value
		if(!silently)
			this.hasMutated(true)
	}
}