import m, { Component, Vnode, VnodeDOM } from "mithril"
import { Lang } from "../singletons/Lang";
import { ObservablePrimitive } from "../observable/ObservablePrimitive";
import { BindObservable } from "./BindObservable";
import { neutralStyle, failStyle, successStyle } from "../constants/formStyles"

const ACCOUNTNAME_MIN_LENGTH = 3;
const PASSWORD_MIN_LENGTH = 12;


class InputStyleData {
	msg: string = ""
	style: typeof neutralStyle | typeof failStyle | typeof successStyle = neutralStyle
}

interface AccountChangerComponentOptions {
	onFinish: (accountName: string, password: string) => Promise<boolean>
	onError: (msg: string) => void
	accountName?: string
	btnLabel: string
}
class AccountChangerComponent implements Component<AccountChangerComponentOptions, any> {
	private needsAccountName: boolean = false
	private readonly accountName: ObservablePrimitive<string> = new ObservablePrimitive("", null, "accountName")
	private readonly password: ObservablePrimitive<string> = new ObservablePrimitive("", null, "password")
	private readonly passwordRepeat: ObservablePrimitive<string> = new ObservablePrimitive("", null, "passwordRepeat")
	private accountNameStyle = new InputStyleData()
	private passStyle = new InputStyleData()
	private passRepeatStyle = new InputStyleData()
	private formEnabled: boolean = false

	public oncreate(vNode: VnodeDOM<AccountChangerComponentOptions, any>): void {
		const accountName = vNode.attrs.accountName
		this.needsAccountName = !accountName

		if (accountName)
			this.accountName.set(accountName)

		this.accountName.addObserver(() => {
			this.accountNameStyle = this.lengthCheck(this.accountName.get(), ACCOUNTNAME_MIN_LENGTH)
			this.tryEnableForm()
		})
		this.password.addObserver(() => {
			this.passStyle = this.lengthCheck(this.password.get(), PASSWORD_MIN_LENGTH)
			this.tryEnableForm()
		})
		this.passwordRepeat.addObserver(() => {
			this.passRepeatStyle = this.password.get() != this.passwordRepeat.get()
				? { msg: "", style: failStyle }
				: this.lengthCheck(this.passwordRepeat.get(), PASSWORD_MIN_LENGTH)
			this.tryEnableForm()
		})
	}

	private lengthCheck(value: string, minLength: number): InputStyleData {
		if (!value.length)
			return { msg: "", style: neutralStyle }
		else if (value.length < minLength)
			return { msg: Lang.get("minimal_length", minLength), style: failStyle }
		else
			return { msg: "", style: successStyle }
	}

	private tryEnableForm(): void {
		this.formEnabled = (!this.needsAccountName || this.accountNameStyle.style == successStyle) && this.passStyle.style == successStyle && this.passRepeatStyle.style == successStyle
	}

	private async submitForm(onFinish: (accountName: string, password: string) => Promise<boolean>, onError: (msg: string) => void, e: InputEvent): Promise<any> {
		e.preventDefault()
		if (this.accountName.get().length < 3)
			onError(Lang.get('error_short_username'))
		else if (this.password.get().length < PASSWORD_MIN_LENGTH)
			onError(Lang.get('error_bad_password'))
		else {
			const response = await onFinish(this.accountName.get(), this.password.get())
			if (response) {
				if (this.needsAccountName)
					this.accountName.set("")
				this.password.set("")
				this.passwordRepeat.set("")
			}
		}
	}

	public view(vNode: Vnode<AccountChangerComponentOptions, any>): Vnode<any, any> {
		return <div>
			<form method="post" action="" class="nowrap" onsubmit={this.submitForm.bind(this, vNode.attrs.onFinish, vNode.attrs.onError)}>
				<div class="element">
					{this.needsAccountName &&
						<label>
							<small>{Lang.get("username")}</small>
							<input
								autocomplete="username"
								type="text" {...BindObservable(this.accountName)}
								style={this.accountNameStyle.style}
							/>
							<small>{this.accountNameStyle.msg}</small>
						</label>
					}

				</div>
				<div class="element">
					<label class="noDesc">
						<small>{Lang.get("password")}</small>
						<input
							autocomplete="new-password"
							type="password"
							style={this.passStyle.style}
							{...BindObservable(this.password, undefined, undefined, "onkeyup")}
						/>
						<small>{this.passStyle.msg}</small>
					</label>
					<br />
					<label>
						<small>{Lang.get("repeat_password")}</small>
						<input
							autocomplete="new-password"
							type="password"
							style={this.passRepeatStyle.style}
							{...BindObservable(this.passwordRepeat, undefined, undefined, "onkeyup")}
						/>
						<small>{this.passRepeatStyle.msg}</small>
					</label>
				</div>
				<div class="element">
					<input type="submit" value={vNode.attrs.btnLabel} disabled={!this.formEnabled} />
				</div>
			</form>
		</div>
	}
}

export function ChangeAccount(
	onFinish: (accountName: string, password: string) => Promise<boolean>,
	onError: (msg: string) => void,
	accountName?: string,
	btnLabel: string = Lang.get("save")
): Vnode<any, any> {
	return m(AccountChangerComponent, {
		onFinish: onFinish,
		onError: onError,
		accountName: accountName,
		btnLabel: btnLabel
	})
}