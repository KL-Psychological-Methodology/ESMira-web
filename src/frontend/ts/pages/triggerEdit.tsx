import { SectionContent } from "../site/SectionContent";
import m, { Vnode } from "mithril";
import { DashRow } from "../widgets/DashRow";
import { DashElement } from "../widgets/DashElement";
import { Lang } from "../singletons/Lang";
import { ObservableLangChooser } from "../widgets/ObservableLangChooser";
import { BindObservable, BooleanTransformer, ConstrainedNumberTransformer, TimeTransformer, Transformer } from "../widgets/BindObservable";
import { ActionTrigger } from "../data/study/ActionTrigger";
import { Schedule } from "../data/study/Schedule";
import { EventTrigger } from "../data/study/EventTrigger";
import { TitleRow } from "../widgets/TitleRow";
import { PrimitiveType } from "../observable/types/PrimitiveType";
import { ACTION_INVITATION } from "../constants/actions";
import { NotCompatibleIcon } from "../widgets/NotCompatibleIcon";
import { SignalTime } from "../data/study/SignalTime";
import { Section } from "../site/Section";
import { BaseObservable } from "../observable/BaseObservable";
import { Action } from "../data/study/Action";
import { Study } from "../data/study/Study";
import { BtnAdd, BtnCopy, BtnTrash } from "../widgets/BtnWidgets";

class SpecificQuestionnaireTransformer implements Transformer {
	private readonly eventTrigger: EventTrigger
	constructor(eventTrigger: EventTrigger) {
		this.eventTrigger = eventTrigger
	}
	public toAttribute(value: PrimitiveType): PrimitiveType {
		return value
	}
	public toObs(value: string): PrimitiveType {
		if (!value)
			this.eventTrigger.specificQuestionnaireInternalId.set(-1)
		return value
	}
}

class CombinedValueTransformer implements Transformer {
	private readonly index: number
	constructor(index: number) {
		this.index = 1 << index
	}
	public toAttribute(value: PrimitiveType): PrimitiveType {
		return ((value as number) & this.index) == this.index
	}
	public toObs(value: string, obs: BaseObservable<number>): PrimitiveType {
		if (value)
			return obs.get() | this.index
		else
			return obs.get() - this.index
	}
}

export class Content extends SectionContent {
	private editEventAsTitle: boolean = false

	public static preLoad(section: Section): Promise<any>[] {
		return [section.getStudyPromise()]
	}
	public title(): string {
		return this.editEventAsTitle ? Lang.get("edit_event") : Lang.get("edit_schedule")
	}

	private removeSignalTime(schedule: Schedule, index: number): void {
		schedule.signalTimes.remove(index)
	}
	private copySignalTime(schedule: Schedule, signalTime: SignalTime, index: number): void {
		schedule.signalTimes.addCopy(signalTime, index)
	}
	private addSignalTime(schedule: Schedule): void {
		schedule.signalTimes.push({})
	}

	private hasSchedule(actionTrigger: ActionTrigger): boolean {
		return actionTrigger.schedules.get().length != 0
	}

	private getActionView(study: Study, action: Action): (Vnode<any, any> | false)[] {
		return [
			DashElement("stretched", {
				content:
					<div>
						<label class="line">
							<small>{Lang.get("action")}</small>
							<select class="big" {...BindObservable(action.type)}>
								<option value="1">{Lang.get("action_invitation")}</option>
								<option value="2">{Lang.get("action_msg")}</option>
								<option value="3">{Lang.get("action_notification")}</option>
							</select>
						</label>
					</div>
			}),
			action.type.get() == ACTION_INVITATION && DashElement("vertical",
				{
					content:
						<div>
							<label class="line">
								<small>{Lang.get("number_of_reminders")}</small>
								<input min="0" type="number" {...BindObservable(action.reminder_count, new ConstrainedNumberTransformer(0, undefined))} />
							</label>
						</div>
				},

				action.reminder_count.get() != 0 && {
					content:
						<div>
							<label class="line">
								<small>{Lang.get("time_delay")}</small>
								<input min="0" type="number" {...BindObservable(action.reminder_delay_minu, new ConstrainedNumberTransformer(0, undefined))} />
								<span>{Lang.get("minutes")}</span>
							</label>
						</div>
				}),
			DashElement(null, action.reminder_count.get() != 0 && {
				content:
					<div>
						<label class="line">
							<small>{Lang.get("timeout_after_last_reminder")}</small>
							<input min="0" type="number" {...BindObservable(action.timeout, new ConstrainedNumberTransformer(0, undefined))} />
							<span class="spacingRight">{Lang.get("minutes")}</span>
							{NotCompatibleIcon("iOS", "Web")}
							<small>{Lang.get("info_timeout_notifications")}</small>
						</label>
					</div>
			}),
			DashElement("stretched", {
				content:
					<div>
						<label class="line">
							<small>{Lang.get("message")}</small>
							<textarea {...BindObservable(action.msgText)}></textarea>
							{ObservableLangChooser(study)}
						</label>
					</div>
			})
		]
	}

	private getEventView(actionTrigger: ActionTrigger): Vnode<any, any> {
		const event = actionTrigger.eventTriggers.get()[0]
		const action = actionTrigger.actions.get()[0]
		const study = this.getStudyOrThrow()
		this.editEventAsTitle = true

		return <div>
			{DashRow(
				DashElement(null,
					{
						content:
							<div>
								<label>
									<small>{Lang.get("event")}</small>

									<select {...BindObservable(event.cueCode)}>
										<option>actions_executed</option>
										<option>invitation</option>
										<option>invitation_missed</option>
										<option>joined</option>
										<option>quit</option>
										<option>questionnaire</option>
										<option>rejoined</option>
										<option>reminder</option>
										<option>schedule_changed</option>
										<option>statistic_viewed</option>
										<option>study_message</option>
										<option>study_updated</option>
									</select>
								</label>
							</div>
					}
				),
				DashElement(null,
					{
						content:
							<div class="center">
								<span>{Lang.get("delay")}</span>
								<label class="horizontal spacingLeft spacingRight center">
									<small>{Lang.get("random")}</small>
									<input type="checkbox" {...BindObservable(event.randomDelay)} />
								</label>
								{event.randomDelay.get() &&
									<label class="horizontal">
										<small>{Lang.get("from")}</small>
										<input min="0" type="number" {...BindObservable(event.delayMinimumSec, new ConstrainedNumberTransformer(0, undefined))} />
										<small>{Lang.get("seconds")}</small>
									</label>
								}
								<label class="horizontal">
									{event.randomDelay.get() &&
										<small>{Lang.get("to")}</small>
									}
									<input min={event.delayMinimumSec.get()} type="number" {...BindObservable(event.delaySec, new ConstrainedNumberTransformer(event.delayMinimumSec.get(), undefined))} />
									<small>{Lang.get("seconds")}</small>
								</label>
							</div>
					}),
				DashElement("stretched", {
					content:
						<div>
							<label class="vertical noTitle noDesc">
								<input type="checkbox" disabled={event.specificQuestionnaireEnabled.get()} {...BindObservable(event.skipThisQuestionnaire)} />
								<span style={`color:${event.specificQuestionnaireEnabled.get() ? 'lightgray' : 'inherit'}`}>{Lang.get("desc_skip_this_questionnaire")}</span>
							</label>
							<label class="vertical noDesc">
								<input type="checkbox" {...BindObservable(event.specificQuestionnaireEnabled, new SpecificQuestionnaireTransformer(event))} />
								<span>{Lang.get("desc_specific_questionnaire")}</span>
							</label>
							{event.specificQuestionnaireEnabled.get() &&
								<label class="vertical noDesc">
									<small>{Lang.get("questionnaire")}</small>
									<select {...BindObservable(event.specificQuestionnaireInternalId)}>
										{study.questionnaires.get().map((questionnaire) =>
											<option value={questionnaire.internalId.get()}>{questionnaire.getTitle()}</option>)
										}
									</select>
								</label>
							}
						</div>
				}),
			)}

			{TitleRow(Lang.getWithColon("action"))}
			{DashRow(...this.getActionView(study, action))}
		</div>
	}

	private getSignalTimeView(schedule: Schedule, signalTime: SignalTime, index: number): Vnode<any, any> {
		return <div class="nowrap">
			<label class="horizontal middle center">
				<small class="smallText">{Lang.get("random")}</small>
				<input type="checkbox" {...BindObservable(signalTime.random)} />
			</label>

			{signalTime.random.get() &&
				<label class="horizontal middle spacingLeft">
					<small>{Lang.get("random_fixed")}</small>
					<select {...BindObservable(signalTime.randomFixed, BooleanTransformer)}>
						<option value="1">{Lang.get("random_fixed_true")}</option>
						<option value="0">{Lang.get("random_fixed_false")}</option>
					</select>
				</label>
			}


			<label class="horizontal middle spacingLeft">
				<small>{signalTime.random.get() ? Lang.get("startTime") : Lang.get("time")}</small>
				<input type="time" {...BindObservable(signalTime.startTimeOfDay, TimeTransformer)} />
			</label>

			{signalTime.random.get() &&
				<label class="horizontal middle spacingLeft">
					<small>{Lang.get("endTime")}</small>
					<input type="time" {...BindObservable(signalTime.endTimeOfDay, TimeTransformer)} />
				</label>
			}

			{signalTime.random.get() &&
				<label class="horizontal middle spacingLeft">
					<small>{Lang.get("frequency")}</small>
					<input type="number" min="1" {...BindObservable(signalTime.frequency)} />
				</label>
			}

			{signalTime.random.get() && signalTime.frequency.get() > 1 &&
				<label class="horizontal middle spacingLeft">
					<small>{Lang.get("minutes_between")}</small>
					<input type="number" min="0" {...BindObservable(signalTime.minutesBetween)} />
					<span>{Lang.get("minutes")}</span>
				</label>
			}

			{BtnCopy(this.copySignalTime.bind(this, schedule, signalTime, index))}
			{BtnTrash(this.removeSignalTime.bind(this, schedule, index))}
		</div>
	}

	/**
	 * Note: Triggers are implemented so that each could hold MULTIPLE schedules, cues and actions.
	 * But since I suspect that this will not be used often, and it is easier to grasp for configuration, I removed that functionality in the admin panel
	 */
	private getScheduleView(actionTrigger: ActionTrigger): Vnode<any, any> {
		const study = this.getStudyOrThrow()
		const schedule = actionTrigger.schedules.get()[0]
		const action = actionTrigger.actions.get()[0]

		return <div>
			{DashRow(
				DashElement("stretched",
					{
						content:
							<div class="center">
								<label class="spacingLeft">
									<small>{Lang.get("repeat_every")}</small>
									<input type="number" min="1" {...BindObservable(schedule.dailyRepeatRate, new ConstrainedNumberTransformer(1, undefined))} />
									<span>{Lang.get("days")}</span>
								</label>
								<br />
								<label class="spacingLeft">
									<input type="checkbox" {...BindObservable(schedule.skipFirstInLoop)} />
									<span>{Lang.get("wait_x_days_until_first", schedule.dailyRepeatRate.get())}</span>
								</label>
							</div>
					}),
				DashElement(null,
					{
						content:
							<div class="center">
								<label class="vertical">
									<small>{Lang.get("dayOfMonth")}</small>
									<select {...BindObservable(schedule.dayOfMonth)}>
										<option value="0">{Lang.get("all")}</option>
										{
											Array.from({ length: 31 }).map((_, index) => {
												return <option>{index + 1}</option>
											})
										}
									</select>
								</label>
							</div>
					}
				),
				DashElement(null,
					{
						content:
							<div>
								<div class="fakeLabel">
									<small>{Lang.get("weekdays_desc")}</small>

									<table>
										<tr>
											<td class="horizontalPadding"><label htmlFor="weekday_all">{Lang.get("all")}</label></td>
											<td class="horizontalPadding"><label htmlFor="weekday_sun">{Lang.get("weekday_sun")}</label></td>
											<td class="horizontalPadding"><label htmlFor="weekdays_mo">{Lang.get("weekday_mon")}</label></td>
											<td class="horizontalPadding"><label htmlFor="weekdays_di">{Lang.get("weekday_tue")}</label></td>
											<td class="horizontalPadding"><label htmlFor="weekdays_mi">{Lang.get("weekday_wed")}</label></td>
											<td class="horizontalPadding"><label htmlFor="weekdays_do">{Lang.get("weekday_thu")}</label></td>
											<td class="horizontalPadding"><label htmlFor="weekdays_fr">{Lang.get("weekday_fri")}</label></td>
											<td class="horizontalPadding"><label htmlFor="weekdays_sa">{Lang.get("weekday_sat")}</label></td>
										</tr>
										<tr>
											<td class="center">
												<input type="checkbox" id="weekday_all" disabled="disabled" checked={schedule.weekdays.get() == 0} />
											</td>
											<td class="center"><input type="checkbox" id="weekday_sun" {...BindObservable(schedule.weekdays, new CombinedValueTransformer(0), "checked")} /></td>
											<td class="center"><input type="checkbox" id="weekdays_mo" {...BindObservable(schedule.weekdays, new CombinedValueTransformer(1), "checked")} /></td>
											<td class="center"><input type="checkbox" id="weekdays_di" {...BindObservable(schedule.weekdays, new CombinedValueTransformer(2), "checked")} /></td>
											<td class="center"><input type="checkbox" id="weekdays_mi" {...BindObservable(schedule.weekdays, new CombinedValueTransformer(3), "checked")} /></td>
											<td class="center"><input type="checkbox" id="weekdays_do" {...BindObservable(schedule.weekdays, new CombinedValueTransformer(4), "checked")} /></td>
											<td class="center"><input type="checkbox" id="weekdays_fr" {...BindObservable(schedule.weekdays, new CombinedValueTransformer(5), "checked")} /></td>
											<td class="center"><input type="checkbox" id="weekdays_sa" {...BindObservable(schedule.weekdays, new CombinedValueTransformer(6), "checked")} /></td>
										</tr>
									</table>
								</div>
							</div>
					})
			)}
			{TitleRow(
				<div>
					<span class="flexGrow flexCenter">{Lang.getWithColon("times_of_day")}</span>
					<label class="noTitle noDesc">
						<input type="checkbox" {...BindObservable(schedule.userEditable)} />
						<span>{Lang.get("userEditable_desc")}</span>
					</label>
				</div>
			)}

			<div>
				<div class="listParent">
					<div class="listChild coloredLines" id="signalTimes">
						{schedule.signalTimes.get().map((signalTime, index) =>
							this.getSignalTimeView(schedule, signalTime, index)
						)}
					</div>
					<div>
						{BtnAdd(this.addSignalTime.bind(this, schedule), Lang.get("add_signalTime"))}
					</div>
				</div>
			</div>

			{TitleRow(Lang.getWithColon("action"))}
			{DashRow(... this.getActionView(study, action))}
		</div>
	}


	public getView(): Vnode<any, any> {
		const triggerI = this.getStaticInt("triggerI")
		if (triggerI == null)
			throw new Error("Trigger does not exist")

		const questionnaire = this.getQuestionnaireOrThrow()
		const actionTrigger = questionnaire.actionTriggers.get()[triggerI]
		const isSchedule = this.hasSchedule(actionTrigger)
		return <div>
			{isSchedule && this.getScheduleView(actionTrigger)}
			{!isSchedule && this.getEventView(actionTrigger)}
		</div>
	}
}