import {AccountPermissions} from "./AccountPermissions";

export interface LoginDataInterface {
	accountName: string
	isLoggedIn: boolean
	loginTime: number
	permissions: AccountPermissions
	newMessages: number[]
	isAdmin: boolean
	canCreate: boolean
	hasErrors: boolean
}