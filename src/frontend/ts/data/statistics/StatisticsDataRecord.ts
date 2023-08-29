import {StatisticsDataEntry} from "./StatisticsDataEntry";

export type StatisticsDataRecord = StatisticsEntryTimed | StatisticsEntryPerValue | StatisticsEntryPerData

export type StatisticsEntryTimed = Record<number, StatisticsDataEntry>
export type StatisticsEntryPerValue = Record<string, number>
export type StatisticsEntryPerData = Record<number, number>