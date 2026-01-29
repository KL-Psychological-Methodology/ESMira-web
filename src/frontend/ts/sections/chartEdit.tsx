import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {ObservableLangChooser} from "../components/ObservableLangChooser";
import {BindObservable} from "../components/BindObservable";
import {RichText} from "../components/RichText";
import {TitleRow} from "../components/TitleRow";
import {ChartData} from "../data/study/ChartData";
import {
	CONDITION_TYPE_ALL,
	CONDITION_TYPE_AND,
	CONDITION_TYPE_OR,
	STATISTICS_CHARTTYPES_PIE,
	STATISTICS_CHARTTYPES_SCATTER,
	STATISTICS_DATATYPES_DAILY,
	STATISTICS_DATATYPES_FREQ_DISTR,
	STATISTICS_DATATYPES_SUM,
	STATISTICS_DATATYPES_XY
} from "../constants/statistics";
import {DATA_MAIN_KEYS} from "../constants/data";
import {DataStructureInputType} from "../data/DataStructure";
import {AxisContainer} from "../data/study/AxisContainer";
import {DragContainer} from "../components/DragContainer";
import {Study} from "../data/study/Study";
import {StudyDataValues} from "../helpers/StudyDataValues";
import {AxisData} from "../data/study/AxisData";
import {DashRow} from "../components/DashRow";
import {DropdownMenu} from "../components/DropdownMenu";
import {DashElement} from "../components/DashElement";
import {ArrayInterface} from "../observable/interfaces/ArrayInterface";
import {BtnAdd, BtnCopy, BtnCustom, BtnRemove, BtnTrash} from "../components/Buttons";
import statisticsSvg from "../../imgs/icons/statistics.svg?raw"
import {SectionData} from "../site/SectionData";

export interface ChartEditSectionCallback {
	getChart(): ChartData
	isCalc: boolean
}

export class Content extends SectionContent {
	private calcChart: ChartData | null = null
	private readonly isCalc: boolean

	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [sectionData.getStudyPromise()]
	}

	constructor(sectionData: SectionData) {
		super(sectionData)
		this.isCalc = this.sectionData.sectionValue == "calc"
	}
	
	getSectionCallback(): ChartEditSectionCallback {
		return {
			getChart: this.getChart.bind(this),
			isCalc: this.isCalc
		};
	}
	
	public title(): string {
		return Lang.get("edit_chart")
	}

	public titleExtra(): Vnode<any, any> | null {
		return <a href={this.getUrl("chartPreview")}>
			{BtnCustom(m.trust(statisticsSvg), undefined, Lang.get(this.sectionData.sectionValue == "calc" ? "calculate" : "preview"))}
		</a>
	}

	public getChart(): ChartData {
		const study = this.getStudyOrThrow()
		if (this.isCalc) {
			if (!this.calcChart)
				this.calcChart = new ChartData({}, null, "chartCalc")
			return this.calcChart
		}
		switch (this.sectionData.sectionValue) {
			case "public":
				return study.publicStatistics.charts.get()[this.getStaticInt("chartI") ?? 0]
			case "personal":
			default:
				return study.personalStatistics.charts.get()[this.getStaticInt("chartI") ?? 0]
		}
	}

	private copyAxisContainer(list: ArrayInterface<DataStructureInputType, AxisContainer>, axis: AxisContainer, index: number): void {
		list.addCopy(axis, index)
	}
	private removeAxisContainer(list: ArrayInterface<DataStructureInputType, AxisContainer>, index: number): void {
		list.remove(index)
	}
	private removeCondition(list: AxisData, index: number): void {
		list.conditions.remove(index)
	}

	private addCondition(axis: AxisData, e: InputEvent): void {
		const element = e.target as HTMLSelectElement

		axis.conditions.push({ key: element.value })
		if (axis.conditionType.get() == CONDITION_TYPE_ALL)
			axis.conditionType.set(CONDITION_TYPE_AND)

		element.selectedIndex = 0
	}
	private addVariable(list: ArrayInterface<DataStructureInputType, AxisContainer>, study: Study): void {
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
		let mainKeys = DATA_MAIN_KEYS
		if (study.randomGroups.get() >= 2) {
			mainKeys = ["group"].concat(mainKeys)
		}
		if (axisValue == "") {
			const questionnaire = study.questionnaires.get()[0]
			let variables = StudyDataValues.getQuestionnaireVariables(questionnaire)
			return variables.concat(mainKeys)
		}
		else {
			const questionnaires = study.questionnaires.get()
			for (let i = questionnaires.length - 1; i >= 0; --i) {
				const variables = StudyDataValues.getQuestionnaireVariables(questionnaires[i])
				if (variables.indexOf(axisValue) != -1)
					return variables.concat(mainKeys)
			}
			return mainKeys
		}
	}

	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		const chart = this.getChart()
		return <div>
			{this.sectionData.sectionValue != "calc" &&
				<div>
					{TitleRow(Lang.getWithColon("description"))}
					{DashRow(
						DashElement("stretched", {
							content:
								<div>
									{!chart.hideOnClient.get() && <div><label class="noTitle noDesc">
										<input type="checkbox" {...BindObservable(chart.hideUntilCompletion)} />
										<span class="smallText">{Lang.get("hideUntilCompletion")}</span>
									</label><br/></div>
									}
									<label class="noTitle noDesc" >
										<input type="checkbox" {...BindObservable(chart.hideOnClient)} />
										<span class="smallText">{Lang.get("hide_on_client")}</span>
									</label>
								</div>
						}),
						DashElement("stretched", {
							content:

								<div>
									<label class="line">
										<small>{Lang.getWithColon("title")}</small>
										<input class="big" type="text" {...BindObservable(chart.title)} />
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
						chart.chartType.get() != STATISTICS_CHARTTYPES_PIE && DashElement(null, {
							content:
								<div>
									<label class="horizontalPadding">
										<small>{Lang.getWithColon("xAxis_label")}</small>
										<input class="big" type="text" {...BindObservable(chart.xAxisLabel)} />
										{!this.isCalc && ObservableLangChooser(study)}
									</label>
								</div>
						}),
						chart.chartType.get() != STATISTICS_CHARTTYPES_PIE && DashElement(null, {
							content:
								<div>
									<label class="horizontalPadding">
										<small>{Lang.getWithColon("yAxis_label")}</small>
										<input class="big" type="text" {...BindObservable(chart.yAxisLabel)} />
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
							<label>
								<small>{Lang.get("statisticsChartType")}</small>
								<select {...BindObservable(chart.chartType)}>
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
							<label>
								<small>{Lang.get("statisticsDataType")}</small>
								<select {...BindObservable(chart.dataType)}>
									<option value="0">{Lang.get("statisticsDataType_daily")}</option>
									<option value="1">{Lang.get("statisticsDataType_frequencyDistr")}</option>
									<option value="2">{Lang.get("statisticsDataType_sum")}</option>
									<option value="3">{Lang.get("statisticsDataType_xy")}</option>
								</select>
							</label>
						</div>
				}),
				(chart.dataType.get() == STATISTICS_DATATYPES_DAILY || chart.dataType.get() == STATISTICS_DATATYPES_SUM) && DashElement(null, {
					content:
						<div>
							<label>
								<small>{Lang.get("statisticsValueType")}</small>
								<select {...BindObservable(chart.valueType)}>
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
								<input type="checkbox" {...BindObservable(chart.inPercent)} />
								<span class="smallText">{Lang.get("values_in_percent")}</span>
							</label>
							<label class="vertical noTitle noDesc nowrap">
								<input type="checkbox" {...BindObservable(chart.xAxisIsNumberRange)} />
								<span class="smallText">{Lang.get("xAxisIsNumberRange_label")}</span>
							</label>
						</div>
				}),
				chart.chartType.get() == STATISTICS_CHARTTYPES_SCATTER && DashElement(null, {
					content:
						<div>
							<label class="noDesc">
								<span>{Lang.getWithColon("fitToShowLinearProgression_label")}</span>
								<input type="number" {...BindObservable(chart.fitToShowLinearProgression)} />
							</label>
						</div>
				}),
				DashElement(null, {
					content:
						<div>
							<label>
								<small>{Lang.get("chart_max_yValue")}</small>
								<input type="number" {...BindObservable(chart.maxYValue)} />
								<small>{Lang.get("chart_max_yValue_explainZero")}</small>
							</label>
						</div>
				}),
				this.sectionData.sectionValue == "personal" && chart.chartType.get() != STATISTICS_CHARTTYPES_PIE && DashElement(null, {
					content:
						<div>
							<label>
								<input type="checkbox" {...BindObservable(chart.displayPublicVariable)} />
								<span class="smallText">{Lang.get("display_additional_publicVariable")}</span>
							</label>
						</div>
				}),
			)}
			{chart.displayPublicVariable.get() &&
				<div>
					{TitleRow(Lang.getWithColon("public_variables"))}
					{this.getVariablesView(study, chart, chart.publicVariables)}
					{<div class="center">
						{BtnAdd(this.addVariable.bind(this, chart.publicVariables, study), Lang.get('add'))}
					</div>}
				</div>
			}

			{TitleRow(Lang.getWithColon("variables"))}
			{this.getVariablesView(study, chart, chart.axisContainer)}
			<br />
			<div class="center">
				{BtnAdd(this.addVariable.bind(this, chart.axisContainer, study), Lang.get('add'))}
			</div>
		</div>
	}

	private getVariablesView(study: Study, chart: ChartData, list: ArrayInterface<DataStructureInputType, AxisContainer>): Vnode<any, any> {
		const studyVariables = StudyDataValues.getStudyVariables(study)
		return DragContainer((dragTools) =>
			DashRow(
				...list.get().map((container, index) =>
					dragTools.getDragTarget(index, list,

						DashElement("stretched", {
							content:
								<div>
									<div class="line horizontal">
										<div class="selfAlignCenter">
											{dragTools.getDragStarter(index, list)}
										</div>
										<div class="fillFlexSpace vertical">
											<div class="horizontal">
												<label>
													<small>{Lang.get("label")}</small>
													<input type="text" {...BindObservable(container.label)} />
												</label>
												<label>
													<small>{Lang.get("color")}</small>
													<input type="color" {...BindObservable(container.color)} />
												</label>

												<div class="fillFlexSpace"></div>

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

											{chart.chartType.get() != STATISTICS_CHARTTYPES_SCATTER && chart.chartType.get() != STATISTICS_CHARTTYPES_PIE &&
												<div class="vertical">
													<label>
														<input type="checkbox" {...BindObservable(container.useThreshold)} />
														<span class="smallText">{Lang.get("axis_use_y_threshold")}</span>
													</label>

													{container.useThreshold.get() &&
														<div class="horizontal hAlignSpaced">
															<label>
																<small>{Lang.get("axis_y_thershold_value")}</small>
																<input type="number" {...BindObservable(container.threshold)} />
															</label>
	
															<label>
																<small>{Lang.get("axis_y_threshold_color")}</small>
																<input type="color" {...BindObservable(container.thresholdColor)} />
															</label>
	
															<label>
																<input type="checkbox" {...BindObservable(container.useThresholdOnClient)} />
																<span class="smallText">{Lang.get("axis_use_threshold_on_client")}</span>
															</label>
	
														</div>}

												</div>}


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

		return <div class="vertical">
			<div class="horizontal">
				<label class="spacingRight">
					<small>{title}</small>
					<select {...BindObservable(axis.variableName)}>
						{studyVariables.map((variable) => <option>{variable}</option>)}
					</select>
				</label>
				<label class="spacingLeft">
					<small>{Lang.get("condition")}</small>
					<select class="smallText" onchange={this.addCondition.bind(this, axis)}>
						<option>{Lang.get("select_to_add")}</option>
						{conditionOptions.map((option) => <option>{option}</option>)}
					</select>
				</label>
			</div >
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
							<label class="middle">
								<small>{Lang.get("variable")}</small>
								<select {...BindObservable(condition.key)}>
									{conditionOptions.map((option) => <option>{option}</option>)}
								</select>
							</label>
						</td>
						<td>
							<label class="middle">
								<small>{Lang.get("operator")}</small>
								<select class="small" {...BindObservable(condition.operator)}>
									<option value="0">=</option>
									<option value="1">≠</option>
									<option value="2">⋝</option>
									<option value="3">⋜</option>
								</select>
							</label>
						</td>
						<td>
							<label>
								<small>{Lang.get("value")}</small>
								<input class="small" type="text" {...BindObservable(condition.value)} />
							</label>
						</td>
						<td>
							{BtnRemove(this.removeCondition.bind(this, axis, index))}
						</td>
					</tr>
				)}
			</table>
		</div >
	}
}