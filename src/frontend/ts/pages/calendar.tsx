import {SectionContent} from "../site/SectionContent";
import m, {Component, Vnode, VnodeDOM} from "mithril";
import {Lang} from "../singletons/Lang";
import {Section} from "../site/Section";
import {Study} from "../data/study/Study";
import {Calendar, EventInput} from "@fullcalendar/core";
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import deLocale from '@fullcalendar/core/locales/de';
import ukLocale from '@fullcalendar/core/locales/uk';
import calendarSvg from "../../imgs/icons/calendar.svg?raw"
import {DashRow} from "../widgets/DashRow";
import {DashElement} from "../widgets/DashElement";
import {BindObservable, DateTransformer, TimeTransformer} from "../widgets/BindObservable";
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {BaseObservable, ObserverId} from "../observable/BaseObservable";
import {Questionnaire} from "../data/study/Questionnaire";
import {getChartColor} from "../helpers/ChartJsBox";
import {Scheduler} from "../helpers/Scheduler";
import {LoadingSpinner} from "../widgets/LoadingSpinner";
import {getMidnightMillis} from "../constants/methods";
import {ActionTrigger} from "../data/study/ActionTrigger";
import {BtnReload} from "../widgets/BtnWidgets";

interface FullcalendarComponentOptions {
	study: Study
}

const ONE_DAY = 1000 * 60 * 60 * 24
const INFINITE_QUESTIONNAIRE_DURATION = ONE_DAY * 150
class FullcalendarComponent implements Component<FullcalendarComponentOptions, any> {
	private calendarView?: HTMLElement
	private scheduler = new Scheduler()
	private calendar?: Calendar
	private joinDate: BaseObservable<number> = new ObservablePrimitive(getMidnightMillis(Date.now()), null, "joinDate")
	private joinTime: BaseObservable<number> = new ObservablePrimitive(Date.now() - getMidnightMillis(Date.now()), null, "joinTime")
	private studyObserverId?: ObserverId
	private visibleCalendars: Record<number, Questionnaire> = {}
	private colors: Record<number, string> = {}
	private isLoading: boolean = false
	
	private getJoinTimestamp(): number {
		return this.joinDate.get() + this.joinTime.get()
	}
	
	private calcQuestionnaireStart(questionnaire: Questionnaire): number {
		const joinDate = this.getJoinTimestamp()
		if(questionnaire.durationStart.get() != 0)
			return questionnaire.durationStart.get()
		else if(questionnaire.durationStartingAfterDays.get() != 0)
			return joinDate + ONE_DAY * questionnaire.durationStartingAfterDays.get()
		else
			return joinDate
	}
	private calcQuestionnaireEnd(questionnaire: Questionnaire): number {
		const joinTimestamp = this.getJoinTimestamp()
		if(questionnaire.durationEnd.get() != 0)
			return questionnaire.durationEnd.get()
		else if(questionnaire.durationPeriodDays.get() != 0)
			return joinTimestamp + ONE_DAY * (questionnaire.durationPeriodDays.get() + 1) //+1: fullcalendar does not include the last day if the time ends before midnight
		else
			return joinTimestamp + INFINITE_QUESTIONNAIRE_DURATION
	}
	
	private getQuestionnaireEvent(questionnaire: Questionnaire): EventInput {
		return {
			title: questionnaire.getTitle(),
			start: this.calcQuestionnaireStart(questionnaire),
			end: this.calcQuestionnaireEnd(questionnaire),
			color: this.colors[questionnaire.internalId.get()],
			allDay: true
		}
	}
	
	private getActionName(actionTrigger: ActionTrigger): string {
		switch(actionTrigger.actions.get()[0].type.get()) {
			case 1:
				return "[invitation]"
			case 2:
				return "[message]"
			case 3:
				return "[notification]"
			default:
				return "[unknown]"
		}
	}
	
	private addEventsFromScheduler(events: EventInput[]) {
		for(let alarm of this.scheduler.alarms) {
			events.push({
				title: this.getActionName(alarm.actionTrigger),
				icon: m.trust(calendarSvg),
				start: alarm.timestamp,
				color: this.colors[alarm.questionnaire.internalId.get()],
			})
		}
	}
	
	private collectScheduleEvents(questionnaire: Questionnaire) {
		const joined = this.getJoinTimestamp()
		let start = this.getJoinTimestamp()
		for(const actionTrigger of questionnaire.actionTriggers.get()) {
			if(actionTrigger.schedules.get().length == 0)
				continue
			const schedule = actionTrigger.schedules.get()[0]
			const initialDelay = schedule.getInitialDelayDays()
			for(let signalTime of schedule.signalTimes.get()) {
				this.scheduler.scheduleSignalTime(joined, questionnaire, schedule, signalTime, actionTrigger, start, initialDelay)
			}
		}
		
		this.scheduler.scheduleAheadJavascript(joined)
	}
	
	private initCalendar() {
		this.isLoading = true
		m.redraw()
		if(!this.calendarView)
			return
		this.scheduler = new Scheduler()
		const events: EventInput[] = []
		
		for(const qId in this.visibleCalendars) {
			const questionnaire = this.visibleCalendars[qId]
			events.push(this.getQuestionnaireEvent(questionnaire))
			this.collectScheduleEvents(questionnaire)
		}
		this.addEventsFromScheduler(events)
		
		this.calendar = new Calendar(this.calendarView, {
			plugins: [ dayGridPlugin, timeGridPlugin, listPlugin ],
			locales: [deLocale, ukLocale],
			locale: Lang.code,
			initialView: this.calendar?.view.type ?? "listWeek",
			headerToolbar: {
				left: "title",
				right: "prev,next,dayGridMonth,timeGridWeek,timeGridDay,listWeek"
			},
			contentHeight: "auto",
			initialDate: Date.now(),
			allDayText: "",
			editable: false,
			dayMaxEvents: false,
			events: events,
			eventTimeFormat: {
				hour: "2-digit",
				minute: "2-digit",
				hour12: false
			}
		})
		
		
		this.calendar.render()
		
		this.isLoading = false
		m.redraw()
	}
	
	private toggleQuestionnaire(questionnaire: Questionnaire, e: EventInput) {
		const target = e.target as HTMLInputElement
		
		if(target.checked) {
			this.visibleCalendars[questionnaire.internalId.get()] = questionnaire
		}
		else
			delete this.visibleCalendars[questionnaire.internalId.get()]
		
		this.initCalendar()
	}
	
	public oncreate(vNode: VnodeDOM<FullcalendarComponentOptions, any>): void {
		const study = vNode.attrs.study
		
		study.questionnaires.get().forEach((questionnaire, index) => {
			this.visibleCalendars[questionnaire.internalId.get()] = questionnaire
			this.colors[questionnaire.internalId.get()] = getChartColor(index)
		})
		
		this.joinDate.addObserver(() => {
			this.initCalendar()
		})
		this.joinTime.addObserver(() => {
			this.initCalendar()
		})
		this.studyObserverId = study.addObserver(() => {
			this.initCalendar()
		})
		this.calendarView = vNode.dom.getElementsByClassName("calendarView")[0] as HTMLElement
		this.initCalendar()
	}
	public onupdate(vNode: VnodeDOM<FullcalendarComponentOptions, any>): void {
		this.calendarView = vNode.dom.getElementsByClassName("calendarView")[0] as HTMLElement
		this.calendar?.render() //rerender calendar in case available space has changed
	}
	
	public view(vNode: Vnode<FullcalendarComponentOptions, any>): Vnode<any, any> {
		const study = vNode.attrs.study
		
		return DashRow(
			DashElement(null, {
				content: <div>
					<label class="noDesc">
						<small>{Lang.get("join_date")}</small>
						<input type="date" {... BindObservable(this.joinDate, DateTransformer)}/>
					</label>
					<label class="noDesc">
						<small>{Lang.get("join_time")}</small>
						<input type="time" {... BindObservable(this.joinTime, TimeTransformer)}/>
					</label>
					<br/>
					<br/>
					<div class="smallText spacingLeft spacingRight">{Lang.get("calendar_description")}</div>
				</div>
			}),
			DashElement(null, {
				content: <div>
					{study.questionnaires.get().map((questionnaire) =>
						<label class="noDesc line">
							<input checked={this.visibleCalendars.hasOwnProperty(questionnaire.internalId.get())} type="checkbox" onchange={this.toggleQuestionnaire.bind(this, questionnaire)}/>
							<span style={`color: ${this.colors[questionnaire.internalId.get()]}`}>{questionnaire.getTitle()}</span>
						</label>
					)}
				</div>
			}),
			DashElement("stretched",
				{
					content:
						<div>
							<div class="center">{LoadingSpinner(!this.isLoading)}</div>
							<div class="calendarView"></div>
						</div>
				}
			)
		)
	}
	
	public onremove(): void {
		this.studyObserverId?.removeObserver()
	}
}

export class Content extends SectionContent {
	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}
	public title(): string {
		return Lang.get("study_description")
	}
	public titleExtra(): Vnode<any, any> | null {
		return BtnReload(() => this.getStudyOrThrow().hasMutated())
	}
	
	public getView(): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		
		if(!study.questionnaires.get().length)
			return <div class="center spacingTop">{Lang.get("info_no_questionnaires_created")}</div>
		else
			return m(FullcalendarComponent, {study: study})
	}
}