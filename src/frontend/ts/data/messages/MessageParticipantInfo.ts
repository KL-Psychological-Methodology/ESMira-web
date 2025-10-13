import {DataStructure} from "../DataStructure";

export class MessageParticipantInfo extends DataStructure {
	public name = this.primitive<string>("name", "")
	public lastMsg = this.primitive<number>("lastMsg", 0)
	public archived = this.primitive<boolean>("archived", false)
	public pending = this.primitive<boolean>("pending", false)
	public unread = this.primitive<boolean>("unread", false)
}