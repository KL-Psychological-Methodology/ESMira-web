import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {TitleRow} from "../widgets/TitleRow";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {FILE_RESPONSES} from "../constants/urls";
import {CsvLoader} from "../loader/csv/CsvLoader";
import {ChartData} from "../data/study/ChartData";
import {CsvLoaderCollectionFromCharts, LoadedStatistics} from "../loader/csv/CsvLoaderCollectionFromCharts";
import {ObservablePromise} from "../observable/ObservablePromise";
import {ChartView} from "../widgets/ChartView";
import {SearchBox} from "../widgets/SearchBox";
import {ValueListInfo} from "../loader/csv/ValueListInfo";
import {StatisticsCollection} from "../data/statistics/StatisticsCollection";
import {Study} from "../data/study/Study";
import {AxisContainer} from "../data/study/AxisContainer";
import {BtnReload} from "../widgets/BtnWidgets";

export class Content extends SectionContent {
	private readonly csvLoader: CsvLoader
	private readonly personalStatisticsCsvLoaderCollection: CsvLoaderCollectionFromCharts
	private readonly enableGroupStatistics: boolean
	private publicStatistics?: StatisticsCollection
	
	private participantList: { name: string, count: number }[] = []
	private groupList: ValueListInfo[] = []
	private timezoneList: ValueListInfo[] = []
	private appTypeList: ValueListInfo[] = []
	private modelList: ValueListInfo[] = []
	private joinedTimeList: ValueListInfo[] = []
	private quitTimeList: ValueListInfo[] = []
	private currentParticipant = ""
	private isLoading: boolean = false
	
	private readonly joinedPerDayChart: ChartData
	
	private readonly joinedPerDayPromise: ObservablePromise<LoadedStatistics>
	private readonly personalChartPromises: ObservablePromise<LoadedStatistics>[]
	
	public static preLoad(section: Section): Promise<any>[] {
		const url = FILE_RESPONSES.replace('%1', (section.getStaticInt("id") ?? 0).toString()).replace('%2', 'events');
		return [
			CsvLoader.fromUrl(section.loader, url),
			section.getStudyPromise()
		]
	}
	
	constructor(section: Section, csvLoader: CsvLoader, study: Study) {
		super(section)
		this.csvLoader = csvLoader
		this.personalStatisticsCsvLoaderCollection = new CsvLoaderCollectionFromCharts(section.loader, this.getStudyOrThrow())
		
		this.enableGroupStatistics = csvLoader.hasColumn("group")
		
		const tempPromise = Promise.resolve({mainStatistics: {}})
		this.joinedPerDayChart = ChartData.createPerDayChartData(Lang.get("questionnaires"))
		this.joinedPerDayPromise = new ObservablePromise<LoadedStatistics>(tempPromise, null, "questionnairePerDayPromise")
		
		this.personalChartPromises = study.personalStatistics.charts.get().map(
			(_chart, index) => new ObservablePromise<LoadedStatistics>(tempPromise, null, `personalChart${index}`)
		)
	}
	
	public async preInit(): Promise<void> {
		await this.personalStatisticsCsvLoaderCollection.setupLoadersForCharts(this.getStudyOrThrow().personalStatistics.charts.get())
		await this.loadParticipants()
		const userId = this.getStaticString("userId")
		if(userId)
			await this.selectParticipant(atob(userId))
		
		window.setTimeout(() => {
			const line = document.getElementsByClassName("currentParticipant")
			if(line[0])
				line[0].scrollIntoView({behavior: "smooth", block: "nearest"})
		}, 500)
	}
	
	public title(): string {
		return Lang.get("participants")
	}
	public titleExtra(): Vnode<any, any> | null {
		return BtnReload(this.section.reload.bind(this.section), Lang.get("reload"))
	}
	
	private async loadParticipants(): Promise<void> {
		await this.csvLoader.filterEntireColumn(false, "eventType")
		await this.csvLoader.filterByValue(true, "eventType", "questionnaire")
		const fullList = await this.csvLoader.getValueCellList("userId")
		
		for(const value in fullList) {
			this.participantList.push({
				name: value,
				count: await this.csvLoader.getVisibleCount("userId", value)
			})
		}
		this.participantList.sort((a, b) => {
			if(a.count == b.count) {
				if(a.name < b.name)
					return -1
				else if(a.name > b.name)
					return 1
				else
					return 0
			}
			if(a.count < b.count)
				return 1
			else
				return -1
		})
	}
	
	private async selectParticipant(userId: string): Promise<void> {
		this.isLoading = true
		await this.csvLoader.reset()
		
		await this.csvLoader.filterEntireColumn(false, "userId")
		await this.csvLoader.filterByValue(true, "userId", userId)
		
		this.timezoneList = await this.csvLoader.getValueListInfo("timezone")
		this.appTypeList = await this.csvLoader.getValueListInfo("appType")
		this.modelList = await this.csvLoader.getValueListInfo("model")
		if(this.enableGroupStatistics)
			this.groupList = await this.csvLoader.getValueListInfo("group")
		
		await this.csvLoader.filterEntireColumn(false, "eventType")
		await this.csvLoader.filterByValue(true, "eventType", "questionnaire")
		
		this.joinedPerDayChart.axisContainer.replace(await AxisContainer.getPerDayAxisCodeFromValueList(this.csvLoader, "questionnaireName"))
		this.joinedPerDayPromise.set(this.csvLoader.getPersonalStatisticsFromChart(this.joinedPerDayChart))
		
		
		await this.csvLoader.filterByValue(false, "eventType", "questionnaire")
		await this.csvLoader.filterByValue(true, "eventType", "joined")
		this.joinedTimeList = await this.csvLoader.getValueListInfo("responseTime")
		await this.csvLoader.filterByValue(false, "eventType", "joined")
		
		await this.csvLoader.filterByValue(true, "eventType", "quit")
		this.quitTimeList = await this.csvLoader.getValueListInfo("responseTime")
		
		this.section.loader.update(Lang.get("state_loading_file", Lang.get("statistics")))
		
		
		const loadedStatisticsData = await this.personalStatisticsCsvLoaderCollection.loadStatisticsFromFiles(userId, !!this.publicStatistics)
		
		
		//we only load public statistics when loading it the first time. For all the other times, we reuse the cached version:
		if(loadedStatisticsData.additionalStatistics)
			this.publicStatistics = loadedStatisticsData.additionalStatistics
		const statisticsData = {
			mainStatistics: loadedStatisticsData.mainStatistics,
			additionalStatistics: this.publicStatistics
		}
		
		this.personalChartPromises.forEach((promise) => {
			promise.set(Promise.resolve(statisticsData))
		})
		
		this.currentParticipant = userId
		this.isLoading = false
		
		m.redraw()
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		const participantList = DashElement(null, {
			content: SearchBox(Lang.get("participants_with_count", this.participantList.length), this.participantList.map((valueListInfo) => {
				return {
					key: valueListInfo.name,
					view:
						<div
							class={`clickable verticalPadding searchTarget smallText ${this.currentParticipant == valueListInfo.name ? "highlight currentParticipant" : ""}`}
							onclick={this.selectParticipant.bind(this, valueListInfo.name)}
						>{Lang.get("text_with_questionnaireCount", valueListInfo.name, valueListInfo.count)}</div>
				}
			}))
		})
		if(this.currentParticipant) {
			return <div class={this.isLoading ? "fadeOut" : "fadeIn"}>
				{DashRow(
					participantList,
					DashElement("vertical",
						this.enableGroupStatistics && {content: this.getListEntryView(Lang.getWithColon("group"), this.groupList)},
						{content: this.getListEntryView(Lang.getWithColon("timezone"), this.timezoneList)},
						{content: this.getListEntryView(Lang.getWithColon("app_type"), this.appTypeList)},
						{content: this.getListEntryView(Lang.getWithColon("model"), this.modelList)}
					),
					DashElement(null, {content: this.getListEntryView(Lang.getWithColon("joined_study"), this.joinedTimeList)}),
					DashElement(null, {content: this.getListEntryView(Lang.getWithColon("quit_study"), this.quitTimeList)}),
					DashElement("stretched", {
						content: ChartView(this.joinedPerDayChart, this.joinedPerDayPromise)
					})
				)}
				
				{TitleRow(Lang.getWithColon("personal_charts_for_x", this.currentParticipant))}
				{
					study.personalStatistics.charts.get().map((chartData, index) => {
						return ChartView(chartData, this.personalChartPromises[index])
					})
				}
			</div>
		}
		else {
			return DashRow(
				participantList
			)
		}
	}
	
	private getListEntryView(header: string, list: ValueListInfo[]): Vnode<any, any> {
		return <div>
			<h2 class="spacingLeft">{header}</h2>
			<div class="horizontalPadding center">
				{list.map((info) =>
					<div class="verticalPadding">{info.name}</div>)
				}
			</div>
		</div>
	}
}