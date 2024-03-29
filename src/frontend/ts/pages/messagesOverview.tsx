import {SectionAlternative, SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {TitleRow} from "../widgets/TitleRow";
import {SharedUrlAlternatives} from "../helpers/SharedUrlAlternatives";
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
		return BtnReload(this.section.reload.bind(this.section), Lang.get("reload"))
	}
	
	public hasAlternatives(): boolean {
		return true
	}
	public getAlternatives(): SectionAlternative[] | null {
		return SharedUrlAlternatives.studyAlternatives(this, "msgs")
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
			<div class="stickerList">
				{messageParticipantEntryList.map((entry) =>
					<div class="line">
						<a class="title" href={this.getUrl(`chat,userId:${btoa(entry.name.get())}`)}>
							<span>{entry.name.get()}</span>
							{entry.pending.get() &&
								<span class="extraNote">{Lang.get('waiting')}</span>
								
							}
						</a>
						{entry.unread.get() &&
							<small class="infoSticker highlight">{Lang.get("unread")}</small>
						}
						<small class="infoSticker">{new Date(entry.lastMsg.get()).toLocaleString()}</small>
					</div>
				)}
			</div>
		</div>
	}
}