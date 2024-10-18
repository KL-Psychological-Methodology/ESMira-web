import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable} from "./BaseObservable";
import { JsonTypes } from "./types/JsonTypes";

/**
 * A observable Wrapper that can hold a Promise
 */
export class ObservablePromise<T> extends BaseObservable<Promise<T>> {
	protected backingField: Promise<T>
	
	constructor(value: Promise<T>, parent: BaseObservable<ObservableTypes> | null, key: string) {
		super(parent, key)
		this.keyName = key
		this.backingField = value
	}
	
	public reCalcIsDifferent(_: boolean = false): void {
		//do nothing
	}
	
	public createJson(): JsonTypes {
        return ""
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
			this.hasMutated(true, true)
	}
	public setValue(value: T, silently: boolean = false) {
		this.set(Promise.resolve(value), silently)
	}
}