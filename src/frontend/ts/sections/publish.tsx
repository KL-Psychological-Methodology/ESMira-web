import { SectionContent } from "../site/SectionContent";
import m, { Vnode } from "mithril";
import { Lang } from "../singletons/Lang";
import { BindObservable, BindValue } from "../components/BindObservable";
import { Study } from "../data/study/Study";
import { ObservablePrimitive } from "../observable/ObservablePrimitive";
import { TabBar, TabContent } from "../components/TabBar";
import { createAppUrl, createFallbackAppUrl, createQuestionnaireUrl, createStudyUrl, safeConfirm } from "../constants/methods";
import qrcode from "qrcode-generator"
import { BtnAdd, BtnCopy, BtnCustom, BtnTrash } from "../components/Buttons";
import { DashRow } from "../components/DashRow";
import { DashElement } from "../components/DashElement";
import { closeDropdown, openDropdown } from "../components/DropdownMenu";
import downloadSvg from "../../imgs/icons/download.svg?raw"
import studyDesc from "../../imgs/dashIcons/studyDesc.svg?raw"
import questionSvg from "../../imgs/icons/question.svg?raw"
import { Requests } from "../singletons/Requests";
import { FILE_ADMIN, URL_WIKI_DIFFERENCE_LINKS } from "../constants/urls";
import warnSvg from "../../imgs/icons/warn.svg?raw";
import { SectionData } from "../site/SectionData";

interface UrlCategory {
	urls: UrlEntry[],
	title?: string,
	footer?: string
	addContentAbove?: () => Vnode<any, any>
}
interface UrlEntry {
	title: string,
	url: string,
	allowSelection?: boolean
}

export class Content extends SectionContent {
	private readonly selectedIndex: ObservablePrimitive<number> = new ObservablePrimitive<number>(0, null, "accessKeyIndex")
	private qrSize: number = 5
	private currentRadioIndex: [number, number] = [0, 0]
	private readonly fallbackUrl?: string
	private duplicateAccessKeys: string[]
	private enableUrlStudyList = false

	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [
			Requests.loadJson(`${FILE_ADMIN}?type=GetOutboundFallbackUrls&study_id=${sectionData.getStaticInt("id") ?? 0}`).catch(() => { return [] }),
			Requests.loadJson(`${FILE_ADMIN}?type=GetDuplicateAccessKeys&study_id=${sectionData.getStaticInt("id") ?? 0}`).catch(() => { return [] }),
			sectionData.getStudyPromise()
		]
	}

	constructor(sectionData: SectionData, fallbackUrls: string[], duplicateAccessKeys: string[]) {
		super(sectionData)
		this.fallbackUrl = fallbackUrls.length ? fallbackUrls[0] : undefined
		this.duplicateAccessKeys = duplicateAccessKeys
	}

	public title(): string {
		return Lang.get("publish_study")
	}
	private checkAccessKeyFormat(s: string): boolean {
		return !!s.match(/^([a-zA-Z][a-zA-Z0-9]+)$/);
	}
	private accessKeyHasDuplications(accessKey: string): boolean {
		return this.duplicateAccessKeys.indexOf(accessKey) !== -1
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

	private onPointerEnterUrl(url: string, e: MouseEvent) {
		openDropdown("url", e.target as HTMLElement,
			() => <div class="smallText center nowrap">{url}</div>
		)
	}
	private onPointerLeaveUrl() {
		closeDropdown("url")
	}
	
	private getUrlView(title: string, url: string, radioIndex?: [number, number]): Vnode<any, any> {
		return <label
			onpointerenter={this.onPointerEnterUrl.bind(null, url)}
			onpointerleave={this.onPointerLeaveUrl.bind(null)}
			class="noTitle noDesc horizontal"
		>
			{ radioIndex &&
				<input type="radio" name="selected_url" checked={this.currentRadioIndex[0] == radioIndex[0] && this.currentRadioIndex[1] == radioIndex[1]} onchange={() => {
					this.currentRadioIndex = radioIndex
				}}/>
			}
			{title}
			{BtnCopy(() => navigator.clipboard.writeText(url))}
		</label>
	}
	
	private getPublishedStateView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		return <div>
			<div class="center">
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

			<div>
				<ol>
					<li><b>{Lang.getWithColon("published_not")}</b> {Lang.get("hint_study_unpublished")}</li>
					<li><b>{Lang.getWithColon("published")}</b> {Lang.get("hint_study_published")}</li>
					<li><b>{Lang.getWithColon("study_over")}</b> {Lang.get("hint_study_over")}</li>
				</ol>
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
		const anyPlatformEnabled = study.publishedAndroid.get() || study.publishedIOS.get() || study.publishedWeb.get()
		return <div>
			{DashRow(
				DashElement("stretched", {
					template: {
						icon: m.trust(studyDesc),
						title: Lang.get("hint_to_best_practices")
					},
					href: "https://github.com/KL-Psychological-Methodology/ESMira/wiki/Best-practices-and-common-problems"
				}),

			)}
			{this.getPublishedStateView()}
			{(study.published.get() || study.accessKeys.get().length != 0) &&
				<div>
					{DashRow(
						DashElement("stretched", {
							content:
								<div class="center scrollBox">
									<div class="vertical hAlignStart">
										{study.accessKeys.get().length == 0
											? <div class="spacingTop spacingBottom highlight center">{Lang.get("info_study_is_public")}</div>
											: study.accessKeys.get().map((accessKey, index) =>
												<div class="horizontal vAlignCenter">
													{BtnTrash(this.removeAccessKey.bind(this, study, index))}
													<label>
														<small>{Lang.get("accessKey")}</small>
														<input type="text" {...BindObservable(accessKey)} />
													</label>
													{this.accessKeyHasDuplications(accessKey.get()) &&
														<small title={Lang.get("duplicate_access_key_tooltip")}>{Lang.get("duplicate_access_key")}</small>
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
					{study.published.get() && (anyPlatformEnabled &&
						<div>{
							study.accessKeys.get().length >= 2
								? TabBar(this.selectedIndex,
									study.accessKeys.get().map((accessKey) => this.getPublishView(study, accessKey.get()))
								)
								: this.getPublishView(study, study.accessKeys.get()[0]?.get() ?? "").view()
						}</div>
						|| <div>
							{DashRow(
								DashElement("stretched", {
									highlight: true,
									content:
										<div class="center">
											{Lang.get("warning_all_platforms_disabled")}
										</div>
								})
							)}
						</div>
					)
					}
				</div>
			}
			{this.getPublishInfoView(study)}
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
						icon: m.trust(downloadSvg),
						title: Lang.get("download_source_for_publication")
					},
					href: window.URL.createObjectURL(new Blob([JSON.stringify(study.createJson())], { type: 'text/json' })),
					downloadHref: `${study.title.get()}.json`
				})
			)}
		</div>
	}

	private getStudyListCheckboxView(accessKey: string) {
		return <div>
			<hr/>
			
			<label title={Lang.get("desc_urls_target_study_list")}>
				<div class="flexBlock horizontal vAlignCenter">
					<input type="checkbox" {... BindValue(this.enableUrlStudyList, value => this.enableUrlStudyList = value)}/>
					<span>{Lang.get("urls_target_study_list")}</span>
					<a class="selfAlignStart" href={URL_WIKI_DIFFERENCE_LINKS} target="_blank">{BtnCustom(m.trust(questionSvg))}</a>
				</div>
				{this.enableUrlStudyList && !this.accessKeyHasDuplications(accessKey) &&
					<small><div class="inlineIcon">{m.trust(warnSvg)}</div>{Lang.get("access_key_has_no_duplications", accessKey)}</small>
				}
			</label>
		</div>
	}

	private getPublishView(study: Study, accessKey: string): TabContent {
		const urlList = this.createUrlList(study, accessKey)
		
		const usesFallback = study.useFallback.get() && !!this.fallbackUrl
		const currentUrl = urlList[this.currentRadioIndex[0]]?.urls[this.currentRadioIndex[1]]?.url ?? urlList[0].urls[0].url
		const qrCodeUrl = usesFallback ? `${currentUrl}?fallback=${this.fallbackUrl}` : currentUrl
		const qr = qrcode(0, 'L')
		qr.addData(qrCodeUrl)
		qr.make()
		const imgUrl = qr.createDataURL(this.qrSize)
		
		return {
			title: accessKey,
			view: () => <div>
				{DashRow(
					DashElement("vertical", ...urlList.map((category, categoryIndex) => ({
						content: <div>
							{category.title &&
								<h2 class="horizontal">
									{category.title}
								</h2>
							}
							{category.urls.map((entry, entryIndex) =>
								<div class="line">
									{this.getUrlView(entry.title, entry.url, entry.allowSelection ? [categoryIndex, entryIndex] : undefined)}
								</div>
							)}
							{category.footer &&
								<div class="smallText">
									{category.footer}
								</div>
							}
							{category.addContentAbove?.()}
						</div>
					}))),
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
								<p class="smallText">{Lang.get("desc_qrCode")}</p>
							</div>
					})
				)}
			</div>
		}
	}

	private createUrlList(study: Study, accessKey: string): UrlCategory[] {
		const infoTitle = study.questionnaires.get().length >= 1 ? Lang.get("questionnaire_view") : Lang.get("study")
		const usesFallback = study.useFallback.get() && !!this.fallbackUrl
		const publishedWeb = study.publishedWeb.get()
		const publishedSmartphone = study.publishedAndroid.get() || study.publishedIOS.get()
		
		const categoryList: UrlCategory[] = []
		
		const entry: UrlCategory = {
			title: Lang.get("study_urls"),
			urls: [],
			addContentAbove: accessKey ? this.getStudyListCheckboxView.bind(this, accessKey) : undefined
		}
		
		if(publishedWeb) {
			entry.urls.push({
				title: infoTitle,
				url: createStudyUrl(accessKey, study.id.get(), !this.enableUrlStudyList, "https"),
				allowSelection: true
			})
		}
		if(publishedSmartphone) {
			entry.urls.push({
				title: Lang.get("app_installation_instructions"),
				url: createAppUrl(accessKey, study.id.get(), !this.enableUrlStudyList, "https"),
				allowSelection: true
			})
		}
		categoryList.push(entry)
		
		if(usesFallback) {
			categoryList.push({
				urls: [{
					title: Lang.get("fallback_app_installation_instructions"),
					url: createFallbackAppUrl(accessKey, study.id.get(), this.fallbackUrl!)
				}]
			});
		}
		
		if(publishedWeb && study.questionnaires.get().length > 0) {
			categoryList.push({
				title: Lang.getWithColon("urls_instruction_questionnaires"),
				urls: study.questionnaires.get().map((questionnaire) => ({
					title: questionnaire.getTitle(),
					url: createQuestionnaireUrl(accessKey, questionnaire.internalId.get()),
					allowSelection: true
				})),
			})
		}
		
		return categoryList;
	}

	private getPublishInfoView(study: Study): Vnode<any, any> {
		const usesFallback = study.useFallback.get() && !!this.fallbackUrl
		const smartphoneAndWeb = (study.publishedAndroid.get() || study.publishedIOS.get()) && study.publishedWeb.get()

		return <div>
			{
				DashRow(
					DashElement("stretched", {
						content:
							<div>
								<h2>{Lang.get("publish_info_title")}</h2>
								<p>{Lang.get("publish_info_text_based")}
									{usesFallback && Lang.get("publish_info_fallback_link")}
									{Lang.get("publish_info_flyer_based")}</p>
								{smartphoneAndWeb && <p>{Lang.get("publish_info_difference_links")}</p>}
								<p>{Lang.get("publish_info_explanation_qr")}</p>
							</div>
					})
				)
			}
		</div>
	}
}