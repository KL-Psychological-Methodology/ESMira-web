import {PrimitiveType} from "../observable/types/PrimitiveType";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {ObservablePrimitiveRecord} from "../observable/ObservableRecord";

/**
 * Dynamic values are a way of communicating between pages. They are shared between pages and can change at any time.
 * They are saved in an observable and have to be loaded (or checked for changes) on each redraw
 */
export interface DynamicValues {
	showSaveButton: boolean
	showPublishButton: boolean
	accessKey: string
	publicAccessKeyIndex: number
	disabledAccessKeyIndex: number
	studiesIndex: number
	questionnaireIndex: number
	pageIndex: number,
	joinTimestamp: number,
	owner: string,
}
/**
 * This is just a {@link ObservableRecord} which is very similar to {@link ObservablePrimitiveRecord}
 * but it can hold different primitiveTypes at the same time and also has convenience functions that return the correct type depending on the key.
 */
export class DynamicValueContainer extends ObservablePrimitiveRecord<
	string,
	PrimitiveType
> {
	constructor() {
		super({}, "dynamicValues");
	}
	
	public getOrCreateObs<T extends PrimitiveType>(key: keyof DynamicValues, value: T): ObservablePrimitive<T> {
		const keyString = key.toString()
		if(this.backingField.hasOwnProperty(keyString))
			return this.backingField[keyString] as ObservablePrimitive<T>
		else {
			const obs = new ObservablePrimitive(value, null, keyString)
			this.backingField[keyString] = obs
			return obs
		}
	}
	public getChild<T extends keyof DynamicValues>(key: T): DynamicValues[T] | null {
		return this.backingField[key.toString()]?.get() as DynamicValues[T]
	}
	
	public setChild<T extends PrimitiveType>(key: keyof DynamicValues, value: T): ObservablePrimitive<T> {
		const obs = this.getOrCreateObs(key, value)
		obs.set(value)
		return obs
	}
}