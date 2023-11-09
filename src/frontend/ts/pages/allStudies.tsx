import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {TabBar, TabContent} from "../widgets/TabBar";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {Study} from "../data/study/Study";
import messageSvg from "../../imgs/icons/message.svg?raw";
import {StudiesDataType} from "../loader/StudyLoader";
import {Content as StudiesContent} from "../pages/studies";
import {SectionAlternative} from "../site/SectionContent";
import {BindObservable} from "../widgets/BindObservable";

export class Content extends StudiesContent {
	protected targetPage: string
	protected titleString: string
	
	private readonly selectedTab: ObservablePrimitive<number>
	private readonly selectedAccessKeyTab: ObservablePrimitive<number>
	private readonly selectedOwner: ObservablePrimitive<string>
	
	private readonly ownerRegister: Record<string, Study[]>
	private accessKeysTabs: TabContent[] = []
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
		this.selectedAccessKeyTab = section.siteData.dynamicValues.getOrCreateObs("accessKeyIndex", 0)
		
		this.selectedTab = section.siteData.dynamicValues.getOrCreateObs("studiesIndex", 3)
		switch(section.sectionValue) {
			case "data":
				this.targetPage = "dataStatistics"
				this.titleString = Lang.get("data")
				break
			case "msgs":
				this.targetPage = "messagesOverview"
				if(section.getTools().messagesLoader.studiesWithNewMessagesCount.get())
					this.selectedTab.set(1)
				this.titleString = Lang.get("messages")
				break
			case "edit":
			default:
				this.targetPage = "studyEdit"
				this.titleString = Lang.get("edit_studies")
				break
		}
		
		this.initAccessKeyIndex(studiesObs)
		
		this.selectedOwner.addObserver(() => {
			this.selectedAccessKeyTab.set(0)
			this.initAccessKeyIndex(studiesObs)
		})
		const messagesObserverId = this.getTools().messagesLoader.studiesWithNewMessagesCount?.addObserver(() => {
			m.redraw()
		})
		const studyObserverId = studiesObs.addObserver((_origin, bubbled) => {
			if(!bubbled) {
				this.initAccessKeyIndex(studiesObs)
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
		if(ownerList.length > 1) {
			return <div>
				<select class="ownerSelector" {...BindObservable(this.selectedOwner)}>
					<option value="~">{Lang.get("all_user")}</option>
					{ownerList.map((name) =>
						<option>{name}</option>
					)}
				</select>
			</div>
		}
		else
			return null
	}
	
	public hasAlternatives(): boolean {
		return true
	}
	public getAlternatives(): SectionAlternative[] | null {
		const allSections = this.section.allSections
		const depth = this.section.depth
		const id = allSections.length > depth+1 ? allSections[depth+1].getStaticInt("id") : null
		
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
		switch(this.section.sectionValue) {
			case "data":
				this.studies = studies.filter((study) => this.hasPermission("read", study.id.get()))
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
	
	private createAccessKeyIndex(): Record<string, Study[]> {
		const accessKeyIndex: Record<string, Study[]> = {}
		for(const id in this.studies) {
			const study = this.studies[id];
			if(!study.published.get())
				continue
			
			let studyAccessKeys = study.accessKeys.get();
			for(let i = studyAccessKeys.length - 1; i >= 0; --i) {
				const accessKey = studyAccessKeys[i].get();
				if(accessKeyIndex.hasOwnProperty(accessKey))
					accessKeyIndex[accessKey].push(study)
				else
					accessKeyIndex[accessKey] = [study]
			}
		}
		return accessKeyIndex
	}
	
	private createAccessKeyTab(title: string, accessKey: string): TabContent {
		return {
			title: title,
			view: () => this.getStudyListView(this.studies.filter((study) => {
				return study.accessKeys.indexOf(accessKey) != -1
			}))
		}
	}
	private getAccessKeyTitle(indexedAccessKeys: string[]): string {
		switch(indexedAccessKeys.length) {
			case 1:
				return indexedAccessKeys[0]
			case 2:
				return `${indexedAccessKeys[0]}, ${indexedAccessKeys[indexedAccessKeys.length-1]}`
			default:
				return `${indexedAccessKeys[0]} ... ${indexedAccessKeys[indexedAccessKeys.length-1]}`
		}
	}
	private initAccessKeyIndex(studiesObs: StudiesDataType): void {
		this.updateSortedStudies(this.selectedOwner.get() == "all" ? Object.values(studiesObs.get()) : this.ownerRegister[this.selectedOwner.get()])
		
		const accessKeyTabs: TabContent[] = [{
			title: Lang.get("all"),
			highlight: true,
			view: () => this.getStudyListView(this.studies.filter((study) => {
				return study.published.get() && study.accessKeys.get().length
			}))
		}]
		
		//create an index to count number of studies using each accessKey:
		const accessKeyIndex = this.createAccessKeyIndex()
		
		
		//For accessKey's with a single study:
		// Create an index to count how many accessKeys this study is using
		//For accessKey's for multiple studies:
		// Add them to the array
		const studyAccessKeyIndex: Record<number, string[]> = {}
		for(const accessKey in accessKeyIndex) {
			const indexedStudies = accessKeyIndex[accessKey]
			if(indexedStudies.length > 1) {
				accessKeyTabs.push(this.createAccessKeyTab(accessKey, accessKey))
				continue
			}
			
			const id = indexedStudies[0].id.get()
			if(!studyAccessKeyIndex.hasOwnProperty(id))
				studyAccessKeyIndex[id] = [accessKey]
			else
				studyAccessKeyIndex[id].push(accessKey)
		}
		
		// Add remaining accessKey's to array but combine the ones leading to the same study:
		for(const id in studyAccessKeyIndex) {
			const indexedAccessKeys = studyAccessKeyIndex[id]
			
			accessKeyTabs.push(
				this.createAccessKeyTab(this.getAccessKeyTitle(indexedAccessKeys), indexedAccessKeys[0])
			)
		}
		accessKeyTabs.sort(function(a, b) {
			if(a.highlight)
				return -1
			if(a.title > b.title)
				return 1
			else if(a.title == b.title)
				return 0
			else
				return -1
		})
		this.accessKeysTabs = accessKeyTabs
	}
	
	private getStudyListView(studies: Study[]): Vnode<any, any> {
		return <div class="stickerList">{
			studies.map((study) =>
				<div class={`line ${study.published.get() ? "" : "unPublishedStudy"}`}>
					<span class="title">
						{this.getStudyLinkView(study)}
						{ study.isDifferent() &&
							<span class="extraNote">{Lang.get('changed')}</span>
						}
					</span>
					
					<span class="accessKeys">
						{ study.accessKeys.get().map((accessKey) =>
								<span>
									<span class="infoSticker">{accessKey.get()}</span>
								</span>
						)}
						{ this.getTools().messagesLoader.studiesWithNewMessagesList[study.id.get()] &&
							<span>
								<span class="infoSticker highlight">{Lang.get("newMessages")}</span>
							</span>
						}
					</span>
					
				</div>
			)
		}
		
		</div>
	}
	
	public getView(): Vnode<any, any> {
		if(this.studies.length == 0) {
			return <div class="center spacingTop">{Lang.get("no_studies")}</div>
		}
		
		const hasNewMessages = !!this.getTools().messagesLoader.studiesWithNewMessagesCount.get()
		const newMessagesList = this.getTools().messagesLoader.studiesWithNewMessagesList || {}
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
				title: Lang.get("public_studies"),
				view: () => this.getStudyListView(this.studies.filter((study) => {
					return study.published.get() && !study.accessKeys.get().length
				}))
			},
			{
				title: Lang.get("hidden_studies"),
				view: () => {
					return TabBar(
						this.selectedAccessKeyTab,
						this.accessKeysTabs,
						true
					)
				}
			},
			{
				title: Lang.get("disabled"),
				view: () => this.getStudyListView(this.studies.filter((study) => {
					return !study.published.get()
				}))
			}
		])
	}
	
	public destroy(): void {
		super.destroy()
		this.destroyImpl()
	}
}