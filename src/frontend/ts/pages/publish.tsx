import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {BindObservable} from "../widgets/BindObservable";
import {TitleRow} from "../widgets/TitleRow";
import {Study} from "../data/study/Study";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {TabBar, TabContent} from "../widgets/TabBar";
import {createAppUrl, createQuestionnaireUrl, createStudyUrl} from "../constants/methods";
import qrcode from "qrcode-generator"
import {Section} from "../site/Section";
import {BtnAdd, BtnCopy, BtnTrash} from "../widgets/BtnWidgets";
import {DashRow} from "../widgets/DashRow";
import {DashElement, DashViewOptions} from "../widgets/DashElement";
import {closeDropdown, openDropdown} from "../widgets/DropdownMenu";
import downloadSvg from "../../imgs/icons/download.svg?raw"

export class Content extends SectionContent {
	private readonly selectedIndex: ObservablePrimitive<number> = new ObservablePrimitive<number>(0, null, "accessKeyIndex")
	private qrSize: number = 5
	private currentUrl: number = 0
	private allUrls: string[] = []
	
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}
	
	public title(): string {
		return Lang.get("publish_study")
	}
	private checkAccessKeyFormat(s: string): boolean {
		return !!s.match(/^([a-zA-Z][a-zA-Z0-9]+)$/);
	}
	private addAccessKey(study: Study): void {
		const accessKey = prompt()
		if(accessKey == null)
			return
		
		if(this.checkAccessKeyFormat(accessKey)) {
			if(study.accessKeys.indexOf(accessKey) == -1)
				study.accessKeys.push(accessKey)
		}
		else
			Lang.get("error_accessKey_wrong_format")
	}
	private removeAccessKey(study: Study, index: number): void {
		study.accessKeys.remove(index)
	}
	
	private changeQrSize(e: InputEvent): void {
		this.qrSize = parseInt((e.target as HTMLInputElement).value)
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
				<input type="radio" name="selected_url" checked={this.currentUrl == index} onchange={this.changeQrUrl.bind(this, index)}/>
				{title}
			</label>
				&nbsp;
				<span class="middle">
					{BtnCopy(() => navigator.clipboard.writeText(url))}
				</span>
		</div>
	}
	
	
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		return <div>
			<div class="center">
				<label>
					<input type="checkbox" {... BindObservable(study.published)}/>
					<span class="highlight">{study.published.get() ? Lang.get("published") :  Lang.get("published_not")}</span>
				</label>
			</div>
			{study.published.get() &&
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
														<input type="text" {...BindObservable(accessKey)}/>
													</label>
												</div>
											)
										}
									</div>
									<br/>
									{BtnAdd(this.addAccessKey.bind(this, study), Lang.get("add_access_key"))}
								</div>
							})
					)}
					<br/>
					
					{
						study.accessKeys.get().length >= 2
							? TabBar(this.selectedIndex,
								study.accessKeys.get().map((accessKey) => this.getPublishView(study, accessKey.get()))
							)
							: this.getPublishView(study, study.accessKeys.get()[0]?.get() ?? "").view()
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
									<br/>
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
					href: window.URL.createObjectURL(new Blob([JSON.stringify(study.createJson())], {type: 'text/json'}))
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
									<input type="number" value={this.qrSize} onchange={this.changeQrSize.bind(this)}/>
								</label>
							</div>
							<div class="center">
								<a download href={imgUrl} title={qrCodeUrl}>
									<img alt="QrCode" src={imgUrl}/>
								</a>
							</div>
							<p class="vertical smallText">{Lang.get("desc_qrCode")}</p>
						</div>
				}),
				DashElement("vertical", ... urlList)
			)
		}
	}
	
	private getUrlListAndCacheUrls(study: Study, accessKey: string): (DashViewOptions | false)[] {
		this.allUrls = []
		const infoTitle = study.questionnaires.get().length >= 1 ? Lang.get("questionnaire_view") : Lang.get("study")
		const appInstrTitle = Lang.get("app_installation_instructions")
		
		return [
			{
				content: <div>
					<div class="line">
						{this.getUrlViewAndCacheUrl(infoTitle, createStudyUrl(accessKey, study.id.get()))}
					</div>
					<div class="line">
						{this.getUrlViewAndCacheUrl(appInstrTitle, createAppUrl(accessKey, study.id.get()))}
					</div>
					{accessKey.length > 0 &&
						<div class="smallText">{Lang.get("info_urls_without_study_id")}</div>
					}
				</div>
			},
			accessKey.length > 0 && {
				content: <div>
					<div class="line">
						{this.getUrlViewAndCacheUrl(infoTitle, createStudyUrl(accessKey, study.id.get(), true))}
					</div>
					<div class="line">
						{this.getUrlViewAndCacheUrl(appInstrTitle, createAppUrl(accessKey, study.id.get(), true))}
					</div>
				</div>
			},
			study.questionnaires.get().length > 0 &&{
				content: <div>
					<h2>{Lang.getWithColon("questionnaires")}</h2>
					{study.questionnaires.get().map((questionnaire) =>
						<div class="line">
							{this.getUrlViewAndCacheUrl(questionnaire.getTitle(), createQuestionnaireUrl(accessKey, questionnaire.internalId.get()))}
						</div>
					)}
				</div>
			}
		]
	}
}