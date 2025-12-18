import { DataStructure, DataStructureInputType } from "../DataStructure";
import { BaseObservable, ObserverKeyType } from "../../observable/BaseObservable";
import { ObservableTypes } from "../../observable/types/ObservableTypes";

export class Account extends DataStructure {
	public accountName = this.primitive<string>("accountName", "")

	public admin = this.primitive<boolean>("admin", false)
	public create = this.primitive<boolean>("create", false)
	public issueFallbackToken = this.primitive<boolean>("issueFallbackToken", false)

	public read = this.primitiveArray<number>("read", [])
	public write = this.primitiveArray<number>("write", [])
	public reward = this.primitiveArray<number>("reward", [])
	public msg = this.primitiveArray<number>("msg", [])
	public publish = this.primitiveArray<number>("publish", [])

	constructor(data: DataStructureInputType, parent: BaseObservable<ObservableTypes> | null = null, _key?: ObserverKeyType, newLang?: string) {
		super(data, parent, data["accountName"] as string, newLang)
	}
	public updateKeyName(_keyName: string) {
		super.updateKeyName(this.accountName.get())
	}
}