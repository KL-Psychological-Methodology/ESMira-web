import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {JsonTypes} from "../observable/types/JsonTypes";
import {ObservableTypes} from "../observable/types/ObservableTypes";
import {TranslatablePrimitive} from "../observable/TranslatablePrimitive";
import {BaseObservable, ObserverKeyType} from "../observable/BaseObservable";
import {TranslatableArray} from "../observable/TranslatableArray";
import {ObservableObject, ObservableObjectDataType} from "../observable/ObservableObject";
import {Translatable} from "../observable/interfaces/Translatable";
import {ObservableStructureDataType, TranslatableObject} from "../observable/TranslatableObject";
import {PrimitiveType} from "../observable/types/PrimitiveType";
import {ObservableArray} from "../observable/ObservableArray";

export type DataStructureInputType = Record<string, JsonTypes>

/**
 * An observable object with immutable properties which are meant to be set explicitly at construction time.
 * Each property is created via {@link primitive()}, {@link primitiveArray()}, {@link translatable()}, {@link translatableArray()} {@link objectArray()} and {@link object()}.
 * @see {@link ObservableObject}
 * @see {@link BaseObservable}
 */
export class DataStructure extends TranslatableObject {
	/**
	 * Contains data for all children. We don't have to sage it in shared memory because {@link DataStructure} is immutable, so initJson is only used at construction time.
	 * (and we only need to save it in the class because properties are defined after the constructor)
	 */
	private readonly initJson: ObservableObjectDataType
	
	constructor(data: ObservableStructureDataType, parent: BaseObservable<ObservableTypes> | null, key: ObserverKeyType, newLang?: string) {
		super(parent, key, newLang)
		this.initJson = data
	}
	
	protected cleanDanglingChildren(): void {
		setTimeout(() => { // properties are only defined after the constructor, so we have to defer until after initialisation has been finished
			const valueIndex = this.getValueIndex()
			for(const key in this.sharedMemory.children) {
				if(!valueIndex.hasOwnProperty(key) && !this.hasOwnProperty(key)) {
					delete this.sharedMemory.children[key]
					--this.sharedMemory.childrenCount
				}
			}
		})
	}
	
	protected getInitJson(): ObservableObjectDataType {
		return this.initJson
	}
	
	/**
	 * @deprecated Access the object and its properties directly
	 */
	public get() {
		return super.get()
	}
	/**
	 * @deprecated ObservableStructures are immutable
	 */
	public set(): void {
		throw new Error("ObservableStructures are immutable")
	}
	
	/**
	 * Creates and returns a primitive observable that can hold any primitive (string, number, boolean)
	 *
	 * @param key - The key of the property which will be used in the resulting JSON.
	 * @param defaultValue - The default value of the property. Will be used in translated versions when this value is not set.
	 * @return The created observable.
	 */
	protected primitive<ValueT extends PrimitiveType>(key: ObserverKeyType, defaultValue: ValueT): BaseObservable<ValueT> {
		const obs = new ObservablePrimitive(this.getInitJson().hasOwnProperty(key) ? this.getInitJson()[key] as ValueT : defaultValue, this, key)
		
		this.setInitDefaultValue(key, defaultValue)
		this.setValueIndex(key, obs)
		return obs
	}
	
	/**
	 * Creates and returns an observable array of primitive types.
	 *
	 * @param key - The key of the property which will be used in the resulting JSON.
	 * @param defaultValue - The default value. Will be used in translated versions when this value is not set.
	 * @return The created observable.
	 */
	protected primitiveArray<T extends PrimitiveType>(key: ObserverKeyType, defaultValue: T[] = []): ObservableArray<T, BaseObservable<T>> {
		const value = this.getInitJson().hasOwnProperty(key) ? this.getInitJson()[key] as T[] : defaultValue
		const obs = new ObservableArray<T, BaseObservable<T>>(value, this, key, (data, parent, childKey) => {
			return new ObservablePrimitive<T>(data, parent, childKey)
		})
		
		this.setInitDefaultValue(key, defaultValue)
		this.setValueIndex(key, obs)
		return obs
	}
	
	/**
	 * Creates and returns an array of {@link DataStructure} objects.
	 *
	 * @param key - The key of the property which will be used in the resulting JSON.
	 * @param typeConstructor - A {@link DataStructure} constructor function that holds the definitions for each object.
	 * @return The created array of  {@link DataStructure} objects.
	 */
	protected objectArray<T extends DataStructure>(
		key: ObserverKeyType,
		typeConstructor: { new(data: ObservableObjectDataType, parent: BaseObservable<ObservableTypes> | null, key: ObserverKeyType, newLang?: string): T ;}
	): TranslatableArray<ObservableObjectDataType, T> {
		const obs = new TranslatableArray<ObservableObjectDataType, T>(
			this.getInitJson().hasOwnProperty(key) ? this.getInitJson()[key] as ObservableObjectDataType[] : [],
			this,
			key,
			(data, parent, childKey) => new typeConstructor(data, parent, childKey)
		)
		this.setValueIndex(key, obs)
		return obs
	}
	/**
	 * Creates and returns a single {@link DataStructure} object.
	 *
	 * @param key - The key of the property which will be used in the resulting JSON.
	 * @param typeConstructor - A {@link DataStructure} constructor function that holds the definitions for each object.
	 * @return The created {@link DataStructure}.
	 */
	protected object<T extends ObservableObject>(
		key: ObserverKeyType,
		typeConstructor: { new(data: ObservableObjectDataType, parent: BaseObservable<ObservableTypes> | null, key: ObserverKeyType, newLang?: string): T ;}
	): T {
		const obs = new typeConstructor(this.getInitJson().hasOwnProperty(key) ? this.getInitJson()[key] as ObservableObjectDataType : {}, this, key)
		this.setValueIndex(key, obs)
		return obs
	}
	
	
	/**
	 * Creates and returns an observable string that can be translated.
	 * If the specified key exists in the initialization data, its value is used; otherwise, a default value is used.
	 * The observable is also registered in the object's tracking structures.
	 *
	 * @param key - The key of the property which will be used in the resulting JSON.
	 * @param defaultValue - The default value. Will be used in translated versions when this value is not set.
	 * @return The created observable.
	 */
	protected translatable(key: ObserverKeyType, defaultValue: string): BaseObservable<string> {
		const obs = new TranslatablePrimitive(this.getInitJson().hasOwnProperty(key) ? this.getInitJson()[key] as string : defaultValue, this, key)
		
		this.setInitDefaultValue(key, defaultValue)
		this.setValueIndex(key, obs)
		return obs
	}
	
	/**
	 * Creates and returns an observable string array that can be translated as a whole.
	 *
	 * @param key - The key of the property which will be used in the resulting JSON.
	 * @param defaultValue - The default value. Will be used in translated versions when this value is not set.
	 * @return The created observable.
	 */
	protected translatableArray(key: ObserverKeyType, defaultValue: string[] = []): TranslatableArray<string, Translatable<string>> {
		const value = this.getInitJson().hasOwnProperty(key) ? this.getInitJson()[key] as string[] : defaultValue
		const obs = new TranslatableArray<string, TranslatablePrimitive<string>>(value, this, key, (data, parent, childKey) => {
			return new TranslatablePrimitive<string>(data, parent, childKey)
		})
		this.setInitDefaultValue(key, defaultValue)
		this.setValueIndex(key, obs)
		return obs
	}
}