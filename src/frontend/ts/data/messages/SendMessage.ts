import {MessageAsRead} from "./MessageAsRead";

export interface SendMessage extends MessageAsRead{
	toAll: boolean
	appVersion: string
	appType: string
	content: string
}