import m, { Component, Vnode, VnodeDOM } from "mithril"
import { failStyle, neutralStyle, successStyle } from "../constants/formStyles"
import { ObservablePrimitive } from "../observable/ObservablePrimitive"
import { Lang } from "../singletons/Lang"
import { BindObservable } from "./BindObservable"


interface AddOutboundTokenComponentOptions {
	onFinish: (url: string, token: string) => Promise<boolean>
	onError: (msg: string) => void
}

class InputStyleData {
	msg: string = ""
	style: typeof neutralStyle | typeof failStyle | typeof successStyle = neutralStyle
}

class AddOutboundTokenComponent implements Component<AddOutboundTokenComponentOptions, any> {
	private readonly url: ObservablePrimitive<string> = new ObservablePrimitive("", null, "url")
	private readonly token: ObservablePrimitive<string> = new ObservablePrimitive("", null, "token")
	private urlStyle = new InputStyleData()
	private tokenStyle = new InputStyleData()
	private formEnabled: boolean = false
	private debuggingOn = process.env.NODE_ENV !== "production"

	public oncreate(vNode: VnodeDOM<AddOutboundTokenComponentOptions, any>) {
		this.url.addObserver(() => {
			this.urlStyle = this.urlCheck(this.url.get())
			this.tryEnableForm()
		})
		this.token.addObserver(() => {
			this.tokenStyle = this.tokenLengthCheck(this.token.get())
			this.tryEnableForm()
		})
	}

	private tokenLengthCheck(value: string): InputStyleData {
		if (!value.length)
			return { msg: "", style: neutralStyle }
		else
			return { msg: "", style: successStyle }
	}

	private urlCheck(value: string): InputStyleData {
		let httpsExpression = /^https:\/\//
		if (this.debuggingOn)
			httpsExpression = /^https?:\/\// //Allow http on debug server
		const httpsRegex = new RegExp(httpsExpression)

		if (!value.length)
			return { msg: "", style: neutralStyle }
		if (!value.match(httpsRegex))
			return { msg: Lang.get("url_https_error"), style: failStyle }
		return { msg: "", style: successStyle }
	}

	private tryEnableForm(): void {
		this.formEnabled = this.urlStyle.style == successStyle && this.tokenStyle.style == successStyle
	}

	private async submitForm(onFinish: (url: string, token: string) => Promise<boolean>, onError: (msg: string) => void, e: InputEvent): Promise<any> {
		e.preventDefault()
		let url = this.url.get()
		if (!url.endsWith("/"))
			url = url + "/"
		const response = await onFinish(url, this.token.get())
		if (response) {
			this.url.set("")
			this.token.set("")
		}
	}

	public view(vNode: Vnode<AddOutboundTokenComponentOptions, any>): Vnode<any, any> {
		return <div>
			<form method="post" action="" class="nowrap" onsubmit={this.submitForm.bind(this, vNode.attrs.onFinish, vNode.attrs.onError)}>
				<div class="element center">
					<label>
						<small>{Lang.get("outbound_fallback_token_url")}</small>
						<input
							type="text" {...BindObservable(this.url)}
							style={this.urlStyle.style}
						/>
						<small>{this.urlStyle.msg}</small>
					</label>
				</div>
				<div class="element center">
					<label>
						<small>{Lang.get("outbound_fallback_token")}</small>
						<input
							type="text" {...BindObservable(this.token)}
							style={this.tokenStyle.style}
						/>
						<small>{this.tokenStyle.msg}</small>
					</label>
				</div>
				<div class="element center">
					<input type="submit" value={Lang.get("save")} disabled={!this.formEnabled} />
				</div>
			</form>
		</div>
	}
}

export function AddOutboundToken(
	onFinish: (url: string, token: string) => Promise<boolean>,
	onError: (msg: string) => void,
): Vnode<any, any> {
	return m(AddOutboundTokenComponent, {
		onFinish: onFinish,
		onError: onError
	})
}