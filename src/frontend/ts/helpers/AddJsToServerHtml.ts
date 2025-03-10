import { createStudyUrl } from "../constants/methods";
import qrcode from "qrcode-generator";
import { SectionContent } from "../site/SectionContent";

export class AddJsToServerHtml {
	private static getAccessKey(sectionContent: SectionContent): string {
		const study = sectionContent.getStudyOrThrow()
		return sectionContent.getDynamic("accessKey", study.accessKeys.get().length ? study.accessKeys.get()[0].get() : "").get()
	}

	private static processView(view: HTMLElement, sectionContent: SectionContent): void {
		const type = view.getAttribute("js-action")

		switch (type) {
			case "internalUrl":
				view.setAttribute("href", sectionContent.getUrl(view.getAttribute("href")?.substring(1) ?? ""))
				break
			case "shown":
				view.classList.remove("hidden")
				break
			case "hidden":
				view.classList.add("hidden")
				break
			case "clickable":
				view.classList.add("clickable")
				if (view.hasAttribute("click-show")) {
					const showTarget = document.getElementById(view.getAttribute("click-show") ?? "") as HTMLElement
					if (showTarget) {
						view.addEventListener("click", () => {
							if (showTarget.classList.contains("hidden"))
								showTarget.classList.remove("hidden")
							else
								showTarget.classList.add("hidden")
						})
					}
				}
				break
			case "qr":
				const qrCodeUrl = createStudyUrl(this.getAccessKey(sectionContent), sectionContent.getStaticInt("id") ?? -1, true)
				this.qrUrl(view, qrCodeUrl)
				break
			case "directQr":
				const url = view.getAttribute("qr-url") ?? ""
				if (url) {
					this.qrUrl(view, url)
				}
				break
		}
	}

	private static qrUrl(view: HTMLElement, url: string) {
		const qr = qrcode(0, 'L')

		qr.addData(url)
		qr.make()
		const imgUrl = qr.createDataURL(6);
		(view as HTMLImageElement).src = imgUrl
	}

	public static process(rootView: HTMLElement, sectionContent: SectionContent): void {
		const views = rootView.querySelectorAll("*[js-action]")
		for (let view of views as any) {
			this.processView(view, sectionContent)
		}
	}
}