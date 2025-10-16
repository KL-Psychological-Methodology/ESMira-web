import m from "mithril"
import { PrimitiveType } from "../observable/types/PrimitiveType";
import { BaseObservable } from "../observable/BaseObservable";
import { getMidnightMillis, timeStampToTimeString } from "../constants/methods";

export interface Transformer {
	toAttribute(value: PrimitiveType): PrimitiveType
	toObs(value: string, obs: BaseObservable<PrimitiveType>): PrimitiveType
}
const OptimusPrimeTransformer: Transformer = {
	toAttribute(value: PrimitiveType): PrimitiveType {
		return value
	},
	toObs(value: string): PrimitiveType {
		return value
	}
}
const OptimusPrimeNumberTransformer: Transformer = {
	toAttribute(value: PrimitiveType): PrimitiveType {
		return value
	},
	toObs(value: string): PrimitiveType {
		return parseInt(value) || 0
	}
}

export class ConstrainedNumberTransformer implements Transformer {
	private readonly min?: number;
	private readonly max?: number;
	private readonly allowEmpty: boolean;

	constructor(min?: number, max?: number, allowEmpty: boolean = false) {
		this.min = min;
		this.max = max;
		this.allowEmpty = allowEmpty
	}
	public toAttribute(value: PrimitiveType): PrimitiveType {
		return value;
	}
	public toObs(value: string): PrimitiveType {
		if (this.allowEmpty && value === "") {
			return "";
		}
		let num = parseInt(value) || 0;
		if (typeof this.min === "number") num = Math.max(this.min, num);
		if (typeof this.max === "number") num = Math.min(this.max, num);
		return num;
	}
}


export class OnBeforeChangeTransformer<T extends PrimitiveType> implements Transformer {
	private readonly onBeforeChange: (before: T, after: T) => T
	constructor(onBeforeChange: (before: T, after: T) => T) {
		this.onBeforeChange = onBeforeChange
	}
	public toAttribute(value: T): T {
		return value
	}
	public toObs(value: string, obs: BaseObservable<T>): T {
		return this.onBeforeChange(obs.get(), value as T) || value as T
	}
}
export const BooleanTransformer: Transformer = {
	toAttribute(value: PrimitiveType): PrimitiveType {
		return value ? "1" : "0"
	},
	toObs(value: string): PrimitiveType {
		return value == "1"
	}
}
export const DateTransformer: Transformer = {
	toAttribute(value: PrimitiveType): string {
		const intValue = typeof value == "number" ? value : (parseInt(value.toString()) || 0)
		if (intValue == 0)
			return ""
		return (new Date(intValue)).toISOString().split("T")[0]
	},
	toObs(value: string): PrimitiveType {
		if (value === "")
			return 0
		else
			return (new Date(value)).getTime()
	}
}
export const TimeTransformer: Transformer = {
	toAttribute(value: PrimitiveType): string {
		const intValue = typeof value == "number" ? value : (parseInt(value.toString()) || 0)
		if (intValue == -1)
			return ""
		else {
			const midnight = getMidnightMillis()

			return timeStampToTimeString(midnight + intValue)
		}
	},
	toObs(value: string): PrimitiveType {
		if (value == "")
			return -1
		else {
			const parts = value.split(":")
			const midnight = getMidnightMillis()

			const date = new Date()
			date.setHours(parseInt(parts[0]) || 0)
			date.setMinutes(parseInt(parts[1]) || 0)

			return date.getTime() - midnight
		}
	}
}

export function BindObservable(obs: BaseObservable<PrimitiveType>, transformer?: Transformer, attr?: keyof HTMLInputElement, event: keyof HTMLInputElement = "onchange"): Record<string, any> {
	const attrValue = obs.get()
	if (!transformer) {
		if (typeof attrValue == "number")
			transformer = OptimusPrimeNumberTransformer
		else
			transformer = OptimusPrimeTransformer
	}
	if (!attr) {
		if (typeof attrValue == "boolean")
			attr = "checked"
		else
			attr = "value"
	}

	return {
		[attr]: transformer.toAttribute(attrValue),
		[event]: (e: InputEvent) => {
			const element = e.target as HTMLInputElement
			obs.set(transformer!.toObs(element[attr!] as string, obs))
		}
	}
}