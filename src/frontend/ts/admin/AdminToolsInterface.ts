import {AccountPermissions} from "./AccountPermissions";
import {ServerSettingsLoader} from "../loader/ServerSettingsLoader";
import {AccountsLoader} from "../loader/AccountsLoader";
import {MessagesLoader} from "../loader/MessagesLoader";

export interface AdminToolsInterface {
	settingsLoader: ServerSettingsLoader
	accountsLoader: AccountsLoader
	messagesLoader: MessagesLoader
	accountName: string
	hasErrors: boolean
	isAdmin: boolean
	canCreate: boolean
	permissions: AccountPermissions
	freeDiskSpace: number
	totalDiskSpace: number
	
	hasPermission(name: keyof AccountPermissions, studyId: number): boolean
}