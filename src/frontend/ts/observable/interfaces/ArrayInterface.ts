import {PrimitiveType} from "../types/PrimitiveType";
import {DataStructureInputType} from "../../data/DataStructure";
import {BaseObservable} from "../BaseObservable";
import {ObservableTypes} from "../types/ObservableTypes";

export interface ArrayInterface<
	InputT extends DataStructureInputType | PrimitiveType,
	ObsT extends BaseObservable<ObservableTypes> | BaseObservable<PrimitiveType>
> {
	
	get(): ObsT[]
	addCopy(original: ObsT, index?: number): ObsT
	push(value: InputT): ObsT
	indexOf(searchElement: PrimitiveType, fromIndex?: number): number
	
	remove(index: number): void
	replace(values: InputT[], silent?: boolean): void
	move(oldIndex: number, newIndex: number): void
	moveFromOtherList(oldList: ArrayInterface<InputT, ObsT>, oldIndex: number, newIndex: number): void
}