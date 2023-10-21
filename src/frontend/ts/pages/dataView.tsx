import {SectionContent} from "../site/SectionContent";
import m, {Component, Vnode, VnodeDOM} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {FILE_ADMIN, FILE_AUDIO, FILE_IMAGE, FILE_RESPONSES} from "../constants/urls";
import {CsvLoader} from "../loader/csv/CsvLoader";
import {closeDropdown, createNativeDropdown, DropdownMenu} from "../widgets/DropdownMenu";
import {SearchWidget} from "../widgets/SearchWidget";
import {LoadingSpinner} from "../widgets/LoadingSpinner";
import {ValueListInfo} from "../loader/csv/ValueListInfo";
import {CsvRow} from "../loader/csv/CsvRow";
import {CsvSpecialType} from "../loader/csv/CsvSpecialType";
import {DATAVIEWER_SKIPPED_COLUMNS} from "../constants/csv";
import {StaticValues} from "../site/StaticValues";
import {BtnReload} from "../widgets/BtnWidgets";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";

const ROW_HEIGHT = 25;

interface DropDownOptions {
	updateTableContent: () => void
	csvLoader: CsvLoader
	columnName: string
	columnIndex: number
	showFilterForHeader: ObservablePrimitive<boolean>
}
class DropDownComponent implements Component<DropDownOptions, any> {
	private csvLoader?: CsvLoader
	private showFilterForHeader?: ObservablePrimitive<boolean>
	private columnIndex: number = 0
	private valueListInfo?: ValueListInfo[]
	private readonly disabledValues: Record<string, boolean> = {}
	private checkedCount: number = 0
	private isAllUnchecked: boolean = true
	private updateTableContent: () => void = () => {/*will be replaced in oncreate()*/}
	
	public async oncreate(vNode: VnodeDOM<DropDownOptions, any>): Promise<void> {
		this.updateTableContent = vNode.attrs.updateTableContent
		this.csvLoader = vNode.attrs.csvLoader
		this.showFilterForHeader = vNode.attrs.showFilterForHeader
		this.columnIndex = vNode.attrs.columnIndex
		
		const valueListInfo = await this.csvLoader.getValueListInfo(vNode.attrs.columnName, false, true)
		this.valueListInfo = valueListInfo
		let isAllUnchecked = true
		valueListInfo.forEach((entry) => {
			if(!entry.visible) {
				this.disabledValues[entry.name] = true
				isAllUnchecked = false
			}
		})
		this.isAllUnchecked = isAllUnchecked
		m.redraw()
	}
	
	private async toggleAll(): Promise<void> {
		if(this.isAllUnchecked) {
			const valueListInfo = this.valueListInfo
			if(!valueListInfo)
				return
			for(let i=valueListInfo.length-1; i >= 0; --i) {
				const info = valueListInfo[i]
				this.disabledValues[info.name] = true
				await this.csvLoader?.filterByValue(false, this.columnIndex, info.name)
			}
			this.checkedCount = this.valueListInfo?.length ?? 0
			this.isAllUnchecked = false
			this.showFilterForHeader?.set(true)
		}
		else {
			for(let key in this.disabledValues) {
				delete this.disabledValues[key]
				await this.csvLoader?.filterByValue(true, this.columnIndex, key)
			}
			this.checkedCount = 0
			this.isAllUnchecked = true
			this.showFilterForHeader?.set(false)
		}
		this.updateTableContent()
	}
	
	private async toggleFilter(value: string): Promise<void> {
		const enable = this.disabledValues.hasOwnProperty(value)
		if(enable) {
			--this.checkedCount
			delete this.disabledValues[value]
			if(this.checkedCount == 0)
				this.showFilterForHeader?.set(false)
		}
		else {
			++this.checkedCount
			this.disabledValues[value] = true
			if(this.checkedCount == 1)
				this.showFilterForHeader?.set(true)
		}
		
		await this.csvLoader?.filterByValue(enable, this.columnIndex, value)
		this.updateTableContent()
	}
	
	public view(): Vnode<any, any> {
		const valueList = this.valueListInfo!
		if(valueList == undefined)
			return <div class="center">{LoadingSpinner()}</div>
		
		return <div>
			{SearchWidget((tools) =>
				<div>
					<input class="search small vertical" type="text" onkeyup={tools.updateSearchFromEvent.bind(tools)}/>
					<label>
						<input type="checkbox" onchange={this.toggleAll.bind(this)} checked={this.isAllUnchecked}/>
						<span class="smallText">{Lang.get("toggle_all", valueList.length)}</span>
					</label>
					{valueList.map((entry) =>
						tools.searchView(entry.name,
							<label class="line noTitle noDesc">
								<input type="checkbox" onchange={this.toggleFilter.bind(this, entry.name)} checked={!this.disabledValues.hasOwnProperty(entry.name)}/>
								<span class="smallText">{entry.totalCount === entry.count ? `${entry.name} (${entry.totalCount})` : `${entry.name} (${entry.count}/${entry.totalCount})`}</span>
							</label>
						)
					)}
				</div>)}
		</div>
	}
}

interface DataComponentInterface {
	csvLoader: CsvLoader
	studyId: number
	filterKey?: string
	filterValue?: string
}
class DataComponent implements Component<DataComponentInterface, any> {
	private scrollRoot?: HTMLElement
	private heightViewForScrollbar?: HTMLElement
	private markedRowsInfoView?: HTMLElement
	private tableContent?: HTMLElement
	
	private filterParentObservable = new ObservablePrimitive<boolean>(false, null, "filterParentObservable")
	private shownHeaderNames: string[] = []
	private showFilterForHeaderArray: ObservablePrimitive<boolean>[] = []
	private userIdColumnNum: number = 0
	private entryIdColumnNum: number = 0
	
	private columnWidths: number[] = []
	private maxElementsDisplayed: number = 0
	private lastScrollY: number = 0
	private markedRowsNum: number = 0
	private studyId: number = 0
	
	private csvLoader?: CsvLoader
	
	private async scrollUpdate(): Promise<void> {
		let current_scrollY = this.scrollRoot?.scrollTop ?? 0
		if(current_scrollY !== this.lastScrollY)
			await this.updateTableContent()
		this.lastScrollY = current_scrollY
	}
	
	private updateVisibleRowNum(): void {
		this.maxElementsDisplayed = Math.floor((this.scrollRoot?.clientHeight ?? 0) / ROW_HEIGHT - 1) //-1: header
	}
	
	private async markRow(row: CsvRow, rowView: HTMLElement): Promise<void> {
		if(rowView.classList.contains("marked")) {
			rowView.classList.remove("marked")
			await this.csvLoader?.mark(false, row.arrayIndex)
			row.marked = false
			--this.markedRowsNum
		}
		else {
			rowView.classList.add("marked");
			await this.csvLoader?.mark(true, row.arrayIndex)
			row.marked = true
			++this.markedRowsNum
		}
		
		if(this.markedRowsInfoView) {
			if(this.markedRowsNum) {
				this.markedRowsInfoView.innerText = Lang.get("selected_rows_x", this.markedRowsNum)
				this.markedRowsInfoView.classList.remove("hidden")
			}
			else
				this.markedRowsInfoView.classList.add("hidden")
		}
	}
	
	private createTrView(row: CsvRow): HTMLElement {
		const tr = document.createElement("tr")
		const csvLoader = this.csvLoader
		if(!csvLoader)
			return tr
		
		tr.style.cssText = `height:${ROW_HEIGHT}px`
		if(row.marked)
			tr.classList.add("marked")
		tr.addEventListener("click", this.markRow.bind(this, row, tr))
		
		const th = document.createElement("th")
		th.innerText = (row.shownIndex+1).toString()
		tr.appendChild(th)
		
		row.columnCells.forEach((cell, columnNum) => {
			let td: HTMLElement
			let hoverInfo: HTMLElement | null = null
			switch(cell.specialType) {
				case CsvSpecialType.timestamp:
					td = document.createElement("td")
					td.style.cssText = "font-style: italic"
					
					const prettyValueDiv = document.createElement("div")
					prettyValueDiv.classList.add("prettyValue")
					prettyValueDiv.innerText = cell.value
					td.appendChild(prettyValueDiv)
					
					const realValueDiv = document.createElement("div")
					realValueDiv.classList.add("realValue")
					realValueDiv.innerText = cell.realValue
					td.appendChild(realValueDiv)
					
					hoverInfo = document.createElement("div")
					hoverInfo.classList.add("center")
					hoverInfo.innerText = `${cell.value}\n${Lang.getWithColon("timestamp")} ${cell.realValue}`
					break
				case CsvSpecialType.image:
					const imageUrl = FILE_IMAGE
						.replace("%1", this.studyId.toString())
						.replace("%2", row.columnCells[this.userIdColumnNum].value)
						.replace("%3", row.columnCells[this.entryIdColumnNum].value)
						.replace("%4", csvLoader.headerNames[columnNum])
					
					td = document.createElement("td")
					td.style.cssText = "font-style: italic"
					const imageA = document.createElement("a")
					imageA.href = imageUrl
					imageA.target = "_blank"
					imageA.innerText = cell.value
					td.appendChild(imageA)
					
					const imageView = document.createElement("img")
					imageView.style.cssText = "max-width: 500px; max-height: 500px;"
					imageView.src = imageUrl
					break
				case CsvSpecialType.audio:
					const audioUrl = FILE_AUDIO
						.replace("%1", this.studyId.toString())
						.replace("%2", row.columnCells[this.userIdColumnNum].value)
						.replace("%3", row.columnCells[this.entryIdColumnNum].value)
						.replace("%4", csvLoader.headerNames[columnNum])
					
					td = document.createElement("td")
					td.style.cssText = "font-style: italic"
					const audioA = document.createElement("a")
					audioA.href = audioUrl
					audioA.target = "_blank"
					audioA.innerText = cell.value
					td.appendChild(audioA)
					break
				case CsvSpecialType.empty:
					td = document.createElement("td")
					td.style.cssText = "font-style: italic"
					td.innerText = cell.value
					break
				case CsvSpecialType.skipped:
					return
				default:
					td = document.createElement("td")
					td.innerText = cell.value
			}
			
			if(hoverInfo != null) {
				createNativeDropdown("dataHoverInfo", td, () => hoverInfo!, undefined, "mouseenter")
				td.addEventListener("mouseleave", () => { closeDropdown("dataHoverInfo") })
			}
			tr.appendChild(td);
		})
		
		return tr
	}
	
	private emptyTableContent(): void {
		while(this.tableContent?.hasChildNodes()) {
			this.tableContent.removeChild(this.tableContent.firstChild!)
		}
	}
	private async updateTableContent(): Promise<void> {
		const csvLoader = this.csvLoader
		if(!csvLoader)
			return Promise.resolve()
		
		const bottomIndex = Math.min(csvLoader.visibleRowsCount, Math.ceil((this.scrollRoot?.scrollTop ?? 0) / ROW_HEIGHT) + this.maxElementsDisplayed - 1) //-1 index starts with 0
		const rows = await csvLoader.getVisibleRows(Math.max(bottomIndex - this.maxElementsDisplayed + 1, 0), bottomIndex + 1)
		
		if(this.heightViewForScrollbar)
			this.heightViewForScrollbar.style.height = `${(csvLoader.visibleRowsCount+1) * ROW_HEIGHT}px` //+1: for header line
		
		const tableContent = this.tableContent
		if(!tableContent)
			return
		this.emptyTableContent()

		//Rows are reversed when adding them to DOM. Remember that the table shows the newest values first (reversed!)
		rows.forEach((row) => {
			tableContent.appendChild(this.createTrView(row))
		})
	}
	
	private getHeaderDropDown(csvLoader: CsvLoader, columnIndex: number, columnName: string): Vnode<any, any> {
		return m(DropDownComponent, {
			updateTableContent: this.updateTableContent.bind(this),
			csvLoader: csvLoader,
			columnName: columnName,
			columnIndex: columnIndex,
			showFilterForHeader: this.showFilterForHeaderArray[columnIndex]
		})
	}
	
	public view(vNode: Vnode<DataComponentInterface, any>): Vnode<any, any> {
		const csvLoader = vNode.attrs.csvLoader
		
		return <div class="dataContainer">
			<div class="heightViewForScrollbar"></div>
			<table class="dataTable">
				<thead class="dataHeader">
				<th style={`min-width: ${this.columnWidths[0] ?? 0}px`}></th>
				{this.shownHeaderNames.map((columnName, columnIndex) =>
					DropdownMenu("dataHeaderMenu",
						<th style={`min-width: ${this.columnWidths[columnIndex + 1] ?? 0}px`}>
							<span class={this.showFilterForHeaderArray[columnIndex].get() ? "highlight clickable" : "clickable"} style={`height: ${ROW_HEIGHT}px`}>{columnName}</span>
						</th>,
						() => this.getHeaderDropDown(csvLoader, columnIndex, columnName)
					)
				)}
				</thead>
				<tbody class="dataContent">
				
				</tbody>
				
			</table>
			<pre class="markedRowsInfoView hidden smallText highlight infoSticker"></pre>
		</div>
	}
	public async oncreate(vNode: VnodeDOM<DataComponentInterface, any>): Promise<void> {
		this.studyId = vNode.attrs.studyId
		this.csvLoader = vNode.attrs.csvLoader
		
		this.scrollRoot = vNode.dom as HTMLElement
		this.tableContent = vNode.dom.getElementsByClassName("dataContent")[0] as HTMLElement
		this.heightViewForScrollbar = vNode.dom.getElementsByClassName("heightViewForScrollbar")[0] as HTMLElement
		this.markedRowsInfoView = vNode.dom.getElementsByClassName("markedRowsInfoView")[0] as HTMLElement
		
		//These columns do not exist in web_access or in old studies
		//But these values are only needed for photo items, which are new and dont exist in web_access
		//So we can ignore them if they do not exist
		this.userIdColumnNum = this.csvLoader.hasColumn("userId") ? this.csvLoader.getColumnNum("userId") : 0
		this.entryIdColumnNum = this.csvLoader.hasColumn("userId") ? this.csvLoader.getColumnNum("entryId") : 0
		
		
		const skippedIndex: Record<string, boolean> = {}
		for(const entry of DATAVIEWER_SKIPPED_COLUMNS) {
			skippedIndex[entry] = true
		}
		
		for(const headerName of this.csvLoader.headerNames) {
			if(skippedIndex.hasOwnProperty(headerName))
				continue
			
			this.shownHeaderNames.push(headerName)
			this.showFilterForHeaderArray.push(new ObservablePrimitive<boolean>(false, this.filterParentObservable, headerName))
		}
		
		
		this.scrollRoot.addEventListener("scroll", this.scrollUpdate.bind(this))
		this.updateVisibleRowNum()
		await this.updateTableContent()
		
		//Making sure changing that column width stays the same when row content changes:
		// (has to happen after tableHeader was added to the DOM)
		const headerChildren = vNode.dom.getElementsByClassName("dataHeader")[0].childNodes
		headerChildren.forEach((view) => {
			const htmlView = view as HTMLElement
			this.columnWidths.push(htmlView.offsetWidth)
		})
		
		const filterKey = vNode.attrs.filterKey
		const filterValue = vNode.attrs.filterValue
		if(filterKey && filterValue && this.csvLoader) {
			await this.csvLoader.filterEntireColumn(false, filterKey)
			await this.csvLoader.filterByValue(true, filterKey, filterValue)
			await this.updateTableContent()
			const columnIndex = this.csvLoader.getColumnNum(filterKey)
			this.showFilterForHeaderArray[columnIndex].set(true)
		}
		
		this.filterParentObservable.addObserver(() => m.redraw())
		this.filterParentObservable.addObserver(() => console.log(234324234))
		m.redraw()
	}
	public onupdate(): void {
		this.updateVisibleRowNum()
	}
	public onremove(): void {
		closeDropdown("dataHoverInfo")
	}
}

export class Content extends SectionContent {
	private readonly csvLoader: CsvLoader
	
	public static preLoad(section: Section): Promise<any>[] {
		if(!window.Worker) {
			section.loader.error(Lang.get('error_no_webWorkers'))
			return []
		}
		switch(section.sectionValue) {
			case "logins":
				return [
					CsvLoader.fromUrl(section.loader, `${FILE_ADMIN}?type=GetLoginHistory`)
				]
			case "questionnaire":
			case "backup":
			case "general":
			default:
				return [
					section.getStudyPromise().then((study) => {
						const studyId = study.id.get()
						const fileName = section.getStaticString(section.sectionValue == "questionnaire" ? "qId" : "file") ?? "error"
						
						return CsvLoader.fromUrl(
							section.loader,
							FILE_RESPONSES.replace('%1', studyId.toString()).replace('%2', fileName),
							study.getInputNamesPerType()
						)
					})
				]
		}
	}
	
	constructor(section: Section, csvLoader: CsvLoader) {
		super(section)
		this.csvLoader = csvLoader
	}
	
	public titleExtra(): Vnode<any, any> | null {
		return BtnReload(this.section.reload.bind(this.section), Lang.get("reload"))
	}
	
	public title(): string {
		const titlePart = `(${Lang.get("entry_count", this.csvLoader.visibleRowsCount)})`
		switch(this.section.sectionValue) {
			case "logins":
				return Lang.get("login_history")
			case "questionnaire":
				return `${this.getQuestionnaireOrThrow().getTitle()}.csv ${titlePart}`
			case "backup":
				const fileName = this.getStaticString("file") ?? ""
				const [match, date, internalId] = fileName.match(/^(\d{4}-\d{2}-\d{2})_(\d+)$/) ?? []
				if(match)
					return `${date} ${this.getQuestionnaireOrNull(parseInt(internalId))?.getTitle() ?? internalId} ${titlePart}`
				else
					return fileName
			default:
				return `${this.getStaticString("file") ?? Lang.get("error_unknown")} ${titlePart}`
		}
	}
	
	public getView(): Vnode<any, any> {
		const filterKey = this.getStaticString("filter")
		const studyId = this.getStaticInt("id") ?? -1
		if(filterKey) {
			const filterValue = this.getStaticString(filterKey as StaticValues)
			if(filterValue)
				return m(DataComponent, {csvLoader: this.csvLoader, studyId: studyId, filterKey: filterKey, filterValue: atob(filterValue)})
		}
		return m(DataComponent, {csvLoader: this.csvLoader, studyId: studyId})
	}
}