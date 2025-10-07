import m, { Vnode } from "mithril";
import { getBaseUrl } from "../constants/methods";
import { FILE_ADMIN } from "../constants/urls";
import { ObservablePrimitive } from "../observable/ObservablePrimitive";
import { Lang } from "../singletons/Lang";
import { Requests } from "../singletons/Requests";
import { Section } from "../site/Section";
import { SectionContent } from "../site/SectionContent";
import { BindObservable } from "../widgets/BindObservable";

export class Content extends SectionContent {
	private readonly report: string
	private recipient: ObservablePrimitive<string> = new ObservablePrimitive<string>("", null, "recipient")
	private errorInfo: ObservablePrimitive<string> = new ObservablePrimitive<string>("", null, "errorInfo")

	public static preLoad(section: Section): Promise<any>[] {
		return [
			Requests.loadRaw(`${FILE_ADMIN}?type=GetError&timestamp=${section.getStaticInt("timestamp")}`)
		]
	}
	constructor(section: Section, report: string) {
		super(section)
		this.report = report
	}
	public title(): string {
		return Lang.get("forward_error_report")
	}

	public getView(): Vnode<any, any> {
		return <div>
			<label class="line">
				<small>{Lang.get("send_to")}</small>
				<input type="text" {...BindObservable(this.recipient)} />
			</label>
			<label class="line">
				<small>{Lang.get("additional_information")}</small>
				<textarea {...BindObservable(this.errorInfo)}></textarea>
			</label>
			<input class="right" type="button" value={Lang.get("send")} onclick={this.sendMessage.bind(this)} />
		</div>
	}

	private async sendMessage() {
		var message = `Error report forwarded from: ${getBaseUrl()}\n\n`;
		if (this.errorInfo.get() !== "") {
			message += `Additional information:\n${this.errorInfo.get()}\n\n`
		}
		message += this.report

		this.section.loader.loadJson(`${FILE_ADMIN}?type=ForwardErrorReport`, "post", `recipient=${this.recipient.get()}&report=${message}`)
	}
}