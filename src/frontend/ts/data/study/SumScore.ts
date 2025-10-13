import {DataStructure} from "../DataStructure";

export class SumScore extends DataStructure {
	public name						= this.primitive<string>(			"name",		"unknown")
	public addList					= this.primitiveArray<string>(		"addList")
	public subtractList				= this.primitiveArray<string>(		"subtractList")
}