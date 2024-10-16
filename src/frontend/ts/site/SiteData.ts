import {Admin} from "../admin/Admin";
import {Container} from "../observable/Container";
import {DynamicValues} from "./DynamicValues";
import {DynamicCallbacks} from "./DynamicCallbacks";
import {StudyLoader} from "../loader/StudyLoader";
import { BookmarkLoader } from "../loader/BookmarkLoader";

export class SiteData {
	public readonly packageVersion: string
	
	/**
	 * Used when admin login is needed, to make sure there is only one login window
	 * Set in {@link Section.load} and removed in {@link Admin.processLoginData} when login succeeds
	 */
	public onlyShowLastSection = false
	
	public currentSection: number = 0
	
	public readonly admin: Admin
	public readonly dynamicValues = new Container<DynamicValues>()
	public readonly dynamicCallbacks: DynamicCallbacks = {}
	public readonly studyLoader: StudyLoader
	public readonly bookmarkLoader: BookmarkLoader
	
	constructor(admin: Admin, serverVersion: number, packageVersion: string) {
		this.admin = admin
		this.packageVersion = packageVersion
		this.studyLoader = new StudyLoader(serverVersion, packageVersion)
		this.bookmarkLoader = new BookmarkLoader()
	}
}