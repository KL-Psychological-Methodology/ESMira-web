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
		const success = await this.sectionData.loader.showLoader(
			this.getAdmin().login(
				formData.get("accountName")?.toString() || "",
				formData.get("password")?.toString() || "",
				!!formData.get("rememberMe")
			)
		)
		if(!success)
			this.sectionData.loader.info(Lang.get("error_wrong_login"));
	}
	
	public getView(): Vnode<any, any> {
		const username = this.getStaticString("username") ?? ""
		const password = this.getStaticString("password") ?? ""
		
		return (
			<div class="login line vertical hAlignCenter vAlignCenter">
				<form method="post" action="" onsubmit={this.formLogin.bind(this)} class="vertical hAlignStretched">
					<div class="horizontal hAlignStretched">
						<label class="noDesc">
							<small>{Lang.get("username")}</small>
							<input type="text" name="accountName" autocomplete="username" value={username}/>
						</label>
						<label class="noDesc">
							<small>{Lang.get("password")}</small>
							<input type="password" name="password" autocomplete="current-password" value={password}/>
						</label>
					</div>
					
					<div class="horizontal">
						<label class="noTitle noDesc">
							<input type="checkbox" name="rememberMe"/>
							<span>{Lang.get("remember_me")}</span>
						</label>
						<div class="fillFlexSpace"></div>
						
						<input type="submit" value={Lang.get("login")}/>
					</div>
				</form>
			</div>
		)
	}
}