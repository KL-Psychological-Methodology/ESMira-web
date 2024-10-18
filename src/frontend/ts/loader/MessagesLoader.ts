import {FILE_ADMIN} from "../constants/urls";
import {PromiseCache} from "../singletons/PromiseCache";
import {Requests} from "../singletons/Requests";
import {ObservableArray} from "../observable/ObservableArray";
import {TranslatableObjectDataType} from "../observable/ObservableStructure";
import {MessageParticipantInfo} from "../data/messages/MessageParticipantInfo";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {LoginDataInterface} from "../admin/LoginDataInterface";
import {ParticipantMessagesContainer} from "../data/messages/ParticipantMessagesContainer";

export type MessageParticipantInfoList = ObservableArray<TranslatableObjectDataType, MessageParticipantInfo>

export class MessagesLoader {
	private messageParticipantInfoLists: Record<number, MessageParticipantInfoList> = {}
	public readonly studiesWithNewMessagesCount = new ObservablePrimitive<number>(0, null, "studiesWithNewMessagesCount")
	public readonly studiesWithNewMessagesList: Record<number, boolean> = {}
	
	constructor(data: LoginDataInterface) {
		if(data.newMessages) {
			for(let id of data.newMessages) {
				this.studiesWithNewMessagesList[id] = true
			}
			this.studiesWithNewMessagesCount.set(data.newMessages.length)
		}
	}
	
	private async hasUnread(list: MessageParticipantInfoList): Promise<boolean> {
		for(let info of list.get()) {
			if(info.unread.get())
				return true
		}
		return false
	}
	
	public async getMessageParticipantInfoList(studyId: number): Promise<MessageParticipantInfoList> {
		return PromiseCache.get(`messageParticipantEntryList-${studyId}`, async () => {
			const jsonList = await this.loadMessageParticipantInfoList(studyId)
			
			if(this.messageParticipantInfoLists.hasOwnProperty(studyId)) {
				const wasUnread = await this.hasUnread(this.messageParticipantInfoLists[studyId])
				this.messageParticipantInfoLists[studyId].replace(jsonList)
				const isUnread = await this.hasUnread(this.messageParticipantInfoLists[studyId])
				
				if(wasUnread && !isUnread) {
					this.studiesWithNewMessagesCount.set(this.studiesWithNewMessagesCount.get() - 1)
					delete this.studiesWithNewMessagesList[studyId]
				}
				else if(!wasUnread && isUnread) {
					this.studiesWithNewMessagesCount.set(this.studiesWithNewMessagesCount.get() + 1)
					this.studiesWithNewMessagesList[studyId] = true
				}
			}
			else {
				const list = new ObservableArray<TranslatableObjectDataType, MessageParticipantInfo>(
					jsonList,
					null,
					`messageParticipantEntryList-${studyId}`,
					(data, parent, key) => {
						return new MessageParticipantInfo(data, parent, key)
					})
				this.messageParticipantInfoLists[studyId] = list
				
				if(await this.hasUnread(list)) {
					this.studiesWithNewMessagesCount.set(this.studiesWithNewMessagesCount.get() + 1)
					this.studiesWithNewMessagesList[studyId] = true
				}
			}
			
			return this.messageParticipantInfoLists[studyId]
		})
	}
	public getReloadedMessageParticipantInfoList(studyId: number): Promise<MessageParticipantInfoList> {
		PromiseCache.remove(`messageParticipantEntryList-${studyId}`)
		return this.getMessageParticipantInfoList(studyId)
	}
	public getMessageParticipantInfoListOrThrow(studyId: number): MessageParticipantInfoList {
		if(!this.messageParticipantInfoLists.hasOwnProperty(studyId))
			throw new Error(`No messages for ${studyId}. Were they loaded?`)
		return this.messageParticipantInfoLists[studyId]
	}
	
	private async loadMessageParticipantInfoList (studyId: number): Promise<TranslatableObjectDataType[]> {
		const listJson: TranslatableObjectDataType[] = await Requests.loadJson(`${FILE_ADMIN}?type=ListUserWithMessages&study_id=${studyId}`)
		
		listJson.sort((a,b) => {
			if(a["lastMsg"] == b["lastMsg"])
				return 0
			else
				return a["lastMsg"] < b["lastMsg"] ? 1 : -1
		})
		return listJson
	}
	
	public async loadMessages(studyId: number, userId: string): Promise<ParticipantMessagesContainer> {
		return Requests.loadJson(`${FILE_ADMIN}?type=ListMessages&study_id=${studyId}&user=${userId}`)
	}
}