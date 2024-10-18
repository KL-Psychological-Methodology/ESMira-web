import {ObservableStructure} from "../../observable/ObservableStructure";
import {CONDITION_TYPE_ALL} from "../../constants/statistics";
import {Conditions} from "./Conditions";

export class AxisData extends ObservableStructure {
	public variableName						= this.primitive<string>(		"variableName",			"")
	public observedVariableIndex			= this.primitive<number>(		"observedVariableIndex",	0)
	public conditionType					= this.primitive<number>(		"conditionType",			CONDITION_TYPE_ALL)
	
	public conditions						= this.objectArray(				"conditions", Conditions)
}