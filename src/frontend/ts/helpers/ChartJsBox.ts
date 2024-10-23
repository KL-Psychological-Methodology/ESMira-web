import {StatisticsCollection} from "../data/statistics/StatisticsCollection";
import {ChartData} from "../data/study/ChartData";
import {
	Chart,
	ArcElement,
	BarController,
	BarElement,
	BubbleController,
	CategoryScale,
	Decimation,
	DoughnutController,
	Filler,
	Legend,
	LinearScale,
	LineController,
	LineElement,
	LogarithmicScale,
	PieController,
	PointElement,
	PolarAreaController,
	RadarController,
	RadialLinearScale,
	ScatterController,
	TimeScale,
	TimeSeriesScale,
	Title,
	Tooltip,
	ChartDataset,
	ChartOptions, ChartType, Plugin
} from "chart.js/auto";
import ChartDataLabels from "chartjs-plugin-datalabels";
import {
	STATISTICS_CHARTTYPES_BARS,
	STATISTICS_CHARTTYPES_LINE,
	STATISTICS_CHARTTYPES_LINE_FILLED,
	STATISTICS_CHARTTYPES_PIE,
	STATISTICS_CHARTTYPES_SCATTER,
	STATISTICS_DATATYPES_DAILY,
	STATISTICS_DATATYPES_FREQ_DISTR,
	STATISTICS_DATATYPES_SUM,
	STATISTICS_DATATYPES_XY, STATISTICS_VALUETYPES_COUNT,
	STATISTICS_VALUETYPES_MEAN, STATISTICS_VALUETYPES_SUM
} from "../constants/statistics";
import {AxisContainer} from "../data/study/AxisContainer";
import {StatisticsEntryPerData, StatisticsEntryPerValue, StatisticsEntryTimed} from "../data/statistics/StatisticsDataRecord";
import {Lang} from "../singletons/Lang";
import {StatisticsDataEntry} from "../data/statistics/StatisticsDataEntry";
import {Point} from "chart.js/dist/types/geometric";

const ONE_DAY = 86400 //in seconds: 60*60*24
const BACKGROUND_ALPHA = 0.7
const CHART_MIN_ENTRY_WIDTH = 35
const MAX_VARIABLE_LABEL_LENGTH = 30
export const CHART_COLORS = [
	//Thanks to: https://sashamaps.net/docs/resources/20-colors/
	'#e6194B',
	'#f58231',
	'#ffe119',
	'#bfef45',
	'#3cb44b',
	'#42d4f4',
	'#4363d8',
	'#911eb4',
	'#f032e6',
	'#a9a9a9',
	
	'#fabed4',
	'#ffd8b1',
	'#fffac8',
	'#aaffc3',
	'#dcbeff',
	'#ffffff',
	
	'#800000',
	'#9A6324',
	'#808000',
	'#469990',
	'#000075',
	'#000000',
]
export function getChartColor(i: number): string {
	return CHART_COLORS[i % CHART_COLORS.length]
}

let isRegistered = false
function registerChart(): void {
	Chart.register(
		ArcElement,
		LineElement,
		BarElement,
		PointElement,
		BarController,
		BubbleController,
		DoughnutController,
		LineController,
		PieController,
		PolarAreaController,
		RadarController,
		ScatterController,
		CategoryScale,
		LinearScale,
		LogarithmicScale,
		RadialLinearScale,
		TimeScale,
		TimeSeriesScale,
		Decimation,
		Filler,
		Legend,
		Title,
		Tooltip
	)
	Chart.register(ChartDataLabels)
}

type DataPoint = (number | null | Point)[]

export class ChartJsBox {
	private chartJs?: Chart
	private readonly chart: ChartData
	private readonly optionFill: string
	private readonly forScatterPlot: boolean
	
	constructor(parentView: HTMLElement, personalStatistics: StatisticsCollection, publicStatistics: StatisticsCollection, chart: ChartData, noSort: boolean = false) {
		if(!isRegistered) {
			registerChart()
			isRegistered = true
		}
		const chartType = chart.chartType.get()
		const dataType = chart.dataType.get()
		
		
		this.chart = chart
		this.optionFill = this.getOptionFill(chartType)
		this.forScatterPlot = this.getForScatterPlot(chartType)
		
		let dataSetCreator: DataSetCreator
		let datasets: ChartDataset[]
		try {
			dataSetCreator = this.getDataSetCreator(dataType, noSort)
			datasets = dataSetCreator.create(personalStatistics, publicStatistics)
		}
		catch(e) {
			console.error(e)
			const errorView = document.createElement("div")
			errorView.classList.add("spacingTop")
			errorView.classList.add("spacingBottom")
			errorView.innerText = Lang.get("error_faulty_chart")
			parentView.append(errorView)
			return
		}
		
		//create chart:
		if(chart.title.get().length) {
			const titleView = document.createElement("h2")
			titleView.innerText = chart.title.get()
			titleView.classList.add("center")
			parentView.appendChild(titleView)
		}
		if(chart.chartDescription.get().length) {
			const descView = document.createElement("p")
			descView.innerHTML = chart.chartDescription.get()
			parentView.appendChild(descView)
		}
		
		
		const scrollable = chartType !== STATISTICS_CHARTTYPES_PIE && dataType !== STATISTICS_DATATYPES_XY && parentView.clientWidth / dataSetCreator.labels.length < CHART_MIN_ENTRY_WIDTH
		const width = scrollable ? `${dataSetCreator.labels.length*CHART_MIN_ENTRY_WIDTH}px` : "100%"
		
		
		const windowView = document.createElement("div")
		windowView.classList.add("chartWindow")
		
		const legendView = document.createElement("div")
		legendView.classList.add("legend")
		
		const scrollView = document.createElement("div")
		scrollView.classList.add("scrollEl")
		if(chartType === STATISTICS_CHARTTYPES_PIE)
			scrollView.classList.add("pie")
		
		const chartView = document.createElement("div")
		chartView.style.cssText = `width: ${width}`
		chartView.classList.add("chartEl")
		
		const canvas = document.createElement("canvas")
		canvas.style.cssText = `height: 200px; width: ${width}`
		
		
		chartView.appendChild(canvas)
		scrollView.appendChild(chartView)
		windowView.appendChild(legendView)
		windowView.appendChild(scrollView)
		parentView.appendChild(windowView)
		
		if(scrollable)
			chartView.classList.add("scrollable")
		
		this.chartJs = new Chart(canvas.getContext('2d') as CanvasRenderingContext2D, {
			type: this.getChartJsType(chartType),
			data: {
				labels: dataSetCreator.labels,
				datasets: datasets
			},
			options: this.getChartOptions(chartType, datasets, chart.inPercent.get()),
			plugins: this.getChartPlugins(legendView)
		})
	}
	
	private getOptionFill(chartType: number): string {
		return chartType == STATISTICS_CHARTTYPES_LINE_FILLED ? "origin" : ""
	}
	private getForScatterPlot(chartType: number): boolean {
		return chartType == STATISTICS_CHARTTYPES_SCATTER
	}
	
	private getChartJsType(chartType: number): ChartType {
		switch(chartType) {
			case STATISTICS_CHARTTYPES_LINE_FILLED:
			case STATISTICS_CHARTTYPES_LINE:
				Chart.defaults.elements.line.spanGaps = true
				return "line"
			case STATISTICS_CHARTTYPES_SCATTER:
				return "scatter"
			case STATISTICS_CHARTTYPES_PIE:
				return "pie"
			case STATISTICS_CHARTTYPES_BARS:
			default:
				return "bar"
		}
	}
	
	private getDataSetCreator(dataType: number, noSort: boolean = false): DataSetCreator {
		switch(dataType) {
			case STATISTICS_DATATYPES_DAILY:
				return new DailyDataSetCreator(this.chart, this.forScatterPlot, this.optionFill)
			case STATISTICS_DATATYPES_FREQ_DISTR:
				return new FreqDistrDataSetCreator(this.chart, this.forScatterPlot, this.optionFill, noSort)
			case STATISTICS_DATATYPES_SUM:
				return new SumDataSetCreator(this.chart, this.forScatterPlot, this.optionFill)
			case STATISTICS_DATATYPES_XY:
				return new XyDataSetCreator(this.chart, this.forScatterPlot, this.optionFill)
			default:
				throw new Error(`Unknown data type: ${dataType}`)
		}
	}
	
	private getChartPlugins(legendView: HTMLElement): Plugin[] {
		return [{ //legend
			id: "legend",
			afterUpdate: function(chartJs) {
				while(legendView.hasChildNodes()) {
					legendView.removeChild(legendView.firstElementChild!)
				}
				const legendPlugin = chartJs.options.plugins?.legend?.labels
				if(!legendPlugin || !legendPlugin.generateLabels)
					return
				const legendItems = legendPlugin.generateLabels(chartJs);
				for(const item of legendItems) {
					if(item.text == undefined || item.text == "")
						continue
					const line = document.createElement("div");
					line.classList.add("line")
					
					const span = document.createElement("span");
					span.style.cssText = `background-color: ${item.fillStyle}`
					span.classList.add("colorRect")
					
					const small = document.createElement("small");
					small.innerText = item.text
					
					line.appendChild(span)
					line.appendChild(small)
					legendView.appendChild(line);
				}
			}
		}]
	}
	
	private getChartOptions(chartType: number, dataSets: ChartDataset[], inPercent: boolean): ChartOptions {
		let verticalPadding = chartType === STATISTICS_CHARTTYPES_PIE ? 40 : 0;
		return {
			layout: {
				padding: {
					left: verticalPadding,
					right: verticalPadding,
					top: 20,
					bottom: verticalPadding
				}
			},
			responsive: true,
			scales: {
				y: {
					display: chartType === STATISTICS_CHARTTYPES_SCATTER
				}
			},
			plugins: {
				legend: {
					display: false
				},
				datalabels: {
					anchor: "end",
					align: "end",
					offset: 0,
					display: chartType === STATISTICS_CHARTTYPES_SCATTER ? false : ({datasetIndex, dataIndex}) => {
						return dataSets[datasetIndex].data[dataIndex] !== 0 ? "auto" : false
					},
					formatter: inPercent ? (value) => { return `${value}%` } : undefined
				}
			}
		}
	}
}

abstract class DataSetCreator {
	protected chart: ChartData
	public readonly labels: string[] = []
	public readonly dataSets: ChartDataset[] = []
	protected forScatterPlot: boolean
	protected optionFill: string
	
	constructor(chart: ChartData, forScatterPlot: boolean = false, optionFill: string = "") {
		this.chart = chart
		this.forScatterPlot = forScatterPlot
		this.optionFill = optionFill
	}
	protected createDataSet(label: string, data: DataPoint, color: string): ChartDataset {
		let backgroundColor: string[] | string
		let borderColor: string[] | string
		
		if(this.chart.chartType.get() === STATISTICS_CHARTTYPES_PIE && data.length > 1) {
			backgroundColor = []
			borderColor = []
			for(let i=0, max=this.labels.length; i<max; ++i) {
				const currentColor = getChartColor(i)
				
				backgroundColor.push(this.hexToRGB(currentColor, BACKGROUND_ALPHA))
				borderColor.push(currentColor)
			}
		}
		else {
			backgroundColor = this.hexToRGB(color, BACKGROUND_ALPHA);
			borderColor = this.hexToRGB(color, 1)
		}
		
		return {
			label: label,
			data: data,
			backgroundColor: backgroundColor,
			borderColor: borderColor,
			borderWidth: 1,
			fill: this.optionFill,
		}
	}
	
	protected hexToRGB(hex: string, alpha: number): string {
		const r = parseInt(hex.slice(1, 3), 16)
		const g = parseInt(hex.slice(3, 5), 16)
		const b = parseInt(hex.slice(5, 7), 16)
		return `rgba(${r}, ${g}, ${b}, ${alpha})`
	}
	
	abstract create(personalStatistics: StatisticsCollection, publicStatistics: StatisticsCollection): ChartDataset[]
}

class DailyDataSetCreator extends DataSetCreator {
	private firstDay: number = Number.MAX_VALUE
	private lastDay: number = 0
	
	private setFirstAndLastDay(axisContainerArray: AxisContainer[], statistics: StatisticsCollection): void {
		for(const axisContainer of axisContainerArray) {
			const yAxis = axisContainer.yAxis
			const variableName = yAxis.variableName.get()
			
			if(!statistics.hasOwnProperty(variableName) || !statistics[variableName][yAxis.observedVariableIndex.get()])
				continue
			
			const rawYData = statistics[variableName][yAxis.observedVariableIndex.get()].data as StatisticsEntryTimed
			if(!rawYData)
				continue
			const keys = Object.keys(rawYData).sort() //should be sorted already but lets make sure
			
			if(parseInt(keys[0]) < this.firstDay)
				this.firstDay = parseInt(keys[0])
			const lastValue = keys[keys.length - 1]
			if(parseInt(lastValue) > this.lastDay)
				this.lastDay = parseInt(lastValue)
		}
	}
	
	private getYValue(valueType: number, rawY: StatisticsDataEntry) : number | null {
		switch(valueType) {
			case STATISTICS_VALUETYPES_MEAN:
				return rawY === undefined ? null : Math.round(rawY.sum / rawY.count * 100)/100
			case STATISTICS_VALUETYPES_SUM:
				return rawY === undefined ? null : rawY.sum
			case STATISTICS_VALUETYPES_COUNT:
				return rawY === undefined ? null : rawY.count
		}
		return 0
	}
	private addVars(containerArray: AxisContainer[], statistics: StatisticsCollection): void {
		const valueType = this.chart.valueType.get()
		for(const axisContainer of containerArray) {
			const yAxis = axisContainer.yAxis
			const variableName = yAxis.variableName.get()
			const rawYData = statistics[variableName][yAxis.observedVariableIndex.get()].data as StatisticsEntryTimed
			const data: DataPoint = []
			
			for(let current_day=this.firstDay, i=0; current_day<=this.lastDay; current_day+=ONE_DAY, ++i) {
				const yValue = this.getYValue(valueType, rawYData[current_day])

				if(this.forScatterPlot) {
					if(yValue !== null)
						data.push({x: i, y: yValue});
				} else
					data.push(yValue)
			}
			
			this.dataSets.push(this.createDataSet(
				axisContainer.label.get(),
				data,
				axisContainer.color.get()
			));
		}
	}
	public create(personalStatistics: StatisticsCollection, publicStatistics: StatisticsCollection): ChartDataset[] {
		this.setFirstAndLastDay(this.chart.axisContainer.get(), personalStatistics)
		if(this.chart.displayPublicVariable.get())
			this.setFirstAndLastDay(this.chart.publicVariables.get(), publicStatistics)
		
		//create data array:
		this.addVars(this.chart.axisContainer.get(), personalStatistics)
		if(this.chart.displayPublicVariable.get())
			this.addVars(this.chart.publicVariables.get(), publicStatistics)
		
		
		//create labels:
		const now = Date.now() / 1000
		const cutoffToday = now - ONE_DAY
		const cutoffYesterday = cutoffToday - ONE_DAY
		const cutoffWeek = now - (ONE_DAY*7)
		for(let i=this.firstDay; i<=this.lastDay; i+=ONE_DAY) {
			if(i < cutoffWeek)
				this.labels.push(new Date(i*1000).toLocaleDateString())
			else if(i < cutoffYesterday)
				this.labels.push(Lang.get("x_days_ago", Math.floor((now - i) / ONE_DAY)))
			else if(i < cutoffToday)
				this.labels.push(Lang.get("yesterday"))
			else
				this.labels.push(Lang.get("today"))
		}
		return this.dataSets
	}
}

class SumDataSetCreator extends DataSetCreator {
	private dataPoint: DataPoint = []
	private backgroundColor: string[] = []
	private borderColors: string[] = []
	
	private getXValue(valueType: number, rawY: StatisticsEntryTimed) : number {
		let num = 0
		switch(valueType) {
			case STATISTICS_VALUETYPES_MEAN:
				let count = 0
				for(const day in rawY) {
					const statistic = rawY[day]
					count += statistic.count
					num += statistic.sum
				}
				if(count)
					return Math.round((num / count) * 100) / 100
				else
					return 0
			case STATISTICS_VALUETYPES_SUM:
				for(const day in rawY) {
					const statistic = rawY[day]
					num += statistic.sum
				}
				return num
			case STATISTICS_VALUETYPES_COUNT:
				for(const day in rawY) {
					const statistic = rawY[day]
					num += statistic.count
				}
				return num
		}
		return 0
	}
	
	private addVars(containerArray: AxisContainer[], statistics: StatisticsCollection): void {
		const valueType = this.chart.valueType.get()
		
		containerArray.forEach((axisContainer, index) => {
			const yAxis = axisContainer.yAxis
			const variableName = yAxis.variableName.get()
			if(!statistics.hasOwnProperty(variableName))
				return
			const rawData = statistics[variableName][yAxis.observedVariableIndex.get()].data as StatisticsEntryTimed
			const num = this.getXValue(valueType, rawData)
			
			if(this.forScatterPlot)
				this.dataPoint.push({ x: index, y: num })
			else
				this.dataPoint.push(num)
			
			this.backgroundColor.push(this.hexToRGB(axisContainer.color.get(), 0.5))
			this.borderColors.push(this.hexToRGB(axisContainer.color.get(), 1))
			this.labels.push(axisContainer.label.get())
		})
	}
	
	public create(personalStatistics: StatisticsCollection, publicStatistics: StatisticsCollection): ChartDataset[] {
		this.addVars(this.chart.axisContainer.get(), personalStatistics);
		if(this.chart.displayPublicVariable.get())
			this.addVars(this.chart.publicVariables.get(), publicStatistics);
		
		return [{
			data: this.dataPoint,
			backgroundColor: this.backgroundColor,
			borderColor: this.borderColors,
			borderWidth: 1,
			fill: this.optionFill
		}]
	}
}
class FreqDistrDataSetCreator extends DataSetCreator {
	private labelsIndex: Record<string, boolean> = {}
	private readonly noSort: boolean = false
	
	constructor(chart: ChartData, forScatterPlot: boolean = false, optionFill: string = "", noSort: boolean = false) {
		super(chart, forScatterPlot, optionFill)
		this.noSort = noSort
	}
	
	
	private createNumData(rawData: StatisticsEntryPerValue, xValue: number, xMin: number, xMax: number, inPercent: boolean): DataPoint {
		const data: DataPoint = []
		if(inPercent) {
			let sum = 0
			for(let i=xMin; i <= xMax; ++i) {
				sum += rawData[i] ?? 0
			}
			for(let i=xMin; i <= xMax; ++i) {
				const value = rawData.hasOwnProperty(i) ? Math.round(100 / (sum / rawData[i])) : 0
				data.push(this.forScatterPlot
					? {x: xValue, y: value}
					: value
				)
				this.labels.push(i.toString())
			}
		}
		else {
			for(let i=xMin; i <= xMax; ++i) {
				data.push(this.forScatterPlot
					? {x: xValue, y: rawData[i]}
					: rawData[i]
				)
				this.labels.push(i.toString())
			}
		}
		return data
	}
	private addNumVar(rawData: StatisticsEntryPerValue, axisContainer: AxisContainer, xValue: number, inPercent: boolean): void {
		let xMin = Number.MAX_SAFE_INTEGER
		let xMax = Number.MIN_SAFE_INTEGER
		
		for(const key in rawData) {
			const num = parseInt(key);
			if(isNaN(num))
				continue
			if(num < xMin)
				xMin = num
			else if(num > xMax)
				xMax = num
		}
		if(xMax == Number.MIN_SAFE_INTEGER) //happens when there is only one entry
			xMax = xMin
		
		const data: DataPoint = xMin != Number.MAX_SAFE_INTEGER
			? this.createNumData(rawData, xValue, xMin, xMax, inPercent)
			: []
		
		this.dataSets.push(this.createDataSet(
			axisContainer.label.get(),
			data,
			axisContainer.color.get()
		))
	}
	private addNumVars(containerArray: AxisContainer[], statistics: StatisticsCollection, inPercent: boolean): void {
		for(let i=containerArray.length-1; i>=0; --i) {
			const axisContainer = containerArray[i]
			const yAxis = axisContainer.yAxis
			const variableName = yAxis.variableName.get()
			if(!statistics.hasOwnProperty(variableName) || yAxis.observedVariableIndex.get() == -1)
				continue
			const rawData = statistics[variableName][yAxis.observedVariableIndex.get()].data as StatisticsEntryPerValue
			this.addNumVar(rawData, axisContainer, i, inPercent)
		}
	}
	
	
	private createStringPercentData(rawData: StatisticsEntryPerValue): DataPoint {
		const labelsMax = this.labels.length
		const data: DataPoint = []
		
		let sum = 0
		for(let i=0; i < labelsMax; ++i) {
			sum += rawData[this.labels[i]] ?? 0
		}
		for(let i=0; i < labelsMax; ++i) {
			const key = this.labels[i]
			const value = rawData.hasOwnProperty(key) ? Math.round(100 / (sum / rawData[key])) : 0
			data.push(this.forScatterPlot ? {x: i, y: value} : value)
		}
		return data
	}
	private createStringCountData(rawData: StatisticsEntryPerValue): DataPoint {
		const labelsMax = this.labels.length
		const data: DataPoint = []
		
		for(let i=0; i < labelsMax; ++i) {
			const key = this.labels[i]
			const value = rawData.hasOwnProperty(key) ? rawData[key] : 0
			data.push(this.forScatterPlot ? {x: i, y: value} : value)
		}
		return data
	}
	private addStringVars(containerArray: AxisContainer[], statistics: StatisticsCollection, inPercent: boolean): void {
		const createData = inPercent ? this.createStringPercentData.bind(this) : this.createStringCountData.bind(this)
		
		for(const axisContainer of containerArray) {
			const yAxis = axisContainer.yAxis
			const variableName = yAxis.variableName.get()
			if(!statistics.hasOwnProperty(variableName) || yAxis.observedVariableIndex.get() == -1)
				continue
			
			const rawData = statistics[variableName][yAxis.observedVariableIndex.get()].data as StatisticsEntryPerValue
			const data = createData(rawData)
			
			this.dataSets.push(this.createDataSet(
				axisContainer.label.get(),
				data,
				axisContainer.color.get()
			));
		}
	}
	
	private addStringLabels(containerArray: AxisContainer[], statistics: StatisticsCollection): void {
		for(const axisContainer of containerArray) {
			const yAxis = axisContainer.yAxis
			const variableName = yAxis.variableName.get()
			if(!statistics.hasOwnProperty(variableName))
				continue
			if(yAxis.observedVariableIndex.get() == -1)
				continue
			const rawData = statistics[variableName][yAxis.observedVariableIndex.get()].data as StatisticsEntryPerValue
			
			for(const key in rawData) {
				if(!rawData.hasOwnProperty(key) || !key.length || this.labelsIndex.hasOwnProperty(key))
					continue
				
				this.labelsIndex[key] = true
				this.labels.push(key)
			}
		}
	}
	
	private sortLabelComparator(a: string, b: string): number {
		const r = parseInt(a) - parseInt(b)
		if(isNaN(r)) {
			const sa = a.toLowerCase(), sb = b.toLowerCase()
			
			if(sa < sb)
				return -1
			else if(sa == sb)
				return 0
			else
				return 1
		}
		else
			return r
	}
	public create(personalStatistics: StatisticsCollection, publicStatistics: StatisticsCollection): ChartDataset[] {
		if(this.chart.xAxisIsNumberRange.get()) {
			this.addNumVars(this.chart.axisContainer.get(), personalStatistics, this.chart.inPercent.get())
			if(this.chart.displayPublicVariable.get())
				this.addNumVars(this.chart.publicVariables.get(), publicStatistics, this.chart.inPercent.get())
		}
		else {
			//create labels first, so we know the order to add data in:
			
			this.addStringLabels(this.chart.axisContainer.get(), personalStatistics)
			if(this.chart.displayPublicVariable.get())
				this.addStringLabels(this.chart.publicVariables.get(), publicStatistics)
			
			if(!this.noSort)
				this.labels.sort(this.sortLabelComparator)
			
			this.addStringVars(this.chart.axisContainer.get(), personalStatistics, this.chart.inPercent.get())
			if(this.chart.displayPublicVariable.get())
				this.addStringVars(this.chart.publicVariables.get(), publicStatistics, this.chart.inPercent.get())
			
			//we do that last because this.labels is used in addStringVars()
			for(let i=0; i < this.labels.length; ++i) {
				this.labels[i] = this.labels[i].substring(0, MAX_VARIABLE_LABEL_LENGTH)
			}
		}
		
		return this.dataSets
	}
}

type AxisIndexType = {
	label: string
	color: string
	index: Record<number, number>
}
class XyDataSetCreator extends DataSetCreator {
	private general_xMinValue: number = Number.MAX_SAFE_INTEGER
	private general_xMaxValue: number = -Number.MAX_SAFE_INTEGER
	
	private axisIndex: AxisIndexType[] = []
	
	private addVarsForScatterPlot(containerArray: AxisContainer[], statistics: StatisticsCollection): void {
		for(const axisContainer of containerArray) {
			const yAxis = axisContainer.yAxis
			const xAxis = axisContainer.xAxis
			let xMinValue = Number.MAX_SAFE_INTEGER
			let xMaxValue = -Number.MAX_SAFE_INTEGER
			
			const rawYData = statistics[yAxis.variableName.get()][yAxis.observedVariableIndex.get()].data as StatisticsEntryPerData
			const rawXData = statistics[xAxis.variableName.get()][xAxis.observedVariableIndex.get()].data as StatisticsEntryPerData
			const data = []
			let xSum = 0
			let ySum = 0
			let xySum = 0
			let xxSum = 0
			let yySum = 0
			
			//add data:
			
			for(const i in rawXData) {
				const xValue = rawXData[i]
				const yValue = rawYData[i]
				
				if(xValue < xMinValue)
					xMinValue = xValue
				if(xValue > xMaxValue)
					xMaxValue = xValue
				
				xSum += xValue
				ySum += yValue;
				xySum += xValue*yValue;
				xxSum += xValue*xValue;
				yySum += yValue*yValue;
				data.push({x: xValue, y: yValue})
			}
			
			this.dataSets.push(this.createDataSet(
				axisContainer.label.get(),
				data,
				axisContainer.color.get()
			))
			
			
			//create regression line:
			
			const fitToShowLinearProgression = this.chart.fitToShowLinearProgression.get()
			const n = data.length
			if(n >= 2) {
				let r2 = Math.pow((n*xySum - xSum*ySum) / Math.sqrt((n*xxSum - xSum*xSum)*(n*yySum - ySum*ySum)),2)
				
				if(r2*100 < fitToShowLinearProgression)
					continue
				const slope = (n*xySum - xSum*ySum) / (n*xxSum - xSum*xSum)
				const intercept = (ySum - slope*xSum) / n
				const regressionData = this.createDataSet(
					"",
					[{x:xMinValue, y:intercept + slope * xMinValue}, {x:xMaxValue, y:intercept + slope * xMaxValue}],
					axisContainer.color.get()
				)
				regressionData.type = "line"
				this.dataSets.push(regressionData)
			}
		}
	}
	
	private addVars(containerArray: AxisContainer[], statistics: StatisticsCollection): void {
		for(const axisContainer of containerArray) {
			const yAxis = axisContainer.yAxis
			const xAxis = axisContainer.xAxis
			const rawYData = statistics[yAxis.variableName.get()][yAxis.observedVariableIndex.get()].data as StatisticsEntryPerData
			const rawXData = statistics[xAxis.variableName.get()][xAxis.observedVariableIndex.get()].data as StatisticsEntryPerData
			
			const newIndex: AxisIndexType = {
				label: axisContainer.label.get(),
				color: axisContainer.color.get(),
				index: {}
			}
			this.axisIndex.push(newIndex)
			const index = newIndex.index
			
			for(let i in rawXData) {
				const xValue = rawXData[i]
				const yValue = rawYData[i]
				
				if(xValue < this.general_xMinValue)
					this.general_xMinValue = xValue
				if(xValue > this.general_xMaxValue)
					this.general_xMaxValue = xValue
				
				index[xValue] = yValue
			}
		}
	}
	
	private addData(): void {
		for(const indexInfo of this.axisIndex) {
			const index = indexInfo.index
			const data: DataPoint = []
			for(let i = this.general_xMinValue; i <= this.general_xMaxValue; ++i) {
				if(!index.hasOwnProperty(i))
					continue
				data.push(index[i])
			}
			this.dataSets.push(this.createDataSet(
				indexInfo.label,
				data,
				indexInfo.color
			))
		}
	}
	
	public create(personalStatistics: StatisticsCollection, publicStatistics: StatisticsCollection): ChartDataset[] {
		if(this.forScatterPlot) {
			this.addVarsForScatterPlot(this.chart.axisContainer.get(), personalStatistics);
			if(this.chart.displayPublicVariable.get())
				this.addVarsForScatterPlot(this.chart.publicVariables.get(), publicStatistics);
		}
		else {
			this.addVars(this.chart.axisContainer.get(), personalStatistics);
			if(this.chart.displayPublicVariable.get())
				this.addVars(this.chart.publicVariables.get(), publicStatistics);
			
			
			//create labels:
			for(let i = this.general_xMinValue; i <= this.general_xMaxValue; ++i) {
				this.labels.push(i.toString());
			}
			
			//add data:
			this.addData()
		}
		
		return this.dataSets
	}
}