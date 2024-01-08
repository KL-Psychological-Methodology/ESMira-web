import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {Requests} from "../singletons/Requests";
import {FILE_SERVER_STATISTICS} from "../constants/urls";
import {ChartView} from "../widgets/ChartView";
import {ChartData} from "../data/study/ChartData";
import {ObservablePromise} from "../observable/ObservablePromise";
import {LoadedStatistics} from "../loader/csv/CsvLoaderCollectionFromCharts";
import {
	STATISTICS_CHARTTYPES_BARS,
	STATISTICS_CHARTTYPES_LINE, STATISTICS_CHARTTYPES_LINE_FILLED,
	STATISTICS_CHARTTYPES_PIE,
	STATISTICS_DATATYPES_DAILY,
	STATISTICS_DATATYPES_FREQ_DISTR, STATISTICS_STORAGE_TYPE_FREQ_DISTR, STATISTICS_STORAGE_TYPE_TIMED,
	STATISTICS_VALUETYPES_COUNT, STATISTICS_VALUETYPES_SUM
} from "../constants/statistics";
import {JsonTypes} from "../observable/types/JsonTypes";
import {StatisticsEntry} from "../data/statistics/StatisticsEntry";
import { StatisticsEntryTimed} from "../data/statistics/StatisticsDataRecord";
import {DayEntry} from "../data/serverStatistics/DayEntry";
import {WeekEntry} from "../data/serverStatistics/WeekEntry";
import {ServerStatistics} from "../data/serverStatistics/ServerStatistics";
import {BtnReload} from "../widgets/BtnWidgets";

const SMALLEST_TIMED_DISTANCE = 675
export class Content extends SectionContent {
	private readonly serverStatistics: ServerStatistics
	
	private readonly appTypeChart: ChartData
	protected dailyAppVersionChart?: ChartData //only written by serverStatisticsAdmin
	private readonly dailyQuestionnaireChart: ChartData
	private readonly dailyJoinedChart: ChartData
	private readonly weekdaysQuestionnaireChart: ChartData
	private readonly weekdaysJoinedChart: ChartData
	
	private readonly appTypePromise: ObservablePromise<LoadedStatistics>
	protected dailyAppVersionPromise?: ObservablePromise<LoadedStatistics> //only written by serverStatisticsAdmin
	private readonly dailyQuestionnairePromise: ObservablePromise<LoadedStatistics>
	private readonly dailyJoinedPromise: ObservablePromise<LoadedStatistics>
	private readonly weekdaysQuestionnairePromise: ObservablePromise<LoadedStatistics>
	private readonly weekdaysJoinedPromise: ObservablePromise<LoadedStatistics>
	
	public static preLoad(_section: Section): Promise<any>[] {
		return [
			Requests.loadJson(FILE_SERVER_STATISTICS)
		]
	}
	constructor(section: Section, serverStatistics: ServerStatistics) {
		super(section)
		this.serverStatistics = serverStatistics
		
		this.appTypeChart = this.createAppTypeChart()
		this.dailyQuestionnaireChart = this.createDailyQuestionnaireChart()
		this.dailyJoinedChart = this.createDailyJoinedChart()
		this.weekdaysQuestionnaireChart = this.createWeekdaysQuestionnaireChart()
		this.weekdaysJoinedChart = this.createWeekdaysJoinedChart()
		
		this.appTypePromise = this.createChartPromise("appType", [{
			storageType: STATISTICS_STORAGE_TYPE_FREQ_DISTR,
			data: {
				[Lang.get("Android")]: serverStatistics.total.android,
				[Lang.get("iOS")]: serverStatistics.total.ios,
				[Lang.get("Web")]: serverStatistics.total.web
			},
			entryCount: 3,
			timeInterval: SMALLEST_TIMED_DISTANCE
		}])
		this.dailyQuestionnairePromise = this.createChartPromise("questionnaire", [this.createDailyStatisticsEntry(serverStatistics, "questionnaire")])
		this.dailyJoinedPromise = this.createChartPromise("joined", [this.createDailyStatisticsEntry(serverStatistics, "joined")])
		this.weekdaysQuestionnairePromise = this.createChartPromise("weekdaysQuestionnaire", [this.createWeeklyStatisticsEntry(serverStatistics, "questionnaire")])
		this.weekdaysJoinedPromise = this.createChartPromise("weekdaysJoin", [this.createWeeklyStatisticsEntry(serverStatistics, "joined")])
	}
	
	
	public titleExtra(): Vnode<any, any> | null {
		return BtnReload(this.section.reload.bind(this.section), Lang.get("reload"))
	}
	
	protected createChartPromise(variable: string, entries: StatisticsEntry[]): ObservablePromise<LoadedStatistics> {
		return new ObservablePromise<LoadedStatistics>(
			Promise.resolve({
				mainStatistics: {[variable]: entries}
			}),
			null,
			"promise"
		)
	}
	
	private createWeeklyStatisticsEntry(serverStatistics: ServerStatistics, type: keyof WeekEntry): StatisticsEntry {
		return {
			storageType: STATISTICS_STORAGE_TYPE_FREQ_DISTR,
			timeInterval: SMALLEST_TIMED_DISTANCE,
			entryCount: 7,
			data: {
				[Lang.get("weekday_mon")]: serverStatistics.week[type][1],
				[Lang.get("weekday_tue")]: serverStatistics.week[type][2],
				[Lang.get("weekday_wed")]: serverStatistics.week[type][3],
				[Lang.get("weekday_thu")]: serverStatistics.week[type][4],
				[Lang.get("weekday_fri")]: serverStatistics.week[type][5],
				[Lang.get("weekday_sat")]: serverStatistics.week[type][6],
				[Lang.get("weekday_sun")]: serverStatistics.week[type][0]
			}
		}
	}
	
	protected createDailyStatisticsEntry(serverStatistics: ServerStatistics, variable: keyof DayEntry, subVariable: string = ""): StatisticsEntry {
		const statisticsData: StatisticsEntryTimed = {}
		let count = 0
		const days = serverStatistics.days
		
		for(const timestamp in days) {
			const entry = days[timestamp]
			if(entry.hasOwnProperty(variable)) {
				if(variable == "appVersion")
					statisticsData[timestamp] = {sum: entry[variable].hasOwnProperty(subVariable) ? entry[variable][subVariable] : 0, count: 1}
				else
					statisticsData[timestamp] = {sum: entry[variable], count: 1}
			}
			else
				statisticsData[timestamp] = {sum: 0, count: 1}
			++count
		}
		
		return {
			storageType: STATISTICS_STORAGE_TYPE_TIMED,
			timeInterval: SMALLEST_TIMED_DISTANCE,
			entryCount: count,
			data: statisticsData
		}
	}
	
	private createAppTypeChart(): ChartData {
		return new ChartData({
			title: Lang.get("app_type"),
			publicVariables: [],
			axisContainer: [
				{
					xAxis: {
						conditions: []
					},
					yAxis: {
						conditions: [],
						variableName: "appType",
						observedVariableIndex: 0
					}
				}
			],
			valueType: STATISTICS_VALUETYPES_COUNT,
			dataType: STATISTICS_DATATYPES_FREQ_DISTR,
			chartType: STATISTICS_CHARTTYPES_PIE
		}, null, "chart")
	}
	protected createDailyAppVersionChart(appVersionAxisContainer: Record<string, JsonTypes>[]): ChartData {
		return new ChartData({
			title: Lang.get("app_version"),
			publicVariables: [],
			axisContainer: appVersionAxisContainer,
			valueType: STATISTICS_VALUETYPES_SUM,
			dataType: STATISTICS_DATATYPES_DAILY,
			chartType: STATISTICS_CHARTTYPES_LINE
		}, null, "chart")
	}
	private createDailyQuestionnaireChart(): ChartData {
		return new ChartData({
			title: Lang.get("per_day"),
			publicVariables: [],
			axisContainer: [
				{
					xAxis: {
						conditions: []
					},
					yAxis: {
						conditions: [],
						variableName: "questionnaire",
						observedVariableIndex: 0
					},
					label: Lang.get("questionnaires"),
					color: "#00ffff"
				}
			],
			valueType: STATISTICS_VALUETYPES_SUM,
			dataType: STATISTICS_DATATYPES_DAILY,
			chartType: STATISTICS_CHARTTYPES_LINE_FILLED
		}, null, "chart")
	}
	private createDailyJoinedChart(): ChartData {
		return new ChartData({
			title: Lang.get("per_day"),
			publicVariables: [],
			axisContainer: [
				{
					xAxis: {
						conditions: []
					},
					yAxis: {
						conditions: [],
						variableName: "joined",
						observedVariableIndex: 0
					},
					label: Lang.get("joined_study"),
					color: "#80ff80"
				}
			],
			valueType: STATISTICS_VALUETYPES_SUM,
			dataType: STATISTICS_DATATYPES_DAILY,
			chartType: STATISTICS_CHARTTYPES_LINE_FILLED
		}, null, "chart")
	}
	private createWeekdaysQuestionnaireChart(): ChartData {
		return new ChartData({
			title: Lang.get("total_count_per_weekday"),
			publicVariables: [],
			axisContainer: [
				{
					xAxis: {
						conditions: []
					},
					yAxis: {
						conditions: [],
						variableName: "weekdaysQuestionnaire",
						observedVariableIndex: 0
					},
					label: Lang.get("questionnaires"),
					color: "#00ffff"
				},
			],
			valueType: STATISTICS_VALUETYPES_SUM,
			dataType: STATISTICS_DATATYPES_FREQ_DISTR,
			chartType: STATISTICS_CHARTTYPES_BARS
		}, null, "chart")
	}
	private createWeekdaysJoinedChart(): ChartData {
		return new ChartData({
			title: Lang.get("total_count_per_weekday"),
			publicVariables: [],
			axisContainer: [
				{
					xAxis: {
						conditions: []
					},
					yAxis: {
						conditions: [],
						variableName: "weekdaysJoin",
						observedVariableIndex: 0
					},
					label: Lang.get("joined_study"),
					color: "#80ff80"
				}
			],
			valueType: STATISTICS_VALUETYPES_SUM,
			dataType: STATISTICS_DATATYPES_FREQ_DISTR,
			chartType: STATISTICS_CHARTTYPES_BARS
		}, null, "chart")
	}
	
	public title(): string {
		return Lang.get("server_statistics")
	}
	
	public getView(): Vnode<any, any> {
		return <div>
			{DashRow(
				DashElement("vertical",
					{
						content:
							<div>
								<h2 class="center">{Lang.getWithColon("active_studies")}</h2>
								<div class="center">{this.serverStatistics.total.studies}</div>
								<br/>
							</div>
					},
					{
						content:
							<div>
								<h2 class="center">{Lang.getWithColon("total_completed_questionnaires")}</h2>
								<div class="center">{this.serverStatistics.total.questionnaire}</div>
								<br/>
							</div>
					},
					{
						content:
							<div>
								<h2 class="center">{Lang.getWithColon("total_participants")}</h2>
								<div class="center">{this.serverStatistics.total.users}</div>
								<br/>
							</div>
					}
				),
				DashElement(null, {
					content: ChartView(this.appTypeChart, this.appTypePromise)
				}),
				this.dailyAppVersionPromise && this.dailyAppVersionChart && DashElement("stretched", {
					content: ChartView(this.dailyAppVersionChart, this.dailyAppVersionPromise)
				}),
				DashElement("stretched", {
					content: ChartView(this.dailyQuestionnaireChart, this.dailyQuestionnairePromise)
				}),
				DashElement("stretched", {
					content: ChartView(this.dailyJoinedChart, this.dailyJoinedPromise)
				}),
				DashElement(null, {
					content: ChartView(this.weekdaysQuestionnaireChart, this.weekdaysQuestionnairePromise, true)
				}),
				DashElement(null, {
					content: ChartView(this.weekdaysJoinedChart, this.weekdaysJoinedPromise, true)
				})
			)}
			
			<br/>
			<br/>
			<div class="smallText right">
				<span>{Lang.getWithColon("data_collected_since")}</span>
				&nbsp;
				<span>{(new Date(this.serverStatistics.created*1000).toLocaleDateString())}</span>
			</div>
		</div>
	}
}