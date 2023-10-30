import {Study} from "../../data/study/Study";
import {FILE_RESPONSES, FILE_STATISTICS} from "../../constants/urls";
import {StudyDataValues} from "../../helpers/StudyDataValues";
import {CsvLoader} from "./CsvLoader";
import {AxisContainer} from "../../data/study/AxisContainer";
import {LoaderState} from "../../site/LoaderState";
import {ChartData} from "../../data/study/ChartData";
import {StatisticsCollection} from "../../data/statistics/StatisticsCollection";
import {Requests} from "../../singletons/Requests";


export interface LoadedStatistics {
	/**
	 * This represents, in most cases, personal statistics. Main exception is the publicStatistics section.
	 */
	mainStatistics: StatisticsCollection
	/**
	 * This represents, in most cases, public statistics. Main exception is the publicStatistics section.
	 */
	additionalStatistics?: StatisticsCollection
}

export class CsvLoaderCollectionFromCharts {
	private readonly loaderState: LoaderState
	private readonly study: Study
	private readonly variableGroupIndex: Record<string, number> = {}
	private readonly urlTemplate: string
	private readonly personalStatistics: StatisticsCollection = {}
	
	private readonly csvLoaders: Record<number, CsvLoader> = {}
	private publicStatistics?: StatisticsCollection
	
	private charts: ChartData[] = []
	private needsPublicStatistics: boolean = false
	
	constructor(loaderState: LoaderState, study: Study) {
		this.loaderState = loaderState
		this.study = study
		this.urlTemplate = FILE_RESPONSES.replace('%1', study.id.get().toString())
	}
	
	/**
	 *
	 * @param charts
	 */
	public async setupLoadersForCharts(charts: ChartData[]): Promise<void> {
		this.charts = charts
		const questionnaires = this.study.questionnaires.get()
		for(let i=questionnaires.length-1; i>=0; --i) {
			const questionnaire = questionnaires[i]
			const variables = StudyDataValues.getQuestionnaireVariables(questionnaire)
			variables.forEach((variable) => {
				this.variableGroupIndex[variable] = i;
			})
		}
		
		for(const chart of this.charts) {
			await this.checkAxis(chart.axisContainer.get())
			if(chart.displayPublicVariable.get())
				await this.checkAxis(chart.publicVariables.get())
		}
	}
	private async checkAxis(axisContainerArray: AxisContainer[]) {
		for(const axisContainer of axisContainerArray) {
			const xAxis = axisContainer.xAxis
			const yAxis = axisContainer.yAxis
			
			await this.addLoader(xAxis.variableName.get())
			await this.addLoader(yAxis.variableName.get())
		}
	}
	private async addLoader(variableName: string): Promise<void> {
		if(!variableName)
			return
		const questionnaireI = this.variableGroupIndex[variableName]
		if(!this.csvLoaders[questionnaireI]) {
			const url = this.urlTemplate.replace('%2', this.study.questionnaires.get()[questionnaireI].internalId.get().toString())
			this.csvLoaders[questionnaireI] = await CsvLoader.fromUrl(this.loaderState, url)
		}
	}
	
	
	private combineStatistics(newStatistics: StatisticsCollection): void {
		for(const variableName in newStatistics) {
			const newVariable = newStatistics[variableName]
			
			if(!this.personalStatistics.hasOwnProperty(variableName))
				this.personalStatistics[variableName] = newVariable
			else {
				const currentVariable = this.personalStatistics[variableName]
				newVariable.forEach((newEntry, index) => {
					if(!currentVariable[index] || newEntry.entryCount != 0) //this could is probably a dry run and the target variable is loaded from another csvLoader
						currentVariable[index] = newEntry
				})
			}
		}
	}
	private async addStatistics(csvLoader: CsvLoader): Promise<void> {
		for(const chart of this.charts) {
			const newStatistics = await csvLoader.getStatistics(
				chart.axisContainer.get(),
				chart.dataType.get()
			)
			this.combineStatistics(newStatistics)
			if(chart.displayPublicVariable.get())
				this.needsPublicStatistics = true
		}
	}
	
	public async loadStatisticsFromFiles(userName?: string, dontLoadPublicStatistics: boolean = false): Promise<LoadedStatistics> {
		for(const i in this.csvLoaders) {
			//Note: This runs through all csvLoaders with every chart and their variables. Even when a chart (or variable) does not need the csvLoader
			const csvLoader = this.csvLoaders[i]
			if(userName) {
				await csvLoader.filterEntireColumn(false, "userId")
				await csvLoader.filterByValue(true, "userId", userName)
			}
			await this.addStatistics(csvLoader)
		}
		
		if(this.needsPublicStatistics && !dontLoadPublicStatistics) {
			if(!this.publicStatistics) {
				const accessKeys = this.study.accessKeys.get()
				const accessKey = accessKeys.length ? accessKeys[0].get() : ""
				this.publicStatistics = await Requests.loadJson(FILE_STATISTICS.replace("%d", this.study.id.get().toString()).replace("%s", accessKey))
			}
			
			return {
				mainStatistics: this.personalStatistics,
				additionalStatistics: this.publicStatistics
			}
		}
		else
			return {mainStatistics: this.personalStatistics}
	}
}