import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {ChartEditSectionContent} from "./chartEdit";
import {CsvLoaderCollectionFromCharts, LoadedStatistics} from "../loader/csv/CsvLoaderCollectionFromCharts";
import {ChartView} from "../widgets/ChartView";
import {ObservablePromise} from "../observable/ObservablePromise";
import {ObserverId} from "../observable/BaseObservable";
import {AxisContainer} from "../data/study/AxisContainer";
import {CsvLoader} from "../loader/csv/CsvLoader";
import {BtnReload} from "../widgets/BtnWidgets";

const ONE_DAY_MS = 86400000

export class Content extends SectionContent {
	private readonly connectedSection: ChartEditSectionContent
	private readonly randomContent: boolean
	private readonly csvLoaderCollection: CsvLoaderCollectionFromCharts
	private readonly promise: ObservablePromise<LoadedStatistics>
	private readonly chartObserverId: ObserverId
	
	public static preLoad(section: Section): Promise<any>[] {
		let connectedSection: Section | undefined = undefined
		const sections = section.allSections
		for(let i=section.depth-1; i>0; --i) {
			const currentSection = sections[i]
			if(currentSection.sectionName == "chartEdit") {
				connectedSection = currentSection
				break
			}
		}
		if(!connectedSection)
			throw new Error("Could not find a proper chartEdit section")
		
		return [
			connectedSection.initPromise,
			section.getStudyPromise()
		]
	}
	constructor(section: Section, connectedSection: Section) {
		super(section)
		
		if(!connectedSection || !(connectedSection.sectionContent instanceof ChartEditSectionContent))
			throw new Error("Could not find a proper chartEdit section")
		
		this.connectedSection = connectedSection.sectionContent
		this.randomContent = connectedSection.sectionValue != "calc"
		this.csvLoaderCollection = new CsvLoaderCollectionFromCharts(section.loader, this.getStudyOrThrow())
		this.promise = new ObservablePromise<LoadedStatistics>(this.loadStatistics(), null, "chartPreview")
		
		this.chartObserverId = this.connectedSection.getChart().addObserver(() => {
			this.promise.set(this.loadStatistics())
		})
	}
	
	public title(): string {
		return Lang.get(this.randomContent ? "preview" : "calculate")
	}
	public titleExtra(): Vnode<any, any> | null {
		return BtnReload(() => this.promise.set(this.loadStatistics()))
	}
	
	private async loadStatistics(): Promise<LoadedStatistics> {
		if(this.randomContent) {
			const chart = this.connectedSection.getChart()
			const csvLoader = await CsvLoader.fromCsv(this.section.loader, this.createRandomCsv())
			
			return {
				mainStatistics: await csvLoader.getStatistics(chart.axisContainer.get(), chart.dataType.get()),
				additionalStatistics: await csvLoader.getStatistics(chart.publicVariables.get(), chart.dataType.get())
			}
		}
		else {
			await this.csvLoaderCollection.setupLoadersForCharts([this.connectedSection.getChart()])
			return this.csvLoaderCollection.loadStatisticsFromFiles()
		}
	}
	
	private addRandomValues(lines: string[][], amount: number = 50) {
		for(let i = 1; i < amount; ++i) { // first line are the headers
			if(lines.length <= i)
				lines.push([])
			lines[i].push(this.getRandomInt(0, 10).toString())
		}
	}
	
	private addTimeVariables(lines: string[][]) {
		const variables = ["uploaded", "responseTime"]
		const amount = lines.length - 1
		let currentTimestamp = 1571820812000
		
		for(const variable of variables) {
			lines[0].push(variable)
		}
		
		for(let i = 1; i < amount; ++i) { // first line are the headers
			for(const _variable of variables) {
				lines[i].push(currentTimestamp.toString())
			}
			currentTimestamp += this.getRandomInt(0, ONE_DAY_MS/2)
		}
	}
	
	private fillAxisContainer(axisContainer: AxisContainer, lines: string[][]) {
		const xAxisName = axisContainer.xAxis.variableName.get()
		if(xAxisName) {
			lines[0].push(xAxisName)
			this.addRandomValues(lines)
		}
		
		const yAxisName = axisContainer.yAxis.variableName.get()
		if(yAxisName) {
			lines[0].push(yAxisName)
			this.addRandomValues(lines)
		}
	}
	
	private createRandomCsv(): string[][] {
		const chart = this.connectedSection.getChart()
		const lines: string[][] = [[]]
		
		for(const axisContainer of chart.axisContainer.get()) {
			this.fillAxisContainer(axisContainer, lines)
		}
		for(const axisContainer of chart.publicVariables.get()) {
			this.fillAxisContainer(axisContainer, lines)
		}
		this.addTimeVariables(lines)
		
		return lines
	}
	
	private getRandomInt(from: number, until: number = 0): number {
		return Math.floor(Math.random() * (until - from)) + from
	}
	
	
	public getView(): Vnode<any, any> {
		return ChartView(this.connectedSection.getChart(), this.promise)
	}
	
	public destroy(): void {
		this.chartObserverId.removeObserver()
		super.destroy()
	}
}