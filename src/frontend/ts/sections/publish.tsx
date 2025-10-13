import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {BindObservable} from "../widgets/BindObservable";
import {Study} from "../data/study/Study";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {TabBar, TabContent} from "../widgets/TabBar";
import {createAppUrl, createFallbackAppUrl, createQuestionnaireUrl, createStudyUrl, safeConfirm} from "../constants/methods";
import qrcode from "qrcode-generator"
import {BtnAdd, BtnCopy, BtnCustom, BtnTrash} from "../widgets/BtnWidgets";
import {DashRow} from "../widgets/DashRow";
import {DashElement, DashViewOptions} from "../widgets/DashElement";
import {closeDropdown, openDropdown} from "../widgets/DropdownMenu";
import downloadSvg from "../../imgs/icons/download.svg?raw"
import studyDesc from "../../imgs/dashIcons/studyDesc.svg?raw"
import questionSvg from "../../imgs/icons/question.svg?raw"
import {Requests} from "../singletons/Requests";
import {FILE_ADMIN, URL_WIKI_DIFFERENCE_LINKS} from "../constants/urls";
import warnSvg from "../../imgs/icons/warn.svg?raw";
import {SectionData} from "../site/SectionData";

export class Content extends SectionContent {
	private readonly selectedIndex: ObservablePrimitive<number> = new ObservablePrimitive<number>(0, null, "accessKeyIndex")
	private qrSize: number = 5
	private currentUrl: number = 0
	private allUrls: string[] = []
	private fallbackUrls: string[]
	private duplicateAccessKeys: string[]

	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [
			Requests.loadJson(`${FILE_ADMIN}?type=GetOutboundFallbackUrls&study_id=${sectionData.getStaticInt("id") ?? 0}`).catch(() => { return [] }),
			Requests.loadJson(`${FILE_ADMIN}?type=GetDuplicateAccessKeys&study_id=${sectionData.getStaticInt("id") ?? 0}`).catch(() => { return [] }),
			sectionData.getStudyPromise()
		]
	}

	constructor(sectionData: SectionData, fallbackUrls: string[], duplicateAccessKeys: string[]) {
		super(sectionData)
		this.fallbackUrls = fallbackUrls
		this.duplicateAccessKeys = duplicateAccessKeys
		const study = this.getStudyOrNull()
		if (study != null && study.accessKeys.get().length > 0) {
			this.currentUrl = 2
		}
	}

	public title(): string {
		return Lang.get("publish_study")
	}
	private checkAccessKeyFormat(s: string): boolean {
		return !!s.match(/^([a-zA-Z][a-zA-Z0-9]+)$/);
	}
	private async addAccessKey(study: Study): Promise<void> {
		const accessKey = prompt()
		if (accessKey == null)
			return

		if (this.checkAccessKeyFormat(accessKey)) {
			if (study.accessKeys.indexOf(accessKey) == -1) {
				study.accessKeys.push(accessKey)
				await this.reloadDuplicatedAccessKeys()
			}
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
	private async reloadDuplicatedAccessKeys(): Promise<void> {
		const study = this.getStudyOrThrow()
		const accessKeys = study.accessKeys.get()
		if (accessKeys.length == 0) {
			return
		}
		const accessKeyString = study.accessKeys.get().map((key) => key.get()).join(",")
		this.duplicateAccessKeys = await this.sectionData.loader.loadJson(
			`${FILE_ADMIN}?type=GetDuplicateAccessKeys&study_id=${this.sectionData.getStaticInt("id") ?? 0}`,
			"post",
			`accessKeys=${accessKeyString}`
		).catch(() => { return [] })
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
													{this.duplicateAccessKeys.indexOf(accessKey.get()) !== -1 &&
														<small title={Lang.get("duplicate_access_key_tooltip")}><div class="inlineIcon">{m.trust(warnSvg)}</div>{Lang.get("duplicate_access_key")}</small>
													}
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
									Lewetz, D., & Stieger, S. (2024). ESMira: A decentralized open-source application for collecting experience sampling data. <i>Behavior Research Methods</i>, <i>56</i>, 4421-4434.
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
					href: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Best-practices-and-common-problems"
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
				DashElement("vertical", ...urlList),
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
				})
			)
		}
	}

	private getUrlListAndCacheUrls(study: Study, accessKey: string): (DashViewOptions | false)[] {
		this.allUrls = []
		const infoTitle = study.questionnaires.get().length >= 1 ? Lang.get("questionnaire_view") : Lang.get("study")
		const appInstrTitle = Lang.get("app_installation_instructions")
		const usesFallback = study.useFallback.get() && this.fallbackUrls.length > 0
		const fallbackUrl = usesFallback ? this.fallbackUrls[0] : ""
		const fallbackAppInstallUrl = createFallbackAppUrl(accessKey, study.id.get(), fallbackUrl)
		const hasAccessKeys = accessKey.length > 0

		return [
			{
				content: <div>
					<h2>{Lang.getWithColon(hasAccessKeys ? "urls_access_key" : "urls_id")} <a href={URL_WIKI_DIFFERENCE_LINKS} class="right" target="_blank">{BtnCustom(m.trust(questionSvg))}</a></h2>
					<div class="line">
						{this.getUrlViewAndCacheUrl(infoTitle, createStudyUrl(accessKey, study.id.get(), false, "https"))}
					</div>
					<div class="line">
						{this.getUrlViewAndCacheUrl(appInstrTitle, createAppUrl(accessKey, study.id.get(), false, "https", fallbackUrl))}
					</div>
					{accessKey.length > 0 &&
						<div class="smallText">
							{this.duplicateAccessKeys.length > 0 && <div class="inlineIcon">{m.trust(warnSvg)}</div>}
							{Lang.get("info_urls_without_study_id")}
						</div>
					}
				</div>
			},
			hasAccessKeys && {
				content: <div>
					<h2>{Lang.getWithColon("urls_id")} <a href={URL_WIKI_DIFFERENCE_LINKS} class="right" target="_blank">{BtnCustom(m.trust(questionSvg))}</a></h2>
					<div class="line">
						{this.getUrlViewAndCacheUrl(infoTitle, createStudyUrl(accessKey, study.id.get(), true, "https"))}
					</div>
					<div class="line">
						{this.getUrlViewAndCacheUrl(appInstrTitle, createAppUrl(accessKey, study.id.get(), true, "https", fallbackUrl))}
					</div>
					{accessKey.length > 0 &&
						<div class="smallText">
							{Lang.get("info_links_with_study_id")}
						</div>
					}
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
			usesFallback && {
				content: <div
					onpointerenter={this.onPointerEnterUrl.bind(null, fallbackAppInstallUrl)}
					onpointerleave={this.onPointerLeaveUrl.bind(null)}
				>
					<h2>{Lang.getWithColon("fallback_app_installation_instructions")}</h2>
					&nbsp;
					<span class="middle">
						{BtnCopy(() => navigator.clipboard.writeText(fallbackAppInstallUrl))}
					</span>
				</div>
			}
		]
	}
}