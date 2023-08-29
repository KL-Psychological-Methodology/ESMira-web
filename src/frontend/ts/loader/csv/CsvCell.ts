import {CsvRow} from "./CsvRow";
import {CsvSpecialType} from "./CsvSpecialType";

export class CsvCell {
	public readonly row: CsvRow
	public value: string //the value that is shown in the data viewer
	public realValue: string
	public special
	public specialType?: CsvSpecialType
	
	constructor(row: CsvRow, value: string, realValue: string, specialType?: CsvSpecialType) {
		this.row = row
		this.value = value
		this.realValue = realValue
		this.specialType = specialType
		this.special = specialType != undefined
	}
}

export interface CsvCellsWithMeta {
	cells: CsvCell[]
	meta: { visible: boolean }
}