import {DataStructure} from "../DataStructure";

export class OutboundFallbackToken extends DataStructure {
	public url = this.primitive<string>("url", "")
}