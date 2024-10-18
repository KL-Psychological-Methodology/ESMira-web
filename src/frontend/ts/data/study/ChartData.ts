import {ObservableStructure} from "../../observable/ObservableStructure";
import {
	STATISTICS_CHARTTYPES_BARS,
	STATISTICS_CHARTTYPES_LINE,
	STATISTICS_DATATYPES_DAILY, STATISTICS_VALUETYPES_COUNT,
	STATISTICS_VALUETYPES_MEAN
} from "../../constants/statistics";
import {AxisContainer} from "./AxisContainer";
import {JsonTypes} from "../../observable/types/JsonTypes";

export class ChartData extends ObservableStructure {
	public valueType						= this.primitive<number>(		"valueType",						STATISTICS_VALUETYPES_MEAN)
	public dataType							= this.primitive<number>(		"dataType",						STATISTICS_DATATYPES_DAILY)
	public chartType						= this.primitive<number>(		"chartType",						STATISTICS_CHARTTYPES_LINE)
	public inPercent						= this.primitive<boolean>(		"inPercent",						false)
	public xAxisIsNumberRange				= this.primitive<boolean>(		"xAxisIsNumberRange",				false)
	public maxYValue						= this.primitive<number>(		"maxYValue",						0)
	public displayPublicVariable			= this.primitive<boolean>(		"displayPublicVariable",			false)
	public hideUntilCompletion				= this.primitive<boolean>(		"hideUntilCompletion",				false)
	public fitToShowLinearProgression		= this.primitive<number>(		"fitToShowLinearProgression",		0)
	
	public title							= this.translatable(			"title",							"")
	public chartDescription					= this.translatable(			"chartDescription",				"")
	public xAxisLabel						= this.translatable(			"xAxisLabel",						"")
	public yAxisLabel						= this.translatable(			"yAxisLabel",						"")
	
	public publicVariables					= this.objectArray(				"publicVariables", AxisContainer)
	public axisContainer					= this.objectArray(				"axisContainer", AxisContainer)
	
	public static createPerDayChartData(title: string, axisContainerArray: JsonTypes[] = [], dataType: number = STATISTICS_DATATYPES_DAILY): ChartData {
		return new ChartData(
			{
				title: title,
				axisContainer: axisContainerArray,
				valueType: STATISTICS_VALUETYPES_COUNT,
				dataType: dataType,
				chartType: STATISTICS_CHARTTYPES_BARS
			},
			null,
			"chartTemp"
		)
	}
}