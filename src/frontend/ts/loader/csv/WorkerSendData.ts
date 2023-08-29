import {InputMediaTypes} from "../../data/study/Input";
import {AxisContainer} from "../../data/study/AxisContainer";

export type WorkerSendTypes = "load"
	| "fromCsv"
	| "reset"
	| "getVisibleCount"
	| "getVisibleRows"
	| "valueListInfo"
	| "valueCellList"
	| "mark"
	| "filterByValue"
	| "filterEntireColumn"
	| "filterRowsByResponseTime"
	| "getStatistics"
	| "getValueCount"

export interface WorkerSendData {
	type: WorkerSendTypes
	id?: number
	specialColumns?: Record<InputMediaTypes, string[]>
	url?: string
	csv?: string[][]
	from?: number
	to?: number
	columnNum?: number
	rowPos?: number
	sortByAmount?: boolean
	includeHiddenValues?: boolean
	enable?: boolean
	value?: string
	newestTimestamp?: number
	axisContainerArrayJson?: string[]
	dataType?: number
	values?: string[]
}