import {SectionContent} from "../site/SectionContent";
import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Questionnaire} from "../data/study/Questionnaire";
import calendarSvg from "../../imgs/icons/calendar.svg?raw"
import schedulesSvg from "../../imgs/icons/schedules.svg?raw"
import eventsSvg from "../../imgs/icons/events.svg?raw"
import {ActionTrigger} from "../data/study/ActionTrigger";
import {EventTrigger} from "../data/study/EventTrigger";
import {Schedule} from "../data/study/Schedule";
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {Section} from "../site/Section";
import {DropdownMenu} from "../widgets/DropdownMenu";
import {BindObservable, DateTransformer, TimeTransformer} from "../widgets/BindObservable";
import {NotCompatibleIcon} from "../widgets/NotCompatibleIcon";
import {BtnCollection} from "../widgets/BtnCollection";
import {TabBar} from "../widgets/TabBar";
import {BtnAdd, BtnCopy, BtnCustom, BtnOk, BtnTrash} from "../widgets/BtnWidgets";
import {getMidnightMillis, timeStampToTimeString} from "../constants/methods";



interface FilterEntry {
	title: string,
	dropdownView?: Vnode<any, any>,
	isActive: () => boolean
	disable: () => void
	enable?: () => void
}

/**
 * Note: Triggers are implemented so that each could hold MULTIPLE schedules, cues and actions.
 * But since we suspect that this will not be used often, and it is easier to grasp for configuration, we removed that functionality in the admin panel
 */
export class Content extends SectionContent {
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}
	
	public title(): string {
		return Lang.get("filter_and_trigger")
	}
	
	private removeActionTrigger(questionnaire: Questionnaire, index: number): void {
		questionnaire.actionTriggers.remove(index)
	}
	private copyActionTrigger(questionnaire: Questionnaire, actionTrigger: ActionTrigger, index: number): void {
		questionnaire.actionTriggers.addCopy(actionTrigger, index)
	}
	
	private addSchedule(questionnaire: Questionnaire): void {
		questionnaire.actionTriggers.push({
			schedules: [{
				signalTimes: [{startTimeOfDay: 0}]
			}],
			actions: [{}]
		})
		this.newSection(`triggerEdit,qId:${questionnaire.internalId.get()},triggerI:${questionnaire.actionTriggers.get().length - 1}`)
	}
	
	private addEvent(questionnaire: Questionnaire): void {
		questionnaire.actionTriggers.push({
			eventTriggers: [{}],
			actions: [{}]
		})
		this.newSection(`triggerEdit,qId:${questionnaire.internalId.get()},triggerI:${questionnaire.actionTriggers.get().length - 1}`)
	}
	
	private getSchedule(actionTrigger: ActionTrigger): Schedule {
		return actionTrigger.schedules.get()[0]
	}
	private getEventTrigger(actionTrigger: ActionTrigger): EventTrigger {
		return actionTrigger.eventTriggers.get()[0]
	}
	
	
	private getScheduleTitle(schedule: Schedule): string {
		const midnight = getMidnightMillis(Date.now())
		const signaleTimes = schedule.signalTimes.get()
		
		if(signaleTimes.length == 0)
			return Lang.get("empty")
		else if(signaleTimes.length == 1 && !signaleTimes[0].random.get())
			return timeStampToTimeString(midnight + signaleTimes[0].startTimeOfDay.get())
		
		let lowest = Number.MAX_VALUE
		let highest = Number.MIN_VALUE
		let count = 0
		
		for(let signalTime of schedule.signalTimes.get()) {
			if(signalTime.startTimeOfDay.get() < lowest)
				lowest = signalTime.startTimeOfDay.get()
			
			if(signalTime.random.get()) {
				count += signalTime.frequency.get()
				if(signalTime.endTimeOfDay.get() > highest)
					highest = signalTime.endTimeOfDay.get()
			}
			else {
				++count
				if(signalTime.startTimeOfDay.get() > highest)
					highest = signalTime.startTimeOfDay.get()
			}
		}
		return Lang.get("x_times_between_y_and_z", count, timeStampToTimeString(midnight + lowest), timeStampToTimeString(midnight + highest))
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		if(!study.questionnaires.get().length)
			return <div class="center spacingTop">{Lang.get("info_no_questionnaires_created")}</div>
		
		return TabBar(this.getDynamic("questionnaireIndex", 0), study.questionnaires.get().map((questionnaire) => {
			return {
				title: questionnaire.getTitle(),
				view: () => this.getQuestionnaireView(questionnaire)
			}
		}))
	}
	
	
	
	private getQuestionnaireView(questionnaire: Questionnaire): Vnode<any, any> {
		const filterEntries = this.createFilterEntries(questionnaire)
		
		return <div class="spacingTop spacingBottom">
			{DashRow(
				DashElement(null, {
					content: this.getFilterView(filterEntries)
				}),
				DashElement(null, {
					content: this.getActionTriggerView(questionnaire)
				}),
				
				DropdownMenu("filterMenu",
					DashElement(null, {
						content:
							BtnCustom(m.trust(calendarSvg), undefined, Lang.get("add_filter")),
						small: true,
						showAsClickable: true
					}),
					() => this.getFilterDropdownView(filterEntries),
					{connectedDropdowns: ["filterSubMenu"]}
				),
				DashElement("horizontal",
					{content: BtnCustom(m.trust(schedulesSvg), undefined, Lang.get("add_schedule")), onclick: this.addSchedule.bind(this, questionnaire)},
					{content: BtnCustom(m.trust(eventsSvg), undefined, Lang.get("add_event")),  onclick: this.addEvent.bind(this, questionnaire)},
				),
			)}
		</div>
	}
	
	private getActionTriggerView(questionnaire: Questionnaire): Vnode<any, any> {
		return <div>
			<h2 class="center">
				{Lang.getWithColon("trigger")}
				{NotCompatibleIcon("Web")}
			</h2>
			{questionnaire.actionTriggers.get().map((actionTrigger, index) => {
				const schedule = this.getSchedule(actionTrigger)
				const eventTrigger = this.getEventTrigger(actionTrigger)
				return <div class="verticalPadding">
					{BtnCollection([
						BtnTrash(this.removeActionTrigger.bind(this, questionnaire, index)),
						BtnCopy(this.copyActionTrigger.bind(this, questionnaire, actionTrigger, index)),
						<a href={this.getUrl(`triggerEdit,qId:${questionnaire.internalId.get()},triggerI:${index}`)}>
							{schedule
								? BtnCustom(m.trust(schedulesSvg), undefined,
									this.getScheduleTitle(schedule)
								)
								: BtnCustom(m.trust(eventsSvg), undefined,
									`${eventTrigger.cueCode.get()}. ${Lang.get('after_x_seconds', eventTrigger.delaySec.get())}`
								)
							}
						</a>
					])}
				</div>
			})
			}</div>
	}
	
	
	private createFilterEntries(questionnaire: Questionnaire): FilterEntry[] {
		const study = this.getStudyOrThrow()
		const list: FilterEntry[] = [
			{
				title: questionnaire.durationStart.get() != 0
					? `${Lang.getWithColon("start_date")} ${new Date(questionnaire.durationStart.get()).toLocaleDateString()}`
					: Lang.get("start_date"),
				isActive: () => questionnaire.durationStart.get() != 0,
				dropdownView:
					<label class="noDesc">
						<small>{Lang.get("start_date")}</small>
						<input type="date" {... BindObservable(questionnaire.durationStart, DateTransformer)}/>
					</label>,
				disable: () => questionnaire.durationStart.set(0)
			},
			{
				title: questionnaire.durationEnd.get() != 0
					? `${Lang.getWithColon("end_date")} ${new Date(questionnaire.durationEnd.get()).toLocaleDateString()}`
					: Lang.get("end_date"),
				isActive: () => questionnaire.durationEnd.get() != 0,
				dropdownView:
					<label class="noDesc">
						<small>{Lang.get("end_date")}</small>
						<input type="date" {... BindObservable(questionnaire.durationEnd, DateTransformer)}/>
					</label>,
				disable: () => questionnaire.durationEnd.set(0)
			},
			{
				title: questionnaire.durationStartingAfterDays.get() != 0
					? `${Lang.getWithColon("activation")} ${Lang.get("after_x_days", questionnaire.durationStartingAfterDays.get())}`
					: Lang.get("activation_after"),
				isActive: () => questionnaire.durationStartingAfterDays.get() != 0,
				dropdownView:
					<label>
						<small>{Lang.get("activation_after")}</small>
						<input type="number" {... BindObservable(questionnaire.durationStartingAfterDays)}/>
						<span>{Lang.get("days")}</span>
						<small>{Lang.get("after_joining_study")}</small>
					</label>,
				disable: () => questionnaire.durationStartingAfterDays.set(0)
			},
			{
				title: questionnaire.durationPeriodDays.get() != 0
					? `${Lang.getWithColon("expiration")} ${Lang.get("after_x_days", questionnaire.durationPeriodDays.get())}`
					: Lang.get("expiration_after"),
				isActive: () => questionnaire.durationPeriodDays.get() != 0,
				dropdownView:
					<label>
						<small>{Lang.get("expiration_after")}</small>
						<input type="number" {... BindObservable(questionnaire.durationPeriodDays)}/>
						<span>{Lang.get("days")}</span>
						<small>{Lang.get("after_joining_study")}</small>
					</label>,
				disable: () => questionnaire.durationPeriodDays.set(0)
			},
			{
				title: Lang.get("questionnaires_can_only_be_completed_once"),
				isActive: () => questionnaire.completableOnce.get(),
				enable: () => questionnaire.completableOnce.set(true),
				disable: () => questionnaire.completableOnce.set(false)
			},
			{
				title: questionnaire.completableMinutesAfterNotification.get() != 0
					? `${Lang.get("questionnaires_can_only_be_completed_per_notification")} (${questionnaire.completableMinutesAfterNotification.get()} ${Lang.get("minutes")})`
					: Lang.get("questionnaires_can_only_be_completed_per_notification"),
				isActive: () => questionnaire.completableOncePerNotification.get(),
				dropdownView:
					<label>
						<span>{Lang.get("part_visibleFor")}</span>
						<input type="number" {... BindObservable(questionnaire.completableMinutesAfterNotification)}/>
						<span>{Lang.get("minutes")}</span>
						<small>{Lang.get("info_zero_disables_timeout")}</small>
					</label>,
				enable: () => questionnaire.completableOncePerNotification.set(true),
				disable: () => questionnaire.completableOncePerNotification.set(false)
			},
			{
				title: questionnaire.limitCompletionFrequency.get()
					? `${Lang.getWithColon("timeDistance_between_completion_of_questionnaire")} ${questionnaire.completionFrequencyMinutes.get()} ${Lang.get("minutes")}`
					: Lang.get("timeDistance_between_completion_of_questionnaire"),
				isActive: () => questionnaire.limitCompletionFrequency.get(),
				dropdownView:
					<label>
						<input type="number" {... BindObservable(questionnaire.completionFrequencyMinutes)}/>
						<span>{Lang.get("minutes")}</span>
					</label>,
				enable: () => questionnaire.limitCompletionFrequency.set(true),
				disable: () => questionnaire.limitCompletionFrequency.set(false)
			},
			{
				title: questionnaire.completableAtSpecificTime.get()
					? `${Lang.getWithColon("only_at_specific_time")} ${TimeTransformer.toAttribute(questionnaire.completableAtSpecificTimeStart.get())} - ${TimeTransformer.toAttribute(questionnaire.completableAtSpecificTimeEnd.get())}`
					: Lang.get("only_at_specific_time"),
				isActive: () => questionnaire.completableAtSpecificTime.get(),
				dropdownView:
					<div class="center">
						<label>
							<small>{Lang.getWithColon("from")}</small>
							<input type="time" {... BindObservable(questionnaire.completableAtSpecificTimeStart, TimeTransformer)}/>
						</label>
						<label>
							<small>{Lang.getWithColon("until")}</small>
							<input type="time" {... BindObservable(questionnaire.completableAtSpecificTimeEnd, TimeTransformer)}/>
						</label>
					</div>,
				enable: () => questionnaire.completableAtSpecificTime.set(true),
				disable: () => questionnaire.completableAtSpecificTime.set(false)
			}
		]
		if(study.randomGroups.get() != 0) {
			list.push({
				title: questionnaire.limitToGroup.get() != 0
					? `${Lang.getWithColon("group_availability")} ${questionnaire.limitToGroup.get()}`
					: Lang.get("group_availability"),
				isActive: () => questionnaire.limitToGroup.get() != 0,
				dropdownView:
					<select {...BindObservable(questionnaire.limitToGroup)}>
						<option value="0">{Lang.get('in_all_groups')}</option>
						{Array.from({length: study.randomGroups.get()}).map((_, index) =>
							<option>{index+1}</option>
						)}
					</select>,
				disable: () => questionnaire.limitToGroup.set(0)
			})
		}
		return list
	}
	
	private getFilterView(filterEntries: FilterEntry[]): Vnode<any, any> {
		return <div>
			<h2 class="center">{Lang.getWithColon("filter")}</h2>
			{filterEntries.map((entry) =>
				entry.isActive() &&
				<div class="line">
					{BtnTrash(entry.disable.bind(this))}
					{this.getFilterEntryView(entry, (dropdown) => <span class={`${dropdown ? "clickable" : ""} smallText`}>{entry.title}</span>)}
				</div>
			)}
			
		</div>
	}
	
	private getFilterEntryView(entry: FilterEntry, clickElement: (dropdown: boolean) => Vnode<any, any>): Vnode<any, any> {
		return entry.dropdownView ?
			DropdownMenu("filterSubMenu",
				clickElement(true),
				(close) =>
					<div>
						<div class="middle horizontal">{entry.dropdownView}</div>
						<div class="middle horizontal spacingLeft">{BtnOk(() => {
							if(entry.enable)
								entry.enable()
							window.setTimeout(() => close(), 10)
						})}</div>
					</div>,
				{dontCenter: true}
			)
			: clickElement(false)
	}
	
	private getFilterDropdownView(filterEntries: FilterEntry[]): Vnode<any, any> {
		return <div>
			{filterEntries.map((entry) =>
				<div>{
					!entry.isActive() &&
						this.getFilterEntryView(entry, (dropdown) => dropdown || !entry.enable
							? BtnAdd(undefined, entry.title)
							: BtnAdd(entry.enable, entry.title)
						)
				}</div>
			)}
		</div>
	}
}