import {SectionAlternative, SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {TitleRow} from "../components/TitleRow";
import {SharedUrlAlternatives} from "../helpers/SharedUrlAlternatives";
import {BtnReload} from "../components/Buttons";
import {makeUrlFriendly} from "../constants/methods";
import {SectionData} from "../site/SectionData";

export class Content extends SectionContent {
	public static preLoad(sectionData: SectionData): Promise<any>[] {
		return [
			sectionData.getStudyPromise(),
			sectionData.getTools().messagesLoader.getReloadedMessageParticipantInfoList(sectionData.getStaticInt("id") ?? -1)
		]
	}

	public title(): string {
		return Lang.get("messages")
	}

	public titleExtra(): Vnode<any, any> | null {
		return BtnReload(this.sectionData.callbacks?.reload.bind(this.sectionData), Lang.get("reload"))
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
			<br />
			{TitleRow(Lang.getWithColon("messages"))}
			<div class="stickerList">
				{messageParticipantEntryList.map((entry) =>
					<div class="line">
						<a class="title" href={this.getUrl(`chat,userId:${makeUrlFriendly(entry.name.get())}`)}>
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