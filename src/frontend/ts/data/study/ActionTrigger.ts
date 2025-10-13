import {DataStructure} from "../DataStructure";
import {Action} from "./Action";
import {Schedule} from "./Schedule";
import {EventTrigger} from "./EventTrigger";

export class ActionTrigger extends DataStructure {
	public actions							= this.objectArray(			"actions", Action)
	public schedules						= this.objectArray(			"schedules", Schedule)
	public eventTriggers					= this.objectArray(			"eventTriggers", EventTrigger)
}