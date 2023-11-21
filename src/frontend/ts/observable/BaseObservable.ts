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
 * Note that observers are stored in {@link shared} which is copied from root-parent container (see code in {@link constructor}).
 * That means that the observable structure can be replaced entirely without observers being lost (as long as the root parent is preserved or its {@link shared} is reused).
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
	private createAddress(): string {
		return `${this.parent?.createAddress() || ""}>${this.keyName}`
	}
	
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
	 * Runs {@param callback} whenever {@link runObservers()} is called (by {@link hasMutated()} or {@link set()})
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
	
	abstract hasMutated(turnedDifferent: boolean, forceIsDifferent: boolean, target: BaseObservable<ObservableTypes>): void
	abstract get(): T
	abstract set(value: T, silently?: boolean): void
	abstract createJson(options?: JsonCreatorOptions): JsonTypes
	abstract isDifferent(): boolean
}