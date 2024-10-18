import {PrimitiveType} from "./types/PrimitiveType";
import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable} from "./BaseObservable";
import {TranslatableObjectDataType} from "./TranslatableObject";
import {JsonTypes} from "./types/JsonTypes";
import {BaseTranslatable, TranslatableJsonCreatorOptions} from "./BaseTranslatable";
import {ObservableArray} from "./ObservableArray";
import {ArrayInterface} from "./interfaces/ArrayInterface";

/**
 * An observable Array that is translatable and can hold {@link BaseTranslatable}
 * Note: TranslatableArray itself does not have translations. Internally it uses an {@link ObservableArray} and all methods are relayed to it.
 * This class mainly exists to make sure that all children are BaseTranslatable (and not only BaseObservable)
 */
export class TranslatableArray<
	InputT extends TranslatableObjectDataType | PrimitiveType,
	ObsT extends BaseTranslatable<ObservableTypes> | BaseTranslatable<PrimitiveType>
	> extends BaseTranslatable<any[]> implements ArrayInterface<InputT, ObsT> {
	
	private readonly array: ObservableArray<InputT, ObsT>
	
	constructor(
		defaultFields: InputT[],
		parent: BaseObservable<ObservableTypes> | null,
		key: string,
		constructObservable: (data: InputT, parent: BaseObservable<ObservableTypes> | null, key: string) => ObsT
	) {
		super(parent, key)
		this.array = new ObservableArray<InputT, ObsT>(
			defaultFields,
			parent,
			key,
			(data: InputT, _parent: BaseObservable<ObservableTypes> | null, childKey: string) => constructObservable(data, this, childKey)
		)
	}
	
	public createJson(options?: TranslatableJsonCreatorOptions): JsonTypes {
		return this.array.createJson(options)
	}
	
	public get(): ObsT[] {
		return this.array.get();
	}
	
	public reCalcIsDifferent(forceIsDifferent: boolean = false): void {
		this.array.reCalcIsDifferent(forceIsDifferent)
	}
	
	public hasMutated(turnedDifferent?: boolean, forceIsDifferent?: boolean, target?: BaseObservable<ObservableTypes>): void {
		this.array.hasMutated(turnedDifferent, forceIsDifferent, target)
	}
	
	public isDifferent(): boolean {
		return this.array.isDifferent();
	}
	
	public addLanguage(langCode: string, langData: any): void {
		this.array.get().forEach((obs, index) => { obs.addLanguage(langCode, (langData && langData[index]) ?? undefined)})
	}
	
	public removeLanguage(langCode: string): void {
		this.array.get().forEach((obs) => obs.removeLanguage(langCode))
		this.array.reCalcIsDifferent()
	}
	
	public renameLanguage(oldLangCode: string, newLangCode: string): void {
		this.array.get().forEach((obs) => obs.renameLanguage(oldLangCode, newLangCode))
	}
	
	public set(value: ObsT[], silently?: boolean): void {
		this.array.set(value, silently)
	}
	
	public updateKeyName(keyName?: string, parent?: BaseObservable<ObservableTypes>): void {
		super.updateKeyName(keyName, parent)
		this.array.updateKeyName(keyName, parent)
	}
	
	public addCopy(original: ObsT, index?: number): ObsT {
		return this.array.addCopy(original, index)
	}
	
	public push(value: InputT): ObsT {
		return this.array.push(value)
	}
	
	public replace(values: InputT[], silent: boolean = false): void {
		this.array.replace(values, silent)
	}
	public remove(index: number): ObsT {
		return this.array.remove(index)
	}
	
	public move(oldIndex: number, newIndex: number): void {
		return this.array.move(oldIndex, newIndex)
	}
	public moveFromOtherList(oldList: ArrayInterface<InputT, ObsT>, oldIndex: number, newIndex: number): void {
		return this.array.moveFromOtherList(oldList, oldIndex, newIndex)
	}
	
	public indexOf(searchElement: PrimitiveType, fromIndex: number = 0): number {
		return this.array.indexOf(searchElement, fromIndex)
	}
}