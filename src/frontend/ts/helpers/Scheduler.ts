import { SignalTime } from "../data/study/SignalTime"
import { ActionTrigger } from "../data/study/ActionTrigger";
import { Schedule } from "../data/study/Schedule";
import { Questionnaire } from "../data/study/Questionnaire";
import "../number.extensions"
import { getMidnightMillis } from "../constants/methods";
import { OnBeforeChangeTransformer } from "../components/BindObservable";

const ONE_DAY_MS = 1000 * 60 * 60 * 24
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

class Interval {
	public readonly start: number
	public readonly end: number
	public readonly includesMidnight: boolean
	public readonly period: number

	constructor(start: number, end: number) {
		this.start = start
		this.end = end
		this.includesMidnight = start > end
		this.period = this.includesMidnight ? start - end + ONE_DAY_MS : end - start

	}

	public getOverlaps(other: Interval): Interval[] {
		const first = this.splitIfIncludesMidnight()
		const second = other.splitIfIncludesMidnight()

		let overlaps = []

		for (const i of first) {
			for (const j of second) {
				const overlap = i.getOverlap(j)
				if (overlap !== null) {
					overlaps.push(overlap)
				}
			}
		}

		return overlaps
	}

	public getOverlap(other: Interval): Interval | null {
		if (this.includesMidnight || other.includesMidnight || this.start > other.end || other.start > this.end) {
			return null
		} else {
			return new Interval(Math.max(this.start, other.start), Math.min(this.end, other.end))
		}
	}

	private splitIfIncludesMidnight(): Interval[] {
		if (this.includesMidnight) {
			return [new Interval(0, this.end), new Interval(this.start, ONE_DAY_MS)]
		} else {
			return [this]
		}
	}
}

/**
 * This class is a copy of the Kotlin code in sharedCode.Scheduler
 * (only the needed methods are included)
 */
export class Scheduler {
	public readonly alarms: Alarm[] = []
	private readonly lastAlarmsPerSignalTime: Record<number, Alarm> = {}
	private useLegacyScheduling: boolean = false

	public setLegacyScheduling(use: boolean) {
		this.useLegacyScheduling = use
	}

	public scheduleAheadJavascript(joined: number) {
		for (let i = 50; i >= 0; --i) { //limit the amount of loops to not crash the browser
			for (const id in this.lastAlarmsPerSignalTime) {
				this.rescheduleFromAlarm(joined, this.lastAlarmsPerSignalTime[id])
			}
		}
	}

	private createAlarm(joined: number, questionnaire: Questionnaire, schedule: Schedule, signalTime: SignalTime, actionTrigger: ActionTrigger, timestamp: number, indexNum: number = 1) {
		const alarm = new Alarm(questionnaire, schedule, signalTime, actionTrigger, timestamp, indexNum)
		if (!questionnaire.isActive(joined, timestamp, this.useLegacyScheduling)) {
			if (questionnaire.willBeActiveIn(joined, timestamp) > 0) {
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
		if (!lastAlarm || lastAlarm.timestamp < timestamp)
			this.lastAlarmsPerSignalTime[signalTime.id] = alarm
	}


	static getDatesDiff(ms1: number, ms2: number): number {
		const date1 = new Date(ms1)
		const date2 = new Date(ms2)

		const utc1 = Date.UTC(date1.getFullYear(), date1.getMonth(), date1.getDate())
		const utc2 = Date.UTC(date2.getFullYear(), date2.getMonth(), date2.getDate())

		return Math.abs(Math.floor((utc1 - utc2) / ONE_DAY_MS))
	}


	private getRandom(): number {
		return Math.random()
	}

	private rescheduleFromAlarm(joined: number, alarm: Alarm) {
		const signalTime = alarm.signalTime

		if (!alarm.canBeRescheduled) {//when frequency > 1, then we want to reschedule only when all the other alarms are done as well
			return
		}

		//Note: We use getLastSignalTimeAlarm() for iOS. On Android no other alarms should exist at this point (the original is deleted in Alarm.exec() )
		const lastAlarm = alarm
		//we have to use lastAlarm.timestamp to make sure we do not skip a day if this function was executed late:
		this.rescheduleFromSignalTime(joined, lastAlarm.questionnaire, lastAlarm.schedule, signalTime, lastAlarm.actionTrigger, lastAlarm.timestamp)
	}

	private rescheduleFromSignalTime(joined: number, questionnaire: Questionnaire, schedule: Schedule, signalTime: SignalTime, actionTrigger: ActionTrigger, timestampAnchor: number): void {
		if (signalTime.randomFixed.get()) {
			//this does the same as scheduleSignalTime() but it ignores frequency and reuses the time from the alarm.

			const loopMs = ONE_DAY_MS * Math.max(1, schedule.dailyRepeatRate.get())

			const baseTimestamp = this.considerDayOptions(joined, timestampAnchor + loopMs, questionnaire, schedule)
			if (baseTimestamp == -1)
				return
			this.createAlarm(joined, questionnaire, schedule, signalTime, actionTrigger, baseTimestamp)
		}
		else
			this.scheduleSignalTime(joined, questionnaire, schedule, signalTime, actionTrigger, timestampAnchor)
	}

	private calculateRandomInterval(questionnaire: Questionnaire, signalTime: SignalTime): Interval | null {
		if (questionnaire.completableAtSpecificTime.get()) {
			const signalInterval = new Interval(signalTime.startTimeOfDay.get(), signalTime.endTimeOfDay.get())
			const filterStart = questionnaire.completableAtSpecificTimeStart.get() != 1 ? questionnaire.completableAtSpecificTimeStart.get() : 0
			const filterEnd = questionnaire.completableAtSpecificTimeEnd.get() != 1 ? questionnaire.completableAtSpecificTimeEnd.get() : ONE_DAY_MS
			const filterInterval = new Interval(filterStart, filterEnd)

			const overlaps = signalInterval.getOverlaps(filterInterval)
			const bothIncludeMidnight = signalInterval.includesMidnight && filterInterval.includesMidnight

			if (overlaps.length == 1) {
				return overlaps[0]
			} else if (overlaps.length == 2 && bothIncludeMidnight) {
				const times = [overlaps[0].start, overlaps[0].end, overlaps[1].start, overlaps[1].end]
				times.sort()
				return new Interval(times[2], times[1])
			} else {
				throw new Error(`SignalTime: Configuration of completableAtSpecificTime filter (${questionnaire.completableAtSpecificTimeStart.get()}, ${questionnaire.completableAtSpecificTimeEnd.get()}) and signalTime (${signalTime.startTimeOfDay.get()}, ${signalTime.endTimeOfDay.get()}) results in more than one interval overlaps.`)
				return null
			}
		} else {
			return new Interval(signalTime.startTimeOfDay.get(), signalTime.endTimeOfDay.get())
		}
	}

	public scheduleSignalTime(joined: number, questionnaire: Questionnaire, schedule: Schedule, signalTime: SignalTime, actionTrigger: ActionTrigger, anchorTimestamp: number, manualDelayDays: number = -1): void {
		const frequency = signalTime.frequency.get()
		const msBetween = signalTime.minutesBetween.get() * 60000
		const interval = (signalTime.random.get()) ? this.calculateRandomInterval(questionnaire, signalTime) : new Interval(signalTime.startTimeOfDay.get(), signalTime.startTimeOfDay.get())
		if (interval == null) {
			return
		}
		const block = interval.period / frequency
		if (signalTime.random.get() && frequency > 1 && block < msBetween)
			throw new Error(`${frequency} blocks with ${msBetween} ms do not fit into ${interval.period} ms for ${questionnaire.title.get()}.`)

		//
		//correct timestamp:
		//
		const midnight = getMidnightMillis(anchorTimestamp)

		//set beginning time:
		let baseTimestamp = midnight + signalTime.startTimeOfDay.get()
		let minDate: number
		if (manualDelayDays != -1) { //is only set when schedules are freshly created
			minDate = anchorTimestamp + (ONE_DAY_MS * manualDelayDays).coerceAtLeast(MIN_SCHEDULE_DISTANCE)

			if (this.useLegacyScheduling) {
				// Assuming that anchorTimestamp = 23:58, startTimeOfDay = 00:00 and dailyRepeatRate = 5 (anything greater than 1).
				// When we used getMidnightMillis(), we calculated backwards a whole day, so baseTimestamp is one day short.
				// That means, when we just added ONE_DAY_MS * dailyRepeatRate, we effectively only added 4 days instead of 5.
				// This would not be true if startTimeOfDay = 23:59, so we cant just blindly add a day.
				// This loop fixes it:
				while (baseTimestamp < minDate) {
					baseTimestamp += ONE_DAY_MS
				}
			} else {
				while (Scheduler.getDatesDiff(baseTimestamp, minDate) < manualDelayDays) {
					baseTimestamp += ONE_DAY_MS
				}
			}
		}
		else
			baseTimestamp += ONE_DAY_MS * Math.max(1, schedule.dailyRepeatRate.get())


		//options:
		baseTimestamp = this.considerDayOptions(joined, baseTimestamp, questionnaire, schedule)
		if (baseTimestamp == -1) {
			return
		}

		//
		//Create alarms for each frequency:
		//
		let nextBlock = block //this variable is needed when we need to shorten a loop-block when a random notification was set less than minutes_between to the next block
		for (let i = 1; i <= frequency; ++i) { //currently, frequency is always 1 when random == false.
			let workTimestamp: number
			if (signalTime.random) {
				const randomBlock = (nextBlock * this.getRandom())

				workTimestamp = this.considerHourOptions(baseTimestamp + randomBlock, interval) //set the actual timing of the notification
				baseTimestamp = this.considerHourOptions(baseTimestamp + nextBlock, interval) //prepare timestamp for the next loop

				if (baseTimestamp - workTimestamp < msBetween) { //if random is very late in this block, make sure that the next time gets shortened to account for minutesBetween
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

	private considerHourOptions(timestamp: number, relevantInterval: Interval): number {
		const midnight = getMidnightMillis(timestamp)
		const fromMidnight = timestamp - midnight
		const intervalStart = relevantInterval.start
		const intervalEnd = relevantInterval.end

		if (relevantInterval.includesMidnight) {
			if (fromMidnight > intervalEnd && fromMidnight < intervalStart) {
				return midnight + ((fromMidnight - intervalEnd < intervalStart - fromMidnight) ? intervalEnd : intervalStart)
			}
		} else {
			if (fromMidnight < intervalStart) {
				return midnight + intervalStart
			} else if (fromMidnight > intervalEnd) {
				return midnight + intervalEnd
			}
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
		if (schedule.dayOfMonth.get() != 0) {
			if (cal.getDate() <= schedule.dayOfMonth.get())
				cal = new Date(
					cal.getFullYear(),
					cal.getMonth(),
					schedule.dayOfMonth.get(),
					cal.getHours(),
					cal.getMinutes(),
					cal.getSeconds(),
				)
			else {
				if (cal.getMonth() < 11)
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
		if (schedule.weekdays.get() != 0) {
			let i = 365
			while ((schedule.weekdays.get() | WEEKDAY_CODES[cal.getDay()]) != schedule.weekdays.get()) {
				cal = new Date(cal.getTime() + ONE_DAY_MS)
				if (--i == 0) {
					throw new Error(`Could not find appropriate weekday (weekdaycode=${schedule.weekdays.get()}!`)
				}
			}
		}
		return cal.getTime()
	}
}