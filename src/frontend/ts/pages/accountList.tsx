import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {Account} from "../data/accounts/Account";
import {ChangeAccount} from "../widgets/ChangeAccount";
import {TitleRow} from "../widgets/TitleRow";
import {AccountsLoader} from "../loader/AccountsLoader";
import {BtnEdit, BtnTrash} from "../widgets/BtnWidgets";

export class Content extends SectionContent {
	private accountsLoader: AccountsLoader
	
	public static preLoad(section: Section): Promise<any>[] {
		return [
			section.getTools().accountsLoader.init()
		]
	}
	constructor(section: Section, accountsLoader: AccountsLoader) {
		super(section)
		this.accountsLoader = accountsLoader
	}
	
	public title(): string {
		return Lang.get("edit_users")
	}
	
	private async createAccount(accountName: string, password: string): Promise<any> {
		const index = await this.section.loader.showLoader(this.accountsLoader.addAccount(accountName, password))
		this.newSection(`accountView,accountI:${index}`)
	}
	
	private async deleteAccount(account: Account, index: number) {
		await this.section.loader.showLoader(this.accountsLoader.deleteAccount(account, index))
	}
	
	public getView(): Vnode<any, any> {
		return <div class="listParent">
			<table id="accountList" class="listChild">
				<thead>
				<tr>
					<th class="accountName"></th>
					<th>{Lang.get("permission_admin")}</th>
					<th>{Lang.get("permission_create")}</th>
					<th>{Lang.get("permission_write")}</th>
					<th>{Lang.get("permission_msg")}</th>
					<th>{Lang.get("permission_read")}</th>
					<th></th>
				</tr>
				</thead>
				<tbody>
					{this.accountsLoader.getAccounts().map((account, index) =>
						<tr>
							<td class="accountName">
								{BtnTrash(this.deleteAccount.bind(this, account, index))}
								<span>{account.accountName.get()}</span>
							</td>
							<td>
								{account.admin.get() &&
									<span>&#10004;</span>
								}
							</td>
							<td>
								{(account.admin.get() || account.create.get()) &&
									<span>&#10004;</span>
								}
							</td>
							<td>
								<span>{account.admin.get() ? m.trust("&#10004;") : account.write.get().length}</span>
							</td>
							<td>
								<span>{account.admin.get() ? m.trust("&#10004;") : account.msg.get().length}</span>
							</td>
							<td>
								<span>{account.admin.get() ? m.trust("&#10004;") : account.read.get().length}</span>
							</td>
							<td>
								<a href={this.getUrl(`accountView,accountI:${index}`)}>
									{BtnEdit()}
								</a>
							</td>
						</tr>
					)}
					
				</tbody>
			</table>
			
			{TitleRow(Lang.getWithColon("add"))}
			
			{
				ChangeAccount(this.createAccount.bind(this), (msg) => { this.section.loader.error(msg)})
			}
		
		</div>
	}
}