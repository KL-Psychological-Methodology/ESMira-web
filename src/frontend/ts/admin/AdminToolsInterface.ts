import { AccountPermissions } from "./AccountPermissions";
import { ServerSettingsLoader } from "../loader/ServerSettingsLoader";
import { AccountsLoader } from "../loader/AccountsLoader";
import { MessagesLoader } from "../loader/MessagesLoader";
import { MerlinLogsLoader } from "../loader/MerlinLogsLoader";
import { BookmarkLoader } from "../loader/BookmarkLoader";

export interface AdminToolsInterface {
	settingsLoader: ServerSettingsLoader
	accountsLoader: AccountsLoader
	messagesLoader: MessagesLoader
	merlinLogsLoader: MerlinLogsLoader
	bookmarksLoader: BookmarkLoader
	accountName: string
	hasErrors: boolean
	isAdmin: boolean
	canCreate: boolean
	canIssueFallbackTokens: boolean
	permissions: AccountPermissions
	freeDiskSpace: number
	totalDiskSpace: number

	hasPermission(name: keyof AccountPermissions, studyId: number): boolean
}