import {ObservableStructure, ObservableStructureDataType} from "../../observable/ObservableStructure";
import {BaseObservable} from "../../observable/BaseObservable";
import {ObservableTypes} from "../../observable/types/ObservableTypes";

export class Account extends ObservableStructure {
	public accountName = this.primitive<string>("accountName", "")
	
	public admin = this.primitive<boolean>("admin", false)
	public create = this.primitive<boolean>("create", false)
	
	public read = this.primitiveArray<number>("read", [])
	public write = this.primitiveArray<number>("write", [])
	public msg = this.primitiveArray<number>("msg", [])
	public publish = this.primitiveArray<number>("publish", [])
	
	constructor(data: ObservableStructureDataType, parent: BaseObservable<ObservableTypes> | null = null, _key?: string, newLang?: string) {
		super(data, parent, data["accountName"] as string, newLang)
	}
	public updateKeyName(_keyName: string, parent?: BaseObservable<ObservableTypes>) {
		super.updateKeyName(this.accountName.get(), parent)
	}
}