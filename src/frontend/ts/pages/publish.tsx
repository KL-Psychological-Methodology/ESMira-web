import { SectionContent } from "../site/SectionContent";
import m, { Vnode } from "mithril";
import { Lang } from "../singletons/Lang";
import { BindObservable } from "../widgets/BindObservable";
import { Study } from "../data/study/Study";
import { ObservablePrimitive } from "../observable/ObservablePrimitive";
import { TabBar, TabContent } from "../widgets/TabBar";
import { createAppUrl, createFallbackAppUrl, createQuestionnaireUrl, createStudyUrl } from "../constants/methods";
import qrcode from "qrcode-generator"
import { Section } from "../site/Section";
import { BtnAdd, BtnCopy, BtnTrash } from "../widgets/BtnWidgets";
import { DashRow } from "../widgets/DashRow";
import { DashElement, DashViewOptions } from "../widgets/DashElement";
import { closeDropdown, openDropdown } from "../widgets/DropdownMenu";
import downloadSvg from "../../imgs/icons/download.svg?raw"
import studyDesc from "../../imgs/dashIcons/studyDesc.svg?raw"
import { safeConfirm } from "../constants/methods";
import { Requests } from "../singletons/Requests";
import { FILE_ADMIN } from "../constants/urls";

export class Content extends SectionContent {
	private readonly selectedIndex: ObservablePrimitive<number> = new ObservablePrimitive<number>(0, null, "accessKeyIndex")
	private qrSize: number = 5
	private currentUrl: number = 0
	private allUrls: string[] = []
	private fallbackUrls: string[]

	public static preLoad(section: Section): Promise<any>[] {
		return [
			Requests.loadJson(`${FILE_ADMIN}?type=GetOutboundFallbackUrls&study_id=${section.getStaticInt("id") ?? 0}`).catch(() => { return [] }),
			section.getStudyPromise()
		]
	}

	constructor(section: Section, fallbackUrls: string[]) {
		super(section)
		this.fallbackUrls = fallbackUrls
	}

	public title(): string {
		return Lang.get("publish_study")
	}
	private checkAccessKeyFormat(s: string): boolean {
		return !!s.match(/^([a-zA-Z][a-zA-Z0-9]+)$/);
	}
	private addAccessKey(study: Study): void {
		const accessKey = prompt()
		if (accessKey == null)
			return

		if (this.checkAccessKeyFormat(accessKey)) {
			if (study.accessKeys.indexOf(accessKey) == -1)
				study.accessKeys.push(accessKey)
		}
		else
			Lang.get("error_accessKey_wrong_format")
	}
	private async removeAccessKey(study: Study, index: number): Promise<void> {
		const accessKey = study.accessKeys.get()[index].get()
		if (study.published.get() && !safeConfirm(Lang.get("confirm_delete_access_key", accessKey)))
			return
		study.accessKeys.remove(index)
	}

	private changeQrSize(e: InputEvent): void {
		this.qrSize = parseInt((e.target as HTMLInputElement).value)
		if (this.qrSize < 1) {
			this.qrSize = 1
		}
	}
	private changeQrUrl(urlIndex: number): void {
		this.currentUrl = urlIndex
	}

	private onPointerEnterUrl(url: string, e: MouseEvent) {
		openDropdown("url", e.target as HTMLElement,
			() => <div class="smallText center nowrap">{url}</div>
		)
	}
	private onPointerLeaveUrl() {
		closeDropdown("url")
	}

	private getUrlViewAndCacheUrl(title: string, url: string): Vnode<any, any> {
		const index = this.allUrls.length
		this.allUrls.push(url)
		return <div
			onpointerenter={this.onPointerEnterUrl.bind(null, url)}
			onpointerleave={this.onPointerLeaveUrl.bind(null)}
		>
			<label class="noTitle noDesc ">
				<input type="radio" name="selected_url" checked={this.currentUrl == index} onchange={this.changeQrUrl.bind(this, index)} />
				{title}
			</label>
			&nbsp;
			<span class="middle">
				{BtnCopy(() => navigator.clipboard.writeText(url))}
			</span>
		</div>
	}

	private getPublishedView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		return <div class="center">
			<div>
				<label class="noTitle noDesc">
					<input type="radio" name="published" checked={!study.published.get()} onchange={this.changePublishedState.bind(this, false, false)} />
					{Lang.get("published_not")}
				</label>
			</div>
			<div>
				<label class="noTitle noDesc">
					<input type="radio" name="published" checked={study.published.get() && !study.studyOver.get()} onchange={this.changePublishedState.bind(this, true, false)} />
					{Lang.get("published")}
				</label>
			</div>
			<div>
				<label class="noTitle noDesc">
					<input type="radio" name="published" checked={study.published.get() && study.studyOver.get()} onchange={this.changePublishedState.bind(this, true, true)} />
					{Lang.get("study_over")}
				</label>
			</div>
		</div>
	}

	private changePublishedState(published: boolean, over: boolean): void {
		const study = this.getStudyOrThrow()
		study.published.set(published)
		study.studyOver.set(over)
	}


	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		return <div>
			{this.getPublishedView()}
			{(study.published.get() || study.accessKeys.get().length != 0) &&
				<div>
					{DashRow(
						DashElement("stretched", {
							content:
								<div class="listParent scrollBox">
									<div class="listChild">
										{study.accessKeys.get().length == 0
											? <div class="spacingTop spacingBottom highlight center">{Lang.get("info_study_is_public")}</div>
											: study.accessKeys.get().map((accessKey, index) =>
												<div>
													{BtnTrash(this.removeAccessKey.bind(this, study, index))}
													<label>
														<small>{Lang.get("accessKey")}</small>
														<input type="text" {...BindObservable(accessKey)} />
													</label>
												</div>
											)
										}
									</div>
									<br />
									{BtnAdd(this.addAccessKey.bind(this, study), Lang.get("add_access_key"))}
								</div>
						})
					)}
					<br />
					{study.published.get() &&
						<div>{
							study.accessKeys.get().length >= 2
								? TabBar(this.selectedIndex,
									study.accessKeys.get().map((accessKey) => this.getPublishView(study, accessKey.get()))
								)
								: this.getPublishView(study, study.accessKeys.get()[0]?.get() ?? "").view()
						}</div>
					}
				</div>
			}
			{DashRow(
				DashElement("stretched", {
					content:
						<div>
							<h2>{Lang.getWithColon("plead_to_cite_us")}</h2>
							<div class="spacingLeft">
								<p class="hanging">
									Lewetz, D., Stieger, S. (2023). ESMira: A decentralized open-source application for collecting experience sampling data. <i>Behavior Research Methods</i>.
									<br />
									<a class="showArrow" href="https://doi.org/10.3758/s13428-023-02194-2" target="_blank">https://doi.org/10.3758/s13428-023-02194-2</a>
								</p>
							</div>

							<div class="spacingTop">{Lang.getWithColon("more_information")}</div>
							<div class="spacingLeft"><a class="showArrow" href="https://github.com/KL-Psychological-Methodology/ESMira/wiki/Conditions-for-using-ESMira" target="_blank">Conditions for using ESMira</a></div>
						</div>
				}),

				DashElement("stretched", {
					template: {
						icon: m.trust(studyDesc),
						title: Lang.get("hint_to_best_practices")
					},
					href: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Best-practices-and-problems"
				}),

				DashElement("stretched", {
					template: {
						icon: m.trust(downloadSvg),
						title: Lang.get("download_source_for_publication")
					},
					href: window.URL.createObjectURL(new Blob([JSON.stringify(study.createJson())], { type: 'text/json' })),
					downloadHref: `${study.title.get()}.json`
				})
			)}
		</div>
	}


	private getPublishView(study: Study, accessKey: string): TabContent {
		//We create urlList first so all urls are cached for the qr code to use:
		const urlList = this.getUrlListAndCacheUrls(study, accessKey)
		const qrCodeUrl = this.allUrls[this.currentUrl]
		const qr = qrcode(0, 'L')
		qr.addData(qrCodeUrl)
		qr.make()
		const imgUrl = qr.createDataURL(this.qrSize)

		return {
			title: accessKey,
			view: () => DashRow(
				DashElement(null, {
					content:
						<div>
							<div class="center">
								<label>
									<small>{Lang.get("size")}</small>
									<input type="number" min="1" value={this.qrSize} onchange={this.changeQrSize.bind(this)} />
								</label>
							</div>
							<div class="center">
								<a download href={imgUrl} title={qrCodeUrl}>
									<img alt="QrCode" src={imgUrl} />
								</a>
							</div>
							<p class="vertical smallText">{Lang.get("desc_qrCode")}</p>
						</div>
				}),
				DashElement("vertical", ...urlList)
			)
		}
	}

	private getUrlListAndCacheUrls(study: Study, accessKey: string): (DashViewOptions | false)[] {
		this.allUrls = []
		const infoTitle = study.questionnaires.get().length >= 1 ? Lang.get("questionnaire_view") : Lang.get("study")
		const appInstrTitle = Lang.get("app_installation_instructions")
		const fallbackUrl = study.useFallback.get() && this.fallbackUrls.length > 0 ? this.fallbackUrls[0] : ""
		const fallbackAppInstallUrl = createFallbackAppUrl(accessKey, study.id.get(), fallbackUrl)

		return [
			{
				content: <div>
					<div class="line">
						{this.getUrlViewAndCacheUrl(infoTitle, createStudyUrl(accessKey, study.id.get(), false, "https"))}
					</div>
					<div class="line">
						{this.getUrlViewAndCacheUrl(appInstrTitle, createAppUrl(accessKey, study.id.get(), false, "https", fallbackUrl))}
					</div>
					{accessKey.length > 0 &&
						<div class="smallText">{Lang.get("info_urls_without_study_id")}</div>
					}
				</div>
			},
			accessKey.length > 0 && {
				content: <div>
					<div class="line">
						{this.getUrlViewAndCacheUrl(infoTitle, createStudyUrl(accessKey, study.id.get(), true, "https"))}
					</div>
					<div class="line">
						{this.getUrlViewAndCacheUrl(appInstrTitle, createAppUrl(accessKey, study.id.get(), true, "https", fallbackUrl))}
					</div>
				</div>
			},
			study.questionnaires.get().length > 0 && {
				content: <div>
					<h2>{Lang.getWithColon("questionnaires")}</h2>
					{study.questionnaires.get().map((questionnaire) =>
						<div class="line">
							{this.getUrlViewAndCacheUrl(questionnaire.getTitle(), createQuestionnaireUrl(accessKey, questionnaire.internalId.get()))}
						</div>
					)}
				</div>
			},
			study.useFallback && {
				content: <div
					onpointerenter={this.onPointerEnterUrl.bind(null, fallbackAppInstallUrl)}
					onpointerleave={this.onPointerLeaveUrl.bind(null)}
				>
					<label class="noTitle noDesc">{Lang.get("fallback_app_installation_instructions")}</label>
					&nbsp;
					<span class="middle">
						{BtnCopy(() => navigator.clipboard.writeText(fallbackAppInstallUrl))}
					</span>
				</div>
			}
		]
	}
}