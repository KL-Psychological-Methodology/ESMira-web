import {ObservableStructure} from "../../observable/ObservableStructure";
import {Input} from "./Input";

export class Page extends ObservableStructure {
	public randomized					= this.primitive<boolean>(		"randomized",					false)
	public skipAfterSecs				= this.primitive<number>(		"skipAfterSecs",				0)
	
	public header							= this.translatable(		"header",						"")
	public footer							= this.translatable(		"footer",						"")
	
	public inputs							= this.objectArray(			"inputs", Input)
	public relevance 					= this.primitive<string>(		"relevance",					"")
}