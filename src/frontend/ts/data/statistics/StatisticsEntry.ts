import {StatisticsDataRecord} from "./StatisticsDataRecord";

export interface StatisticsEntry {
	storageType: number
	timeInterval: number
	entryCount: number
	data: StatisticsDataRecord
}


