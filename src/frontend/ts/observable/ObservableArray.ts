import {PrimitiveType} from "./types/PrimitiveType";
import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable, JsonCreatorOptions, ObserverKeyType} from "./BaseObservable";
import {DataStructureInputType} from "../data/DataStructure";
import {JsonTypes} from "./types/JsonTypes";
import {ArrayInterface} from "./interfaces/ArrayInterface";

/**
 * An observable Array that can hold any Observable or primitive types (string, number, boolean).
 * @see {@link BaseObservable}
 */
export class ObservableArray<
	InputT extends DataStructureInputType | PrimitiveType,
	ObsT extends BaseObservable<ObservableTypes> | BaseObservable<PrimitiveType>
> extends BaseObservable<ObsT[], string[]> implements ArrayInterface<InputT, ObsT> {
	private readonly constructObservable: (data: InputT, parent: BaseObservable<ObservableTypes>, key: string) => ObsT
	
	constructor(
		defaultFields: InputT[],
		parent: BaseObservable<ObservableTypes> | null,
		key: ObserverKeyType,
		constructObservable: (data: InputT, parent: BaseObservable<ObservableTypes> | null, key: ObserverKeyType) => ObsT
	) {
		super(parent, key)
		const values: ObsT[] = []
		const defaultObsValues: string[] = []
		this.set(values, true)
		this.setDefault(defaultObsValues)
		this.constructObservable = constructObservable
		
		for(let i = 0; i < defaultFields.length; ++i) {
			const value = defaultFields[i];
			if(value == null) { //happens when value has the wrong type (the source was faulty)
				continue;
			}
			const obs = constructObservable(value, this, i.toString())
			values.push(obs)
			defaultObsValues.push(obs.keyName.toString())
		}
		
		//cleanup dangling children from previous observable:
		for(let i = values.length; i < this.sharedMemory.childrenCount; ++i) {
			delete this.sharedMemory.children[i]
		}
	}
	
	public createJson(options?: JsonCreatorOptions): JsonTypes {
		return this.get().map((obs) => { return obs.createJson(options)})
	}
	
	
	protected reCalcIsDifferent(forceIsDifferent: boolean = false): void {
		const data = this.get()
		if(forceIsDifferent || this.getDefault().length != data.length) {
			this.setIsDifferent(true, true)
			return
		}
		else {
			//defaultField preserves the original order of backingField but uses the same objects as backingField
			//keyName (which, normally, is just the array index) is always synced with the order in backingField.
			// That means the keyNames in defaultFiled will be out of order
			
			for(let i = data.length - 1; i>=0; --i) {
				const field = data[i]
				const defaultValue = this.getDefault()[i]
				if(field.isDifferent() || defaultValue != field.keyName) {
					this.setIsDifferent(true, true)
					return
				}
			}
		}
		this.setIsDifferent(false, true)
	}
	
	public addCopy(original: ObsT, index: number = this.get().length): ObsT {
		const jsonObj = original.createJson()
		const newObs = this.constructObservable(jsonObj as InputT, this, index.toString())
		this.get().push(newObs)
		if(index != this.get().length-1)
			this.move( this.get().length-1, index)
		else
			this.hasMutated(!this.isDifferent())
		return newObs
	}
	public push(value: InputT): ObsT {
		const obs = this.constructObservable(value, this, this.get().length.toString())
		this.get().push(obs)
		this.hasMutated(!this.isDifferent())
		return obs
	}
	
	public remove(index: number): ObsT {
		const data = this.get()
		const oldEntry = data[index]
		data.splice(index, 1)
		oldEntry.removeConnectedObservers()
		
		for(let i = index, max = data.length; i < max; ++i) {
			data[i].updateKeyName(i.toString()) //also takes care of this.sharedMemory.children
		}
		this.hasMutated(!this.isDifferent())
		return oldEntry
	}
	public replace(values: InputT[], silent: boolean = false): void {
		const newData: ObsT[] = []
		this.set(newData, true)
		for(const value of values) {
			const obs = this.constructObservable(value, this, newData.length.toString())
			newData.push(obs)
		}
		if(!silent)
			this.hasMutated(!this.isDifferent())
	}
	public move(oldIndex: number, newIndex: number): void {
		const data = this.get()
		const oldEntry = data[oldIndex]
		if(oldIndex == newIndex)
			return
		data.splice(oldIndex, 1)
		data.splice(newIndex, 0, oldEntry)
		
		oldEntry.updateKeyName("~temp")
		for(let i = oldIndex; i < newIndex; ++i) {
			data[i].updateKeyName(i.toString())
		}
		oldEntry.updateKeyName(newIndex.toString())
		this.hasMutated()
	}
	
	public moveFromOtherList(oldList: ArrayInterface<InputT, ObsT>, oldIndex: number, newIndex: number): void {
		const data = this.get()
		const oldEntry = oldList.get()[oldIndex]
		oldList.remove(oldIndex)
		
		data.splice(newIndex, 0, oldEntry)
		oldEntry.updateKeyName("~temp")
		for(let i = data.length - 1; i >= newIndex; --i) {
			data[i].parent = this
			data[i].updateKeyName(i.toString())
		}
		oldEntry.updateKeyName(newIndex.toString())
		this.hasMutated()
	}
	
	public indexOf(searchElement: PrimitiveType, fromIndex: number = 0): number {
		const data = this.get()
		for(let i = fromIndex, max = data.length; i < max; ++i) {
			if(data[i].get() == searchElement)
				return i
		}
		return -1
	}
}