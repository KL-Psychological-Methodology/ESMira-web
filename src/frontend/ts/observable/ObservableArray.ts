import {PrimitiveType} from "./types/PrimitiveType";
import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable, JsonCreatorOptions} from "./BaseObservable";
import {TranslatableObjectDataType} from "./TranslatableObject";
import {JsonTypes} from "./types/JsonTypes";
import {ArrayInterface} from "./interfaces/ArrayInterface";


export class ObservableArray<
	InputT extends TranslatableObjectDataType | PrimitiveType,
	ObsT extends BaseObservable<ObservableTypes> | BaseObservable<PrimitiveType>
> extends BaseObservable<any[]> implements ArrayInterface<InputT, ObsT> {
	private _isDifferent = false
	private readonly constructObservable: (data: InputT, parent: BaseObservable<ObservableTypes>, key: string) => ObsT
	private backingField: ObsT[]
	private readonly defaultField: ObsT[]
	
	constructor(
		defaultFields: InputT[],
		parent: BaseObservable<ObservableTypes> | null,
		key: string,
		constructObservable: (data: InputT, parent: BaseObservable<ObservableTypes> | null, key: string) => ObsT
	) {
		super(parent, key)
		const values: ObsT[] = []
		const defaultObsValues: ObsT[] = []
		this.backingField = values
		this.defaultField = defaultObsValues
		this.constructObservable = constructObservable
		
		defaultFields.forEach((value, index) => {
			if(value == null) //happens when value has the wrong type (source was faulty)
				return
			const obs = constructObservable(value, this, index.toString())
			values.push(obs)
			defaultObsValues.push(obs)
		})
	}
	
	public createJson(options?: JsonCreatorOptions): JsonTypes {
		return this.backingField.map((obs) => { return obs.createJson(options)})
	}
	
	
	public reCalcIsDifferent(forceIsDifferent: boolean = false): void {
		if(forceIsDifferent || this.defaultField.length != this.backingField.length) {
			this._isDifferent = true
			return
		}
		else {
			//defaultField preserves the original order of backingField but uses the same objects as backingField
			//keyName (which, normally, is just the array index) is always synced with the order in backingField.
			// That means the keyNames in defaultFiled will be out of order
			
			for(let i = this.backingField.length - 1; i>=0; --i) {
				const backingField = this.backingField[i]
				const defaultValue = this.defaultField[i]
				if(backingField.isDifferent() || defaultValue.keyName != backingField.keyName) {
					this._isDifferent = true
					return
				}
			}
		}
		this._isDifferent = false
	}
	public isDifferent(): boolean {
		return this._isDifferent
    }
	public get(): ObsT[] {
		return this.backingField
    }
	public set(_value: ObsT[], _silently?: boolean): void {
        throw new Error("Method not implemented.");
    }
	
	public updateKeyName(keyName?: string, parent?: BaseObservable<ObservableTypes>): void {
		super.updateKeyName(keyName, parent)
		this.backingField.forEach((obs) => obs.updateKeyName())
	}
	
	public addCopy(original: ObsT, index: number = this.backingField.length): ObsT {
		const jsonObj = original.createJson()
		const newObs = this.constructObservable(jsonObj as InputT, this, index.toString())
		this.backingField.push(newObs)
		if(index != this.backingField.length-1)
			this.move( this.backingField.length-1, index)
		else
			this.hasMutated(!this._isDifferent)
		return newObs
	}
	public push(value: InputT): ObsT {
		const obs = this.constructObservable(value, this, this.backingField.length.toString())
		this.backingField.push(obs)
		this.hasMutated(!this._isDifferent)
		return obs
	}
	
	public remove(index: number): ObsT {
		const oldEntry = this.backingField[index]
		this.backingField.splice(index, 1)
		oldEntry.removeAllConnectedObservers()
		
		for(let i = index, max = this.backingField.length; i < max; ++i) {
			this.backingField[i].updateKeyName(i.toString())
		}
		this.hasMutated(!this._isDifferent)
		return oldEntry
	}
	public replace(values: InputT[], silent: boolean = false): void {
		this.backingField = []
		for(const value of values) {
			const obs = this.constructObservable(value, this, this.backingField.length.toString())
			this.backingField.push(obs)
		}
		if(!silent)
			this.hasMutated(!this._isDifferent)
	}
	public move(oldIndex: number, newIndex: number): void {
		const oldEntry = this.backingField[oldIndex]
		if(oldIndex == newIndex)
			return
		this.backingField.splice(oldIndex, 1)
		this.backingField.splice(newIndex, 0, oldEntry)
		
		oldEntry.updateKeyName("~temp")
		for(let i = oldIndex; i < newIndex; ++i) {
			this.backingField[i].updateKeyName(i.toString())
		}
		oldEntry.updateKeyName(newIndex.toString())
		this.hasMutated()
	}
	
	public moveFromOtherList(oldList: ArrayInterface<InputT, ObsT>, oldIndex: number, newIndex: number): void {
		const oldEntry = oldList.get()[oldIndex]
		oldList.remove(oldIndex)
		
		this.backingField.splice(newIndex, 0, oldEntry)
		oldEntry.updateKeyName("~temp")
		for(let i = this.backingField.length - 1; i >= newIndex; --i) {
			this.backingField[i].updateKeyName(i.toString(), this)
		}
		oldEntry.updateKeyName(newIndex.toString())
		this.hasMutated()
	}
	
	public indexOf(searchElement: PrimitiveType, fromIndex: number = 0): number {
		for(let i = fromIndex, max = this.backingField.length; i < max; ++i) {
			if(this.backingField[i].get() == searchElement)
				return i
		}
		return -1
	}
}