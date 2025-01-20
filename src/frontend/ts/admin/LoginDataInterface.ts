import { AccountPermissions } from "./AccountPermissions";

export interface LoginDataInterface {
	accountName: string
	isLoggedIn: boolean
	loginTime: number
	permissions: AccountPermissions
	newMessages: number[]
	newMerlinLogs: number[]
	isAdmin: boolean
	canCreate: boolean
	canIssueFallbackTokens: boolean
	hasErrors: boolean
	totalDiskSpace: number
	freeDiskSpace: number
}