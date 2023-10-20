import {CsvCell} from "./CsvCell";

export class CsvRow {
	public readonly shownIndex: number //rows reversed. That means shownIndex and arrayIndex are oposite of each other
	public arrayIndex: number
	
	public readonly columnCells: CsvCell[]
	public hiddenSum: number = 0 //how many columns are hiding this row
	public visible: boolean = true
	public marked: boolean = false
	
	constructor(arrayIndex: number, shownIndex: number, columnCells: CsvCell[]) {
		this.arrayIndex = arrayIndex
		this.shownIndex = shownIndex
		this.columnCells = columnCells
	}
}