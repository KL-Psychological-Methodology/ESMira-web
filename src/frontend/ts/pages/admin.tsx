import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {Lang} from "../singletons/Lang";
import addSvg from "../../imgs/icons/add.svg?raw"
import dataSvg from "../../imgs/icons/data.svg?raw"
import editSvg from "../../imgs/icons/change.svg?raw"
import editAccountSvg from "../../imgs/dashIcons/editAccount.svg?raw"
import editUsersSvg from "../../imgs/dashIcons/editUsers.svg?raw"
import errorReportsSvg from "../../imgs/dashIcons/errorReports.svg?raw"
import logoutSvg from "../../imgs/dashIcons/logout.svg?raw"
import messagesSvg from "../../imgs/icons/message.svg?raw"
import serverStatisticsSvg from "../../imgs/dashIcons/serverStatistics.svg?raw"
import serverSettingsSvg from "../../imgs/dashIcons/settings.svg?raw"
import {TitleRow} from "../widgets/TitleRow";
import {AddDropdownMenus} from "../helpers/AddDropdownMenus";
import { RssFetcher, RssItem } from "../singletons/RssFetcher";
import { NewsItem } from "../widgets/NewsItem";
import { Section } from "../site/Section";
import { Requests } from "../singletons/Requests";
import { FILE_ADMIN } from "../constants/urls";
import { BtnTrash } from "../widgets/BtnWidgets";

const MINIMAL_DISK_SPACE = 1000 * 1000 * 100 //100 Mb
/**
 * This section needs to be the very first section to make sure that {@link Admin.tools} is loaded
 * {@link Site.constructor()} check for "admin" in the url hash
 */
export class Content extends SectionContent {
	private readonly addDropdownMenus = new AddDropdownMenus(this)
	private rssItems: RssItem[]

	public static preLoad(section: Section): Promise<any>[] {
		return [
			RssFetcher.loadFeed(3)
				.catch(() => {
					section.loader.error(Lang.get("error_news"))
					return []
				})//,
			//Requests.loadJson(`${FILE_ADMIN}?type=GetBookmarks`)
		]
	}

	constructor(section: Section, rssItems: RssItem[]) {
		super(section)
		this.rssItems = rssItems
	}

	public title(): string {
		return Lang.get("admin")
	}
	
	private async addStudy(e: MouseEvent): Promise<void> {
		return this.addDropdownMenus.addStudy(e.target as Element)
	}
	
	private logout(): Promise<void> {
		return this.section.loader.showLoader(this.getAdmin().logout())
	}

	private editBookmark(url: string, oldName: string) {
		const newName = prompt(Lang.get("prompt_bookmark_name"), oldName)
		if(!newName)
			return
		this.section.siteData.bookmarkLoader.setBookmark(url, newName)
	}

	private bookmarkList(): Vnode<any, any> {
		const bookmarkLoader = this.section.siteData.bookmarkLoader

		return <div class="listParent">
			<div class="listChild">
				{Object.entries(bookmarkLoader.getBookmarkList()).sort(([,nameA], [,nameB]) => {
					return nameA.localeCompare(nameB)
				}).map(([url, name]) => {
					return <div>
						<a class="btn" onclick={this.editBookmark.bind(this, url, name)}>{m.trust(editSvg)}</a>
						<a href={url}>{name}</a>
					</div>
				})}
			</div>
		</div>
	}
	
	public getView(): Vnode<any, any> {
		const tools = this.getTools()
		return <div>
			{
				DashRow(
					tools.isAdmin && tools.freeDiskSpace < MINIMAL_DISK_SPACE &&
					DashElement("stretched", {
						highlight: true,
						small: true,
						content: <div class="highlight">{Lang.get("info_low_disk_space", Math.round(tools.freeDiskSpace / 1000 / 1000))}</div>
					}),
					
					(tools.permissions.write || tools.canCreate) &&
					DashElement(null,
						{
							template: { title: Lang.get("edit_studies"), icon: m.trust(editSvg) },
							href: this.getUrl("allStudies:edit")
						},
						tools.canCreate && {
							floating: true,
							template: {title: Lang.get("create"), icon: m.trust(addSvg) },
							onclick: this.addStudy.bind(this)
						},
					),
					
					tools.permissions.msg &&
						DashElement(null, {
							highlight: !!(tools.messagesLoader.studiesWithNewMessagesCount.get()),
							template: {title: Lang.get("messages"), icon: m.trust(messagesSvg) },
							href: this.getUrl("allStudies:msgs")
						}),
					
					tools.permissions.read &&
						DashElement(null, {
							highlight: !!(tools.merlinLogsLoader.studiesWithNewMerlinLogsCount.get()),
							template: {title: Lang.get("show_data_statistics"), icon: m.trust(dataSvg) },
							href: this.getUrl("allStudies:data")
						}),
					
					tools?.isAdmin &&
						DashElement(null, {
							template: {title: Lang.get("show_server_statistics"), icon: m.trust(serverStatisticsSvg) },
							href: this.getUrl("serverStatisticsAdmin")
						})
				)
			}
			{
				!this.section.siteData.bookmarkLoader.isBookmarkListEmpty() &&
				<div>
					{
						TitleRow(Lang.getWithColon("bookmarks"))
					}
					{
						this.bookmarkList()
					}	
				</div>
			}
			{
				tools?.isAdmin &&
					<div>
						{
							TitleRow(Lang.getWithColon("server"))
						}
						{
							DashRow(
								DashElement(null, {
									template: {title: Lang.get("edit_users"), icon: m.trust(editUsersSvg) },
									href: this.getUrl("accountList")
								}),
								DashElement(null, {
									template: {title: Lang.get("server_settings"), icon: m.trust(serverSettingsSvg) },
									href: this.getUrl("serverSettings")
								}),
								DashElement(null, {
									highlight: tools?.hasErrors,
									template: {title: Lang.get("show_errorReports"), icon: m.trust(errorReportsSvg) },
									href: this.getUrl("errorList")
								})
							)
						}
					</div>
			}
			<br/>
			{
				DashRow(
					DashElement(null, {
						small: true,
						template: {title: Lang.get("edit_user_account"), icon: m.trust(editAccountSvg) },
						href: this.getUrl("myAccount")
					}),
					DashElement(null, {
						small: true,
						template: {title: Lang.get("logout_x", tools?.accountName), icon: m.trust(logoutSvg)},
						onclick: this.logout.bind(this)
					})
				)
			}
			<br/>
			{ !!this.rssItems.length &&
				<div>
					{
						TitleRow(Lang.getWithColon("news"))
					}
					<div>
					{
						this.rssItems.map(item => {
							return NewsItem(item)
						})
					}
					</div>
				</div>
			}
		</div>
		
	}
}