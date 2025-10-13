import {PrimitiveType} from "./PrimitiveType";
import {BaseObservable, ObserverKeyType} from "../BaseObservable";

export type ObservableTypes = PrimitiveType
	| Promise<any> //for ObservablePromise
	| ObservableTypes[] //for ObservableArray
	| BaseObservable<ObservableTypes> //for ObservableArray
	| Record<ObserverKeyType, BaseObservable<ObservableTypes>> //for ObservableObject
