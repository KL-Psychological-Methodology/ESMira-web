import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {ChangeAccount} from "../widgets/ChangeAccount";
import {TitleRow} from "../widgets/TitleRow";
import {Requests} from "../singletons/Requests";
import {FILE_ADMIN} from "../constants/urls";
import {DropdownMenu} from "../widgets/DropdownMenu";
import {BtnTrash} from "../widgets/BtnWidgets";

interface LoginToken {
	lastUsed: number
	tokenId: string
	current: boolean
}

export class Content extends SectionContent {
	private loginToken: LoginToken[]
	
	public static preLoad(_section: Section): Promise<any>[] {
		return [
			Requests.loadJson(`${FILE_ADMIN}?type=GetTokenList`)
		]
	}
	constructor(section: Section, loginToken: LoginToken[]) {
		super(section)
		
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
		
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=ChangeAccountName`,
			"post",
			`accountName=${oldAccountName}&new_account=${newAccountName}`
		)
		this.getTools().accountName = oldAccountName
		this.section.loader.info(Lang.get("info_successful"));
	}
	private async changePassword(password: string): Promise<void> {
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=ChangePassword`,
			"post",
			`accountName=${this.getTools().accountName}&new_pass=${password}`
		)
		this.section.loader.info(Lang.get("info_successful"))
	}
	
	private async removeLoginToken(loginToken: LoginToken): Promise<void> {
		if(!confirm())
			return
		
		const newLoginToken = await this.section.loader.loadJson(`${FILE_ADMIN}?type=RemoveToken`, "post", `token_id=${loginToken.tokenId}`)
		this.sortLoginToken(newLoginToken)
		this.loginToken = newLoginToken
		
		m.redraw()
	}
	
	public getView(): Vnode<any, any> {
		return <div>
			<p class="clickable vertical" onclick={this.changeAccountName.bind(this)}>{Lang.get("change_username")}</p>
			
			{DropdownMenu("changePassword",
				<p class="clickable vertical">{Lang.get("change_password")}</p>,
				(close) =>
					ChangeAccount(
						async (_accountName, password) => {
							await this.changePassword(password)
							close()
							return true
						},
						(msg) => { this.section.loader.error(msg) },
						this.getTools().accountName,
						Lang.get("change_password")
					)
			)}
			
			<a href={this.getUrl("dataView:logins")}>{Lang.get("login_history")}</a>
			<br/>
			<br/>
			{TitleRow(Lang.getWithColon("remember_me_token"))}
			
			{this.loginToken.map((loginToken) =>
				<div class="spacingBottom">
					{BtnTrash(this.removeLoginToken.bind(this, loginToken))}
					<div class="horizontal">
						<div>
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