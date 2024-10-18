import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable} from "./BaseObservable";
import {PrimitiveType} from "./types/PrimitiveType";
import {ObservableRecordBase} from "./ObservableRecordBase";
import {ObservablePrimitive} from "./ObservablePrimitive";
import {TranslatableObject} from "./TranslatableObject";

/**
 * An observable Record that can hold any Observable or primitive types (string, number, boolean).
 * Primitive types will internally be packed into an {@link ObservablePrimitive}
 */
export class ObservableRecord<
	KeyT extends number | string,
	InputT extends ObservableTypes,
	ObsT extends BaseObservable<ObservableTypes> | BaseObservable<PrimitiveType>
> extends ObservableRecordBase<KeyT, ObsT> {
	private readonly constructObservable: (data: InputT, parent: BaseObservable<ObservableTypes>, key: string) => ObsT
	
	constructor(
		defaultFields: Record<KeyT, InputT>,
		key: string = "",
		constructObservable: (data: InputT, parent: BaseObservable<ObservableTypes> | null, key: string) => ObsT
	) {
		super(key)
		this.defaultKeys = Object.keys(defaultFields)
		
		this.constructObservable = constructObservable
		
		for(const key in defaultFields) {
			const value = defaultFields[key]
			this.backingField[key] = constructObservable(value, this, key)
		}
	}
	
	public set(data: Record<KeyT, InputT>, _silently?: boolean): void {
		const newData = {} as Record<KeyT, ObsT>
		for(const key in data) {
			const value = data[key]
			newData[key] = this.constructObservable(value, this, key)
		}
		
		this.setBase(newData, _silently)
	}
	
	public add(key: KeyT, value: InputT): void {
		this.addBase(key, this.constructObservable(value, this, key.toString()))
	}
}

/**
 * This is just a {@link ObservableRecord} with the primitive typing already prefilled for convenience
 */
export class ObservablePrimitiveRecord<
	KeyT extends number | string,
	InputT extends PrimitiveType
> extends ObservableRecord<KeyT, InputT, ObservablePrimitive<InputT>> {
	constructor(
		defaultFields: Record<KeyT, InputT>,
		key: string = ""
	) {
		super(defaultFields, key, (data, parent, key) => new ObservablePrimitive<InputT>(data, parent, key))
	}
}

/**
 * An observable that can be used with {@link TranslatableObject}. Functionally it is the same as {@link ObservableRecord}
 * but easier to use with TranslatableObjects
 */
export class TranslatableObjectRecord<KeyT extends number | string, ObsT extends TranslatableObject> extends ObservableRecordBase<KeyT, ObsT> {
	constructor(data: Record<KeyT, ObsT>, key: string = "") {
		super(key)
		this.defaultKeys = Object.keys(data)
	}
	
	public set(data: Record<KeyT, ObsT>, _silently?: boolean): void {
		this.setBase(data, _silently)
	}
	
	public add(key: KeyT, value: ObsT): void {
		this.addBase(key, value)
	}
}