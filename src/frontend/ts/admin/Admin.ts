import {AdminToolsInterface} from "./AdminToolsInterface";
import {FILE_ADMIN} from "../constants/urls";
import {Requests} from "../singletons/Requests";
import {LoginDataInterface} from "./LoginDataInterface";
import {PromiseCache} from "../singletons/PromiseCache";
import {Site} from "../site/Site";

/**
 * Loaded by {@link Site}
 * {@link isEnabled} will be true if the hash starts with "admin"
 * (or will be constructed as enabled if the startHash starts with "admin" - when using "?admin", see in index.php).
 * Each {@link Section.load()} first calls {@link Admin.init()} before loading, which will - if {@link isEnabled} is true - make sure the user is logged in and {@link AdminTools} is loaded
 */
export class Admin {
	private isEnabled: boolean
	private isLoggedInState: boolean = false
	private tools?: AdminToolsInterface
	private readonly site: Site
	
	constructor(isEnabled: boolean, site: Site) {
		this.isEnabled = isEnabled
		this.site = site
	}
	
	public enableAdmin(): void {
		this.isEnabled = true
	}
	public disableAdmin(): void {
		this.isEnabled = false
	}
	
	/**
	 *
	 * @returns false if a login is needed
	 */
	public init(): Promise<boolean> {
		if(this.isEnabled)
			return PromiseCache.get("admin", async () => {
				const data: LoginDataInterface = await Requests.loadJson(FILE_ADMIN+"?type=GetPermissions")
				return this.processLoginData(data)
			})
		else
			return Promise.resolve(true)
	}
	
	/**
	 *
	 * @returns false if a login is needed
	 */
	private async processLoginData(data: LoginDataInterface): Promise<boolean> {
		if(!data.isLoggedIn) {
			PromiseCache.remove("admin")
			this.isLoggedInState = false
			return false
		}
		this.tools = new (await import("./AdminTools")).AdminTools(data)
		this.isLoggedInState = true
		
		return true
	}
	
	public isLoggedIn(): boolean {
		return this.isEnabled && this.isLoggedInState
	}
	
	/**
	 *
	 * @returns false if a login is needed
	 */
	public login(accountName: string, password: string, rememberMe: boolean): Promise<boolean> {
		PromiseCache.remove("admin")
		return PromiseCache.get("admin", async () => {
			try {
				const data: LoginDataInterface = await Requests.loadJson(
					FILE_ADMIN + "?type=login",
					"post",
					`accountName=${accountName}&pass=${password}${rememberMe ? "&rememberMe" : ""}`
				)
				if(await this.processLoginData(data)) {
					this.site.siteData.onlyShowLastSection = false
					this.site.reload() //we can not use await here because site.reload() will trigger Admin.init() eventually. Which will wait for the return true from here. So everything will be stuck
					return true
				}
			}
			catch(e) {
				return false
			}
			
			return false
		})
	}
	
	/**
	 * returns the AdminTools object.
	 * {@link tools} should always exist, when the first section is "admin". It will be loaded by {@link Section.load()} which calls {@link init()}
	 */
	public getTools(): AdminToolsInterface {
		if(!this.tools) {
			throw new Error("AdminTools was not loaded")
		}
		return this.tools
	}
	
	public async logout(): Promise<void> {
		PromiseCache.remove("admin")
		await Requests.loadJson(`${FILE_ADMIN}?type=logout`)
		await this.site.reload()
	}
}