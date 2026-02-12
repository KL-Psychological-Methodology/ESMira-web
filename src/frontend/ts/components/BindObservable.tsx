import { PrimitiveType } from "../observable/types/PrimitiveType";
import { BaseObservable } from "../observable/BaseObservable";
import { getMidnightMillis, timeStampToTimeString } from "../constants/methods";

export interface Transformer {
	toAttribute(value: PrimitiveType): PrimitiveType
	toValue(value: string): PrimitiveType
}
const OptimusPrimeTransformer: Transformer = {
	toAttribute(value: PrimitiveType): PrimitiveType {
		return value
	},
	toValue(value: string): PrimitiveType {
		return value
	}
}
const OptimusPrimeNumberTransformer: Transformer = {
	toAttribute(value: PrimitiveType): PrimitiveType {
		return value
	},
	toValue(value: string): PrimitiveType {
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
	public toValue(value: string): PrimitiveType {
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
	private readonly obs: BaseObservable<T>
	
	constructor(obs: BaseObservable<T>, onBeforeChange: (before: T, after: T) => T) {
		this.obs = obs
		this.onBeforeChange = onBeforeChange
	}
	public toAttribute(value: T): T {
		return value
	}
	public toValue(value: string): T {
		return this.onBeforeChange(this.obs.get(), value as T) || value as T
	}
}
export const BooleanTransformer: Transformer = {
	toAttribute(value: PrimitiveType): PrimitiveType {
		return value ? "1" : "0"
	},
	toValue(value: string): PrimitiveType {
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
	toValue(value: string): PrimitiveType {
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
	toValue(value: string): PrimitiveType {
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

/**
 * Binds the value of a form element (e.g. input, select, ...) to an observable and automatically updates the observable when the value changes.
 *  * Usage example:
 * ```
 * const value = new ObservablePrimitive("value", null, "test");
 * <input type="text" {...BindObservable(value)}/>
 * ```
 * @see {@link BindValue}
 *
 * @param obs - The observable to bind to
 * @param transformer - An optional transformer to assure that the value adheres to a certain format (e.g. number, date, ...)
 * @param attr - The attribute of the form element to bind to (e.g. value, checked, ...). Usually the correct attribute can be inferred from the data type of the observable.
 * @param event - Which event to listen to. Uses `onchange` by default.
 * @returns A Record with the attribute and event handler which is meant to be passed via spread operator (`...`) to the element attributes.
 */
export function BindObservable(obs: BaseObservable<PrimitiveType>, transformer?: Transformer, attr?: keyof HTMLInputElement, event: keyof HTMLInputElement = "onchange"): Record<string, any> {
	return BindValue(obs.get(), value => obs.set(value), transformer, attr, event)
}

/**
 * Binds a value to an input element and calls a provided setter when data changes.
 * Usage example:
 * ```
 * const value = "changeMe";
 * <input type="text" {...BindValue(value, (newValue) => value = newValue)}/>
 * ```
 *
 * @param attrValue - The initial value to bind to the input element.
 * @param set - A callback function that is expected to update the state with the new value.
 * @param transformer - An optional transformer to assure that the value adheres to a certain format (e.g. number, date, ...)
 * @param attr - The attribute of the form element to bind to (e.g. value, checked, ...). Usually the correct attribute can be inferred from the data type of the observable.
 * @param event - Which event to listen to. Uses `onchange` by default.
 * @returns A Record with the attribute and event handler which is meant to be passed via spread operator (`...`) to the element attributes.
 */
export function BindValue<T extends PrimitiveType>(attrValue: T, set: (value: T) => void, transformer?: Transformer, attr?: keyof HTMLInputElement, event: keyof HTMLInputElement = "onchange") {
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
			set(transformer!.toValue(element[attr!] as string) as T)
		}
	};
}