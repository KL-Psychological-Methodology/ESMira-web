import m, { Component, Vnode, VnodeDOM } from "mithril"
import { failStyle, neutralStyle } from "../constants/formStyles"
import { Study } from "../data/study/Study"
import { BaseObservable } from "../observable/BaseObservable"
import { PrimitiveType } from "../observable/types/PrimitiveType"
import { BindObservable } from "./BindObservable"
import { ObservableLangChooser } from "./ObservableLangChooser"


// BaseObservable<PrimitiveType>
interface RegexTextInputComponentOptions {
	label: string,
	observable: BaseObservable<string>
	regex: RegExp,
	warningMessage: string,
	labelCssClasses: string,
	inputCssClasses: string,
	description: string,
	useLanguageChooser: boolean,
	study: Study | null,
	disabled: boolean
}

class RegexTextInputComponent implements Component<RegexTextInputComponentOptions, any> {
	private msg: string = ""
	private style: typeof neutralStyle | typeof failStyle = neutralStyle

	public oncreate(vNode: VnodeDOM<RegexTextInputComponentOptions, any>) {
		const validate = () => {
			const input = vNode.attrs.observable.get()
			if (input === "" || input.match(vNode.attrs.regex)) {
				this.style = neutralStyle
				this.msg = ""
			} else {
				this.style = failStyle
				this.msg = vNode.attrs.warningMessage
			}
		}

		vNode.attrs.observable.addObserver(validate)
		validate()
	}

	public view(vNode: Vnode<RegexTextInputComponentOptions, any>): Vnode<any, any> {
		return <label class={vNode.attrs.labelCssClasses}>
			<small>{vNode.attrs.label}</small>
			<input type="text" class={vNode.attrs.inputCssClasses} style={this.style} disabled={vNode.attrs.disabled} {...BindObservable(vNode.attrs.observable, undefined, undefined, "onkeyup")} />
			{vNode.attrs.label && vNode.attrs.study !== null && ObservableLangChooser(vNode.attrs.study)}
			{vNode.attrs.description && <small>{vNode.attrs.description}</small>}
			<small>{this.msg}</small>
		</label>
	}
}

export function RegexTextInput(
	label: string,
	observable: BaseObservable<string>,
	regex: RegExp,
	warningMessage: string,
	labelCssClasses: string = "",
	inputCssClasses: string = "",
	description: string = "",
	useLanguageChooser: boolean = false,
	study: Study | null = null,
	disabled: boolean = false
): Vnode<any, any> {
	return m(RegexTextInputComponent, {
		label: label,
		observable: observable,
		regex: regex,
		warningMessage: warningMessage,
		labelCssClasses: labelCssClasses,
		inputCssClasses: inputCssClasses,
		description: description,
		useLanguageChooser: useLanguageChooser,
		study: study,
		disabled: disabled,
	})
}