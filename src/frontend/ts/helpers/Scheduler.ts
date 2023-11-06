import { SignalTime } from "../data/study/SignalTime"
import {ActionTrigger} from "../data/study/ActionTrigger";
import {Schedule} from "../data/study/Schedule";
import {Questionnaire} from "../data/study/Questionnaire";
import "../number.extensions"
import {getMidnightMillis} from "../constants/methods";

const ONE_DAY_MS = 1000 * 60 * 60* 24
const MIN_SCHEDULE_DISTANCE = 60000
const WEEKDAY_CODES = [ //Date.getDay() starts with Sunday
	1,  //Sunday
	2,  //Monday
	4,  //Tuesday
	8,  //Wednesday
	16,  //Thursday
	32,  //Friday
	64, //Saturday
]

class Alarm {
	public readonly questionnaire: Questionnaire
	public readonly schedule: Schedule
	public readonly signalTime: SignalTime
	public readonly actionTrigger: ActionTrigger
	public readonly indexNum: number
	public readonly timestamp: number
	
	constructor(questionnaire: Questionnaire, schedule: Schedule, signalTime: SignalTime, actionTrigger: ActionTrigger, timestamp: number, indexNum: number) {
		this.questionnaire = questionnaire
		this.schedule = schedule
		this.signalTime = signalTime
		this.actionTrigger = actionTrigger
		this.timestamp = timestamp
		this.indexNum = indexNum
	}
	
	
	public canBeRescheduled(): boolean {
		const signalTime = this.signalTime
		return signalTime != null && (!signalTime.random.get() || this.indexNum == signalTime.frequency.get())
	}
}

/**
 * This class is a copy of the Kotlin code in sharedCode.Scheduler
 * (only the needed methods are included)
 */
export class Scheduler {
	public readonly alarms: Alarm[] = []
	private readonly lastAlarmsPerSignalTime: Record<number, Alarm> = {}
	
	
	public scheduleAheadJavascript(joined: number) {
		for(let i=50; i >= 0; --i) { //limit the amount of loops to not crash the browser
			for(const id in this.lastAlarmsPerSignalTime) {
				this.rescheduleFromAlarm(joined, this.lastAlarmsPerSignalTime[id])
			}
		}
	}
	
	private createAlarm(joined: number, questionnaire: Questionnaire, schedule: Schedule, signalTime: SignalTime, actionTrigger: ActionTrigger, timestamp: number, indexNum: number = 1) {
		const alarm = new Alarm(questionnaire, schedule, signalTime, actionTrigger, timestamp, indexNum)
		if(!questionnaire.isActive(joined, timestamp)) {
			if(questionnaire.willBeActiveIn(joined, timestamp) > 0) {
				this.rescheduleFromAlarm(joined, alarm)
				return
			}
			else {
				delete this.lastAlarmsPerSignalTime[signalTime.id]
				return
			}
		}
		this.alarms.push(alarm)
		const lastAlarm = this.lastAlarmsPerSignalTime[signalTime.id]
		if(!lastAlarm || lastAlarm.timestamp < timestamp)
			this.lastAlarmsPerSignalTime[signalTime.id] = alarm
	}
	
	
	
	
	
	private getRandom(): number {
		return Math.random()
	}
	
	private rescheduleFromAlarm(joined: number, alarm: Alarm) {
		const signalTime = alarm.signalTime
		
		if(!alarm.canBeRescheduled) {//when frequency > 1, then we want to reschedule only when all the other alarms are done as well
			return
		}
		
		//Note: We use getLastSignalTimeAlarm() for iOS. On Android no other alarms should exist at this point (the original is deleted in Alarm.exec() )
		const lastAlarm = alarm
		//we have to use lastAlarm.timestamp to make sure we do not skip a day if this function was executed late:
		this.rescheduleFromSignalTime(joined, lastAlarm.questionnaire, lastAlarm.schedule, signalTime, lastAlarm.actionTrigger, lastAlarm.timestamp)
	}
	
	private rescheduleFromSignalTime(joined: number, questionnaire: Questionnaire, schedule: Schedule, signalTime: SignalTime, actionTrigger: ActionTrigger, timestampAnchor: number): void {
		if(signalTime.randomFixed.get()) {
			//this does the same as scheduleSignalTime() but it ignores frequency and reuses the time from the alarm.
			
			const loopMs = ONE_DAY_MS * schedule.dailyRepeatRate.get()
			
			const baseTimestamp = this.considerDayOptions(joined, timestampAnchor + loopMs, questionnaire, schedule)
			if(baseTimestamp == -1)
				return
			this.createAlarm(joined, questionnaire, schedule, signalTime, actionTrigger, baseTimestamp)
		}
		else
			this.scheduleSignalTime(joined, questionnaire, schedule, signalTime, actionTrigger, timestampAnchor)
	}
	
	private calculateRandomPeriod(questionnaire: Questionnaire, signalTime: SignalTime): number {
		if(questionnaire.completableAtSpecificTime.get()) {
			if(questionnaire.completableAtSpecificTimeStart.get() != -1 && questionnaire.completableAtSpecificTimeEnd.get() != -1) {
				if(questionnaire.completableAtSpecificTimeStart.get() > questionnaire.completableAtSpecificTimeEnd.get()) { //start and end include midnight
					let period = 0
					if(questionnaire.completableAtSpecificTimeStart.get() < signalTime.endTimeOfDay.get())
						period += signalTime.endTimeOfDay.get() - questionnaire.completableAtSpecificTimeStart.get()
					if(questionnaire.completableAtSpecificTimeEnd.get() > signalTime.startTimeOfDay.get())
						period += questionnaire.completableAtSpecificTimeEnd.get() - signalTime.startTimeOfDay.get()
					return period
				}
				else {
					let period = 0
					if(questionnaire.completableAtSpecificTimeEnd.get() < signalTime.endTimeOfDay.get())
						period += signalTime.endTimeOfDay.get() - questionnaire.completableAtSpecificTimeEnd.get()
					if(questionnaire.completableAtSpecificTimeStart.get() > signalTime.startTimeOfDay.get())
						period += questionnaire.completableAtSpecificTimeStart.get() - signalTime.startTimeOfDay.get()
					return period
				}
			}
			else if(questionnaire.completableAtSpecificTimeStart.get() != -1)
				return questionnaire.completableAtSpecificTimeStart.get() - signalTime.startTimeOfDay.get()
			else if(questionnaire.completableAtSpecificTimeEnd.get() != -1)
				return signalTime.endTimeOfDay.get() - questionnaire.completableAtSpecificTimeEnd.get()
			else
				return signalTime.endTimeOfDay.get() - signalTime.startTimeOfDay.get()
		}
		else
			return signalTime.endTimeOfDay.get() - signalTime.startTimeOfDay.get()
	}
	
	public scheduleSignalTime(joined: number, questionnaire: Questionnaire, schedule: Schedule, signalTime: SignalTime, actionTrigger: ActionTrigger, anchorTimestamp: number, manualDelayDays: number = -1): void {
		const frequency = signalTime.frequency.get()
		const msBetween = signalTime.minutesBetween.get() * 60000
		const period = (signalTime.random.get()) ? this.calculateRandomPeriod(questionnaire, signalTime) : 0
		const block = period / frequency
		if(signalTime.random.get() && frequency > 1 && block < msBetween)
			throw new Error(`${frequency} blocks with ${msBetween} ms do not fit into ${period} ms for ${questionnaire.title.get()}.`)
		
		//
		//correct timestamp:
		//
		const midnight = getMidnightMillis(anchorTimestamp)
		
		//set beginning time:
		let baseTimestamp = midnight + signalTime.startTimeOfDay.get()
		let minDate: number
		if(manualDelayDays != -1) { //is only set when schedules are freshly created
			baseTimestamp += ONE_DAY_MS * manualDelayDays
			minDate = anchorTimestamp + (ONE_DAY_MS * manualDelayDays).coerceAtLeast(MIN_SCHEDULE_DISTANCE)
			
			// Assuming that anchorTimestamp = 23:58, startTimeOfDay = 00:00 and dailyRepeatRate = 5 (anything greater than 1).
			// When we used getMidnightMillis(), we calculated backwards a whole day, so baseTimestamp is one day short.
			// That means, when we just added ONE_DAY_MS * dailyRepeatRate, we effectively only added 4 days instead of 5.
			// This would not be true if startTimeOfDay = 23:59, so we cant just blindly add a day.
			// This loop fixes it:
			while(baseTimestamp < minDate) {
				baseTimestamp += ONE_DAY_MS
			}
		}
		else
			baseTimestamp += ONE_DAY_MS * schedule.dailyRepeatRate.get()
		
		
		//options:
		baseTimestamp = this.considerDayOptions(joined, baseTimestamp, questionnaire, schedule)
		if(baseTimestamp == -1) {
			return
		}
		
		//
		//Create alarms for each frequency:
		//
		let nextBlock = block //this variable is needed when we need to shorten a loop-block when a random notification was set less than minutes_between to the next block
		for(let i=1; i<= frequency; ++i) { //currently, frequency is always 1 when random == false.
			let workTimestamp: number
			if(signalTime.random) {
				const randomBlock = (nextBlock * this.getRandom())
				
				workTimestamp = this.considerHourOptions(baseTimestamp + randomBlock, questionnaire) //set the actual timing of the notification
				baseTimestamp = this.considerHourOptions(baseTimestamp + nextBlock, questionnaire) //prepare timestamp for the next loop
				
				if(baseTimestamp - workTimestamp < msBetween) { //if random is very late in this block, make sure that the next time gets shortened to account for minutesBetween
					const shorten = msBetween - (baseTimestamp - workTimestamp)
					baseTimestamp += shorten //start the next block later
					nextBlock = block - shorten //make sure that the next block ends at the same time (so we shorten it in the end, to account for the later start)
				}
				else nextBlock = block
			}
			else {
				baseTimestamp += block //this has no effect. I will leave it in, in case we ever want to use frequency on non-random schedules
				workTimestamp = baseTimestamp
			}
			
			this.createAlarm(joined, questionnaire, schedule, signalTime, actionTrigger, workTimestamp, i)
		}
	}
	
	private considerHourOptions(timestamp: number, questionnaire: Questionnaire): number {
		const midnight = getMidnightMillis(timestamp)
		const fromMidnight = timestamp - midnight
		if(!questionnaire.completableAtSpecificTime.get())
			return timestamp
		else if(questionnaire.completableAtSpecificTimeStart.get() != -1 && questionnaire.completableAtSpecificTimeEnd.get() != -1) {
			if(questionnaire.completableAtSpecificTimeStart.get() > questionnaire.completableAtSpecificTimeEnd.get()) { //start and end include midnight
				if(fromMidnight < questionnaire.completableAtSpecificTimeStart.get())
					return midnight + questionnaire.completableAtSpecificTimeStart.get()
				else if(fromMidnight > questionnaire.completableAtSpecificTimeEnd.get()) //this should never happen
					return fromMidnight + questionnaire.completableAtSpecificTimeEnd.get()
			}
			else {
				if(fromMidnight > questionnaire.completableAtSpecificTimeStart.get() && fromMidnight < questionnaire.completableAtSpecificTimeEnd.get())
					return midnight + questionnaire.completableAtSpecificTimeEnd.get()
			}
		}
		else if(questionnaire.completableAtSpecificTimeStart.get() != -1) {
			if(fromMidnight < questionnaire.completableAtSpecificTimeStart.get())
				return midnight + questionnaire.completableAtSpecificTimeStart.get()
		}
		else if(questionnaire.completableAtSpecificTimeEnd.get() != -1) {
			if(fromMidnight > questionnaire.completableAtSpecificTimeEnd.get()) //this should never happen
				return midnight + questionnaire.completableAtSpecificTimeEnd.get()
		}
		
		return timestamp
	}
	
	
	private considerDayOptions(joined: number, timestamp: number, questionnaire: Questionnaire, schedule: Schedule): number {
		const activeInDays = Math.ceil(questionnaire.willBeActiveIn(joined, timestamp) / ONE_DAY_MS)
		const newTimestamp = this.considerDayOptionsLogic(timestamp + activeInDays * ONE_DAY_MS, schedule)
		
		return (questionnaire.isActive(joined, newTimestamp)) ? newTimestamp : -1
	}
	
	private considerDayOptionsLogic(timestamp: number, schedule: Schedule): number {
		let cal = new Date(timestamp)
		if(schedule.dayOfMonth.get() != 0) {
			if(cal.getDate() <= schedule.dayOfMonth.get())
				cal = new Date(
					cal.getFullYear(),
					cal.getMonth(),
					schedule.dayOfMonth.get(),
					cal.getHours(),
					cal.getMinutes(),
					cal.getSeconds(),
				)
			else {
				if(cal.getMonth() < 11)
					cal = new Date(
						cal.getFullYear(),
						cal.getMonth() + 1,
						schedule.dayOfMonth.get(),
						cal.getHours(),
						cal.getMinutes(),
						cal.getSeconds(),
					)
				else
					cal = new Date(
						cal.getFullYear() + 1,
						0,
						schedule.dayOfMonth.get(),
						cal.getHours(),
						cal.getMinutes(),
						cal.getSeconds(),
					)
			}
		}
		
		//consider weekdays:
		if(schedule.weekdays.get() != 0) {
			let i = 365
			while((schedule.weekdays.get() | WEEKDAY_CODES[cal.getDay()]) != schedule.weekdays.get()) {
				cal = new Date(cal.getTime() + ONE_DAY_MS)
				if(--i==0) {
					throw new Error(`Could not find appropriate weekday (weekdaycode=${schedule.weekdays.get()}!`)
				}
			}
		}
		return cal.getTime()
	}
}