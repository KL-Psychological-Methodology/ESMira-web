import {SectionAlternative, SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {BindObservable} from "../widgets/BindObservable";
import {Section} from "../site/Section";
import {Requests} from "../singletons/Requests";
import {FILE_ADMIN} from "../constants/urls";
import {Message} from "../data/messages/Message";
import {TitleRow} from "../widgets/TitleRow";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {SearchWidget} from "../widgets/SearchWidget";
import {DropdownMenu} from "../widgets/DropdownMenu";
import {ParticipantMessagesContainer} from "../data/messages/ParticipantMessagesContainer";
import {SendMessage} from "../data/messages/SendMessage";
import {MessageAsRead} from "../data/messages/MessageAsRead";
import {safeConfirm} from "../constants/methods";
import participantsSvg from "../../imgs/icons/participants.svg?raw"
import dataSvg from "../../imgs/icons/data.svg?raw"
import {BtnCustom, BtnOk, BtnReload, BtnTrash} from "../widgets/BtnWidgets";

export class Content extends SectionContent {
	private readonly userIdList: string[]
	private readonly studyId: number
	private userId: string
	private readonly fixedRecipient: boolean
	private messages?: ParticipantMessagesContainer
	private sortedMessages: Message[] = []
	private toAll: boolean = false
	private appVersion: ObservablePrimitive<string> = new ObservablePrimitive<string>("", null, "appVersion")
	private appType: ObservablePrimitive<string> = new ObservablePrimitive<string>("", null, "appType")
	private messageContent: ObservablePrimitive<string> = new ObservablePrimitive<string>("", null, "messageContent")
	private isLoading: boolean = false
	
	public static preLoad(section: Section): Promise<any>[] {
		const studyId = section.getStaticInt("id") ?? -1
		return [
			Requests.loadJson(`${FILE_ADMIN}?type=ListParticipants&study_id=${studyId}`),
			section.getStudyPromise()
		]
	}
	
	constructor(section: Section, userIdList: string[]) {
		super(section)
		this.studyId = this.getStaticInt("id") ?? -1
		userIdList.sort()
		this.userIdList = userIdList
		this.userId = atob(this.getStaticString("userId") ?? "")
		this.fixedRecipient = !!this.userId
	}
	
	public preInit(): Promise<any> {
		return this.loadParticipantMessages()
	}
	
	public title(): string {
		return this.fixedRecipient ? this.userId : Lang.get("message")
	}
	
	public titleExtra(): Vnode<any, any> | null {
		if(this.userId && this.getTools().hasPermission("read", this.getStaticInt("id") ?? -1)) {
			const userIdAddition = this.getStaticString("userId") ? "" : `,userId:${btoa(this.userId)}`
			return <div>
				{BtnReload(
					() => this.section.loader.showLoader(this.loadParticipantMessages()),
					Lang.get("reload")
				)}
				{DropdownMenu("fileOptions",
					BtnCustom(m.trust(dataSvg), undefined, Lang.get("data")),
					(close) => <div>
						<a class="line" href={this.getUrl(`dataView:general,file:events,filter:userId${userIdAddition}`)} onclick={close}>Events.csv</a>
						{this.getStudyOrThrow().questionnaires.get().map((questionnaire) =>
							<a class="line" href={this.getUrl(`dataView:questionnaire,qId:${questionnaire.internalId.get()},filter:userId${userIdAddition}`)}
							   onclick={close}>{questionnaire.getTitle()}.csv</a>
						)}
					</div>
				)}
				<a href={this.getUrl(`statsParticipants${userIdAddition}`)}>
					{BtnCustom(m.trust(participantsSvg), undefined, Lang.get("participants"))}
				</a>
			</div>
		}
		else
			return null
	}
	
	public hasAlternatives(): boolean {
		return true
	}
	public getAlternatives(): Promise<SectionAlternative[]> {
		return this.section.getTools().messagesLoader.getReloadedMessageParticipantInfoList(this.section.getStaticInt("id") ?? -1)
			.then((participantList) => {
				const output: SectionAlternative[] = []
				participantList.get().forEach((entry) => {
					const currentUserId = entry.name.get()
					output.push({
						title: currentUserId,
						target: this.userId != currentUserId && this.getUrl(`chat,userId:${btoa(currentUserId)}`, this.section.depth - 1)
					})
				})
				return output
			})
	}
	
	private async loadParticipantMessages(): Promise<void> {
		if(this.userId) {
			this.isLoading = true
			m.redraw()
			this.messages = await this.getTools().messagesLoader.loadMessages(this.studyId, this.userId)
			
			this.sortedMessages = []
			
			this.sortedMessages = this.sortedMessages.concat(this.messages.archive)
			this.sortedMessages = this.sortedMessages.concat(this.messages.unread)
			this.sortedMessages = this.sortedMessages.concat(this.messages.pending)
			this.sortedMessages.reverse() //our div is reversed. So we have to reverse data too
			this.isLoading = false
			m.redraw()
		}
	}
	
	private getUnreadTimestamps(): number[] {
		const timestamps: number[] = []
		const listUnread = this.messages?.unread
		if(!listUnread || !listUnread.length)
			return []
		
		for(let message of listUnread) {
			timestamps.push(message.sent)
		}

		return timestamps
	}
	
	private async removeMessage(message: Message): Promise<void> {
		if(!safeConfirm(Lang.get("confirm_delete_message")))
			return
		
		const studyId = this.getStaticInt("id") ?? -1
		
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=DeleteMessage&study_id=${studyId}`,
			"post",
			`"study_id=${studyId}&userId=${this.userId}&sent=${message.sent}`
		)
		
		await this.loadParticipantMessages()
		await this.getTools().messagesLoader.getReloadedMessageParticipantInfoList(studyId)
		m.redraw()
	}
	
	private async sendMessage(): Promise<void> {
		const content = this.messageContent.get()
		const recipient = this.userId
		const toAll = this.toAll
		const appVersion = this.appVersion.get()
		const appType = this.appType.get()
		const studyId = this.getStaticInt("id") ?? -1
		
		if(content.length < 2) {
			this.section.loader.info(Lang.get("error_short_message"))
			return
		}
		else if(!toAll && (!recipient || !recipient.length)) {
			this.section.loader.info(Lang.get("error_not_selected_recipient"))
			return
		}
		if(!confirm(Lang.get("confirm_distribute_message", content)))
			return
		
		
		const sendMessage: SendMessage = {
			userId: this.userId,
			toAll: toAll,
			appVersion: appVersion,
			appType: appType,
			content: content,
			timestamps: this.getUnreadTimestamps()
		}
		
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=SendMessage&study_id=${studyId}`,
			"post",
			JSON.stringify(sendMessage)
		)
		
		await this.loadParticipantMessages()
		await this.getTools().messagesLoader.getReloadedMessageParticipantInfoList(studyId)
		this.messageContent.set("")
		m.redraw()
	}
	
	private async setMessagesAsRead(): Promise<void> {
		const studyId = this.getStaticInt("id") ?? -1
		const data: MessageAsRead = {
			userId: this.userId,
			timestamps: this.getUnreadTimestamps()
		}
		
		await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=MessageSetRead&study_id=${studyId}`,
			"post",
			JSON.stringify(data)
		)
		
		await this.loadParticipantMessages()
		await this.getTools().messagesLoader.getReloadedMessageParticipantInfoList(studyId)
		m.redraw()
	}
	
	public getView(): Vnode<any, any> {
		return <div>
			<div>
				{this.messages &&
					<div class="scrollBox reversedScroll big">
						{ this.messages.unread.length >= 1 &&
							BtnOk(this.setMessagesAsRead.bind(this), Lang.get("mark_messages_as_read"))
						}
						{ this.sortedMessages.map((message) =>
							this.getBubble(message)
						)}
					</div>
				}
			</div>
			
			
			{ TitleRow(Lang.getWithColon("send_message_to_user")) }
			{ !this.fixedRecipient &&
				<div class="recipientBox">
					<div class="vertical">
						<label class="horizontal">
							<input name="recipient" type="radio" checked={this.toAll} onchange={() => {
								this.userId = ""
								this.toAll = true
							}}/>
							<span>{Lang.get("to_all")}</span>
						</label>
						
						{ this.toAll &&
							<div class="horizontal spacingLeft">
								<label>
									<small>{Lang.get("app_version")}</small>
									<input type="text" {... BindObservable(this.appVersion)}/>
								</label>
								<label>
									<small>{Lang.get("app_type")}</small>
									<select {... BindObservable(this.appType)}>
										<option value="">{ Lang.get("all")}</option>
										<option>Android</option>
										<option>iOS</option>
									</select>
								</label>
							</div>
						}
					</div>
					
					
					<div class="vertical">
						<label class="horizontal">
							<input name="recipient" type="radio" checked={!this.toAll} onchange={() => {
								this.toAll = false
							}}/>
						</label>
						<div class="recipientChooser">
							<label>
								<small>{Lang.getWithColon("recipient")}</small>
								{
									SearchWidget((tools) => {
										return DropdownMenu("recipient",
											<input
												class="vertical"
												type="text"
												value={this.userId}
												onkeyup={(e: InputEvent) => {
													const value = (e.target as HTMLInputElement).value
													this.userId = value
													tools.updateSearch(value)
												}}
												onfocusin={() => { this.toAll = false }}
												onchange={this.loadParticipantMessages.bind(this)}
											/>,
											(close) => <div class="listParent"><div class="listChild">
												{this.userIdList.map((userId) =>
													tools.searchView(userId,
														<div class="clickable smallText line"
															 onclick={() => {
																 this.userId = userId
																 close()
																 this.loadParticipantMessages()
															 }}>{userId}</div>
													)
												)}
											
											</div></div>
										)
									}
								)}
							</label>
						</div>
					</div>
				</div>
			}
			
			<label class="line">
				<small>{Lang.getWithColon("message")}</small>
				<textarea {... BindObservable(this.messageContent)}></textarea>
			</label>
			
			<input class="right" type="button" value={Lang.get("send")} onclick={this.sendMessage.bind(this)}/>
		</div>
	}
	
	private getBubble(message: Message): Vnode<any, any> {
		let className = "chatBubble"
		if(!this.isLoading)
			className += " fadeIn"
		if(message.unread)
			className += " unread"
		if(message.pending)
			className += " pending"
		if(message.from == this.userId)
			className += " fromClient"
		else
			className += " fromServer"
		
		return <div class={className}>
			<div class="headline">
				<span>{Lang.getWithColon("from")}</span>
				&nbsp;
				<span>{message.from}</span>
			</div>
			
			{ message.pending &&
				<div class="horizontal">
					{BtnTrash(this.removeMessage.bind(this, message))}
				</div>
			}
			<div class="msg">
				<div class="header">{new Date(message.sent).toLocaleString()}</div>
				<div class="content">{message.content}</div>
				<div class="footer">
					{message.pending &&
						<span>{Lang.get("delivered_x_times", message.delivered)}</span>
					}
					{(!message.pending && message.read != 0) &&
						<div>
							<span>{Lang.getWithColon("confirmed")}</span>
							&nbsp;
							<span>{new Date(message.read).toLocaleString()}</span>
						</div>
					}
				</div>
			</div>
		</div>
	}
}