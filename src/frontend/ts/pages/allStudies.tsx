import m, { Vnode } from "mithril";
import { Lang } from "../singletons/Lang";
import { Section } from "../site/Section";
import { TabBar, TabContent } from "../widgets/TabBar";
import { ObservablePrimitive } from "../observable/ObservablePrimitive";
import { Study } from "../data/study/Study";
import messageSvg from "../../imgs/icons/message.svg?raw";
import merlinLogsSvg from "../../imgs/icons/merlinLogs.svg?raw";
import { StudiesDataType } from "../loader/StudyLoader";
import { Content as StudiesContent } from "../pages/studies";
import { SectionAlternative } from "../site/SectionContent";
import { BindObservable } from "../widgets/BindObservable";

export class Content extends StudiesContent {
	protected targetPage: string
	protected titleString: string

	private readonly selectedTab: ObservablePrimitive<number>
	private readonly selectedPublicAccessKeyTab: ObservablePrimitive<number>
	private readonly selectedDisabledAccessKeyTab: ObservablePrimitive<number>
	private readonly selectedOwner: ObservablePrimitive<string>

	private readonly ownerRegister: Record<string, number[]>
	private publicAccessKeysTabs: TabContent[] = []
	private disabledAccessKeysTabs: TabContent[] = []
	protected studies: Study[] = []
	private readonly destroyImpl: (() => void)

	public static preLoad(section: Section): Promise<any>[] {
		return [
			section.getStrippedStudyListPromise()
		]
	}

	constructor(section: Section, studiesObs: StudiesDataType) {
		super(section)
		this.ownerRegister = section.siteData.studyLoader.ownerRegister
		this.selectedOwner = section.siteData.dynamicValues.getOrCreateObs("owner", "~")
		this.selectedPublicAccessKeyTab = section.siteData.dynamicValues.getOrCreateObs("publicAccessKeyIndex", 0)
		this.selectedDisabledAccessKeyTab = section.siteData.dynamicValues.getOrCreateObs("disabledAccessKeyIndex", 0)

		this.selectedTab = section.siteData.dynamicValues.getOrCreateObs("studiesIndex", 0)
		switch (section.sectionValue) {
			case "data":
				this.targetPage = "dataStatistics"
				if (section.getTools().merlinLogsLoader.studiesWithNewMerlinLogsCount.get())
					this.selectedTab.set(2)
				this.titleString = Lang.get("data")
				break
			case "msgs":
				this.targetPage = "messagesOverview"
				if (section.getTools().messagesLoader.studiesWithNewMessagesCount.get())
					this.selectedTab.set(1)
				this.titleString = Lang.get("messages")
				break
			case "edit":
			default:
				this.targetPage = "studyEdit"
				this.titleString = Lang.get("edit_studies")
				break
		}

		this.updateSortedStudies(this.getStudiesFromObservable(studiesObs))
		this.createAccessKeyTabLists()

		this.selectedOwner.addObserver(() => {
			this.selectedPublicAccessKeyTab.set(0)
			this.updateSortedStudies(this.getStudiesFromObservable(studiesObs))
			this.createAccessKeyTabLists()
		})
		const messagesObserverId = this.getTools().messagesLoader.studiesWithNewMessagesCount?.addObserver(() => {
			m.redraw()
		})
		const studyObserverId = studiesObs.addObserver((_origin, bubbled) => {
			if (!bubbled) {
				this.updateSortedStudies(this.getStudiesFromObservable(studiesObs))
				this.createAccessKeyTabLists()
			}
		})

		this.destroyImpl = () => {
			messagesObserverId.removeObserver()
			studyObserverId.removeObserver()
		}
	}
	public preInit(): Promise<any> {
		return Promise.resolve()
	}

	public titleExtra(): Vnode<any, any> | null {
		const ownerList = Object.keys(this.ownerRegister)
		if (ownerList.length > 1) {
			return <div>
				<select class="ownerSelector" {...BindObservable(this.selectedOwner)}>
					<option value="~">{Lang.get("all_user")}</option>
					{ownerList.map((name) =>
						<option value={name}>{name} ({this.ownerRegister[name].length})</option>
					)}
				</select>
			</div>
		}
		else
			return null
	}

	public hasAlternatives(): boolean {
		return this.studies.length > 1
	}
	public getAlternatives(): SectionAlternative[] | null {
		const allSections = this.section.allSections
		const depth = this.section.depth
		const id = allSections.length > depth + 1 ? allSections[depth + 1].getStaticInt("id") : null

		return this.studies.map((study) => {
			const currentId = study.id.get()
			return {
				title: study.title.get(),
				target: id != currentId ? this.getUrl(`${this.targetPage},id:${currentId}`) : false
			}
		})
	}

	protected updateSortedStudies(unsortedStudies: Study[]): void {
		const studies = this.section.siteData.studyLoader.getSortedStudyList(unsortedStudies)
		switch (this.section.sectionValue) {
			case "data":
				this.studies = studies.filter((study) => (this.hasPermission("read", study.id.get())) || this.hasPermission("readSimplified", study.id.get()))
				break
			case "edit":
				this.studies = studies.filter((study) => this.hasPermission("write", study.id.get()))
				break
			case "msgs":
				this.studies = studies.filter((study) => this.hasPermission("msg", study.id.get()))
				break
			default:
				this.studies = studies
				break
		}
	}
	private getStudiesFromObservable(studiesObs: StudiesDataType): Study[] {
		if (this.selectedOwner.get() == "~")
			return Object.values(studiesObs.get())
		else {
			const studies: Study[] = []
			for (const studyId of this.ownerRegister[this.selectedOwner.get()]) {
				const study = studiesObs.get()[studyId]
				if (study) {
					studies.push(study)
				}
			}
			return studies
		}
	}

	private getStudiesPerAccessKey(published: boolean): Record<string, Study[]> {
		const accessKeyIndex: Record<string, Study[]> = {}
		for (const id in this.studies) {
			const study = this.studies[id];
			if (study.published.get() != published)
				continue

			let studyAccessKeys = study.accessKeys.get();
			for (let i = studyAccessKeys.length - 1; i >= 0; --i) {
				const accessKey = studyAccessKeys[i].get();
				if (accessKeyIndex.hasOwnProperty(accessKey))
					accessKeyIndex[accessKey].push(study)
				else
					accessKeyIndex[accessKey] = [study]
			}
		}
		return accessKeyIndex
	}
	private getAccessKeyTab(title: string, studies: Study[], highlight: boolean = false): TabContent {
		return {
			highlight: highlight,
			title: title,
			view: () => this.getStudyListView(studies)
		}
	}
	private getAccessKeyTitle(indexedAccessKeys: string[]): string {
		switch (indexedAccessKeys.length) {
			case 1:
				return indexedAccessKeys[0]
			case 2:
				return `${indexedAccessKeys[0]}, ${indexedAccessKeys[indexedAccessKeys.length - 1]}`
			default:
				indexedAccessKeys.sort()
				return `${indexedAccessKeys[0]} ... ${indexedAccessKeys[indexedAccessKeys.length - 1]}`
		}
	}
	private getStudyBundleKey(studies: Study[]): string {
		const list = studies.map((study) => study.id.get())
		list.sort((a, b) => (a - b))
		return list.join(",")
	}
	private getAccessKeyTabList(published: boolean): TabContent[] {
		const accessKeyTabs: TabContent[] = [this.getAccessKeyTab(
			Lang.get("all"),
			this.studies.filter((study) =>
				study.published.get() == published && study.accessKeys.get().length
			),
			true
		)]

		//create an index to count number of studies using each accessKey:
		const studiesPerAccessKey = this.getStudiesPerAccessKey(published)

		//make sure each access key shows a different list of studies,
		//If not, we combine them in [exclusiveAccessKeyAndStudyCombinations]
		const exclusiveAccessKeyAndStudyCombinations: Record<string, { accessKeys: string[], studies: Study[] }> = {}
		for (const accessKey in studiesPerAccessKey) {
			const indexedStudies = studiesPerAccessKey[accessKey]
			const key = this.getStudyBundleKey(indexedStudies)
			if (!exclusiveAccessKeyAndStudyCombinations.hasOwnProperty(key))
				exclusiveAccessKeyAndStudyCombinations[key] = { accessKeys: [accessKey], studies: indexedStudies }
			else
				exclusiveAccessKeyAndStudyCombinations[key].accessKeys.push(accessKey)
		}

		//go through all combinations and create a tab (with according title)
		for (const id in exclusiveAccessKeyAndStudyCombinations) {
			const indexedAccessKeys = exclusiveAccessKeyAndStudyCombinations[id]

			accessKeyTabs.push(
				this.getAccessKeyTab(this.getAccessKeyTitle(indexedAccessKeys.accessKeys), indexedAccessKeys.studies)
			)
		}

		//For accessKey's with a single study:
		// Create an index to count how many accessKeys this study is using
		//For accessKey's for multiple studies:
		// Add them to the array
		// const exclusiveAccessKeysPerStudy: Record<number, string[]> = {}
		// for(const accessKey in studiesPerAccessKey) {
		// 	const indexedStudies = studiesPerAccessKey[accessKey]
		// 	if(indexedStudies.length > 1)
		// 		accessKeyTabs.push(this.getAccessKeyTab(accessKey, indexedStudies))
		// 	else {
		// 		//Note: Studies can have shared and exclusive accessKeys at the same time
		// 		const id = indexedStudies[0].id.get()
		// 		if(!exclusiveAccessKeysPerStudy.hasOwnProperty(id))
		// 			exclusiveAccessKeysPerStudy[id] = [accessKey]
		// 		else
		// 			exclusiveAccessKeysPerStudy[id].push(accessKey)
		// 	}
		// }
		//
		// // Add remaining accessKey's to array but combine the ones leading to the same study:
		// for(const id in exclusiveAccessKeysPerStudy) {
		// 	const indexedAccessKeys = exclusiveAccessKeysPerStudy[id]
		//
		// 	accessKeyTabs.push(
		// 		this.getAccessKeyTab(this.getAccessKeyTitle(indexedAccessKeys), [this.getStudyOrThrow(parseInt(id))])
		// 	)
		// }
		accessKeyTabs.sort(function (a, b) {
			if (a.highlight)
				return -1
			if (a.title > b.title)
				return 1
			else if (a.title == b.title)
				return 0
			else
				return -1
		})
		return accessKeyTabs
	}
	private createAccessKeyTabLists(): void {
		this.publicAccessKeysTabs = this.getAccessKeyTabList(true)
		this.disabledAccessKeysTabs = this.getAccessKeyTabList(false)
	}

	private getStudyListView(studies: Study[]): Vnode<any, any> {
		return <div class="stickerList">{
			studies.map((study) =>
				<div class={`line ${study.published.get() ? "" : "unPublishedStudy"}`}>
					<span class="title">
						{this.getStudyLinkView(study)}
						{study.isDifferent() &&
							<span class="extraNote">{Lang.get('changed')}</span>
						}
					</span>

					<span class="accessKeys">
						{study.accessKeys.get().map((accessKey) =>
							<span>
								<span class="infoSticker">{accessKey.get()}</span>
							</span>
						)}
						{this.getTools().messagesLoader.studiesWithNewMessagesList[study.id.get()] &&
							<span>
								<span class="infoSticker highlight">{Lang.get("newMessages")}</span>
							</span>
						}
						{
							this.getTools().merlinLogsLoader.studiesWithNewMerlinLogsList[study.id.get()] &&
							<span>
								<span class="infoSticker highlight">{Lang.get("new_merlin_logs")}</span>
							</span>
						}
					</span>

				</div>
			)
		}

		</div>
	}

	public getView(): Vnode<any, any> {
		if (this.studies.length == 0) {
			return <div class="center spacingTop">{Lang.get("no_studies")}</div>
		}

		const hasNewMessages = !!this.getTools().messagesLoader.studiesWithNewMessagesCount.get()
		const newMessagesList = this.getTools().messagesLoader.studiesWithNewMessagesList || {}
		const hasNewMerlinLogs = !!this.getTools().merlinLogsLoader.studiesWithNewMerlinLogsCount.get()
		const newMerlinLogsList = this.getTools().merlinLogsLoader.studiesWithNewMerlinLogsList || {}
		return TabBar(this.selectedTab, [
			{
				title: Lang.get("all"),
				highlight: true,
				view: () => this.getStudyListView(this.studies)
			},
			{
				title: m.trust(messageSvg),
				highlight: hasNewMessages,
				view: () => this.getStudyListView(this.studies.filter((study) => {
					return newMessagesList[study.id.get()]
				}))
			},
			{
				title: m.trust(merlinLogsSvg),
				highlight: hasNewMerlinLogs,
				view: () => this.getStudyListView(this.studies.filter((study) => {
					return newMerlinLogsList[study.id.get()]
				}))
			},
			{
				title: Lang.get("public_studies"),
				view: () => this.getStudyListView(this.studies.filter((study) => {
					return study.published.get() && !study.accessKeys.get().length
				}))
			},
			{
				title: Lang.get("hidden_studies"),
				view: () => {
					return TabBar(
						this.selectedPublicAccessKeyTab,
						this.publicAccessKeysTabs,
						true
					)
				}
			},
			{
				title: Lang.get("concluded_studies"),
				view: () => this.getStudyListView(this.studies.filter((study) => {
					return study.studyOver.get()
				}))
			},
			{
				title: Lang.get("disabled"),
				view: () => {
					return TabBar(
						this.selectedDisabledAccessKeyTab,
						this.disabledAccessKeysTabs,
						true
					)
				}
			},
			// {
			// 	title: Lang.get("disabled"),
			// 	view: () => this.getStudyListView(this.studies.filter((study) => {
			// 		return !study.published.get()
			// 	}))
			// }
		])
	}

	public destroy(): void {
		super.destroy()
		this.destroyImpl()
	}
}