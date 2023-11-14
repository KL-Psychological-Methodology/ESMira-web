import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {Requests} from "../singletons/Requests";
import {FILE_ADMIN, FILE_SERVER_STATISTICS} from "../constants/urls";
import {getChartColor} from "../helpers/ChartJsBox";
import {JsonTypes} from "../observable/types/JsonTypes";
import {StatisticsEntry} from "../data/statistics/StatisticsEntry";
import {ServerStatistics} from "../data/serverStatistics/ServerStatistics";
import {Content as ServerStatisticsContent} from "../pages/serverStatistics";
import {TabBar} from "../widgets/TabBar";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";

const SECONDS_14_DAYS = 60*60*24*14

export class Content extends ServerStatisticsContent {
	private tabIndex = new ObservablePrimitive(0, null, "serverStatistics")
	
	private readonly lastActivitiesList: { id: number, timestamp: number }[]
	private readonly usedSpaceList: { id: number, fileSize: number }[]
	private readonly totalUsedSpace: number
	
	
	public static preLoad(section: Section): Promise<any>[] {
		return [
			Requests.loadJson(FILE_SERVER_STATISTICS),
			Requests.loadJson(`${FILE_ADMIN}?type=GetLastActivities`),
			Requests.loadJson(`${FILE_ADMIN}?type=GetUsedSpacePerStudy`),
			section.getStrippedStudyListPromise()
		]
	}
	constructor(section: Section, serverStatistics: ServerStatistics, lastActivities: Record<number, number>, usedSpace: Record<number, number>) {
		super(section, serverStatistics)
		
		//lastActivities:
		const lastActivitiesList: { id: number, timestamp: number }[] = []
		for(const studyId in lastActivities) {
			lastActivitiesList.push({id: parseInt(studyId), timestamp: lastActivities[studyId]})
		}
		lastActivitiesList.sort(function(a, b) {
			return b.timestamp - a.timestamp
		})
		this.lastActivitiesList = lastActivitiesList
		
		
		//usedSpace:
		const usedSpaceList: { id: number, fileSize: number }[] = []
		let totalUsedSpace = 0
		for(const studyId in usedSpace) {
			const fileSize = usedSpace[studyId]
			totalUsedSpace += fileSize
			usedSpaceList.push({id: parseInt(studyId), fileSize: fileSize})
		}
		usedSpaceList.sort(function(a, b) {
			return b.fileSize - a.fileSize
		})
		this.usedSpaceList = usedSpaceList
		this.totalUsedSpace = totalUsedSpace
		
		this.setAppVersionChartAndPromise(serverStatistics)
	}
	
	private setAppVersionChartAndPromise(serverStatistics: ServerStatistics): void {
		const appVersionLabels: Record<string, boolean> = {}
		const days = serverStatistics.days
		for(const timestamp in days) {
			const appVersion = days[timestamp].appVersion
			if(appVersion) {
				for(const key in appVersion) {
					if(!appVersionLabels.hasOwnProperty(key))
						appVersionLabels[key] = true
				}
			}
		}
		const keys = Object.keys(appVersionLabels).sort()
		
		const appVersionAxisContainer: Record<string, JsonTypes>[] = []
		const appVersionStatistics: StatisticsEntry[] = []
		let i = 0
		for(const key of keys) {
			if(key.indexOf("_dev") != -1 || key.indexOf("wasDev") != -1)
				continue
			appVersionStatistics.push(this.createDailyStatisticsEntry(serverStatistics, "appVersion", key))
			
			appVersionAxisContainer.push({
				xAxis: {
					conditions: []
				},
				yAxis: {
					conditions: [],
					variableName: "appVersion",
					observedVariableIndex: i
				},
				label: key,
				color: getChartColor(i)
			})
			++i
		}
		
		this.dailyAppVersionChart = this.createDailyAppVersionChart(appVersionAxisContainer)
		this.dailyAppVersionPromise = this.createChartPromise("appVersion", appVersionStatistics)
	}
	
	private getReadableByteSize(bytes: number): string {
		if(bytes > 1000000000)
			return `${Math.round(bytes / 10000000) / 100} Gb`
		else if(bytes > 1000000)
			return `${Math.round(bytes / 10000) / 100} Mb`
		else
			return `${Math.round(bytes / 1000)} Kb`
	}
	
	public getView(): Vnode<any, any> {
		const studies = this.section.siteData.studyLoader.getStudies()
		return TabBar(this.tabIndex, [
			{
				title: Lang.get("server_statistics"),
				view: () => super.getView()
			},
			{
				title: Lang.get("last_activities"),
				view: () => <table style="width: 100%">
					{this.lastActivitiesList.map((entry) => {
							const study = studies.getEntry(entry.id)
							return <tr>
								<td class={study?.published.get() ? "" : "unPublishedStudy"}>
									<a href={this.getUrl(`dataStatistics,id:${entry.id}`)}>{study?.title.get()}</a>
								</td>
								<td class={Date.now()/1000 - entry.timestamp < SECONDS_14_DAYS ? "highlight" : ""}>{new Date(entry.timestamp*1000).toLocaleString()}</td>
							</tr>
						}
					)}
				</table>
			},
			{
				title: Lang.get("disk_space"),
				view: () => {
					const tools = this.section.getTools()
					return <table style="width: 100%">
						<tr class="highlight">
							<td>{Lang.getWithColon("disk_space")}</td>
							<td>{this.getReadableByteSize(tools.totalDiskSpace - tools.freeDiskSpace)} / {this.getReadableByteSize(tools.totalDiskSpace)}</td>
						</tr>
						<tr class="highlight">
							<td>{Lang.getWithColon("studies")}</td>
							<td>{this.getReadableByteSize(this.totalUsedSpace)}</td>
						</tr>
						<tr><td colspan="2"><hr/></td></tr>
						{this.usedSpaceList.map((entry) => {
							const study = studies.getEntry(entry.id)
							return <tr>
								<td class={study?.published.get() ? "" : "unPublishedStudy"}>
									<a href={this.getUrl(`dataStatistics,id:${entry.id}`)}>{Lang.get("colon", study?.title.get() ?? "Error")}</a>
								</td>
								<td class={entry.fileSize > 100000000 ? "highlight" : ""}>{this.getReadableByteSize(entry.fileSize)}</td>
							</tr>
						})}
					</table>
				}
			},
		])
	}
}