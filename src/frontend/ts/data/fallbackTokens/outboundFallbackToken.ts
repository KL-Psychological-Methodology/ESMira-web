import { ObservableStructure } from "../../observable/ObservableStructure";

export class OutboundFallbackToken extends ObservableStructure {
	public url = this.primitive<string>("url", "")
}