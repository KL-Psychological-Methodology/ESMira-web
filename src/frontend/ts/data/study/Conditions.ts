import {ObservableStructure} from "../../observable/ObservableStructure";
import {JsonTypes} from "../../observable/types/JsonTypes";

export class Conditions extends ObservableStructure {
	public key					= this.primitive<string>(		"key",				"")
	public value				= this.primitive<string>(		"value",			"")
	public operator				= this.primitive<number>(		"operator",		0)
	
	public static createDataStructure(key: string, value: string, operator: number): Record<string, JsonTypes> {
		return {
			key: key,
			value: value,
			operator: operator
		}
	}
}