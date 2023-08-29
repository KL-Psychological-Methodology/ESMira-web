import {TranslatableObject} from "../../observable/TranslatableObject";
import {AxisData} from "./AxisData";
import {JsonTypes} from "../../observable/types/JsonTypes";
import {CONDITION_OPERATOR_EQUAL, CONDITION_TYPE_AND} from "../../constants/statistics";
import {Lang} from "../../singletons/Lang";
import {getChartColor} from "../../helpers/ChartJsBox";
import {CsvLoader} from "../../loader/csv/CsvLoader";

export class AxisContainer extends TranslatableObject {
	public color = this.primitive<string>("color","#00bbff")
	
	public label = this.translatable("label","")
	
	public xAxis = this.object("xAxis", AxisData)
	public yAxis = this.object("yAxis", AxisData)
	
	public static async getPerDayAxisCodeFromValueList(csvLoader: CsvLoader, columnKey: string): Promise<Record<string, JsonTypes>[]> {
		const axis: Record<string, JsonTypes>[] = [];
		const valueList = await csvLoader.getValueListInfo(columnKey)
		valueList.forEach((entry, index) => {
			const key = entry.name;
			axis.push({
				xAxis: {
					conditions: []
				},
				yAxis: {
					conditions: [
						{
							key: columnKey,
							value: key,
							operator: CONDITION_OPERATOR_EQUAL
						}
					],
					variableName: "responseTime",
					conditionType: CONDITION_TYPE_AND,
					observedVariableIndex: index
				},
				label: Lang.get("text_with_count", key, entry.count),
				color: getChartColor(index)
			});
		})
		return axis
	}
}