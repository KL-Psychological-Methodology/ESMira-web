import {SectionAlternative, SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {TitleRow} from "../widgets/TitleRow";
import {UrlAlternatives} from "../helpers/UrlAlternatives";
import {BtnReload} from "../widgets/BtnWidgets";

export class Content extends SectionContent {
	public static preLoad(section: Section): Promise<any>[] {
		return [
			section.getStudyPromise(),
			section.getTools().messagesLoader.getReloadedMessageParticipantInfoList(section.getStaticInt("id") ?? -1)
		]
	}
	
	public title(): string {
		return Lang.get("messages")
	}
	
	public titleExtra(): Vnode<any, any> | null {
		return BtnReload(this.section.reload.bind(this.section))
	}
	
	public hasAlternatives(): boolean {
		return true
	}
	public getAlternatives(): SectionAlternative[] | null {
		return UrlAlternatives.studyAlternatives(this.getStudyOrThrow().id.get(), "messagesOverview")
	}
	
	public getView(): Vnode<any, any> {
		const studyId = this.getStudyOrThrow().id.get()
		const messageParticipantEntryList = this.getTools().messagesLoader.getMessageParticipantInfoListOrThrow(studyId).get()
		return <div>
			<div class="center">
				<a href={this.getUrl("chat")}>{Lang.get("send_message_to_user")}</a>
			</div>
			<br/>
			{TitleRow(Lang.getWithColon("messages"))}
			{messageParticipantEntryList.map((entry) =>
				<div class="verticalPadding">
					<a href={this.getUrl(`chat,userId:${btoa(entry.name.get())}`)}>
						<span>{entry.name.get()}</span>
						<small class="infoSticker">{new Date(entry.lastMsg.get()).toLocaleString()}</small>
						{entry.unread.get() &&
							<small class="infoSticker highlight">{Lang.get("unread")}</small>
						}
						{entry.pending.get() &&
							<small class="infoSticker highlight">{Lang.get("waiting")}</small>
						}
					</a>
				</div>
			)}
		</div>
	}
}