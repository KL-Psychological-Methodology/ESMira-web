import {Message} from "./Message";

export interface ParticipantMessagesContainer {
	archive: Message[]
	pending: Message[]
	unread: Message[]
}