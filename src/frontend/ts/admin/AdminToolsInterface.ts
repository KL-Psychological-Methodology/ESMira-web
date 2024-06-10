import {AccountPermissions} from "./AccountPermissions";
import {ServerSettingsLoader} from "../loader/ServerSettingsLoader";
import {AccountsLoader} from "../loader/AccountsLoader";
import {MessagesLoader} from "../loader/MessagesLoader";
import { MerlinLogsLoader } from "../loader/MerlinLogsLoader";

export interface AdminToolsInterface {
	settingsLoader: ServerSettingsLoader
	accountsLoader: AccountsLoader
	messagesLoader: MessagesLoader
	merlinLogsLoader: MerlinLogsLoader
	accountName: string
	hasErrors: boolean
	isAdmin: boolean
	canCreate: boolean
	permissions: AccountPermissions
	freeDiskSpace: number
	totalDiskSpace: number
	
	hasPermission(name: keyof AccountPermissions, studyId: number): boolean
}