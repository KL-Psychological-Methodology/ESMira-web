import {ObservableStructure} from "../../observable/ObservableStructure";

export class MessageParticipantInfo extends ObservableStructure {
	public name = this.primitive<string>("name", "")
	public lastMsg = this.primitive<number>("lastMsg", 0)
	public archived = this.primitive<boolean>("archived", false)
	public pending = this.primitive<boolean>("pending", false)
	public unread = this.primitive<boolean>("unread", false)
}