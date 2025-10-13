import {ObserverKeyType} from "./BaseObservable";

/**
 * A class to store shared memory between {@link BaseObservable}s.
 * Each {@link BaseObservable} will add its own SharedMemory to the children of its root {@link BaseObservable}
 * ensuring that all observables in a structure are connected to the same root.
 * The structure of SharedMemory copies the structure of their observable (through {@link children}).
 * When a child in an observable structure is replaced, it will access the existing SharedMemory in the structure.
 * That means copies of observables will always be identical, even if one of them changes.
 * @see {@link BaseObservable}
 */
export class SharedMemory {
	public data: any | null = null
	public default: any | null = null
	public isDifferent: boolean = false
	public preventIsDifferentRecalculations: boolean = false
	public observers: Record<number, (... args: any[]) => void> = {}
	public children: Record<ObserverKeyType, SharedMemory> = {}
	public childrenCount: number = 0
	public idCounter = 0
	
	public getOrAddChild(key: ObserverKeyType): SharedMemory {
		if(!this.children.hasOwnProperty(key)) {
			this.children[key] = new SharedMemory()
			++this.childrenCount
		}
		return this.children[key]
	}
}