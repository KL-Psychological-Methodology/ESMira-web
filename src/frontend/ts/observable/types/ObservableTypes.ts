import {TranslatableObject} from "../TranslatableObject";
import {PrimitiveType} from "./PrimitiveType";
import {BaseObservable} from "../BaseObservable";

export type ObservableTypes = Promise<any> | PrimitiveType | BaseObservable<PrimitiveType>[] | BaseObservable<ObservableTypes>[] | TranslatableObject | TranslatableObject[] | Record<number, TranslatableObject | string>