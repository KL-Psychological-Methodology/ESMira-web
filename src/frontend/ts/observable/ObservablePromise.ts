import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable, ObserverKeyType} from "./BaseObservable";
import {JsonTypes} from "./types/JsonTypes";

/**
 * An observable that can hold a Promise.
 * @see {@link BaseObservable}
 */
export class ObservablePromise<T> extends BaseObservable<Promise<T>> {
	constructor(value: Promise<T>, parent: BaseObservable<ObservableTypes> | null, key: ObserverKeyType) {
		super(parent, key)
		this.keyName = key
		this.set(value, true)
	}
	
	protected reCalcIsDifferent(_: boolean = false): void {
		//do nothing
	}
	
	public createJson(): JsonTypes {
        return ""
    }
	
	protected turnedDifferent(_: boolean): boolean {
		return true
	}
	
	public setValue(value: T, silently: boolean = false) {
		this.set(Promise.resolve(value), silently)
	}
}