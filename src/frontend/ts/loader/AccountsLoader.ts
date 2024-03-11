import {FILE_ADMIN} from "../constants/urls";
import {PromiseCache} from "../singletons/PromiseCache";
import {Requests} from "../singletons/Requests";
import {ObservableArray} from "../observable/ObservableArray";
import {TranslatableObjectDataType} from "../observable/TranslatableObject";
import {Account} from "../data/accounts/Account";
import {safeConfirm} from "../constants/methods";
import {Lang} from "../singletons/Lang";

export type AccountList = ObservableArray<TranslatableObjectDataType, Account>

export class AccountsLoader {
	private accounts?: AccountList
	public async init(): Promise<AccountsLoader> {
		return PromiseCache.get("accountsList", async () => {
			const accountsJson = await Requests.loadJson(`${FILE_ADMIN}?type=ListAccounts`)
			this.accounts = new ObservableArray<TranslatableObjectDataType, Account>(
				accountsJson,
				null,
				"accountList",
				(data, parent, key) => {
					return new Account(data, parent, key)
				})
			return this
		})
	}
	public getAccounts(): Account[] {
		return this.accounts!.get()
	}
	
	public async addAccount(accountName: string, password: string): Promise<number> {
		const accounts = this.accounts!
		const accountJson = await Requests.loadJson(`${FILE_ADMIN}?type=CreateAccount`, "post", `new_account=${accountName}&pass=${password}`)
		accounts.push(accountJson)
		return accounts.get().length - 1
	}
	public async deleteAccount(account: Account, index: number): Promise<boolean> {
		const accountName = account.accountName.get()
		if(!safeConfirm(Lang.get("confirm_delete_account", accountName)))
			return false
		const accounts = this.accounts!
		await Requests.loadJson(`${FILE_ADMIN}?type=DeleteAccount`, "post", `accountName=${accountName}`)
		accounts.remove(index)
		return true
	}
}