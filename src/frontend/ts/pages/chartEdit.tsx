import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {ObservableLangChooser} from "../widgets/ObservableLangChooser";
import {BindObservable} from "../widgets/BindObservable";
import {RichText} from "../widgets/RichText";
import {TitleRow} from "../widgets/TitleRow";
import {ChartData} from "../data/study/ChartData";
import {
	CONDITION_TYPE_ALL, CONDITION_TYPE_AND, CONDITION_TYPE_OR,
	STATISTICS_CHARTTYPES_PIE,
	STATISTICS_CHARTTYPES_SCATTER,
	STATISTICS_DATATYPES_DAILY,
	STATISTICS_DATATYPES_FREQ_DISTR, STATISTICS_DATATYPES_XY
} from "../constants/statistics";
import {DATA_MAIN_KEYS} from "../constants/data";
import {TranslatableObjectDataType} from "../observable/TranslatableObject";
import {AxisContainer} from "../data/study/AxisContainer";
import {DragContainer} from "../widgets/DragContainer";
import {Study} from "../data/study/Study";
import {StudyDataValues} from "../helpers/StudyDataValues";
import {AxisData} from "../data/study/AxisData";
import {DashRow} from "../widgets/DashRow";
import {DropdownMenu} from "../widgets/DropdownMenu";
import {DashElement} from "../widgets/DashElement";
import {Section} from "../site/Section";
import {ArrayInterface} from "../observable/interfaces/ArrayInterface";
import {BtnAdd, BtnCopy, BtnCustom, BtnRemove, BtnTrash} from "../widgets/BtnWidgets";
import statisticsSvg from "../../imgs/icons/statistics.svg?raw"

export abstract class ChartEditSectionContent extends SectionContent{
	abstract getChart(): ChartData
}

export class Content extends ChartEditSectionContent {
	private calcChart: ChartData | null = null
	private readonly isCalc: boolean
	
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}
	
	constructor(section: Section) {
		super(section)
		this.isCalc = this.section.sectionValue == "calc"
	}
	
	public title(): string {
		return Lang.get("edit_chart")
	}
	
	public titleExtra(): Vnode<any, any> | null {
		return <a href={this.getUrl("chartPreview")}>
			{BtnCustom(m.trust(statisticsSvg), undefined, Lang.get(this.section.sectionValue == "calc" ? "calculate" : "preview"))}
		</a>
	}
	
	public getChart(): ChartData {
		const study = this.getStudyOrThrow()
		if(this.isCalc) {
			if(!this.calcChart)
				this.calcChart = new ChartData({}, null, "chartCalc")
			return this.calcChart
		}
		switch(this.section.sectionValue) {
			case "public":
				return study.publicStatistics.charts.get()[this.getStaticInt("chartI") ?? 0]
			case "personal":
			default:
				return study.personalStatistics.charts.get()[this.getStaticInt("chartI") ?? 0]
		}
	}
	
	private copyAxisContainer(list: ArrayInterface<TranslatableObjectDataType, AxisContainer>, axis: AxisContainer, index: number): void {
		list.addCopy(axis, index)
	}
	private removeAxisContainer(list: ArrayInterface<TranslatableObjectDataType, AxisContainer>, index: number): void {
		list.remove(index)
	}
	private removeCondition(list: AxisData, index: number): void {
		list.conditions.remove(index)
	}
	
	private addCondition(axis: AxisData, e: InputEvent): void {
		const element = e.target as HTMLSelectElement
		
		axis.conditions.push({key: element.value})
		if(axis.conditionType.get() == CONDITION_TYPE_ALL)
			axis.conditionType.set(CONDITION_TYPE_AND)
		
		element.selectedIndex = 0
	}
	private addVariable(list: ArrayInterface<TranslatableObjectDataType, AxisContainer>, study: Study): void {
		const studyVariables = StudyDataValues.getStudyVariables(study)
		const variableName = studyVariables.length ? studyVariables[0] : ""
		
		list.push({
			xAxis: {
				variableName: variableName
			},
			yAxis: {
				variableName: variableName
			}
		})
	}
	
	private getConditionVariables(study: Study, axisValue: string): string[] {
		if(axisValue == "") {
			const questionnaire = study.questionnaires.get()[0]
			const variables = StudyDataValues.getQuestionnaireVariables(questionnaire)
			return variables.concat(DATA_MAIN_KEYS)
		}
		else {
			const questionnaires = study.questionnaires.get()
			for(let i = questionnaires.length-1; i >= 0; --i) {
				const variables = StudyDataValues.getQuestionnaireVariables(questionnaires[i])
				if(variables.indexOf(axisValue) != -1)
					return variables.concat(DATA_MAIN_KEYS)
			}
			return DATA_MAIN_KEYS
		}
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		const chart = this.getChart()
		return <div>
			{this.section.sectionValue != "calc" &&
				<div>
					{TitleRow(Lang.getWithColon("description"))}
					{DashRow(
						DashElement("stretched", {
							content:
								<div>
									<label class="noTitle noDesc">
										<input type="checkbox" {... BindObservable(chart.hideUntilCompletion)}/>
										<span class="smallText">{Lang.get("hideUntilCompletion")}</span>
									</label>
								</div>
						}),
						DashElement("stretched", {
							content:
								
								<div>
									<label class="line">
										<small>{Lang.getWithColon("title")}</small>
										<input class="big" type="text" {... BindObservable(chart.title)}/>
										{!this.isCalc && ObservableLangChooser(study)}
									</label>
								</div>
						}),
						DashElement("stretched", {
							content:
								<div>
									<div class="fakeLabel line">
										<small>{Lang.getWithColon("description")}</small>
										{RichText(chart.chartDescription)}
										{!this.isCalc && ObservableLangChooser(study)}
									</div>
								</div>
						}),
						DashElement(null, {
							content:
								<div>
									<label class="horizontalPadding">
										<small>{Lang.getWithColon("xAxis_label")}</small>
										<input class="big" type="text" {... BindObservable(chart.xAxisLabel)}/>
										{!this.isCalc && ObservableLangChooser(study)}
									</label>
								</div>
						}),
						DashElement(null, {
							content:
								<div>
									<label class="horizontalPadding">
										<small>{Lang.getWithColon("yAxis_label")}</small>
										<input class="big" type="text" {... BindObservable(chart.yAxisLabel)}/>
										{!this.isCalc && ObservableLangChooser(study)}
									</label>
								</div>
						})
					)}
				</div>
			}
			
			
			{TitleRow(Lang.getWithColon("settings"))}
			
			{DashRow(
				
				DashElement(null, {
					content:
						<div>
							<label class="horizontal">
								<small>{Lang.get("statisticsChartType")}</small>
								<select {... BindObservable(chart.chartType)}>
									<option value="0">{Lang.get("statisticsChart_line")}</option>
									<option value="1">{Lang.get("statisticsChart_line_filled")}</option>
									<option value="2">{Lang.get("statisticsChart_bars")}</option>
									<option value="3">{Lang.get("statisticsChart_scatter")}</option>
									<option value="4">{Lang.get("statisticsChart_pie")}</option>
								</select>
							</label>
						</div>
				}),
				DashElement(null, {
					content:
						<div>
							<label class="horizontal">
								<small>{Lang.get("statisticsDataType")}</small>
								<select {... BindObservable(chart.dataType)}>
									<option value="0">{Lang.get("statisticsDataType_daily")}</option>
									<option value="1">{Lang.get("statisticsDataType_frequencyDistr")}</option>
									<option value="2">{Lang.get("statisticsDataType_sum")}</option>
									<option value="3">{Lang.get("statisticsDataType_xy")}</option>
								</select>
							</label>
						</div>
				}),
				chart.dataType.get() == STATISTICS_DATATYPES_DAILY && DashElement(null, {
					content:
						<div>
							<label class="horizontal">
								<small>{Lang.get("statisticsValueType")}</small>
								<select {... BindObservable(chart.valueType)}>
									<option value="0">{Lang.get("statisticsValueType_mean")}</option>
									<option value="1">{Lang.get("statisticsValueType_sum")}</option>
									<option value="2">{Lang.get("statisticsValueType_count")}</option>
								</select>
							</label>
						</div>
				}),
				chart.dataType.get() == STATISTICS_DATATYPES_FREQ_DISTR && DashElement(null, {
					content:
						<div>
							<label class="vertical noTitle noDesc nowrap">
								<input type="checkbox" {... BindObservable(chart.inPercent)}/>
								<span class="smallText">{Lang.get("values_in_percent")}</span>
							</label>
							<label class="vertical noTitle noDesc nowrap">
								<input type="checkbox" {... BindObservable(chart.xAxisIsNumberRange)}/>
								<span class="smallText">{Lang.get("xAxisIsNumberRange_label")}</span>
							</label>
						</div>
				}),
				chart.chartType.get() == STATISTICS_CHARTTYPES_SCATTER && DashElement(null, {
					content:
						<div>
							<label class="noDesc">
								<span>{Lang.getWithColon("fitToShowLinearProgression_label")}</span>
								<input type="number" {... BindObservable(chart.fitToShowLinearProgression)}/>
							</label>
						</div>
				}),
				DashElement(null, {
					content:
						<div>
							<label>
								<small>{Lang.get("chart_max_yValue")}</small>
								<input type="number" {... BindObservable(chart.maxYValue)}/>
								<small>{Lang.get("chart_max_yValue_explainZero")}</small>
							</label>
						</div>
				}),
				this.section.sectionValue == "personal" && chart.chartType.get() != STATISTICS_CHARTTYPES_PIE && DashElement(null, {
					content:
						<div>
							<label>
								<input class="horizontal" type="checkbox" {... BindObservable(chart.displayPublicVariable)}/>
								<span class="horizontal smallText">{ Lang.get("display_additional_publicVariable")}</span>
							</label>
						</div>
				}),
			)}
			{chart.displayPublicVariable.get() &&
				<div>
					{TitleRow(Lang.getWithColon("public_variables"))}
					{ this.getVariablesView(study, chart, chart.publicVariables)}
					{<div class="center">
						{BtnAdd(this.addVariable.bind(this, chart.publicVariables, study), Lang.get('add'))}
					</div>}
				</div>
			}
			
			{TitleRow(Lang.getWithColon("variables"))}
			{this.getVariablesView(study, chart, chart.axisContainer)}
			<br/>
			<div class="center">
				{BtnAdd(this.addVariable.bind(this, chart.axisContainer, study), Lang.get('add'))}
			</div>
		</div>
	}
	
	private getVariablesView(study: Study, chart: ChartData, list: ArrayInterface<TranslatableObjectDataType, AxisContainer>): Vnode<any, any> {
		const studyVariables = StudyDataValues.getStudyVariables(study)
		return DragContainer((dragTools) =>
			DashRow(
				...list.get().map((container, index) =>
					dragTools.getDragTarget(index, list,
						
						DashElement("stretched", {
							content:
								<div>
									<div class="line flexHorizontal">
										<div class="flexCenter">
											{dragTools.getDragStarter(index, list)}
										</div>
										<div class="flexGrow">
											<div class="flexHorizontal">
												<label class="horizontal">
													<small>{Lang.get("label")}</small>
													<input type="text" {... BindObservable(container.label)}/>
												</label>
												<label class="horizontal">
													<small>{Lang.get("color")}</small>
													<input type="color" {... BindObservable(container.color)}/>
												</label>
												
												<div class="flexGrow"></div>
												
												<div>
													{BtnCopy(this.copyAxisContainer.bind(this, list, container, index))}
												</div>
												<div>
													{BtnTrash(this.removeAxisContainer.bind(this, list, index))}
												</div>
											</div>
											
											{chart.dataType.get() == STATISTICS_DATATYPES_XY &&
												this.getAxisView(study, Lang.get("axis_x"), container.xAxis, studyVariables)
											}
											{this.getAxisView(study, Lang.get("axis_y"), container.yAxis, studyVariables)}
										</div>
									</div>
								</div>
						})
					)
				)
			)
		
		)
	}
	
	private getAxisView(study: Study, title: string, axis: AxisData, studyVariables: string[]): Vnode<any, any> {
		const conditionOptions = this.getConditionVariables(study, axis.variableName.get())
		
		return <div>
			<div class="vertical">
				<label class="horizontal spacingRight">
					<small>{title}</small>
					<select {... BindObservable(axis.variableName)}>
						{studyVariables.map((variable) => <option>{variable}</option>)}
					</select>
				</label>
				<label class="horizontal spacingLeft">
					<small>{Lang.get("condition")}</small>
					<select class="smallText" onchange={this.addCondition.bind(this, axis)}>
						<option>{Lang.get("select_to_add")}</option>
						{conditionOptions.map((option) => <option>{option}</option>)}
					</select>
				</label>
			</div>
			<table>
				{axis.conditions.get().map((condition, index) =>
					condition.key.get() &&
						<tr>
							<td class="center">
								{index == 0
									? <div style="width:75px"></div>
									: DropdownMenu("conditionType",
										<span class="clickable">{axis.conditionType.get() == CONDITION_TYPE_AND ? "AND" : "OR"}</span>,
										(close) => <div>
											<div class="clickable" onclick={() => {
												axis.conditionType.set(CONDITION_TYPE_AND)
												close()
											}}>{Lang.get("conditionTypeAnd")}</div>
											<div class="clickable spacingTop" onclick={() => {
												axis.conditionType.set(CONDITION_TYPE_OR)
												close()
											}}>{Lang.get("conditionTypeOr")}</div>
										</div>
									)
								}
							</td>
							<td>
							<label class="horizontal middle">
								<small>{Lang.get("variable")}</small>
								<select {... BindObservable(condition.key)}>
									{conditionOptions.map((option) => <option>{option}</option>)}
								</select>
							</label>
							</td>
							<td>
							<label class="horizontal middle">
								<small>{Lang.get("operator")}</small>
								<select class="small" {... BindObservable(condition.operator)}>
									<option value="0">=</option>
									<option value="1">≠</option>
									<option value="2">⋝</option>
									<option value="3">⋜</option>
								</select>
							</label>
							</td>
							<td>
							<label class="horizontal">
								<small>{Lang.get("value")}</small>
								<input class="small" type="text" {... BindObservable(condition.value)}/>
							</label>
							</td>
							<td>
							{BtnRemove(this.removeCondition.bind(this, axis, index))}
							</td>
						</tr>
				)}
			</table>
		</div>
	}
}