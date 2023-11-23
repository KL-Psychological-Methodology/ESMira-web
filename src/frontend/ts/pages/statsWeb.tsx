import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {FILE_RESPONSES} from "../constants/urls";
import {CsvLoader} from "../loader/csv/CsvLoader";
import {ChartData} from "../data/study/ChartData";
import {
	CONDITION_OPERATOR_GREATER,
	CONDITION_OPERATOR_LESS,
	CONDITION_TYPE_AND,
	STATISTICS_CHARTTYPES_BARS, STATISTICS_CHARTTYPES_PIE,
	STATISTICS_DATATYPES_FREQ_DISTR,
	STATISTICS_VALUETYPES_COUNT
} from "../constants/statistics";
import {getChartColor} from "../helpers/ChartJsBox";
import {LoadedStatistics} from "../loader/csv/CsvLoaderCollectionFromCharts";
import {ObservablePromise} from "../observable/ObservablePromise";
import {ChartView} from "../widgets/ChartView";
import {JsonTypes} from "../observable/types/JsonTypes";
import {SearchBox, SearchBoxEntry} from "../widgets/SearchBox";
import {ValueListInfo} from "../loader/csv/ValueListInfo";
import {BindObservable} from "../widgets/BindObservable";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {BtnReload} from "../widgets/BtnWidgets";

export class Content extends SectionContent {
	private csvLoader: CsvLoader
	
	private monthsCount: ObservablePrimitive<number> = new ObservablePrimitive<number>(3, null, "days")
	
	private refererList: ValueListInfo[] = []
	private userAgentList: ValueListInfo[] = []
	
	
	private readonly totalChart: ChartData
	private readonly perMonthsChart: ChartData
	
	private readonly totalChartPromise: ObservablePromise<LoadedStatistics>
	private readonly perMonthsPromise: ObservablePromise<LoadedStatistics>
	
	public static preLoad(section: Section): Promise<any>[] {
		const url = FILE_RESPONSES.replace('%1', (section.getStaticInt("id") ?? 0).toString()).replace('%2', 'web_access');
		return [
			CsvLoader.fromUrl(section.loader, url),
			section.getStudyPromise()
		]
	}
	
	constructor(section: Section, csvLoader: CsvLoader) {
		super(section)
		this.csvLoader = csvLoader
		
		this.totalChart = this.createPieChartData(Lang.get("total_pageViews"))
		this.perMonthsChart = this.createPerMonthChartData(Lang.get("monthly_pageViews"), this.monthsCount.get())
		
		const tempPromise = Promise.resolve({mainStatistics: {}})
		this.totalChartPromise = new ObservablePromise<LoadedStatistics>(tempPromise, null, "totalChartPromise")
		this.perMonthsPromise = new ObservablePromise<LoadedStatistics>(tempPromise, null, "perMonthsPromise")
		
		this.monthsCount.addObserver(this.reloadDynamicStatistics.bind(this))
	}
	
	public async preInit(): Promise<any> {
		await this.loadFixedStatistics()
		await this.reloadDynamicStatistics()
	}
	
	public title(): string {
		return Lang.get("web_access")
	}
	public titleExtra(): Vnode<any, any> | null {
		return <div>
			<label class="noTitle noDesc spacingRight">
				<span>{Lang.getWithColon("months")}</span>
				<input type="number" {... BindObservable(this.monthsCount)}/>
			</label>
			{BtnReload(this.section.reload.bind(this.section), Lang.get("reload"))}
		</div>
	}
	
	private createPieChartData(title: string): ChartData {
		return new ChartData(
			{
				title: title,
				axisContainer: [{
					label: "page",
					xAxis: {
						conditions: []
					},
					yAxis: {
						conditions: [],
						variableName: "page",
						observedVariableIndex: 0
					}
				}],
				valueType: STATISTICS_VALUETYPES_COUNT,
				dataType: STATISTICS_DATATYPES_FREQ_DISTR,
				chartType: STATISTICS_CHARTTYPES_PIE
			},
			null,
			"chartTemp"
		)
	}
	
	
	private getDayPerMonthAxisContainerCode(monthsNum: number): Record<string, JsonTypes>[] {
		const date = new Date()
		let currentDate = date.getTime()
		let month = date.getMonth()
		let year = date.getFullYear()
		date.setHours(0, 0, 0, 0)
		date.setFullYear(year, month, 1)
		
		const axisContainerArray: Record<string, JsonTypes>[] = []
		
		for(let i=0; i<monthsNum; ++i) {
			const previousDate = currentDate
			currentDate = date.getTime()
			axisContainerArray.push({
				xAxis: {
					conditions: []
				},
				yAxis: {
					conditions: [
						{
							key: "responseTime",
							value: previousDate,
							operator: CONDITION_OPERATOR_LESS
						},
						{
							key: "responseTime",
							value: currentDate,
							operator: CONDITION_OPERATOR_GREATER
						}
					],
					variableName: "page",
					observedVariableIndex: i,
					conditionType: CONDITION_TYPE_AND
				},
				label: date.toLocaleString('default', { month: 'long' }),
				color: getChartColor(i)
			})
			
			
			if(--month <0) {
				year -= 1
				month = 11
				date.setFullYear(year)
				date.setMonth(month)
			}
			else
				date.setMonth(month)
		}
		
		return axisContainerArray
	}
	private createPerMonthChartData(title: string, monthsNum: number): ChartData {
		return new ChartData(
			{
				title: title,
				axisContainer: this.getDayPerMonthAxisContainerCode(monthsNum),
				valueType: STATISTICS_VALUETYPES_COUNT,
				dataType: STATISTICS_DATATYPES_FREQ_DISTR,
				chartType: STATISTICS_CHARTTYPES_BARS
			},
			null,
			"chartTemp"
		)
	}
	
	private async loadFixedStatistics(): Promise<void> {
		this.refererList = await this.csvLoader.getValueListInfo("referer", true)
		this.userAgentList = await this.csvLoader.getValueListInfo("userAgent", true)
		this.totalChartPromise.set(this.csvLoader.getPersonalStatisticsFromChart(this.totalChart))
	}
	
	private async reloadDynamicStatistics(): Promise<void> {
		this.perMonthsChart.axisContainer.replace(this.getDayPerMonthAxisContainerCode(this.monthsCount.get()))
		this.perMonthsPromise.set(this.csvLoader.getPersonalStatisticsFromChart(this.perMonthsChart))
	}
	
	
	public getView(): Vnode<any, any> {
		return DashRow(
			DashElement(null, {
				content: ChartView(this.perMonthsChart, this.perMonthsPromise)
			}),
			DashElement(null, {
				content: ChartView(this.totalChart, this.totalChartPromise)
			}),
			DashElement("stretched", {
				content: SearchBox(Lang.get("referer_with_count", this.refererList.length), this.refererList.map((valueList) =>
					this.getValueListView(valueList))
				)
			}),
			DashElement("stretched", {
				content: SearchBox(Lang.get("userAgent_with_count", this.userAgentList.length), this.userAgentList.map((valueList) =>
					this.getValueListView(valueList))
				)
			}),
		)
	}
	private getValueListView(valueList: ValueListInfo): SearchBoxEntry {
		return {
			key: valueList.name,
			view: <div class="verticalPadding smallText nowrap">
				<a class="searchTarget" target="_blank" href={valueList.name}>{valueList.name}</a>
				<span class="bold">&nbsp;({valueList.count})</span>
			</div>
		}
	}
}