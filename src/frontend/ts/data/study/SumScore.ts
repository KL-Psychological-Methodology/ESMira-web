import {TranslatableObject} from "../../observable/TranslatableObject";

export class SumScore extends TranslatableObject {
	public name						= this.primitive<string>(			"name",		"unknown")
	public addList					= this.primitiveArray<string>(		"addList")
	public subtractList				= this.primitiveArray<string>(		"subtractList")
}