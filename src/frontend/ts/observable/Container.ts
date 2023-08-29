import {ObservablePrimitive} from "./ObservablePrimitive";
import {PrimitiveType} from "./types/PrimitiveType";

export class Container<KeyT> {
	private backingField: Record<string, ObservablePrimitive<any>> = {}
	
	public getOrCreateObs<T extends PrimitiveType>(key: keyof KeyT, value: T): ObservablePrimitive<T> {
		const keyString = key.toString()
		if(this.backingField.hasOwnProperty(keyString))
			return this.backingField[keyString]
		else {
			const obs = new ObservablePrimitive(value, null, keyString)
			this.backingField[keyString] = obs
			return obs
		}
	}
	public get<T extends keyof KeyT>(key: T): KeyT[T] | null {
		return this.backingField[key.toString()]?.get()
	}
	
	public set<T extends PrimitiveType>(key: keyof KeyT, value: T): ObservablePrimitive<T> {
		const obs = this.getOrCreateObs(key, value)
		obs.set(value)
		return obs
	}
	
}