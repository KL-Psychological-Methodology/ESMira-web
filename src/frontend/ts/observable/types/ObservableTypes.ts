import {ObservableStructure} from "../ObservableStructure";
import {PrimitiveType} from "./PrimitiveType";
import {BaseObservable} from "../BaseObservable";

export type ObservableTypes = Promise<any> | PrimitiveType | BaseObservable<PrimitiveType>[] | BaseObservable<ObservableTypes>[] | ObservableStructure | ObservableStructure[] | Record<number, BaseObservable<ObservableTypes>> | Record<string, BaseObservable<ObservableTypes>>