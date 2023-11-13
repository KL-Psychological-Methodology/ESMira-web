import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {BindObservable} from "../widgets/BindObservable";
import {TitleRow} from "../widgets/TitleRow";
import {Study} from "../data/study/Study";
import {safeConfirm} from "../constants/methods";
import {FILE_ADMIN} from "../constants/urls";
import {Requests} from "../singletons/Requests";
import {Section} from "../site/Section";
import {BtnTrash} from "../widgets/BtnWidgets";
import {DashRow} from "../widgets/DashRow";
import { DashElement } from "../widgets/DashElement";
import {StudyMetadata} from "../loader/StudyLoader";

export class Content extends SectionContent {
	private isFrozen: boolean = false
	
	public static preLoad(section: Section): Promise<any>[] {
		return [
			Requests.loadJson(`${FILE_ADMIN}?type=IsFrozen&study_id=${section.getStaticInt("id")}`),
			section.getStudyPromise()
		]
	}
	
	constructor(section: Section, [ frozen ] : boolean[]) {
		super(section);
		this.isFrozen = frozen
	}
	
	public title(): string {
		return Lang.get("study_description")
	}
	
	private async toggleFreezeStudy(study: Study, e: InputEvent): Promise<void> {
		const sendFrozen = (e.target as HTMLInputElement).checked
		const [ loadFrozen ]: boolean[] = await this.section.loader.loadJson(`${FILE_ADMIN}?type=FreezeStudy${sendFrozen ? "&frozen" : ""}&study_id=${study.id.get()}`)
		this.isFrozen = loadFrozen
		this.section.loader.info(this.isFrozen ? Lang.get("info_study_frozen") : Lang.get("info_study_unfrozen"))
	}
	
	private async deleteStudy(study: Study): Promise<void> {
		if(!safeConfirm(Lang.get("confirm_delete_study", study.title.get())))
			return
		
		await this.section.loader.showLoader(this.section.siteData.studyLoader.deleteStudy(study))
		
		this.goTo("admin/allStudies:edit")
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		const studyMetadata: StudyMetadata | null = this.section.siteData.studyLoader.studyMetadata[study.id.get()]
		const uploadSettings = study.eventUploadSettings
		return <div>
			{DashRow(
				DashElement("stretched", {
					content:
						<div class="center">
							<table class="studyInfoTable">
								<tr>
									<td>{Lang.getWithColon("study_version")}</td>
									<td>{`${study.version.get()}.${study.subVersion.get()}`}</td>
								</tr>
								<tr>
									<td>{Lang.getWithColon("created_by")}</td>
									{studyMetadata && <td>{studyMetadata.owner}</td>}
								</tr>
								<tr>
									<td>{Lang.getWithColon("creation_date")}</td>
									{studyMetadata && <td>{new Date(studyMetadata.createdTimestamp * 1000).toLocaleString()}</td>}
								</tr>
								{studyMetadata && studyMetadata.owner != studyMetadata.lastSavedBy && <tr>
									<td>{Lang.getWithColon("last_saved_by")}</td>
									{studyMetadata && <td>{studyMetadata.lastSavedBy}</td>}
								</tr>}
							</table>
						</div>
				}),
				DashElement(null, {
					content:
						<div class="center">
							<span class="middle spacingRight">
								{Lang.getWithColon("study_availability")}
							</span>
							<br/>
							<br/>
							<label class="middle noDesc">
								<small>{Lang.get('Android')}</small>
								<input type="checkbox" {...BindObservable(study.publishedAndroid)}/>
							</label>
							&nbsp;
							<label class="middle noDesc">
								<small>{Lang.get('iOS')}</small>
								<input type="checkbox" {...BindObservable(study.publishedIOS)}/>
							</label>
							&nbsp;
							<label class="middle noDesc">
								<small>{Lang.get('Web')}</small>
								<input type="checkbox" {...BindObservable(study.publishedWeb)}/>
							</label>
						</div>
				}),
				DashElement("vertical", {
					content:
						<div>
							<label class="vertical noTitle noDesc">
								<input type="checkbox" {...BindObservable(study.sendMessagesAllowed)}/>
								<span>{Lang.get('allow_incoming_messages')}</span>
							</label>
						</div>
				}),
				DashElement(null,  {
					content:
						<div>
							<label class="vertical noTitle">
								<input type="checkbox" checked={this.isFrozen} onchange={this.toggleFreezeStudy.bind(this, study)}/>
								<span>{Lang.get("freeze_study")}</span>
								<small>{Lang.get("desc_freeze_study")}</small>
							</label>
						</div>
				})
			)}
			
			
			
			
			
			
			{TitleRow(Lang.getWithColon("inform_server_about_events"))}
			<div class="scrollBox">
				<label class="noTitle vertical">
					<input type="checkbox" checked="checked" disabled="disabled"/>
						<span>joined</span>
						<small>{Lang.get("desc_joined")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" checked="checked" disabled="disabled"/>
						<span>questionnaire</span>
						<small>{Lang.get("desc_questionnaire")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" checked="checked" disabled="disabled" />
						<span>quit</span>
						<small>{Lang.get("desc_quit")}</small>
				</label>
				
				
				<label class="noTitle vertical">
					<input type="checkbox" checked="checked" {... BindObservable(uploadSettings.actions_executed)}/>
						<span>actions_executed</span>
						<small>{Lang.get("desc_actions_executed")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" {... BindObservable(uploadSettings.invitation)}/>
						<span>invitation</span>
						<small>{Lang.get("desc_invitation")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" {... BindObservable(uploadSettings.invitation_missed)}/>
						<span>invitation_missed</span>
						<small>{Lang.get("desc_invitation_missed")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" {... BindObservable(uploadSettings.message)}/>
						<span>message</span>
						<small>{Lang.get("desc_message")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" {... BindObservable(uploadSettings.notification)}/>
						<span>notification</span>
						<small>{Lang.get("desc_notification")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" {... BindObservable(uploadSettings.rejoined)}/>
						<span>rejoined</span>
						<small>{Lang.get("desc_rejoined")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" {... BindObservable(uploadSettings.reminder)}/>
						<span>reminder</span>
						<small>{Lang.get("desc_reminder")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" checked="checked" {... BindObservable(uploadSettings.schedule_planned)}/>
					<span>schedule_planned</span>
					<small>{Lang.get("desc_schedule_planned")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" checked="checked" {... BindObservable(uploadSettings.schedule_removed)}/>
					<span>schedule_removed</span>
					<small>{Lang.get("desc_schedule_removed")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" {... BindObservable(uploadSettings.schedule_changed)}/>
						<span>schedule_changed</span>
						<small>{Lang.get("desc_schedule_changed")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" {... BindObservable(uploadSettings.statistic_viewed)}/>
						<span>statistic_viewed</span>
						<small>{Lang.get("desc_statistic_viewed")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" {... BindObservable(uploadSettings.study_message)}/>
						<span>study_message</span>
						<small>{Lang.get("desc_study_message")}</small>
				</label>
				<label class="noTitle vertical">
					<input type="checkbox" {... BindObservable(uploadSettings.study_updated)}/>
						<span>study_updated</span>
						<small>{Lang.get("desc_study_updated")}</small>
				</label>
			</div>
			
			{TitleRow(Lang.getWithColon("delete_study"))}
			<div class="center">
				{BtnTrash(this.deleteStudy.bind(this, study), Lang.get("delete_study"))}
			</div>
		</div>
	}
}