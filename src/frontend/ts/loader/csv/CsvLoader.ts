import {LoaderState} from "../../site/LoaderState";
import {Lang, LangKey} from "../../singletons/Lang";
import {WorkerResponseData} from "./WorkerResponseData";
import {WorkerSendData} from "./WorkerSendData";
import {InputMediaTypes} from "../../data/study/Input";
import {CsvRow} from "./CsvRow";
import {CsvCellsWithMeta} from "./CsvCell";
import {ValueListInfo} from "./ValueListInfo";
import {AxisContainer} from "../../data/study/AxisContainer";
import {StatisticsCollection} from "../../data/statistics/StatisticsCollection";
import {DATAVIEWER_TIMESTAMP_COLUMNS} from "../../constants/csv";
import {ChartData} from "../../data/study/ChartData";
import {LoadedStatistics} from "./CsvLoaderCollectionFromCharts";

interface ResolveData {
	success: (value: any) => void
	error: (msg: string) => void
}


export class CsvLoader {
	static async fromUrl(loader: LoaderState, url: string, specialColumns?: Record<InputMediaTypes, string[]>): Promise<CsvLoader> {
		const csvLoader = new CsvLoader(loader)
		await csvLoader.loadUrl(url, specialColumns)
		return csvLoader
	}
	static async fromCsv(loader: LoaderState, csv: string[][], specialColumns?: Record<InputMediaTypes, string[]>): Promise<CsvLoader> {
		const csvLoader = new CsvLoader(loader)
		await csvLoader.loadCsv(csv, specialColumns)
		return csvLoader
	}
	
	private readonly timeSpanColumnList: Record<string, boolean> = {}
	private readonly resolveQueue: Record<number, ResolveData> = {}
	private queueCount: number = 0
	private promiseChain: Promise<any> = Promise.resolve()
	
	private readonly csvWorker: Worker
	private readonly loader: LoaderState
	
	public headerNames: string[] = []
	public visibleRowsCount: number = 0
	
	private constructor(loader: LoaderState) {
		this.loader = loader
		this.csvWorker = new Worker(new URL("CsvWorker", import.meta.url))
		this.csvWorker.onmessage = this.onWorkerMessage.bind(this)
		
		DATAVIEWER_TIMESTAMP_COLUMNS.forEach((name) => {
			this.timeSpanColumnList[name] = true
		})
	}
	private onWorkerMessage(e: MessageEvent): void {
		const response = e.data as WorkerResponseData
		
		if(response.loadingState)
			this.loader.update(Lang.get("state_loading_entryNum", response.loadingState))
		if(response.indexingState)
			this.loader.update(Lang.get("state_creatingIndex_entryNum", response.indexingState))
		else if(response.id) {
			const id = response.id
			if(this.resolveQueue.hasOwnProperty(id)) {
				if(response.error)
					this.resolveQueue[id].error(response.error)
				else
					this.resolveQueue[id].success(response)
				delete this.resolveQueue[id]
			}
		}
	}
	
	
	private addPromise(sendData: WorkerSendData, state: LangKey = "state_loading"): Promise<WorkerResponseData> {
		this.promiseChain = this.promiseChain
			.then(() => this.loader.showLoader(
				new Promise((resolve, reject) => {
					this.resolveQueue[++this.queueCount] = {success: resolve, error: reject}
					sendData = sendData || {}
					sendData.id = this.queueCount
					this.csvWorker.postMessage(sendData)
				}),
				Lang.get(state)
			))
		
		return this.promiseChain
	}
	
	private async load(sendData: WorkerSendData): Promise<any> {
		const response = await this.addPromise(sendData, "state_downloading")
		this.headerNames = response.headerNames ?? []
		this.visibleRowsCount = response.visibleRowsCount ?? 0
	}
	
	private async loadUrl(url: string, specialColumns?: Record<InputMediaTypes, string[]>): Promise<any> {
		await this.load({
			type: "load",
			url: location.origin + location.pathname + url,
			specialColumns: specialColumns
		})
	}
	private async loadCsv(csv: string[][], specialColumns?: Record<InputMediaTypes, string[]>): Promise<any> {
		await this.load({
			type: "fromCsv",
			csv: csv,
			specialColumns: specialColumns
		})
	}
	public waitUntilReady(): Promise<any> {
		return this.promiseChain
	}
	public reset(): Promise<any> {
		return this.addPromise({type: "reset"})
	}
	
	public close(): void {
		this.csvWorker.terminate()
	}
	
	
	//
	// convenience methods
	//
	
	public hasColumn(column: string): boolean {
		return this.headerNames.includes(column)
	}
	public getColumnNum(column: string): number {
		const r = this.headerNames.indexOf(column)
		if(r == -1)
			console.trace(`${column} does not exist in get_columnNum()`, this.headerNames)
		return r
	}
	
	
	//
	// data methods
	//
	
	public async getVisibleRows(from: number, to: number): Promise<CsvRow[]> {
		const response = await this.addPromise({type: "getVisibleRows", from: from, to: to})
		return response.rows ?? []
	}
	
	public async getVisibleCount(columnName: string, value: string): Promise<number> {
		const response = await this.addPromise({type: "getVisibleCount", columnNum: this.getColumnNum(columnName), value: value})
		return response.visibleRowsCount ?? 0
	}
	
	public async getValueListInfo(columnName: string, sortByAmount: boolean = false, includeHiddenValues: boolean = false): Promise<ValueListInfo[]> {
		const response = await this.addPromise(
			{type: "valueListInfo", columnNum: this.getColumnNum(columnName), sortByAmount: sortByAmount, includeHiddenValues: includeHiddenValues},
			"state_creatingIndex"
		)
		return response.valueListInfo ?? []
	}
	public async getValueCellList(columnName: string): Promise<Record<string, CsvCellsWithMeta>> {
		const response = await this.addPromise(
			{type: "valueCellList", columnNum: this.getColumnNum(columnName)},
			"state_creatingIndex"
		)
		return response.valueCellList ?? {}
	}
	public async getStatistics(axisContainerArray: AxisContainer[], dataType: number): Promise<StatisticsCollection> {
		const response = await this.addPromise(
			{type: "getStatistics", axisContainerArrayJson: axisContainerArray.map((axisContainer) => JSON.stringify(axisContainer.createJson())), dataType: dataType},
			"state_creatingStatistics"
		)
		return response.statistics ?? {}
	}
	public async getPersonalStatisticsFromChart(chart: ChartData): Promise<LoadedStatistics> {
		return {
			mainStatistics: await this.getStatistics(chart.axisContainer.get(), chart.dataType.get())
		}
	}
	public async getValueCount(columnName: string, values: string[]): Promise<Record<string, number>> {
		const response = await this.addPromise({type: "getValueCount", columnNum: this.getColumnNum(columnName), values: values})
		return response.valueCount ?? {}
	}
	
	
	//
	// change functions
	//
	
	public async filterByValue(enable: boolean, column: string | number, value: string): Promise<void> {
		const columnNum = typeof column == "number" ? column : this.getColumnNum(column)
		if(columnNum === -1) {
			console.error(`${column} does not exist. Aborting`)
			return
		}
		const response = await this.addPromise({type: "filterByValue", columnNum: columnNum, value: value, enable: enable}, "state_applyingFilter")
		this.visibleRowsCount = response.visibleRowsCount ?? 0
	}
	public async filterEntireColumn(enable: boolean, column: string | number): Promise<void> {
		const columnNum = typeof column == "number" ? column : this.getColumnNum(column)
		if(columnNum === -1) {
			console.error(`${column} does not exist. Aborting`)
			return
		}
		const response = await this.addPromise({type: "filterEntireColumn", columnNum: columnNum, enable: enable}, "state_applyingFilter")
		this.visibleRowsCount = response.visibleRowsCount ?? 0
	}
	public async filterRowsByResponseTime(enable: boolean, newestTimestamp: number): Promise<void> {
		const response = await this.addPromise({type: "filterRowsByResponseTime", newestTimestamp: newestTimestamp, enable: enable}, "state_applyingFilter")
		this.visibleRowsCount = response.visibleRowsCount ?? 0
	}
	public async mark(enable: boolean, rowPos: number): Promise<void> {
		await this.addPromise({type: "mark", enable: enable, rowPos: rowPos}, "state_loading")
	}
	
	//
	// data functions
	//
	public isTimestampColumn(columnValue: string): boolean {
		return this.timeSpanColumnList[columnValue]
	}
}