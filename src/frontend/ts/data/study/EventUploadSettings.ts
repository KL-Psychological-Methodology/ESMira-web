import {TranslatableObject} from "../../observable/TranslatableObject";

export class EventUploadSettings extends TranslatableObject {
	public actions_executed			= this.primitive<boolean>(		"actions_executed",		true)
	public invitation				= this.primitive<boolean>(		"invitation",				true)
	public invitation_missed		= this.primitive<boolean>(		"invitation_missed",		false)
	public message					= this.primitive<boolean>(		"message",					false)
	public notification				= this.primitive<boolean>(		"notification",			false)
	public rejoined					= this.primitive<boolean>(		"rejoined",				false)
	public reminder					= this.primitive<boolean>(		"reminder",				false)
	public schedule_changed			= this.primitive<boolean>(		"schedule_changed",		true)
	public schedule_planned			= this.primitive<boolean>(		"schedule_planned",		false)
	public schedule_removed			= this.primitive<boolean>(		"schedule_removed",		false)
	public statistic_viewed			= this.primitive<boolean>(		"statistic_viewed",		false)
	public study_message			= this.primitive<boolean>(		"study_message",			false)
	public study_updated			= this.primitive<boolean>(		"study_updated",			false)
}