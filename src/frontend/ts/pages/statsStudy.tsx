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
import {
	CONDITION_OPERATOR_GREATER,
	CONDITION_OPERATOR_LESS,
	CONDITION_TYPE_AND,
	STATISTICS_CHARTTYPES_BARS, STATISTICS_CHARTTYPES_PIE,
	STATISTICS_DATATYPES_FREQ_DISTR, STATISTICS_DATATYPES_SUM,
	STATISTICS_VALUETYPES_COUNT
} from "../constants/statistics";
import {getChartColor} from "../helpers/ChartJsBox";
import {LoadedStatistics} from "../loader/csv/CsvLoaderCollectionFromCharts";
import {ObservablePromise} from "../observable/ObservablePromise";
import {ChartView} from "../widgets/ChartView";
import {JsonTypes} from "../observable/types/JsonTypes";
import {SearchBox} from "../widgets/SearchBox";
import {ValueListInfo} from "../loader/csv/ValueListInfo";
import {BindObservable} from "../widgets/BindObservable";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {BtnReload} from "../widgets/BtnWidgets";

const ONE_DAY_MS = 86400000
export class Content extends SectionContent {
	private csvLoader: CsvLoader
	private readonly enableGroupStatistics: boolean
	private days: ObservablePrimitive<number> = new ObservablePrimitive<number>(3, null, "days")
	
	private questionnairesTotalCount: number = 0
	private joinedTotalCount: number = 0
	private quitTotalCount: number = 0
	
	private modelsList: ValueListInfo[] = []
	
	private readonly appTypeChart: ChartData
	private readonly questionnairesChart?: ChartData
	private readonly joinedPerDayChart: ChartData
	private readonly groupJoinedChart?: ChartData
	private readonly quitPerDayChart: ChartData
	private readonly groupQuitChart?: ChartData
	private readonly questionnairePerDayChart: ChartData
	private readonly groupQuestionnairePerDayChart: ChartData
	private readonly appVersionPerDayChart: ChartData
	private readonly studyVersionPerDayChart: ChartData
	
	private readonly appTypePromise: ObservablePromise<LoadedStatistics>
	private readonly questionnairesPromise: ObservablePromise<LoadedStatistics>
	private readonly questionnairePerDayPromise: ObservablePromise<LoadedStatistics>
	private readonly joinedPerDayPromise: ObservablePromise<LoadedStatistics>
	private readonly groupJoinedPromise: ObservablePromise<LoadedStatistics>
	private readonly quitPerDayPromise: ObservablePromise<LoadedStatistics>
	private readonly groupQuitPromise: ObservablePromise<LoadedStatistics>
	private readonly appVersionPerDayPromise: ObservablePromise<LoadedStatistics>
	private readonly studyVersionPerDayPromise: ObservablePromise<LoadedStatistics>
	
	public static preLoad(section: Section): Promise<any>[] {
		const url = FILE_RESPONSES.replace('%1', (section.getStaticInt("id") ?? 0).toString()).replace('%2', 'events');
		return [
			CsvLoader.fromUrl(section.loader, url),
			section.getStudyPromise()
		]
	}
	
	constructor(section: Section, csvLoader: CsvLoader) {
		super(section)
		this.csvLoader = csvLoader
		this.enableGroupStatistics = csvLoader.hasColumn("group")
		
		const tempPromise = Promise.resolve({mainStatistics: {}})
		this.appTypePromise = new ObservablePromise<LoadedStatistics>(tempPromise, null, "appTypePromise")
		this.questionnairesPromise = new ObservablePromise<LoadedStatistics>(tempPromise, null, "questionnairesPromise")
		this.questionnairePerDayPromise = new ObservablePromise<LoadedStatistics>(tempPromise, null, "questionnairePerDayPromise")
		this.joinedPerDayPromise = new ObservablePromise<LoadedStatistics>(tempPromise, null, "joinedPerDayPromise")
		this.groupJoinedPromise = new ObservablePromise<LoadedStatistics>(tempPromise, null, "groupJoinedPromise")
		this.quitPerDayPromise = new ObservablePromise<LoadedStatistics>(tempPromise, null, "quitPerDayPromise")
		this.groupQuitPromise = new ObservablePromise<LoadedStatistics>(tempPromise, null, "groupQuitPromise")
		this.appVersionPerDayPromise = new ObservablePromise<LoadedStatistics>(tempPromise, null, "appVersionPerDayPromise")
		this.studyVersionPerDayPromise = new ObservablePromise<LoadedStatistics>(tempPromise, null, "studyVersionPerDayPromise")
		
		this.appTypeChart = this.createSumChartData(Lang.get("app_type_per_questionnaire"), "appType", STATISTICS_CHARTTYPES_PIE)
		if(this.enableGroupStatistics) {
			this.questionnairesChart = this.createSumChartData(Lang.get("questionnaires_per_group"), "group", STATISTICS_CHARTTYPES_PIE)
			this.joinedPerDayChart = this.createPerDayChartData(Lang.get("joined_study"), STATISTICS_DATATYPES_FREQ_DISTR, "group", this.days.get())
			this.groupJoinedChart = this.createSumChartData(Lang.get("joined_per_group"), "group", STATISTICS_CHARTTYPES_PIE)
			this.quitPerDayChart = this.createPerDayChartData(Lang.get("quit_study"), STATISTICS_DATATYPES_FREQ_DISTR, "group", this.days.get())
			this.groupQuitChart = this.createSumChartData(Lang.get("quit_per_group"), "group", STATISTICS_CHARTTYPES_PIE)
		}
		else {
			this.joinedPerDayChart = this.createPerDayChartData(Lang.get("joined_study"), STATISTICS_DATATYPES_SUM, "userId", this.days.get())
			this.quitPerDayChart = this.createPerDayChartData(Lang.get("quit_study"), STATISTICS_DATATYPES_SUM, "userId", this.days.get())
		}
		
		this.questionnairePerDayChart = this.createPerDayChartData(Lang.get("questionnaires"), STATISTICS_DATATYPES_FREQ_DISTR, "questionnaireName", this.days.get())
		this.groupQuestionnairePerDayChart = this.createPerDayChartData(Lang.get("questionnaires_per_group"), STATISTICS_DATATYPES_FREQ_DISTR, "group", this.days.get())
		this.appVersionPerDayChart = this.createPerDayChartData(Lang.get("used_app_version"), STATISTICS_DATATYPES_FREQ_DISTR, "appVersion", this.days.get())
		this.studyVersionPerDayChart = this.createPerDayChartData(Lang.get("used_study_version"), STATISTICS_DATATYPES_FREQ_DISTR, "studyVersion", this.days.get())
		
		this.days.addObserver(this.reloadDynamicStatistics.bind(this))
	}
	
	public async preInit(): Promise<void> {
		await this.createFixedStatistics()
		await this.reloadDynamicStatistics()
	}
	
	public title(): string {
		return Lang.get("summary")
	}
	public titleExtra(): Vnode<any, any> | null {
		return <div>
			<label class="noTitle noDesc spacingRight">
				<span>{Lang.getWithColon("days")}</span>
				<input type="number" {... BindObservable(this.days)}/>
			</label>
			{BtnReload(this.section.reload.bind(this.section), Lang.get("reload"))}
		</div>
	}
	
	private getDayChartAxisContainerCode(variableName: string, days: number): Record<string, JsonTypes>[] {
		let day = Math.ceil(Date.now() / ONE_DAY_MS) * ONE_DAY_MS + (new Date).getTimezoneOffset()
		const axisContainerArray: Record<string, JsonTypes>[] = []
		for(let i = 0; i < days; ++i) {
			let label: string
			if(!i)
				label = Lang.get("today")
			else if(i == 1)
				label = Lang.get("yesterday")
			else
				label = Lang.get("x_days_ago", i)
			
			const dayValue = day.toString()
			day -= ONE_DAY_MS
			const dayBeforeValue = day.toString()
			axisContainerArray.push({
				xAxis: {
					conditions: []
				},
				yAxis: {
					conditions: [
						{
							key: "responseTime",
							value: dayValue,
							operator: CONDITION_OPERATOR_LESS
						},
						{
							key: "responseTime",
							value: dayBeforeValue,
							operator: CONDITION_OPERATOR_GREATER
						}
					],
					variableName: variableName,
					observedVariableIndex: i,
					conditionType: CONDITION_TYPE_AND
				},
				label: label,
				color: getChartColor(i)
			})
		}
		return axisContainerArray
	}
	private createPerDayChartData(title: string, dataType: number, variableName: string, days: number): ChartData {
		return ChartData.createPerDayChartData(title, this.getDayChartAxisContainerCode(variableName, days), dataType)
	}
	
	private createSumChartData(title: string, variableName: string, chartType: number): ChartData {
		return new ChartData(
			{
				title: title,
				axisContainer: [{
					xAxis: {
						conditions: []
					},
					yAxis: {
						conditions: [],
						variableName: variableName,
						observedVariableIndex: 0
					},
					label: variableName
				}],
				valueType: STATISTICS_VALUETYPES_COUNT,
				dataType: STATISTICS_DATATYPES_FREQ_DISTR,
				chartType: chartType
			},
			null,
			"chartTemp"
		)
	}
	
	private updateCharts(): void {
		if(this.enableGroupStatistics) {
			this.joinedPerDayChart.axisContainer.replace(this.getDayChartAxisContainerCode("group", this.days.get()), true)
			this.quitPerDayChart.axisContainer.replace(this.getDayChartAxisContainerCode("group", this.days.get()), true)
		}
		else {
			this.joinedPerDayChart.axisContainer.replace(this.getDayChartAxisContainerCode("userId", this.days.get()), true)
			this.quitPerDayChart.axisContainer.replace(this.getDayChartAxisContainerCode("userId", this.days.get()), true)
		}
		this.questionnairePerDayChart.axisContainer.replace(this.getDayChartAxisContainerCode("questionnaireName", this.days.get()), true)
		this.groupQuestionnairePerDayChart.axisContainer.replace(this.getDayChartAxisContainerCode("group", this.days.get()), true)
		this.appVersionPerDayChart.axisContainer.replace(this.getDayChartAxisContainerCode("appVersion", this.days.get()), true)
		this.studyVersionPerDayChart.axisContainer.replace(this.getDayChartAxisContainerCode("studyVersion", this.days.get()), true)
	}
	
	private async createFixedStatistics(): Promise<void> {
		const eventTypeValueCount = await this.csvLoader.getValueCount("eventType", ["questionnaire", "joined", "quit"])
		this.questionnairesTotalCount = eventTypeValueCount.questionnaire
		await this.csvLoader.filterEntireColumn(false, "eventType")
		await this.csvLoader.filterByValue(true, "eventType", "questionnaire")

		if(this.enableGroupStatistics)
			this.questionnairesPromise.set(this.csvLoader.getPersonalStatisticsFromChart(this.questionnairesChart!))
		else {
			this.joinedTotalCount = eventTypeValueCount.joined
			this.quitTotalCount = eventTypeValueCount.quit
		}

		this.appTypePromise.set(this.csvLoader.getPersonalStatisticsFromChart(this.appTypeChart))
		this.modelsList = await this.csvLoader.getValueListInfo("model", true)
	}
	
	private async reloadDynamicStatistics(): Promise<void> {
		this.updateCharts()
		await this.csvLoader.reset()
		
		const day = Date.now() - (ONE_DAY_MS * this.days.get())
		await this.csvLoader.filterRowsByResponseTime(false, day)
		
		this.questionnairePerDayPromise.setValue(await this.csvLoader.getPersonalStatisticsFromChart(this.questionnairePerDayChart))
		if(this.enableGroupStatistics)
			this.questionnairePerDayPromise.setValue(await this.csvLoader.getPersonalStatisticsFromChart(this.groupQuestionnairePerDayChart))
		
		await this.csvLoader.filterByValue(false, "eventType", "questionnaire")
		await this.csvLoader.filterByValue(true, "eventType", "joined")
		
		if(this.enableGroupStatistics) {
			this.joinedPerDayPromise.setValue(await this.csvLoader.getPersonalStatisticsFromChart(this.joinedPerDayChart))
			this.groupJoinedPromise.setValue(await this.csvLoader.getPersonalStatisticsFromChart(this.groupJoinedChart!))
		}
		else
			this.joinedPerDayPromise.setValue(await this.csvLoader.getPersonalStatisticsFromChart(this.joinedPerDayChart))
		
		await this.csvLoader.filterByValue(false, "eventType", "joined")
		await this.csvLoader.filterByValue(true, "eventType", "quit")
		
		if(this.enableGroupStatistics) {
			this.quitPerDayPromise.setValue(await this.csvLoader.getPersonalStatisticsFromChart(this.quitPerDayChart))
			this.groupQuitPromise.setValue(await this.csvLoader.getPersonalStatisticsFromChart(this.groupQuitChart!))
		}
		else
			this.quitPerDayPromise.setValue(await this.csvLoader.getPersonalStatisticsFromChart(this.quitPerDayChart))
		
		await this.csvLoader.filterByValue(false, "eventType", "quit")
		await this.csvLoader.filterEntireColumn(true, "eventType")
		
		this.appVersionPerDayPromise.setValue(await this.csvLoader.getPersonalStatisticsFromChart(this.appVersionPerDayChart))
		this.studyVersionPerDayPromise.setValue(await this.csvLoader.getPersonalStatisticsFromChart(this.studyVersionPerDayChart))
	}
	
	
	public getView(): Vnode<any, any> {
		return <div>
			{TitleRow(Lang.getWithColon("joined_study"))}
			{DashRow(
				DashElement(null, {
					content: ChartView(this.joinedPerDayChart, this.joinedPerDayPromise)
				}),
				DashElement(null, {
					content:
						this.enableGroupStatistics
							? ChartView(this.groupJoinedChart!, this.groupJoinedPromise)
							: <div>
								<h2 class="center">{Lang.get("total")}</h2>
								<div class="center largeText spacingTop">{this.joinedTotalCount}</div>
							</div>
				})
			)}
			
			{TitleRow(Lang.getWithColon("quit_study"))}
			{DashRow(
				DashElement(null, {
					content: ChartView(this.quitPerDayChart, this.quitPerDayPromise)
				}),
				DashElement(null, {
					content:
						this.enableGroupStatistics
							? ChartView(this.groupQuitChart!, this.groupQuitPromise)
							: <div>
								<h2 class="center">{Lang.get("total")}</h2>
								<div class="center largeText spacingTop">{this.quitTotalCount}</div>
							</div>
				})
			)}
			
			{TitleRow(Lang.getWithColon("questionnaires"))}
			{DashRow(
				DashElement(null, {
					content: ChartView(this.questionnairePerDayChart, this.questionnairePerDayPromise)
				}),
				DashElement(null, {
					content: <div>
						<h2 class="center">{Lang.get("total")}</h2>
						<div class="center largeText spacingTop">{this.questionnairesTotalCount}</div>
					</div>
				}),
				this.enableGroupStatistics && DashElement(null, {
					content: ChartView(this.groupQuestionnairePerDayChart, this.questionnairePerDayPromise)
				}),
				this.questionnairesChart && DashElement(null, {
					content: ChartView(this.questionnairesChart, this.questionnairesPromise)
				})
			)}
			
			{TitleRow(Lang.getWithColon("device_information"))}
			{DashRow(
				DashElement(null, {
					content: ChartView(this.appVersionPerDayChart, this.appVersionPerDayPromise)
				}),
				DashElement(null, {
					content: ChartView(this.studyVersionPerDayChart, this.studyVersionPerDayPromise)
				}),
				DashElement(null, {
					content: ChartView(this.appTypeChart, this.appTypePromise)
				}),
				DashElement(null, {
					content: SearchBox(Lang.get("model_with_count", this.modelsList.length), this.modelsList.map((valueList) => {
						return {
							key: valueList.name,
							view: <div class="smallText">{Lang.get("text_with_questionnaireCount", valueList.name, valueList.count)}</div>
						}
					}))
				})
			)}
		</div>
	}
}