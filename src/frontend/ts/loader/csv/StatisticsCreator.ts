import {Conditions} from "../../data/study/Conditions";
import {
	CONDITION_OPERATOR_EQUAL,
	CONDITION_OPERATOR_GREATER,
	CONDITION_OPERATOR_LESS,
	CONDITION_OPERATOR_UNEQUAL,
	CONDITION_TYPE_ALL,
	CONDITION_TYPE_AND,
	CONDITION_TYPE_OR,
	STATISTICS_DATATYPES_DAILY, STATISTICS_DATATYPES_FREQ_DISTR,
	STATISTICS_DATATYPES_SUM, STATISTICS_DATATYPES_XY,
	STATISTICS_STORAGE_TYPE_FREQ_DISTR,
	STATISTICS_STORAGE_TYPE_PER_DATA,
	STATISTICS_STORAGE_TYPE_TIMED
} from "../../constants/statistics";
import {AxisData} from "../../data/study/AxisData";
import {StatisticsCollection} from "../../data/statistics/StatisticsCollection";
import {StatisticsEntry} from "../../data/statistics/StatisticsEntry";
import {StatisticsEntryPerData, StatisticsEntryPerValue, StatisticsEntryTimed} from "../../data/statistics/StatisticsDataRecord";
import {CsvRow} from "./CsvRow";
import {CsvCell} from "./CsvCell";
import {CsvData} from "./CsvWorker";
import {AxisContainer} from "../../data/study/AxisContainer";

const ONE_DAY = 86400 //in seconds: 60*60*24

export class StatisticsCreator {
	private csvData: CsvData
	
	constructor(csvData: CsvData) {
		this.csvData = csvData
	}
	
	private getStorageType(dataType: number): number {
		switch(dataType) {
			case STATISTICS_DATATYPES_XY:
				return STATISTICS_STORAGE_TYPE_PER_DATA
			case STATISTICS_DATATYPES_FREQ_DISTR:
				return STATISTICS_STORAGE_TYPE_FREQ_DISTR
			case STATISTICS_DATATYPES_DAILY:
			case STATISTICS_DATATYPES_SUM:
			default:
				return STATISTICS_STORAGE_TYPE_TIMED
		}
	}
	private addTimedStatisticsEntryData(value: string, entry: StatisticsEntry, row: CsvRow, responseTimeColumnNum: number, uploadedColumnNum: number): void {
		const cells = row.columnCells
		let intValue = parseInt(value)
		let day = cells[responseTimeColumnNum] == undefined
			? NaN //can happen if there is an error in dataset
			: Math.floor(Math.round(parseInt(cells[responseTimeColumnNum].realValue) / 1000) / ONE_DAY) * ONE_DAY
		
		if(isNaN(day)) { //fallback
			day = cells[responseTimeColumnNum] === undefined
				? NaN //can happen if there is an error in dataset
				: Math.floor(Math.round(parseInt(cells[uploadedColumnNum].realValue)) / ONE_DAY) * ONE_DAY
			if(isNaN(day))
				return
		}
		if(isNaN(intValue))
			intValue = 0
		
		if(!isNaN(day)) {
			const timedEntryData = entry.data as StatisticsEntryTimed
			if(!timedEntryData.hasOwnProperty(day))
				timedEntryData[day] = {sum: intValue, count: 1}
			else {
				timedEntryData[day].sum += intValue
				++timedEntryData[day].count
			}
		}
	}
	private addFreqDistrEntryData(value: string, entry: StatisticsEntry): void {
		const perValueEntryData = entry.data as StatisticsEntryPerValue
		if(perValueEntryData.hasOwnProperty(value))
			++perValueEntryData[value]
		else
			perValueEntryData[value] = 1
	}
	private addPerDataEntryData(value: string, entry: StatisticsEntry): void {
		const perDataEntryData = entry.data as StatisticsEntryPerData
		perDataEntryData[entry.entryCount] = parseFloat(value)
	}
	private statisticConditionsAreMet(conditionType: number, conditions: Conditions[], cells: CsvCell[]): boolean {
		if(conditionType == CONDITION_TYPE_ALL)
			return true
		const conditionTypeIsAnd = conditionType == CONDITION_TYPE_AND
		const conditionTypeIsOr = conditionType == CONDITION_TYPE_OR
		
		let conditionIsMet = !conditionTypeIsOr
		
		for(let i = conditions.length - 1; i >= 0; --i) {
			const condition = conditions[i]
			const conditionColumn = cells[this.csvData.getColumnNum(condition.key.get())]
			if(conditionColumn == undefined) //can happen if there was an error in the dataset
				continue
			const conditionCompareValue = conditionColumn.special ? conditionColumn.realValue : conditionColumn.value
			let isTrue
			const conditionValue = condition.value.get()
			switch(condition.operator.get()) {
				case CONDITION_OPERATOR_EQUAL:
					isTrue = conditionCompareValue == conditionValue
					break
				case CONDITION_OPERATOR_UNEQUAL:
					isTrue = conditionCompareValue != conditionValue
					break
				case CONDITION_OPERATOR_GREATER:
					isTrue = conditionCompareValue >= conditionValue
					break
				case CONDITION_OPERATOR_LESS:
					isTrue = conditionCompareValue <= conditionValue
					break
				default:
					isTrue = true
			}
			if(isTrue) {
				if(conditionTypeIsOr) {
					conditionIsMet = true
					break
				}
			}
			else if(conditionTypeIsAnd) {
				conditionIsMet = false
				break
			}
		}
		
		return conditionIsMet
	}
	private createDataFromAxis(axis: AxisData, dataType: number, statisticsObj: StatisticsCollection): void {
		const storageType = this.getStorageType(dataType)
		const timeInterval = dataType == STATISTICS_DATATYPES_DAILY || dataType == STATISTICS_DATATYPES_SUM ? ONE_DAY : 0
		const visibleRows = this.csvData.visibleRowsList
		const responseTimeColumnNum = this.csvData.getColumnNum("responseTime")
		const uploadedColumnNum = this.csvData.getColumnNum("uploaded")
		
		if(axis.variableName.get().length == 0)
			return
		const variableName = axis.variableName.get()
		if(variableName.length == 0)
			return
		
		const columnNum = this.csvData.getColumnNum(variableName)
		const conditions = axis.conditions.get()
		const conditionType = axis.conditionType.get()
		
		if(!statisticsObj.hasOwnProperty(variableName))
			statisticsObj[variableName] = []
		const a = statisticsObj[variableName]
		
		
		const observedVariableIndex = axis.observedVariableIndex.get()
		if(!a[observedVariableIndex]) {
			a[observedVariableIndex] = {
				storageType: storageType,
				timeInterval: timeInterval,
				entryCount: 0,
				data: {}
			}
		}
		const entry = a[observedVariableIndex]
		
		
		let addEntryData: (value: string, row: CsvRow) => void
		
		switch(storageType) {
			case STATISTICS_STORAGE_TYPE_TIMED:
				addEntryData = (value: string, row: CsvRow) => this.addTimedStatisticsEntryData(value, entry, row, responseTimeColumnNum, uploadedColumnNum)
				break
			case STATISTICS_STORAGE_TYPE_FREQ_DISTR:
				addEntryData = (value: string) => this.addFreqDistrEntryData(value, entry)
				break
			case STATISTICS_STORAGE_TYPE_PER_DATA:
				addEntryData = (value: string) => this.addPerDataEntryData(value, entry)
				break
			default:
				return
		}
		
		visibleRows.forEach((row) => {
			const cells = row.columnCells
			const columnCell = cells[columnNum]
			if(!columnCell) //can happen if there was an error in the dataset
				return
			
			const value = columnCell.special ? columnCell.realValue : columnCell.value
			
			if(!this.statisticConditionsAreMet(conditionType, conditions, cells))
				return
			
			addEntryData(value, row)
			++entry.entryCount
		})
	}
	public create(axisContainerArray: AxisContainer[], dataType: number): StatisticsCollection {
		this.csvData.indexing()
		const statisticsObj: StatisticsCollection = {}
		
		axisContainerArray.forEach((axisContainer) => {
			this.createDataFromAxis(axisContainer.yAxis, dataType, statisticsObj)
			this.createDataFromAxis(axisContainer.xAxis, dataType, statisticsObj)
		})
		
		return statisticsObj
	}
}