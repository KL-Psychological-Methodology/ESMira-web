import {PrimitiveType} from "./PrimitiveType";

export type JsonTypes = PrimitiveType | PrimitiveType[] | { [key: string | number]: JsonTypes } | { [key: string | number]: JsonTypes }[] | JsonTypes[]