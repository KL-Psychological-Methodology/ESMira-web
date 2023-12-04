import {AdminToolsInterface} from "./AdminToolsInterface";
import {LoginDataInterface} from "./LoginDataInterface";
import {AccountPermissions} from "./AccountPermissions";
import "../../css/styleAdmin.css";
import {ServerSettingsLoader} from "../loader/ServerSettingsLoader";
import {AccountsLoader} from "../loader/AccountsLoader";
import {MessagesLoader} from "../loader/MessagesLoader";

/**
 * See description of {@link Admin}
 */
export class AdminTools implements AdminToolsInterface {
	public readonly settingsLoader = new ServerSettingsLoader()
	public readonly accountsLoader = new AccountsLoader()
	public readonly messagesLoader: MessagesLoader
	public hasErrors: boolean = false
	
	public readonly isAdmin: boolean = false
	public readonly canCreate: boolean = false
	public readonly permissions: AccountPermissions
	public accountName: string = ""
	public readonly freeDiskSpace: number
	public readonly totalDiskSpace: number
	
	constructor(data: LoginDataInterface) {
		this.hasErrors = data.hasErrors
		this.messagesLoader = new MessagesLoader(data)
		
		this.accountName = data.accountName
		this.canCreate = data.canCreate
		
		if(data.isAdmin) {
			this.isAdmin = true
			this.permissions = {publish: [], msg: [], read: [], write: []}
			this.freeDiskSpace = data.freeDiskSpace
			this.totalDiskSpace = data.totalDiskSpace
		}
		else {
			this.isAdmin = false
			this.permissions = data.permissions
			this.freeDiskSpace = 0
			this.totalDiskSpace = 0
		}
	}
	
	public hasPermission(name: keyof AccountPermissions, studyId: number): boolean {
		return this.isAdmin || this.permissions[name]?.indexOf(studyId) != -1
	}
}