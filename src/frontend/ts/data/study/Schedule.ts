import {ObservableStructure} from "../../observable/ObservableStructure";
import {SignalTime} from "./SignalTime";

export class Schedule extends ObservableStructure {
	public userEditable					= this.primitive<boolean>(		"userEditable",		true)
	public dailyRepeatRate				= this.primitive<number>(		"dailyRepeatRate",		1)
	public skipFirstInLoop				= this.primitive<boolean>(		"skipFirstInLoop",		false)
	public weekdays						= this.primitive<number>(		"weekdays",			0)
	public dayOfMonth					= this.primitive<number>(		"dayOfMonth",			0)
	
	public signalTimes					= this.objectArray(				"signalTimes", SignalTime)
	
	/**
	 * Copied from sharedCode.Schedule in kotlin
	 */
	public getInitialDelayDays(): number {
		return this.skipFirstInLoop.get() ? this.dailyRepeatRate.get() : 0
	}
}