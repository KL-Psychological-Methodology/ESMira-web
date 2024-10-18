import {ObservableStructure} from "../../observable/ObservableStructure";
import {ChartData} from "./ChartData";

export class Statistics extends ObservableStructure {
	public charts = this.objectArray("charts", ChartData)
	
}