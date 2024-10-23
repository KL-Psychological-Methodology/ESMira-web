import {ObservableStructure} from "../../observable/ObservableStructure";

export class SumScore extends ObservableStructure {
	public name						= this.primitive<string>(			"name",		"unknown")
	public addList					= this.primitiveArray<string>(		"addList")
	public subtractList				= this.primitiveArray<string>(		"subtractList")
}