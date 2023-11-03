import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {FILE_ADMIN} from "../constants/urls";
import {Account} from "../data/accounts/Account";
import {ChangeAccount} from "../widgets/ChangeAccount";
import {TitleRow} from "../widgets/TitleRow";
import {BindObservable} from "../widgets/BindObservable";
import {AccountPermissions} from "../admin/AccountPermissions";
import {closeDropdown, DropdownMenu} from "../widgets/DropdownMenu";
import {AccountsLoader} from "../loader/AccountsLoader";
import {BtnAdd, BtnTrash} from "../widgets/BtnWidgets";

export class Content extends SectionContent {
	private accountsLoader: AccountsLoader
	private readonly isOwnAccount: boolean
	private showPasswordField = false
	
	public static preLoad(section: Section): Promise<any>[] {
		return [
			section.getTools().accountsLoader.init(),
			section.getStrippedStudyListPromise()
		]
	}
	constructor(section: Section, accountsLoader: AccountsLoader) {
		super(section)
		this.accountsLoader = accountsLoader
		const account = this.getAccount()
		this.isOwnAccount = account.accountName.get() == this.getTools().accountName
		
		account.admin.addObserver(this.toggleAdmin.bind(this))
		account.create.addObserver(this.toggleCreate.bind(this))
	}
	
	public title(): string {
		return this.getAccount().accountName.get()
	}
	
	private getAccount(): Account {
		return this.accountsLoader.getAccounts()[this.getStaticInt("accountI") ?? 0]
	}
	
	private async changeUsername(account: Account): Promise<void> {
		const oldAccountName = account.accountName.get()
		const newAccountName = prompt(Lang.get("prompt_newUsername"), oldAccountName)
		if(!newAccountName)
			return
		
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=ChangeAccountName`,
			"post",
			`accountName=${oldAccountName}&new_account=${newAccountName}`
		)
		if(this.isOwnAccount)
			this.getTools().accountName = oldAccountName
		
		account.accountName.set(newAccountName)
		
		this.section.loader.info(Lang.get("info_successful"));
	}
	
	private async changePassword(account: Account, password: string): Promise<void> {
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=ChangePassword`,
			"post",
			`accountName=${account.accountName.get()}&new_pass=${password}`
		)
		this.section.loader.info(Lang.get("info_successful"))
	}
	
	private async toggleAdmin(): Promise<any> {
		const account = this.getAccount()
		if(this.isOwnAccount) {
			account.admin.set(true)
			return
		}
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=ToggleAccountPermission`,
			"post",
			`accountName=${account.accountName.get()}&admin=${(account.admin.get() ? "1" : "0")}`
		)
		this.section.loader.info(Lang.get("info_successful"))
	}
	
	private async toggleCreate(): Promise<void> {
		const account = this.getAccount()
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=ToggleAccountPermission`,
			"post",
			`accountName=${account.accountName.get()}&create=${(account.admin.get() ? "1" : "0")}`
		)
		this.section.loader.info(Lang.get("info_successful"))
	}
	
	private async addPermission(account: Account, permissionName: keyof AccountPermissions, studyId: number): Promise<void> {
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=AddStudyPermission`,
			"post",
			`accountName=${account.accountName.get()}&permission=${permissionName}&study_id=${studyId}`
		)
		
		if(this.isOwnAccount) {
			const tools = this.getTools()
			const permissions = tools.permissions
			const selectedPermissions = permissions[permissionName]
			
			selectedPermissions.push(studyId)
			if(permissionName == "publish" && permissions.write.indexOf(studyId) == -1)
				permissions.write.push(studyId)
		}
		const list = account[permissionName]
		list.push(studyId)
		if(permissionName == "publish" && account.write.indexOf(studyId) == -1)
			account.write.push(studyId)
		closeDropdown("addPermission")
		this.section.loader.info(Lang.get("info_successful"))
	}
	
	private async removePermission(account: Account, permissionName: keyof AccountPermissions, studyId: number): Promise<void> {
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=DeleteStudyPermission`,
			"post",
			`accountName=${account.accountName.get()}&permission=${permissionName}&study_id=${studyId}`
		)
		if(this.isOwnAccount) {
			const permissions = this.getTools().permissions[permissionName]
			const i = permissions?.indexOf(studyId) ?? -1
			if(i != -1)
				permissions?.splice(i, 1)
		}
		
		const list = account[permissionName]
		const index = list.indexOf(studyId)
		if(index != -1)
			list.remove(index)
		this.section.loader.info(Lang.get("info_successful"))
	}
	
	public getView(): Vnode<any, any> {
		const account = this.getAccount()
		return <div>
			<p class="clickable vertical" onclick={this.changeUsername.bind(this, account)}>{Lang.get("change_username")}</p>
			
			{DropdownMenu("changePassword",
				<p class="clickable vertical">{Lang.get("change_password")}</p>,
				(close) =>
					ChangeAccount(
						async (_accountName, password) => {
							await this.changePassword(account, password)
							close()
							return true
						},
						(msg) => { this.section.loader.error(msg) },
						account.accountName.get(),
						Lang.get("change_password")
					)
			)}
			
			
			<label class="vertical">
				<span>{Lang.getWithColon("permissions_admin")}</span>
				<input type="checkbox" {... BindObservable(account.admin)}/>
			</label>
			{!account.admin.get() &&
				<label class="vertical">
					<span>{Lang.getWithColon("permissions_create")}</span>
					<input type="checkbox" {...BindObservable(account.create)}/>
				</label>
			}
			
			{this.getListPermissionView(account, Lang.getWithColon("permissions_publish"), "publish")}
			{this.getListPermissionView(account, Lang.getWithColon("permissions_write"), "write")}
			{this.getListPermissionView(account, Lang.getWithColon("permissions_msg"), "msg")}
			{this.getListPermissionView(account, Lang.getWithColon("permissions_read"), "read")}
		</div>
	}
	
	private getListPermissionView(account: Account, title: string, permissionName: keyof AccountPermissions): Vnode<any, any> {
		const permission = account[permissionName]
		
		return <div>
			{TitleRow(title)}
			<div class="listParent">
				{account.admin.get()
					? m.trust("&#10004;")
					: <div>
						<div class="listChild">
							{ permission.get().map((obs) =>
									<div>
										{BtnTrash(this.removePermission.bind(this, account, permissionName, obs.get()))}
										<span>{this.getStudyOrNull(obs.get())?.title.get() ?? `${Lang.get('unknown')} (${obs.get()})`}</span>
									</div>
								)
							}
							
						</div>
						<div>
							{DropdownMenu(
								"addPermission",
								BtnAdd(),
								() =>
									<ul>
										<h2>{Lang.get("select_a_study")}</h2>
										{this.section.siteData.studyLoader.getSortedStudyList()
											.filter((study) => {
												const id = study.id.get()
												for(const obs of permission.get()) {
													if(obs.get() == id)
														return false
												}
												return true
											})
											.map((study) =>
												<li
													class={`clickable ${study.published.get() ? "" : "unPublishedStudy"}`}
													onclick={this.addPermission.bind(this, account, permissionName, study.id.get())}
												>{study.title.get()}</li>
											)}
									</ul>
							)}
						</div>
					</div>
				}
			</div>
		</div>
	}
}