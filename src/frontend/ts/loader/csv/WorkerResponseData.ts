import {CsvRow} from "./CsvRow";
import {StatisticsCollection} from "../../data/statistics/StatisticsCollection";
import {ValueListInfo} from "./ValueListInfo";
import {CsvCellsWithMeta} from "./CsvCell";


export interface WorkerResponseData {
	loadingState?: number
	indexingState?: number
	
	id?: number
	visibleRowsCount?: number
	headerNames?: string[]
	error?: string
	rows?: CsvRow[]
	valueCellList?: Record<string, CsvCellsWithMeta>
	valueListInfo?: ValueListInfo[]
	statistics?: StatisticsCollection
	valueCount?: Record<string, number>
	
}