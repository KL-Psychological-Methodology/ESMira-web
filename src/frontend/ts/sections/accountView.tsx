import { SectionContent } from "../site/SectionContent";
import m, { Vnode } from "mithril";
import { Lang } from "../singletons/Lang";
import { FILE_ADMIN } from "../constants/urls";
import { Account } from "../data/accounts/Account";
import { ChangeAccount } from "../components/ChangeAccount";
import { TitleRow } from "../components/TitleRow";
import { BindObservable } from "../components/BindObservable";
import { AccountPermissions } from "../admin/AccountPermissions";
import { closeDropdown, DropdownMenu } from "../components/DropdownMenu";
import { AccountsLoader } from "../loader/AccountsLoader";
import { BtnAdd, BtnTrash } from "../components/Buttons";
import { SectionData } from "../site/SectionData";

export class Content extends SectionContent {
	private accountsLoader: AccountsLoader
	private readonly isOwnAccount: boolean

	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [
			sectionData.getTools().accountsLoader.init(),
			sectionData.getStrippedStudyListPromise()
		]
	}
	constructor(sectionData: SectionData, accountsLoader: AccountsLoader) {
		super(sectionData)
		this.accountsLoader = accountsLoader
		const account = this.getAccount()
		this.isOwnAccount = account.accountName.get() == this.getTools().accountName

		account.admin.addObserver(this.toggleAdmin.bind(this))
		account.create.addObserver(this.toggleCreate.bind(this))
		account.issueFallbackToken.addObserver(this.toggleIssueFallbackToken.bind(this))
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
		if (!newAccountName)
			return

		await this.sectionData.loader.loadJson(
			`${FILE_ADMIN}?type=ChangeAccountName`,
			"post",
			`accountName=${oldAccountName}&new_account=${newAccountName}`
		)
		if (this.isOwnAccount)
			this.getTools().accountName = oldAccountName

		account.accountName.set(newAccountName)

		this.sectionData.loader.info(Lang.get("info_successful"));
	}

	private async changePassword(account: Account, password: string): Promise<void> {
		await this.sectionData.loader.loadJson(
			`${FILE_ADMIN}?type=ChangePassword`,
			"post",
			`accountName=${account.accountName.get()}&new_pass=${password}`
		)
		this.sectionData.loader.info(Lang.get("info_successful"))
	}

	private async toggleAdmin(): Promise<any> {
		const account = this.getAccount()
		if (this.isOwnAccount) {
			account.admin.set(true)
			return
		}
		await this.sectionData.loader.loadJson(
			`${FILE_ADMIN}?type=ToggleAccountPermission`,
			"post",
			`accountName=${account.accountName.get()}&admin=${(account.admin.get() ? "1" : "0")}`
		)
		this.sectionData.loader.info(Lang.get("info_successful"))
	}

	private async toggleCreate(): Promise<void> {
		const account = this.getAccount()
		await this.sectionData.loader.loadJson(
			`${FILE_ADMIN}?type=ToggleAccountPermission`,
			"post",
			`accountName=${account.accountName.get()}&create=${(account.create.get() ? "1" : "0")}`
		)
		this.sectionData.loader.info(Lang.get("info_successful"))
	}

	private async toggleIssueFallbackToken(): Promise<void> {
		const account = this.getAccount()
		await this.sectionData.loader.loadJson(
			`${FILE_ADMIN}?type=ToggleAccountPermission`,
			"post",
			`accountName=${account.accountName.get()}&issueFallbackToken=${(account.issueFallbackToken.get() ? "1" : "0")}`
		)
		this.sectionData.loader.info(Lang.get("info_successful"))
	}


	private async addPermission(account: Account, permissionName: keyof AccountPermissions, studyId: number, handleUi: boolean = true): Promise<void> {
		await this.sectionData.loader.loadJson(
			`${FILE_ADMIN}?type=AddStudyPermission`,
			"post",
			`accountName=${account.accountName.get()}&permission=${permissionName}&study_id=${studyId}`
		)

		if (this.isOwnAccount) {
			const tools = this.getTools()
			const permissions = tools.permissions
			const selectedPermissions = permissions[permissionName]

			selectedPermissions.push(studyId)
			if (permissionName == "publish" && permissions.write.indexOf(studyId) == -1)
				permissions.write.push(studyId)
		}
		const list = account[permissionName]
		list.push(studyId)
		if (permissionName == "publish" && account.write.indexOf(studyId) == -1)
			account.write.push(studyId)
		if (handleUi) {
			closeDropdown("addPermission")
			this.sectionData.loader.info(Lang.get("info_successful"))
		}
	}

	private async addAllPermissions(account: Account, studyId: number): Promise<void> {
		const permissionNames: (keyof AccountPermissions)[] = ["publish", "read", "msg", "write", "reward"]
		permissionNames.forEach(async (permissionName) => { if (account[permissionName].indexOf(studyId) == -1) { this.addPermission(account, permissionName, studyId, false) } })
		closeDropdown("addAllPermissions")
		this.sectionData.loader.info(Lang.get("info_successful"))
	}

	private async removePermission(account: Account, permissionName: keyof AccountPermissions, studyId: number): Promise<void> {
		await this.sectionData.loader.loadJson(
			`${FILE_ADMIN}?type=DeleteStudyPermission`,
			"post",
			`accountName=${account.accountName.get()}&permission=${permissionName}&study_id=${studyId}`
		)
		if (this.isOwnAccount) {
			const permissions = this.getTools().permissions[permissionName]
			const i = permissions?.indexOf(studyId) ?? -1
			if (i != -1)
				permissions?.splice(i, 1)
		}

		const list = account[permissionName]
		const index = list.indexOf(studyId)
		if (index != -1)
			list.remove(index)
		this.sectionData.loader.info(Lang.get("info_successful"))
	}

	public getView(): Vnode<any, any> {
		const account = this.getAccount()
		return <div>
			<div class="vertical">
				<div class="clickable" onclick={this.changeUsername.bind(this, account)}>{Lang.get("change_username")}</div>
	
				{DropdownMenu("changePassword",
					<div class="clickable">{Lang.get("change_password")}</div>,
					(close) =>
						ChangeAccount(
							async (_accountName, password) => {
								await this.changePassword(account, password)
								close()
								return true
							},
							(msg) => { this.sectionData.loader.error(msg) },
							account.accountName.get(),
							Lang.get("change_password")
						)
				)}
	
	
				<label class="noTitle noDesc">
					<input type="checkbox" {...BindObservable(account.admin)} />
					<span>{Lang.get("permissions_admin")}</span>
				</label>
			
				{!account.admin.get() &&
					<div>
						<label class="noTitle noDesc">
							<input type="checkbox" {...BindObservable(account.create)} />
							<span>{Lang.get("permissions_create")}</span>
						</label>
						<label class="noTitle noDesc">
							<input type="checkbox" {...BindObservable(account.issueFallbackToken)} />
							<span>{Lang.get("permissions_issue_fallback_tokens")}</span>
						</label>
						<div>
							{DropdownMenu(
								"addAllPermissions",
								<p class="clickable" >{Lang.get("add_all_permissions")}</p>,
								() =>
									<ul>
										<h2>{Lang.get("select_a_study")}</h2>
										{this.sectionData.siteData.studyLoader.getSortedStudyList()
											.filter((study) => {
												const id = study.id.get()
												const permissionNames: (keyof AccountPermissions)[] = ["publish", "write", "read", "msg"]
												permissionNames.forEach((permissionName): boolean => {
													for (const obs of account[permissionName].get()) {
														if (obs.get() == id)
															return false
													}
													return true
												})
											})
											.map((study) =>
												<li
													class={`clickable ${study.published.get() ? "" : "unPublishedStudy"}`}
													onclick={this.addAllPermissions.bind(this, account, study.id.get())}
												>{study.title.get()}</li>
											)}
	
									</ul>
							)}
						</div>
					</div>
				}
			</div>
			
			{this.getListPermissionView(account, Lang.getWithColon("permissions_publish"), "publish")}
			{this.getListPermissionView(account, Lang.getWithColon("permissions_write"), "write")}
			{this.getListPermissionView(account, Lang.getWithColon("permissions_msg"), "msg")}
			{this.getListPermissionView(account, Lang.getWithColon("permissions_read"), "read")}
			{this.getListPermissionView(account, Lang.getWithColon("permissions_reward"), "reward")}
		</div>
	}

	private getListPermissionView(account: Account, title: string, permissionName: keyof AccountPermissions): Vnode<any, any> {
		const permission = account[permissionName]

		return <div>
			{TitleRow(title)}
			<div class="center">
				{account.admin.get()
					? m.trust("&#10004;")
					: <div>
						<div class="vertical hAlignStart">
							{permission.get().map((obs) => {
								const studyId = obs.get()
								return <div>
									{BtnTrash(this.removePermission.bind(this, account, permissionName, studyId))}
									<a class="middle" href={this.getUrl(`studyEdit,id:${studyId}`)}>{this.getStudyOrNull(studyId)?.title.get() ?? `${Lang.get('unknown')} (${studyId})`}</a>
								</div>
							})}

						</div>
						<br />
						<br />
						<div>
							{DropdownMenu(
								"addPermission",
								BtnAdd(),
								() =>
									<ul>
										<h2>{Lang.get("select_a_study")}</h2>
										{this.sectionData.siteData.studyLoader.getSortedStudyList()
											.filter((study) => {
												const id = study.id.get()
												for (const obs of permission.get()) {
													if (obs.get() == id)
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