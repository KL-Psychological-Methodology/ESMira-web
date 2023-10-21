import {CSV_DELIMITER, DATAVIEWER_SKIPPED_COLUMNS, DATAVIEWER_TIMESTAMP_COLUMNS} from "../../constants/csv";
import Papa from 'papaparse';
import {InputMediaTypes} from "../../data/study/Input";
import {CsvRow} from "./CsvRow";
import {CsvSpecialType} from "./CsvSpecialType";
import {CsvCell, CsvCellsWithMeta} from "./CsvCell";
import {StatisticsCreator} from "./StatisticsCreator";
import {StatisticsCollection} from "../../data/statistics/StatisticsCollection";
import {AxisContainer} from "../../data/study/AxisContainer";
import {WorkerResponseData} from "./WorkerResponseData";
import {ValueListInfo} from "./ValueListInfo";
import {WorkerSendData} from "./WorkerSendData";


const EMPTY_DATA_SYMBOL = "-";
const LOADING_INFO_MESSAGE_FREQUENCY = 1000

class ColumnData {
	readonly headerName: string
	
	readonly specialType?: CsvSpecialType
	
	/**
	 * Lists all cells with the same value in that column.
	 */
	readonly valueCellList: Record<string, CsvCellsWithMeta> = {}
	
	/**
	 * Counts all cells with the same value in that column.
	 * Only cells that are visible are considered
	 */
	visibleValueCountList: Record<string, number> = {}
	
	public invisibleValuesList: Record<string, boolean> = {}
	public invisibleValuesCount: number = 0
	
	constructor(headerName: string, specialType?: CsvSpecialType) {
		this.headerName = headerName
		this.specialType = specialType
	}
}

export class CsvData {
	private loadingPromise?: Promise<any>
	private rawData: string[][] = []
	private importingComplete: boolean = false
	
	private readonly specialColumnsIndex: {
		timestamp: Record<string, boolean>
		skipped: Record<string, boolean>
		image: Record<string, boolean>
		audio: Record<string, boolean>
	}
	
	private readonly columnDataList: ColumnData[] = []
	
	/**
	 * An index of all rows that have been indexed (and are available to be displayed)
	 */
	private readonly rowsIndex: CsvRow[] = []
	
	/**
	 * An index of rows that are currently displayed (by default is the same as {@link rowsIndex})
	 */
	public visibleRowsList: CsvRow[] = []
	
	// private readonly filteredColumnsIndex: Record<number, Record<string, boolean>> = {}
	private filteredRowsIndex: Record<number, boolean> = {}
	
	private needsHeader: boolean = true
	
	private needsIndexing: boolean = true
	public visibleRowsCount: number = 0
	
	
	constructor(specialInputColumns: Record<InputMediaTypes, string[]>) {
		this.specialColumnsIndex = {
			timestamp: {},
			skipped: {},
			image: {},
			audio: {},
		}
		
		for(let mediaType in specialInputColumns) {
			const names = specialInputColumns[mediaType as InputMediaTypes]
			const index = this.specialColumnsIndex[mediaType as InputMediaTypes]
			for(const name of names) {
				index[name] = true
			}
		}
		for(const name of DATAVIEWER_TIMESTAMP_COLUMNS) {
			this.specialColumnsIndex.timestamp[name] = true
		}
		for(const name of DATAVIEWER_SKIPPED_COLUMNS) {
			this.specialColumnsIndex.skipped[name] = true
		}
	}
	
	private loadCsvRow(csvColumns: string[]): void {
		if(!csvColumns.length) {
			let msg = csvColumns.toString()
			try {
				msg = JSON.parse(msg).error
			}
			finally {
				throw new Error(msg)
			}
		}
		
		if(this.needsHeader) {
			this.needsHeader = false
			
			csvColumns.forEach((columnValue, columnIndex) => {
				let specialType: CsvSpecialType | undefined
				if(this.specialColumnsIndex.timestamp.hasOwnProperty(columnValue))
					specialType = CsvSpecialType.timestamp
				else if(this.specialColumnsIndex.image.hasOwnProperty(columnValue))
					specialType = CsvSpecialType.image
				else if(this.specialColumnsIndex.audio.hasOwnProperty(columnValue))
					specialType = CsvSpecialType.audio
				else if(this.specialColumnsIndex.skipped.hasOwnProperty(columnValue))
					specialType = CsvSpecialType.skipped
				
				
				this.columnDataList[columnIndex] = new ColumnData(columnValue, specialType)
			})
		}
		else {
			this.rawData.push(csvColumns);
			if((++this.visibleRowsCount) % LOADING_INFO_MESSAGE_FREQUENCY === 0)
				sendMessage({loadingState: this.visibleRowsCount});
		}
	}
	
	public loadData(url: string): Promise<any> {
		if(this.loadingPromise)
			return this.loadingPromise
		
		this.loadingPromise = new Promise((complete, error) => {
			Papa.parse<string[]>(`${url}${url.indexOf("?") == -1 ? "?" : "&"}${Date.now()}`, {
				download: true,
				step: (rowData) => this.loadCsvRow(rowData.data),
				delimiter: CSV_DELIMITER,
				complete: complete,
				error: error
			})
		})
		return this.loadingPromise
	}
	
	public loadCsv(csvRows: string[][]): void {
		for(const row of csvRows) {
			this.loadCsvRow(row)
		}
	}
	
	private createCell(row: CsvRow, columnIndex: number, columnCellData: string): CsvCell {
		if(columnIndex >= this.columnDataList.length) {
			console.warn(`Row ${row.shownIndex + 1} has too many entries. Creating a new column`)
			while(columnIndex >= this.columnDataList.length) {
				this.columnDataList.push(new ColumnData("", undefined))
			}
		}
		const columnData = this.columnDataList[columnIndex]
		
		if(!columnCellData && columnData.specialType != CsvSpecialType.skipped)
			return new CsvCell(row, EMPTY_DATA_SYMBOL, "", CsvSpecialType.empty)
		
		switch(columnData.specialType) {
			case CsvSpecialType.image:
				return new CsvCell(row, columnCellData, columnCellData, CsvSpecialType.image)
				
			case CsvSpecialType.audio:
				return new CsvCell(row, columnCellData, columnCellData, CsvSpecialType.audio)
				
			case CsvSpecialType.timestamp:
				const timestamp = parseInt(columnCellData)
				if(!timestamp)
					return new CsvCell(row, EMPTY_DATA_SYMBOL, "", CsvSpecialType.empty)
				else if(timestamp > 32532447600) //test if timestamp is in ms or seconds. NOTE: Exactly in the year 3000 when ducks have taken over the world, this code will stop working!!
					return new CsvCell(row, (new Date(timestamp)).toLocaleString(), columnCellData, CsvSpecialType.timestamp)
				else
					return new CsvCell(row, (new Date(timestamp * 1000)).toLocaleString(), columnCellData, CsvSpecialType.timestamp)
				
			default:
					return new CsvCell(row, columnCellData, columnCellData, columnData.specialType)
		}
	}
	
	private indexRowData(rowData: string[], shownIndex: number): void {
		const columnCells: CsvCell[] = []
		const row = new CsvRow(this.rowsIndex.length, shownIndex, columnCells)
		
		rowData.forEach((columnCellData, columnI) => {
			const cell = this.createCell(row, columnI, columnCellData)
			
			const columnData = this.columnDataList[columnI]
			if(!columnData) //this will only be false when datasets.php has been changed after the csv was created and data has more columns than the header line
				return
			const valueCellList = columnData.valueCellList
			const visibleValueCountList = columnData.visibleValueCountList
			
			const columnCellValue = cell.value;
			
			if(!valueCellList.hasOwnProperty(columnCellValue)) {
				valueCellList[columnCellValue] = { cells: [cell], meta: {visible: true} }
				visibleValueCountList[columnCellValue] = 1
			}
			else {
				valueCellList[columnCellValue].cells.push(cell);
				++visibleValueCountList[columnCellValue]
			}
			columnCells[columnI] = cell
		})
		
		this.rowsIndex.push(row)
		this.visibleRowsList.push(row)
	}
	private indexNewData(until: number = this.visibleRowsCount-1): void {
		const missingRowsCount = until - (this.visibleRowsList.length-1)
		if(missingRowsCount <= 0)
			return
		
		const notIndexedRowsCount = this.rawData.length
		const rowsForIndexing = notIndexedRowsCount > missingRowsCount
			? this.rawData.splice(notIndexedRowsCount - missingRowsCount, missingRowsCount) //we splice from the back
			: this.rawData.splice(0, notIndexedRowsCount)
		const newNotIndexedRowsCount = this.rawData.length
		//Note that rows are reversed. So firstRowIndex counts down. When we are done indexing, the very last value will be 0
		const startingRowIndex = newNotIndexedRowsCount - (newNotIndexedRowsCount - notIndexedRowsCount) - rowsForIndexing.length
		
		//This loop needs to be reversed or new data will be added in the wrong order:
		for(let i = rowsForIndexing.length - 1; i >= 0; --i) {
			this.indexRowData(rowsForIndexing[i], startingRowIndex + i)
			if(i % 1000 === 0)
				sendMessage({indexingState: i})
		}
		
		this.needsIndexing = this.importingComplete = this.rawData.length == 0
	}
	public indexing(until: number = this.visibleRowsCount-1): void {
		if(!this.importingComplete) {
			this.indexNewData(until)
			return
		}
		
		if(!this.needsIndexing || until < this.visibleRowsList.length || !this.visibleRowsCount)
			return
		let visibleRowI = this.visibleRowsList.length
		let realRowI = visibleRowI ? this.visibleRowsList[visibleRowI - 1].arrayIndex + 1 : 0 // we dont know the REAL row position because some rows might hav been skipped
		
		//Note: if a filter happened, then reset_visibleFilter() should have been called and visible_rowsIndex is empty
		// if not, we continue an index-action from before
		for(; visibleRowI <= until && realRowI < this.rowsIndex.length; ++realRowI) {
			if(this.rowsIndex[realRowI].hiddenSum)
				continue
			
			const row = this.rowsIndex[realRowI]
			this.visibleRowsList.push(row)
			
			
			//Note: To be able to deal with bugs or corrupted files, we are not assuming that each row has the same number of columns
			row.columnCells.forEach((columnCell, index) => {
				const value = columnCell.value
				const columnList = this.columnDataList[index]
				if(!columnList)
					return
				if(columnList.visibleValueCountList.hasOwnProperty(value))
					++columnList.visibleValueCountList[value]
				else
					columnList.visibleValueCountList[value] = 1
			})
			
			++visibleRowI
		}
		this.needsIndexing = realRowI != this.rowsIndex.length - 1
	}
	
	
	private completeReset(): void {
		for(const row of this.rowsIndex) {
			if(row.visible) //if it is already visible we do nothing
				return
			
			++this.visibleRowsCount
			row.hiddenSum = 0
			row.visible = true
		}
		
		for(const columnData of this.columnDataList) {
			for(const search_key in columnData.invisibleValuesList) {
				const list = columnData.valueCellList
				if(list.hasOwnProperty(search_key))
					list[search_key].meta.visible = true;
			}
			columnData.invisibleValuesList = {}
		}
	}
	public reset(): void {
		if(!this.importingComplete)
			return
		
		let needsCompleteReset = false;
		for(const _ in this.filteredRowsIndex) { //check if there are any entries
			needsCompleteReset = true
			break
		}
		
		if(needsCompleteReset)
			this.completeReset()
		else {
			this.columnDataList.forEach((columnData, columnNum) => {
				for(const searchKey in columnData.invisibleValuesList) {
					this.filterByValue(true, columnNum, searchKey)
				}
				columnData.invisibleValuesList = {}
			})
		}
		
		this.filteredRowsIndex = {}
		this.resetVisibleFilter()
		this.indexing()
	}
	
	public getColumnNum(columnName: string): number {
		return this.columnDataList.findIndex((columnData) => columnData.headerName == columnName)
	}
	
	
	
	public getVisibleCount(columnNum: number, value: string): number {
		const visibleList = this.columnDataList[columnNum].visibleValueCountList
		return visibleList.hasOwnProperty(value) ? visibleList[value] : 0
	}
	
	public getVisibleRows(from: number, to: number): CsvRow[] {
		this.indexing(to - 1)
		return this.visibleRowsList.slice(from, to)
	}
	
	public getValueCellList(columnNum: number): Record<string, CsvCellsWithMeta> {
		this.indexing()
		return this.columnDataList[columnNum].valueCellList
	}
	
	public getValueListInfos(columnNum: number, sortByAmount: boolean, includeHiddenValues: boolean): ValueListInfo[] {
		this.indexing()
		
		const columnData = this.columnDataList[columnNum]
		const visibleList = columnData.visibleValueCountList
		const valueList = Object.keys(includeHiddenValues ? columnData.valueCellList : visibleList);
		if(sortByAmount) {
			valueList.sort(function(a, b) {
				const l1 = visibleList.hasOwnProperty(a) ? visibleList[a] : 0
				const l2 = visibleList.hasOwnProperty(b) ? visibleList[b] : 0
				return l2 - l1
			});
		}
		else
			valueList.sort()
		
		const list: ValueListInfo[] = [];
		const addToList = includeHiddenValues
			? (key: string) => {
				const valueIndexEntry = columnData.valueCellList[key];
				list.push({
					name: key,
					count: visibleList.hasOwnProperty(key) ? visibleList[key] : 0,
					totalCount: valueIndexEntry.cells.length,
					visible: valueIndexEntry.meta.visible
				})
			}
			: (key: string) => {
				const count = visibleList[key]
				list.push({
					name: key,
					count: count,
					totalCount: count,
					visible: true
				})
			}
		for(const value of valueList) {
			addToList(value)
		}
		return list
	}
	
	public getStatisticsCollection(axisContainerArray: AxisContainer[], dataType: number): StatisticsCollection {
		const creator = new StatisticsCreator(this)
		return creator.create(axisContainerArray, dataType)
	}
	
	public getValueCount(columnNum: number, values: string[]): Record<string, number> {
		if(!this.importingComplete)
			this.indexing()
		const valueList = this.columnDataList[columnNum].valueCellList
		const r: Record<string, number> = {}
		for(const key of values) {
			r[key] = valueList.hasOwnProperty(key) ? valueList[key].cells.length : 0;
		}
		return r
	}
	
	private setRowVisibility(row: CsvRow, visible: boolean): void {
		if(visible) {
			if(!row.visible && !--row.hiddenSum) { //if it is already visible we do nothing
				++this.visibleRowsCount
				row.visible = true
			}
		}
		else {
			if(++row.hiddenSum === 1) {
				--this.visibleRowsCount
				row.visible = false
			}
		}
	}
	
	private resetVisibleFilter(): void {
		this.visibleRowsList = []
		
		for(const columnData of this.columnDataList) {
			columnData.visibleValueCountList = {}
		}
	}
	
	public mark(enable: boolean, rowPos: number): void {
		this.rowsIndex[rowPos].marked = enable
	}
	
	public filterByValue(visible: boolean, columnNum: number, value: string): void {
		if(!this.importingComplete)
			this.indexing()
		const columnData = this.columnDataList[columnNum]
		if(columnData.valueCellList.hasOwnProperty(value)) {
			const keyCells = columnData.valueCellList[value]
			
			if(keyCells.meta.visible == visible)
				return
			
			keyCells.meta.visible = visible
			
			for(const cell of keyCells.cells) {
				this.setRowVisibility(cell.row, visible)
			}
		}
		this.resetVisibleFilter()
		
		//keep track for reset()
		if(visible) {
			if(columnData.invisibleValuesList.hasOwnProperty(value)) {
				delete columnData.invisibleValuesList[value]
				--columnData.invisibleValuesCount
			}
		}
		else {
			columnData.invisibleValuesList[value] = true
			++columnData.invisibleValuesCount
		}
		
		this.needsIndexing = true
	}
	
	public filterEntireColumn(visible: boolean, columnNum: number): void {
		if(!this.importingComplete)
			this.indexing()
		const valueList = this.columnDataList[columnNum].valueCellList
		for(const key in valueList) {
			if(valueList.hasOwnProperty(key))
				this.filterByValue(visible, columnNum, key)
		}
		this.needsIndexing = true
	}
	
	filterRowsByResponseTime(visible: boolean, newestTimestamp: number): void {
		if(!this.importingComplete)
			this.indexing()
		const responseTimeNum = this.getColumnNum("responseTime")
		
		if(!visible && this.filteredRowsIndex.hasOwnProperty(responseTimeNum)) //we don't want to filter the same row twice
			return
		
		for(const row of this.visibleRowsList) {
			const cells = row.columnCells
			
			if(cells.length <= responseTimeNum) //if there was an error in dataset row.columnCells[responseTimeNum] can be undefined
				this.setRowVisibility(row, false)
			else if(parseInt(cells[responseTimeNum].realValue) < newestTimestamp)
				this.setRowVisibility(row, visible)
		}
		
		this.resetVisibleFilter()
		
		//keep track for reset() :
		if(visible) {
			if(this.filteredRowsIndex.hasOwnProperty(responseTimeNum))
				delete this.filteredRowsIndex[responseTimeNum]
		}
		else
			this.filteredRowsIndex[responseTimeNum] = true
		this.needsIndexing = true
	}
	
	public getHeaderNames(): string[] {
		const output: string[] = []
		for(const columnData of this.columnDataList) {
			output.push(columnData.headerName)
		}
		return output
	}
}

let csvData: CsvData

onmessage = async (event) => {
	const data = event.data as WorkerSendData
	const id = data.id
	
	const returnObj: WorkerResponseData = { id: id }
	switch(data.type) {
		case "load":
			try {
				csvData = new CsvData(data.specialColumns ?? {} as Record<InputMediaTypes, string[]>)
				await csvData.loadData(data.url ?? "missing")
				returnObj.visibleRowsCount = csvData.visibleRowsCount
				returnObj.headerNames = csvData.getHeaderNames()
				sendMessage(returnObj)
			}
			catch(error: any) {
				console.error(error)
				returnObj.error = error.toString()
				sendMessage(returnObj)
			}
			return
		case "fromCsv":
			csvData = new CsvData(data.specialColumns ?? {} as Record<InputMediaTypes, string[]>)
			csvData.loadCsv(data.csv ?? []);
			returnObj.visibleRowsCount = csvData.visibleRowsCount
			returnObj.headerNames = csvData.getHeaderNames()
			break
		case "reset":
			csvData.reset()
			break
		case "getVisibleCount":
			returnObj.visibleRowsCount = csvData.getVisibleCount(data.columnNum ?? 0, data.value ?? "")
			break
		case "getVisibleRows":
			returnObj.rows = csvData.getVisibleRows(data.from ?? 0, data.to ?? 0)
			break
		case "valueCellList":
			returnObj.valueCellList = csvData.getValueCellList(data.columnNum ?? 0)
			break
		case "valueListInfo":
			returnObj.valueListInfo = csvData.getValueListInfos(data.columnNum ?? 0, !!data.sortByAmount, !!data.includeHiddenValues)
			break
		case "mark":
			csvData.mark(!!data.enable, data.rowPos ?? 0)
			break
		case "filterByValue":
			csvData.filterByValue(!!data.enable, data.columnNum ?? 0, data.value ?? "")
			returnObj.visibleRowsCount = csvData.visibleRowsCount
			break
		case "filterEntireColumn":
			csvData.filterEntireColumn(!!data.enable, data.columnNum ?? 0)
			returnObj.visibleRowsCount = csvData.visibleRowsCount
			break
		case "filterRowsByResponseTime":
			csvData.filterRowsByResponseTime(!!data.enable, data.newestTimestamp ?? 0)
			returnObj.visibleRowsCount = csvData.visibleRowsCount
			break
		case "getStatistics":
			returnObj.statistics = csvData.getStatisticsCollection(
				data.axisContainerArrayJson?.map((json) => new AxisContainer(JSON.parse(json), null, "axisContainer")) ?? [],
				data.dataType ?? 0
			)
			break
		case "getValueCount":
			returnObj.valueCount = csvData.getValueCount(data.columnNum ?? 0, data.values ?? [])
			break;
		default:
			returnObj.error = "Unknown error"
			break
	}
	sendMessage(returnObj)
}


function sendMessage(returnData: WorkerResponseData): void {
	postMessage(returnData)
}