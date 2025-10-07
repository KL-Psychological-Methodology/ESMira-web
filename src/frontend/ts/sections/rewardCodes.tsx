import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {Lang} from "../singletons/Lang";
import {BindObservable} from "../widgets/BindObservable";
import {TitleRow} from "../widgets/TitleRow";
import {SearchBox} from "../widgets/SearchBox";
import {Requests} from "../singletons/Requests";
import {FILE_ADMIN} from "../constants/urls";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {Section} from "../site/Section";

interface EntriesPerQuestionnaire {
	title: string
	count: number
}

interface CodeResponse {
	faultyCode: boolean
	timestamp: number
	questionnaireDataSetCount: Record<number, number>
	namedQuestionnaireDataSetCount: EntriesPerQuestionnaire[]
}

interface RewardCodeData {
	rewardCodes: string[]
	userIdsWithRewardCode: string[]
	userIdsWithoutRewardCode: string[]
}

export class Content extends SectionContent {
	private readonly rewardCodeData: RewardCodeData
	private codeResponse: CodeResponse | null = null
	private readonly currentCode = new ObservablePrimitive<string>("", null, "currentRewardCode")
	
	public static preLoad(section: Section): Promise<any>[] {
		return [
			Requests.loadJson(
				`${FILE_ADMIN}?type=GetRewardCodeData`, "post", `study_id=${section.getStaticInt("id") ?? 0}`
			),
			section.getStrippedStudyListPromise()
		]
	}
	
	constructor(section: Section, rewardCodeData: RewardCodeData) {
		super(section)
		
		rewardCodeData.rewardCodes.sort()
		rewardCodeData.userIdsWithRewardCode.sort()
		rewardCodeData.userIdsWithoutRewardCode.sort()
		this.rewardCodeData = rewardCodeData
		
		this.currentCode.addObserver(async () => {
			await this.checkCode()
		})
	}
	
	
	public title(): string {
		return Lang.get("reward_codes")
	}
	
	private async checkCode(): Promise<void> {
		this.codeResponse = null
		
		if(!this.currentCode.get().length)
			return
		const studyId = this.getStaticInt("id") ?? 0
		
		const codeResponse: CodeResponse = await this.section.loader.loadJson(
			`${FILE_ADMIN}?type=ValidateRewardCode`,
			"post",
			`study_id=${studyId}&code=${this.currentCode.get()}`
		)
		this.codeResponse = codeResponse
		
		if(codeResponse.faultyCode)
			return
		
		
		//create index for internalId:
		let questionnaireIndex: Record<number, string> = {}
		let study = this.getStudyOrThrow()
		study.questionnaires.get().forEach((questionnaire) => {
			questionnaireIndex[questionnaire.internalId.get()] = questionnaire.getTitle();
		})
		
		//combine data:
		let namedQuestionnaireEntryCount: EntriesPerQuestionnaire[] = [];
		for(let internalId in codeResponse.questionnaireDataSetCount) {
			if(!codeResponse.questionnaireDataSetCount.hasOwnProperty(internalId))
				continue;
			namedQuestionnaireEntryCount.push({title: questionnaireIndex[internalId], count: codeResponse.questionnaireDataSetCount[internalId]});
		}
		codeResponse.namedQuestionnaireDataSetCount = namedQuestionnaireEntryCount
	}
	
	private selectRewardCode(code: string): void {
		this.currentCode.set(code)
	}
	
	public getView(): Vnode<any, any> {
		return <div class="rewardCodes">
			{TitleRow(Lang.getWithColon("reward_codes"))}
			{DashRow(
				DashElement(null, {
					content:
						SearchBox(Lang.get("generated_reward_codes", this.rewardCodeData.rewardCodes.length), this.rewardCodeData.rewardCodes.map((code) => {
							return {key: code, view: <span class={`line clickable verticalPadding smallText ${this.currentCode.get() == code ? "highlight" : ""}`} onclick={this.selectRewardCode.bind(this, code)}>{code}</span>}
						}))
				}),
				
				DashElement("vertical", {
					content:
						<div class="horizontalPadding verticalPadding center">
							<label>
								<small>{Lang.get("reward_code")}</small>
								<input type="text" class={`small ${this.codeResponse != null ? (!this.codeResponse?.faultyCode ? "success" : "failed") : "nothing"}`} {... BindObservable(this.currentCode)}/>
								&nbsp;
								<input class="small" type="button" onclick={this.checkCode.bind(this)} value={Lang.get("check")}/>
							</label>
						</div>
				}, this.codeResponse != null && !this.codeResponse.faultyCode && {
					content:
						<div class="fadeIn">
							<h2 class="spacingLeft">{Lang.getWithColon("creation_date")}</h2>
							<div class="spacingLeft horizontalPadding spacingBottom">{(new Date(this.codeResponse.timestamp)).toLocaleString()}</div>
						</div>
				}, this.codeResponse != null && !this.codeResponse.faultyCode && {
					content:
						<div class="fadeIn">
							<h2 class="spacingLeft">{Lang.getWithColon('entries_at_creation_time')}</h2>
							<table class="spacingLeft spacingBottom">
								{this.codeResponse.namedQuestionnaireDataSetCount.map((entry) =>
									<tr>
										<td class="horizontalPadding">{Lang.get("colon", entry.title ?? Lang.get("unknown"))}</td>
										<td class="horizontalPadding">{entry.count}</td>
									</tr>
								)}
								
							</table>
						</div>
				})
			)}
			
			{TitleRow(Lang.getWithColon("participants"))}
			{DashRow(
				DashElement(null, {
					content:
						SearchBox(Lang.get("without_reward_code", this.rewardCodeData.userIdsWithoutRewardCode.length), this.rewardCodeData.userIdsWithoutRewardCode.map((userId) => {
							return {key: userId, view: <span class="line verticalPadding smallText">{userId}</span>}
						}))
				}),
				
				DashElement(null, {
					content:
						SearchBox(Lang.get("with_reward_code", this.rewardCodeData.userIdsWithRewardCode.length), this.rewardCodeData.userIdsWithRewardCode.map((userId) => {
							return {key: userId, view: <span class="line verticalPadding smallText">{userId}</span>}
						}))
				})
			)}
		</div>
	}
	
	public destroy(): void {
		this.currentCode.removeAllConnectedObservers()
		super.destroy()
	}
}