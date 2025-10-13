import {DataStructure} from "../DataStructure";
import {ChartData} from "./ChartData";

export class Statistics extends DataStructure {
	public charts = this.objectArray("charts", ChartData)
	
}