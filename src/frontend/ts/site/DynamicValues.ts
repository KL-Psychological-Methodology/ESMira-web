import {PrimitiveType} from "../observable/types/PrimitiveType";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {ObservableObject} from "../observable/ObservableObject";

/**
 * Dynamic values are a way of communicating between sections. They are shared between sections and can change at any time.
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
 * This is just an {@link ObservableObject} that can dynamically create fields and has convenience functions that return the correct type depending on the key.
 */
export class DynamicValueContainer extends ObservableObject<ObservablePrimitive<any>> {
	constructor() {
		super(null, "dynamicValues");
	}
	
	public getOrCreateObs<T extends PrimitiveType>(key: keyof DynamicValues, value: T): ObservablePrimitive<T> {
		const keyString = key.toString()
		if(this.contains(keyString))
			return this.getEntry(keyString)!
		else {
			const obs = new ObservablePrimitive(value, null, keyString)
			this.insert(keyString, obs)
			return obs
		}
	}
	public getChild<T extends keyof DynamicValues>(key: T): DynamicValues[T] | null {
		return this.getEntry(key)?.get() as DynamicValues[T]
	}
	
	public setChild<T extends PrimitiveType>(key: keyof DynamicValues, value: T): ObservablePrimitive<T> {
		const obs = this.getOrCreateObs(key, value)
		obs.set(value)
		return obs
	}
}