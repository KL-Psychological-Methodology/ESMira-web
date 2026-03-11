import {ObservableTypes} from "./types/ObservableTypes";
import {JsonTypes} from "./types/JsonTypes";
import {SharedMemory} from "./SharedMemory";


export class ObserverId {
	public readonly sharedMemory: SharedMemory
	public readonly id: number
	
	constructor(sharedMemory: SharedMemory, id: number) {
		this.sharedMemory = sharedMemory
		this.id = id
	}
	
	public removeObserver() {
		delete this.sharedMemory.observers[this.id]
	}
}

export type ObserverCallbackType<T extends ObservableTypes> = (obj: BaseObservable<T>, turnedDifferent: boolean, bubbled: boolean) => void
export type ObserverKeyType = string | number

export type JsonCreatorOptions = { dontFilterDefaults?: boolean }

/**
 * Observables are wrappers that hold a value which can be retrieved via {@link get()} and changed via {@link set()}.
 * Each Observable can have multiple observers which are essentially callbacks that are called when the value of an Observable was changed.
 * Changes are monitored and bubbled upwards in the structure (meaning that when a child is changed, its parent and parent parents are also informed about it).
 * When an observer notices a change (in itself or its children), it runs {@link hasMutated} which will run all its observers (added via {@link addObserver()}) in {@link runObservers()}.
 * and then runs its parent {@link hasMutated}.
 *
 * Important notes:
 * - Changes can only be noticed if their respective {@link set()} method is used. If a value was changed directly
 *   (and / or if it is not wrapped in an observable), its change will go unnoticed.
 * - Copies / Replacements of observables will always be identical, even if one of them changes (this is new and a lot of old code still assumes that observables cannot be cached):
 *   Each Observable has a {@link keyName} and saves its value and connected observers into a storage that is shared between all children ({@link SharedMemory}).
 *   When a child is replaced or copied, each child (or copy of a child) will reconnect to the same shared storage and retain its value and observers
 *   as long as its root {@link parent} is connected to the same {@link parent} and its {@link keyName} and position in the structure remained the same.
 *   See ({@link StudyLoader.studyCache}) for an example.
 * - The code assumes that the last created observable has the most up-to-date data.
 *   That means a newly created observable checks if there already is a shared memory object and overwrites its values (which will affect other copies of that observable).
 */
export abstract class BaseObservable<T extends ObservableTypes, DefaultT extends ObservableTypes = T>{
	protected sharedMemory: SharedMemory
	public parent: BaseObservable<ObservableTypes> | null
	public keyName: ObserverKeyType
	
	protected constructor(parent: BaseObservable<ObservableTypes> | null, key: ObserverKeyType) {
		if(parent) {
			this.sharedMemory = parent.sharedMemory.getOrAddChild(key)
			
			// New structures always overwrite their value. So, isDifferent can only be true if it was intentionally set to true by preventIsDifferentRecalculations
			this.sharedMemory.isDifferent = this.sharedMemory.preventIsDifferentRecalculations
		}
		else {
			this.sharedMemory = new SharedMemory()
		}
		
		this.keyName = key
		this.parent = parent
	}
	
	protected getChildrenCount(): number {
		return this.sharedMemory.childrenCount
	}
	
	/**
	 * Runs all added Observers (added via {@link addObserver}) for this observer.
	 * Does not bubble to the parent's observers. hasMutated bubbles instead.
	 * @param turnedDifferent ONLY true if the value just turned different from its DEFAULT VALUE (will not be true if it was already different from its default value).
	 * @param target Where the change originated from. Also used to determine the value of bubbled (true when this observable is not the source of the change) in the observer.
	 */
	protected runObservers(turnedDifferent: boolean, target: BaseObservable<ObservableTypes> = this): void {
		const bubbled = target != this
		const observers = this.sharedMemory.observers
		for(const id in observers) {
			observers[id](target, turnedDifferent, bubbled)
		}
	}
	
	/**
	 * Runs {@param callback} whenever {@link runObservers()} is called (by {@link hasMutated()} when the value is changed).
	 * Note that observers are stored in {@link shared} which is copied from the root-parent container (see code in {@link constructor}).
	 * That means that the observable structure can be replaced entirely without observers being lost (as long as the root parent is preserved or its {@link shared} is reused).
	 * As long as {@link address} stays the same, observers will still function.
	 * @param callback Runs whenever {@link runObservers()} is called (by {@link hasMutated()} or {@link set()}).
	 * @param existingId only save this {@param callback} if {@param existingId} does not exist yet.
	 * @returns an id object that can be used to remove the observer via {@link ObserverId.removeObserver()}
	 */
	public addObserver(callback: ObserverCallbackType<T>, existingId?: ObserverId): ObserverId {
		if(existingId && this.sharedMemory.observers.hasOwnProperty(existingId.id))
			existingId.removeObserver()
		
		const id = this.sharedMemory.idCounter++
		
		this.sharedMemory.observers[id] = callback
		
		return new ObserverId(this.sharedMemory, id)
	}
	
	/**
	 * Removes all observers from this observable but not from its children.
	 */
	public removeConnectedObservers(): void {
		for(const key in this.sharedMemory.observers) {
			delete this.sharedMemory.observers[key]
		}
	}
	
	/**
	 * Updates the keyName and its representation in the parent sharedMemory.
	 * @param keyName - The keyName for this observable and its representation in the parent sharedMemory.
	 */
	public updateKeyName(keyName: ObserverKeyType): void {
		if(keyName == this.keyName) {
			return
		}
		if(this.parent) {
			this.parent.sharedMemory.children[keyName] = this.sharedMemory
			delete this.parent.sharedMemory.children[this.keyName]
		}
		this.keyName = keyName
	}
	
	/**
	 * Called when the value of an observable has changed.
	 * Will only be called if the new value is actually different from the old (or if changed can not be detected properly).
	 * @param turnedDifferent ONLY true if the value just turned different from its DEFAULT VALUE (will not be true if it was already different from its default value).
	 * @param forceIsDifferent Force hasMutated() to assume that the value just changed from its default value (mostly true when bubbling from a child that is different).
	 * @param target Where the change originated from. Also used to determine the value of bubbled (true when this observable is not the source of the change) in the observer.
	 */
	public hasMutated(turnedDifferent: boolean = false, forceIsDifferent: boolean = false, target: BaseObservable<ObservableTypes> = this): void {
		const wasDifferent = this.isDifferent()
		if(!this.sharedMemory.preventIsDifferentRecalculations) {
			this.reCalcIsDifferent(forceIsDifferent)
		}
		turnedDifferent = turnedDifferent || this.turnedDifferent(wasDifferent)
		this.runObservers(turnedDifferent, target)
		if(this.parent) {
			this.parent.hasMutated(!wasDifferent && turnedDifferent, forceIsDifferent || this.isDifferent(), target)
		}
	}
	
	/**
	 * Retrieves the value associated with this observable.
	 */
	public get(): T {
		return this.sharedMemory.data
	}
	
	/**
	 * Changes the value associated with this observable.
	 * If `silently` is not provided or false, {@link hasMutated()} will be called.
	 * @param value - the value to set.
	 * @param silently - if false or undefined, {@link hasMutated()} will not be called.
	 */
	public set(value: T, silently: boolean = false): void {
		this.sharedMemory.data = value
		if(!silently) {
			this.hasMutated()
		}
	}
	
	/**
	 * @returns the default value for this observable.
	 */
	protected getDefault(): DefaultT {
		return this.sharedMemory.default
	}
	
	/**
	 * Sets the default value for this observable.
	 * @param value - the default value to set.
	 */
	protected setDefault(value: DefaultT): void {
		this.sharedMemory.default = value
	}
	
	/**
	 * Sets the internal `isDifferent`state of this observable.
	 * When isDifferent is set to true and silent is false, the internal `isDifferent` state is set permanently
	 * (meaning it will always stay true on future mutations until manually set to false again).
	 * @param isDifferent - the state to set.
	 * @param silently - if false or undefined, {@link hasMutated()} will not be called.
	 * If true and `silently` is true, the internal `isDifferent` state will be set permanently (by setting {@link sharedMemory.preventIsDifferentRecalculations} to true).
	 */
	public setIsDifferent(isDifferent: boolean, silently: boolean = false): void {
		this.sharedMemory.isDifferent = isDifferent
		if(!silently) {
			this.sharedMemory.preventIsDifferentRecalculations = isDifferent
			this.hasMutated(isDifferent)
		}
	}
	
	/**
	 * @returns the internal `isDifferent`state of this observable.
	 */
	public isDifferent(): boolean {
		return this.sharedMemory.isDifferent
	}
	
	/**
	 * Returns true if the value just turned different from its default value.
	 * This is meant to be overridden by subclasses
	 * @param wasDifferent - the `isDifferent` state of the observable before the change
	 * @returns true if the value just turned different from its default value.
	 */
	protected turnedDifferent(wasDifferent: boolean): boolean {
		return !wasDifferent && this.isDifferent()
	}
	
	/**
	 * Forces the observable to recalculate if it was changed from its default value and set its `isDifferent`state. Is usually called when its value was changed.
	 * @param forceIsDifferent Force reCalcIsDifferent() to assume that the value just changed from its default value.
	 */
	protected abstract reCalcIsDifferent(forceIsDifferent: boolean): void
	
	/**
	 * Generate a JSON value from its value based on the provided options.
	 *
	 * @param options - Optional configuration object to customize the JSON creation process.
	 * @return The resulting JSON structure.
	 */
	public abstract createJson(options?: JsonCreatorOptions): JsonTypes
}