import {AdminToolsInterface} from "./AdminToolsInterface";
import {LoginDataInterface} from "./LoginDataInterface";
import {AccountPermissions} from "./AccountPermissions";
import "../../css/styleAdmin.css";
import {ServerSettingsLoader} from "../loader/ServerSettingsLoader";
import {AccountsLoader} from "../loader/AccountsLoader";
import {MessagesLoader} from "../loader/MessagesLoader";

export class AdminTools implements AdminToolsInterface {
	public readonly settingsLoader = new ServerSettingsLoader()
	public readonly accountsLoader = new AccountsLoader()
	public readonly messagesLoader: MessagesLoader
	public hasErrors: boolean = false
	
	public isAdmin = false
	public canCreate = false
	public permissions: AccountPermissions
	public accountName: string = ""
	private loginTime: number = -1
	
	constructor(data: LoginDataInterface) {
		this.hasErrors = data.hasErrors
		this.messagesLoader = new MessagesLoader(data)
		
		this.loginTime = data.loginTime
		this.accountName = data.accountName
		this.canCreate = data.canCreate
		
		if(data.isAdmin) {
			this.isAdmin = true
			this.permissions = {publish: [], msg: [], read: [], write: []}
		}
		else {
			this.isAdmin = false
			this.permissions = data.permissions
		}
	}
	
	public hasPermission(name: keyof AccountPermissions, studyId: number): boolean {
		return this.isAdmin || this.permissions[name].indexOf(studyId) != -1
	}
}