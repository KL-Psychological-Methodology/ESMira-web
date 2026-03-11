import { SectionContent } from "../site/SectionContent";
import m, { Vnode } from "mithril";
import { Lang } from "../singletons/Lang";
import { Account } from "../data/accounts/Account";
import { ChangeAccount } from "../components/ChangeAccount";
import { TitleRow } from "../components/TitleRow";
import { AccountsLoader } from "../loader/AccountsLoader";
import { BtnEdit, BtnTrash } from "../components/Buttons";
import { SectionData } from "../site/SectionData";

export class Content extends SectionContent {
	private accountsLoader: AccountsLoader

	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [
			sectionData.getTools().accountsLoader.init()
		]
	}
	constructor(sectionData: SectionData, accountsLoader: AccountsLoader) {
		super(sectionData)
		this.accountsLoader = accountsLoader
	}

	public title(): string {
		return Lang.get("edit_users")
	}

	private async createAccount(accountName: string, password: string): Promise<any> {
		const index = await this.sectionData.loader.showLoader(this.accountsLoader.addAccount(accountName, password))
		this.newSection(`accountView,accountI:${index}`)
	}

	private async deleteAccount(account: Account, index: number) {
		const deleted = await this.sectionData.loader.showLoader(this.accountsLoader.deleteAccount(account, index))
		if (deleted) {
			window.location.hash = `${this.sectionData.getHash(this.sectionData.depth)}`
		}
	}

	public getView(): Vnode<any, any> {
		return <div class="center">
			<table id="accountList">
				<thead>
					<tr>
						<th class="accountName"></th>
						<th>{Lang.get("permission_admin")}</th>
						<th>{Lang.get("permission_create")}</th>
						<th>{Lang.get("permission_write")}</th>
						<th>{Lang.get("permission_msg")}</th>
						<th>{Lang.get("permission_read")}</th>
						<th>{Lang.get("permission_reward")}</th>
						<th>{Lang.get("permission_fallback_tokens")}</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					{this.accountsLoader.getAccounts().map((account, index) =>
						<tr>
							<td class="accountName">
								<div class="horizontal">
									{BtnTrash(this.deleteAccount.bind(this, account, index))}
									<a href={this.getUrl(`accountView,accountI:${index}`)}>{account.accountName.get()}</a>
								</div>
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
								<span>{account.admin.get() ? m.trust("&#10004;") : account.reward.get().length}</span>
							</td>
							<td>
								{(account.admin.get() || account.issueFallbackToken.get()) &&
									<span>&#10004;</span>
								}
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
				ChangeAccount(this.createAccount.bind(this), (msg) => { this.sectionData.loader.error(msg) })
			}

		</div>
	}
}