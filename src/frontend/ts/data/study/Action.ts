import {TranslatableObject} from "../../observable/TranslatableObject";

export class Action extends TranslatableObject {
	public type								= this.primitive<number>(		"type",					1) //is Invitation
	public timeout							= this.primitive<number>(		"timeout",					0)
	public reminder_count					= this.primitive<number>(		"reminder_count",			0)
	public reminder_delay_minu				= this.primitive<number>(		"reminder_delay_minu",		5)
	
	public msgText							= this.translatable(			"msgText",					"")
}