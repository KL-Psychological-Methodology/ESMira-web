import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {TabBar, TabContent} from "../widgets/TabBar";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {Study} from "../data/study/Study";
import messageSvg from "../../imgs/icons/message.svg?raw";
import {StudiesDataType} from "../loader/StudyLoader";
import {ObserverId} from "../observable/BaseObservable";
import {Content as StudiesContent} from "../pages/studies";

export class Content extends StudiesContent {
	protected targetPage: string
	protected titleString: string
	private openedTab: number
	
	private readonly selectedTab: ObservablePrimitive<number>
	private readonly selectedAccessKeyTab: ObservablePrimitive<number>
	
	private accessKeysTabs: TabContent[] = []
	protected studies: Study[] = []
	private readonly destroyImpl2: (() => void)
	
	public static preLoad(section: Section): Promise<any>[] {
		return [
			section.getStrippedStudyListPromise()
		]
	}
	
	constructor(section: Section, studiesObs: StudiesDataType) {
		super(section, studiesObs)
		this.selectedTab = section.siteData.dynamicValues.getOrCreateObs("studiesIndex", 3)
		this.selectedAccessKeyTab = section.siteData.dynamicValues.getOrCreateObs("accessKeyIndex", 0)
		
		this.openedTab = 3
		switch(section.sectionValue) {
			case "data":
				this.targetPage = "dataStatistics"
				this.titleString = Lang.get("data")
				break
			case "msgs":
				this.targetPage = "messagesOverview"
				if(section.getTools().messagesLoader.studiesWithNewMessagesCount.get())
					this.openedTab = 0;
				this.titleString = Lang.get("messages")
				break
			case "edit":
			default:
				this.targetPage = "studyEdit"
				this.titleString = Lang.get("edit_studies")
				break
		}
		
		this.initAccessKeyIndex(studiesObs)
		
		const messagesObserverId = this.getTools().messagesLoader.studiesWithNewMessagesCount?.addObserver(() => {
			m.redraw()
		})
		const studyObserverId = studiesObs.addObserver((_origin, bubbled) => {
			if(!bubbled)
				this.initAccessKeyIndex(studiesObs)
		})
		
		this.destroyImpl2 = () => {
			messagesObserverId.removeObserver()
			studyObserverId.removeObserver()
		}
	}
	
	protected updateSortedStudies(studiesObs: StudiesDataType): void {
		const studies = this.section.siteData.studyLoader.getSortedStudyList(Object.values(studiesObs.get()))
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
	private initAccessKeyIndex(studiesObs: StudiesDataType): void {
		this.updateSortedStudies(studiesObs)
		
		const createTab = (title: string, accessKey: string) => {
			return {
				title: title,
				view: () => this.getStudyListView(this.studies.filter((study) => {
					return study.accessKeys.indexOf(accessKey) != -1
				}))
			} as TabContent
		}
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
				accessKeyTabs.push(createTab(accessKey, accessKey))
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
			let title: string
			switch(indexedAccessKeys.length) {
				case 1:
					title = indexedAccessKeys[0]
					break
				case 2:
					title = `${indexedAccessKeys[0]}, ${indexedAccessKeys[indexedAccessKeys.length-1]}`
					break
				default:
					title = `${indexedAccessKeys[0]} ... ${indexedAccessKeys[indexedAccessKeys.length-1]}`
					break
			}
			accessKeyTabs.push(
				createTab(title, indexedAccessKeys[0])
			)
		}
		
		this.accessKeysTabs = accessKeyTabs
	}
	
	private getStudyListView(studies: Study[]): Vnode<any, any> {
		return <div class="studyList">{
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
		this.destroyImpl2()
	}
}