import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";

export class Content extends SectionContent {
	public title(): string {
		return Lang.get("home")
	}
	
	private async formLogin(e: SubmitEvent): Promise<void> {
		e.preventDefault()
		const formData = new FormData(e.target as HTMLFormElement)
		const success = await this.section.loader.showLoader(
			this.getAdmin().login(
				formData.get("accountName")?.toString() || "",
				formData.get("password")?.toString() || "",
				!!formData.get("rememberMe")
			)
		)
		if(!success)
			this.section.loader.info(Lang.get("error_wrong_login"));
	}
	
	public getView(): Vnode<any, any> {
		return (
			<div class="login centerChildrenVertically listParent spacingLeft spacingRight">
				<form method="post" action="" onsubmit={this.formLogin.bind(this)}>
					<label class="horizontal noDesc">
						<small>{Lang.get("username")}</small>
						<input type="text" name="accountName" autocomplete="username"/>
					</label>
					<label class="horizontal noDesc">
						<small>{Lang.get("password")}</small>
						<input type="password" name="password" autocomplete="current-password"/>
					</label>
					
					<br/>
					<br/>
					<label class="left horizontal noTitle noDesc">
						<input type="checkbox" name="rememberMe"/>
						<span>{Lang.get("remember_me")}</span>
					</label>
					
					<input class="right horizontal" type="submit" value={Lang.get("login")}/>
				</form>
			</div>
		)
	}
}