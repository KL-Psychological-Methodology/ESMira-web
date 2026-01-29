import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {ChangeAccount} from "../components/ChangeAccount";
import {TitleRow} from "../components/TitleRow";
import {Requests} from "../singletons/Requests";
import {FILE_ADMIN} from "../constants/urls";
import {DropdownMenu} from "../components/DropdownMenu";
import {BtnTrash} from "../components/Buttons";
import {SectionData} from "../site/SectionData";

interface LoginToken {
	lastUsed: number
	tokenId: string
	current: boolean
}

export class Content extends SectionContent {
	private loginToken: LoginToken[]
	
	public static preLoad(): Promise<any>[] {
		return [
			Requests.loadJson(`${FILE_ADMIN}?type=GetTokenList`)
		]
	}
	constructor(sectionData: SectionData, loginToken: LoginToken[]) {
		super(sectionData)
		
		this.sortLoginToken(loginToken)
		this.loginToken = loginToken
	}
	
	public title(): string {
		return Lang.get("edit_user_account")
	}
	
	private sortLoginToken(loginToken: LoginToken[]): void {
		loginToken.sort(function(a, b) {
			if(a.lastUsed < b.lastUsed)
				return 1
			else if(a.lastUsed == b.lastUsed)
				return 0
			else
				return -1
		})
	}
	
	private async changeAccountName(): Promise<void> {
		const oldAccountName = this.getTools().accountName
		const newAccountName = prompt(Lang.get("prompt_newUsername"), oldAccountName)
		if(!newAccountName)
			return
		
		await this.sectionData.loader.loadJson(
			`${FILE_ADMIN}?type=ChangeAccountName`,
			"post",
			`accountName=${oldAccountName}&new_account=${newAccountName}`
		)
		this.getTools().accountName = oldAccountName
		this.sectionData.loader.info(Lang.get("info_successful"));
	}
	private async changePassword(password: string): Promise<void> {
		await this.sectionData.loader.loadJson(
			`${FILE_ADMIN}?type=ChangePassword`,
			"post",
			`accountName=${this.getTools().accountName}&new_pass=${password}`
		)
		this.sectionData.loader.info(Lang.get("info_successful"))
	}
	
	private async removeLoginToken(loginToken: LoginToken): Promise<void> {
		if(!confirm())
			return
		
		const newLoginToken = await this.sectionData.loader.loadJson(`${FILE_ADMIN}?type=RemoveToken`, "post", `token_id=${loginToken.tokenId}`)
		this.sortLoginToken(newLoginToken)
		this.loginToken = newLoginToken
		
		m.redraw()
	}
	
	public getView(): Vnode<any, any> {
		return <div>
			<div class="vertical">
				<div class="clickable" onclick={this.changeAccountName.bind(this)}>{Lang.get("change_username")}</div>
				
				{DropdownMenu("changePassword",
					<div class="clickable">{Lang.get("change_password")}</div>,
					(close) =>
						ChangeAccount(
							async (_accountName, password) => {
								await this.changePassword(password)
								close()
								return true
							},
							(msg) => { this.sectionData.loader.error(msg) },
							this.getTools().accountName,
							Lang.get("change_password")
						)
				)}
				
				<a href={this.getUrl("dataView:logins")}>{Lang.get("login_history")}</a>
			</div>
			<br/>
			<br/>
			{TitleRow(Lang.getWithColon("remember_me_token"))}
			
			{this.loginToken.map((loginToken) =>
				<div class="spacingBottom">
					{BtnTrash(this.removeLoginToken.bind(this, loginToken))}
					<div class="vertical">
						<div class="horizontal">
							<span>{Lang.getWithColon("last_used")}</span>
							<span class="highlight">{(new Date(loginToken.lastUsed*1000)).toLocaleString()}</span>
						</div>
						<div class="smallText">{loginToken.tokenId}</div>
					</div>
					{ loginToken.current &&
						<span class="infoSticker highlight">{Lang.get("this")}</span>
					}
				</div>
			)}
		</div>
	}
}