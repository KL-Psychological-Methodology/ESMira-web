import {ObservableTypes} from "./types/ObservableTypes";
import {SharedForObservable} from "./SharedForObservable";
import {JsonTypes} from "./types/JsonTypes";


export class ObserverId {
	public readonly shared: SharedForObservable
	public readonly id: number
	public readonly address: string
	
	constructor(shared: SharedForObservable, id: number, address: string) {
		this.shared = shared
		this.id = id
		this.address = address
	}
	
	public removeObserver() {
		if(this.shared.observerContainer.hasOwnProperty(this.address))
			delete this.shared.observerContainer[this.address][this.id]
	}
}

export type ObserverCallbackType<T extends ObservableTypes> = (obj: BaseObservable<T>, turnedDifferent: boolean, bubbled: boolean) => void

export type JsonCreatorOptions = { dontFilterDefaults?: boolean }

/**
 * Observables are wrappers that hold a value which can be retrieved via {@link get()} and changed via {@link set()}.
 * Each Observable can have multiples Observers which are essentially callbacks that are called when an Observable was changed.
 * Changes are monitored and bubbled upwards in the structure (meaning that when a child is changed, its parent and parent parents are also informed about it).
 * When an observer notices a change (in itself or its children) it runs {@link hasMutated} which will run all its observers (added via {@link addObserver()}) in {@link runObservers()}.
 * and then runs its parent {@link hasMutated}.
 *
 * Important: Changes can only be noticed if their respective {@link set()} method is used. If a value was changed directly
 * (and / or if it is not wrapped in an observable), its change will go unnoticed.
 *
 * Note that observers are stored in {@link shared} which always reference the object from their root-parent container
 * (see code in {@link constructor} and {@link addObserver()} and documentation in {@link SharedForObservable}).
 * So in each structure, only a single {@link SharedForObservable}, that is shared between all its members, exists.
 * This means that a child structure can be replaced entirely without their observers being lost
 * (as long as the root parent is preserved or its {@link shared} is reused).
 */
export abstract class BaseObservable<T extends ObservableTypes>{
	public readonly shared: SharedForObservable
	public parent: BaseObservable<ObservableTypes> | null
	private address: string
	public keyName: string
	
	protected constructor(parent: BaseObservable<ObservableTypes> | null, key: string) {
		this.shared = parent?.shared ?? new SharedForObservable()
		this.keyName = key
		this.parent = parent
		this.address = this.createAddress()
	}
	
	/**
	 * Calculates the address of this observable using its key and its parent address.
	 * The address is used to find the correct Observers (which are stored in {@link shared} to run.
	 * Each observable address needs to be unique inside a structure.
	 */
	private createAddress(): string {
		return `${this.parent?.createAddress() || ""}>${this.keyName}`
	}
	
	/**
	 * Runs all added Observers (added via {@link addObserver}) for this observer
	 * @param turnedDifferent ONLY true if the value just turned different from its DEFAULT VALUE (will not be true if it was already different from its default value)
	 * @param target Where the change originated from. Also used to determine the value of bubbled (true when this observable is not the source of the change) in the observer
	 */
	protected runObservers(turnedDifferent: boolean, target: BaseObservable<ObservableTypes> = this): void {
		const bubbled = target != this
		
		if(this.shared.observerContainer.hasOwnProperty(this.address)) {
			const observers = this.shared.observerContainer[this.address]
			for(const id in observers) {
				observers[id](target, turnedDifferent, bubbled)
			}
		}
	}
	
	/**
	 * Runs {@param callback} whenever {@link runObservers()} is called (by {@link hasMutated()} when the value is changed)
	 * Note that observers are stored in {@link shared} which is copied from the root-parent container (see code in {@link constructor}).
	 * That means that the observable structure can be replaced entirely without observers being lost (as long as the root parent is preserved or its {@link shared} is reused).
	 * As long as {@link address} stays the same, observers will still function
	 * @param callback Runs whenever {@link runObservers()} is called (by {@link hasMutated()} or {@link set()})
	 * @param existingId only save this {@param callback} if {@param existingId} does not exist yet
	 */
	public addObserver(callback: ObserverCallbackType<T>, existingId?: ObserverId): ObserverId {
		if(existingId && this.shared.observerContainer.hasOwnProperty(existingId.address) && this.shared.observerContainer[existingId.address].hasOwnProperty(existingId.id))
			existingId.removeObserver()
		
		const id = this.shared.idCounter++
		
		if(!this.shared.observerContainer.hasOwnProperty(this.address))
			this.shared.observerContainer[this.address] = {}
		this.shared.observerContainer[this.address][id] = callback
		
		return new ObserverId(this.shared, id, this.address)
	}
	
	/**
	 * Copies all observers from another structure to this structure.
	 * This method assumes that this observable has the same key and address
	 * @param other
	 */
	public importObserverData(other: BaseObservable<T>): void {
		const shared = other.shared
		this.shared.observerContainer = shared.observerContainer
		this.shared.idCounter = shared.idCounter
	}
	
	public removeAllConnectedObservers(): void {
		delete this.shared.observerContainer[this.address]
	}
	
	/**
	 * Updates the keyName, parent and recalculates address (even if keyName has not changed because it is assumed that keyName changed in a parent)
	 * @param keyName
	 * @param parent
	 */
	public updateKeyName(keyName?: string, parent?: BaseObservable<ObservableTypes>): void {
		if(parent)
			this.parent = parent
		if(keyName && this.keyName != keyName)
			this.keyName = keyName
		
		const oldAddress = this.address
		const newAddress = this.createAddress()
		if(oldAddress != newAddress) {
			this.address = newAddress
			
			const observerContainer = this.shared.observerContainer
			observerContainer[newAddress] = observerContainer[oldAddress]
			delete observerContainer[oldAddress]
		}
	}
	
	/**
	 * Called when the value of an observable has changed.
	 * Will only be called if the new value is actually different from the old (or if changed can not be detected properly).
	 * @param turnedDifferent ONLY true if the value just turned different from its DEFAULT VALUE (will not be true if it was already different from its default value)
	 * @param forceIsDifferent Force hasMutated() to assume that the value just changed from its default value
	 * @param target Where the change originated from. Also used to determine the value of bubbled (true when this observable is not the source of the change) in the observer
	 */
	public hasMutated(turnedDifferent: boolean = false, forceIsDifferent: boolean = false, target: BaseObservable<ObservableTypes> = this): void {
		const wasDifferent = this.isDifferent()
		this.reCalcIsDifferent(forceIsDifferent)
		this.runObservers(turnedDifferent, target)
		if(this.parent)
			this.parent.hasMutated(!wasDifferent && turnedDifferent, forceIsDifferent || this.isDifferent(), target)
	}
	
	/**
	 * Forces the observable to recalculate if it was changed from its default value. Is usually called when its value was changed.
	 * @param forceIsDifferent Force reCalcIsDifferent() to assume that the value just changed from its default value
	 */
	abstract reCalcIsDifferent(forceIsDifferent: boolean): void
	abstract get(): T
	abstract set(value: T, silently?: boolean): void
	abstract createJson(options?: JsonCreatorOptions): JsonTypes
	abstract isDifferent(): boolean
}