import {TranslatableObject} from "../../observable/TranslatableObject";

let idCounter = 0
export class SignalTime extends TranslatableObject {
	public readonly id = ++idCounter //only used for Scheduler
	public startTimeOfDay				= this.primitive<number>(		"startTimeOfDay",			0)
	public endTimeOfDay					= this.primitive<number>(		"endTimeOfDay",			0)
	public random						= this.primitive<boolean>(		"random",					false)
	public randomFixed					= this.primitive<boolean>(		"randomFixed",				false)
	public frequency					= this.primitive<number>(		"frequency",				1)
	public minutesBetween				= this.primitive<number>(		"minutesBetween",			60)
}