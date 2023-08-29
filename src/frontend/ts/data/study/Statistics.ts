import {TranslatableObject} from "../../observable/TranslatableObject";
import {ChartData} from "./ChartData";

export class Statistics extends TranslatableObject {
	public charts = this.objectArray("charts", ChartData)
	
}