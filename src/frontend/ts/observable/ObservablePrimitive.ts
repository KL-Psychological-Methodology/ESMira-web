import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable, ObserverKeyType} from "./BaseObservable";
import {PrimitiveType} from "./types/PrimitiveType";
import {JsonTypes} from "./types/JsonTypes";

/**
 * An observable that can hold any primitive (string, number, boolean).
 * @see {@link BaseObservable}
 */
export class ObservablePrimitive<T extends PrimitiveType> extends BaseObservable<T> {
	constructor(value: T, parent: BaseObservable<ObservableTypes> | null, key: ObserverKeyType) {
		super(parent, key)
		this.keyName = key
		this.setDefault(value)
		this.set(value, true)
	}
	
	public createJson(): JsonTypes {
        return this.get();
    }
	
	protected reCalcIsDifferent(forceIsDifferent: boolean = false): void {
		this.setIsDifferent(forceIsDifferent || this.get() != this.getDefault(), true)
	}
	
	public set(value: T, silently: boolean = false) {
		if(silently) {
			this.setDefault(value)
		}
		super.set(value, silently)
	}
}