import {PrimitiveType} from "./types/PrimitiveType";
import {ObservableTypes} from "./types/ObservableTypes";
import {BaseObservable, ObserverKeyType} from "./BaseObservable";
import {DataStructureInputType} from "../data/DataStructure";
import {ObservableArray} from "./ObservableArray";
import {ArrayInterface} from "./interfaces/ArrayInterface";
import {defineCurrentLangCode, Translatable, TranslatableJsonCreatorOptions} from "./interfaces/Translatable";
import {ObservablePrimitive} from "./ObservablePrimitive";
import {JsonTypes} from "./types/JsonTypes";

/**
 * An observable Array that holds strings and is translatable.
 * @see {@link ObservableArray}
 * @see {@link Translatable}
 * @see {@link BaseObservable}
 */
export class TranslatableArray<
	InputT extends DataStructureInputType | PrimitiveType,
	ObsT extends Translatable<ObservableTypes> | Translatable<PrimitiveType>
> extends ObservableArray<InputT, ObsT> implements Translatable<ObsT[], string[]>, ArrayInterface<InputT, ObsT> {
	public readonly currentLangCode: ObservablePrimitive<string>
	
	constructor(
		defaultFields: InputT[],
		parent: BaseObservable<ObservableTypes> | null,
		key: ObserverKeyType,
		constructObservable: (data: InputT, parent: BaseObservable<ObservableTypes> | null, key: ObserverKeyType) => ObsT,
	) {
		super(defaultFields,
			parent,
			key,
			(data: InputT, _parent: BaseObservable<ObservableTypes> | null, childKey: ObserverKeyType) => constructObservable(data, this, childKey),
		)
		this.currentLangCode = defineCurrentLangCode(this)
		super.fillArray(defaultFields)
	}
	protected fillArray() {
		//disable for ObservableArray. Instead, we call super.fillArray() in the constructor of TranslatableArray after currentLangCode has been defined.
	}
	
	public createJson(options?: TranslatableJsonCreatorOptions): JsonTypes {
		return super.createJson(options)
	}
	
	public addLanguage(langCode: string, langData: any): void {
		this.get().forEach((obs, index) => obs.addLanguage(langCode, (langData && langData[index]) ?? undefined))
	}
	
	public removeLanguage(langCode: string): void {
		this.get().forEach((obs) => obs.removeLanguage(langCode))
		if(!this.sharedMemory.preventIsDifferentRecalculations) {
			this.reCalcIsDifferent()
		}
	}
	
	public renameLanguage(oldLangCode: string, newLangCode: string): void {
		this.get().forEach((obs) => obs.renameLanguage(oldLangCode, newLangCode))
	}
}