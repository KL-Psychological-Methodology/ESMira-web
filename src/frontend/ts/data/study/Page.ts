import {TranslatableObject} from "../../observable/TranslatableObject";
import {Input} from "./Input";

export class Page extends TranslatableObject {
	public randomized					= this.primitive<boolean>(		"randomized",					false)
	public skipAfterSecs				= this.primitive<number>(		"skipAfterSecs",				0)
	
	public header							= this.translatable(		"header",						"")
	public footer							= this.translatable(		"footer",						"")
	
	public inputs							= this.objectArray(			"inputs", Input)
}