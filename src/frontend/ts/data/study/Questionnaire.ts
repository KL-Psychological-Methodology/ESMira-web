import {TranslatableObject, TranslatableObjectDataType} from "../../observable/TranslatableObject";
import {ActionTrigger} from "./ActionTrigger";
import {Page} from "./Page";
import {SumScore} from "./SumScore";
import {BaseObservable} from "../../observable/BaseObservable";
import {ObservableTypes} from "../../observable/types/ObservableTypes";
import "../../number.extensions"
import {Lang} from "../../singletons/Lang";
import {Scheduler} from "../../helpers/Scheduler";

const ONE_DAY_MS = 86400000

export class Questionnaire extends TranslatableObject {
	public internalId								= this.primitive<number>(	"internalId",								-1)
	public publishedAndroid							= this.primitive<boolean>(	"publishedAndroid",						true)
	public publishedIOS								= this.primitive<boolean>(	"publishedIOS",							true)
	public publishedWeb								= this.primitive<boolean>(	"publishedWeb",							true)
	public durationStart							= this.primitive<number>(	"durationStart",							0)
	public durationEnd								= this.primitive<number>(	"durationEnd",								0)
	public durationPeriodDays						= this.primitive<number>(	"durationPeriodDays",						0)
	public durationStartingAfterDays				= this.primitive<number>(	"durationStartingAfterDays",				0)
	public completableOnce							= this.primitive<boolean>(	"completableOnce",							false)
	public completableOncePerNotification			= this.primitive<boolean>(	"completableOncePerNotification",			false)
	public completableMinutesAfterNotification		= this.primitive<number>(	"completableMinutesAfterNotification",		0)
	public limitCompletionFrequency					= this.primitive<boolean>(	"limitCompletionFrequency",				false)
	public completionFrequencyMinutes				= this.primitive<number>(	"completionFrequencyMinutes",				60)
	public completableAtSpecificTime				= this.primitive<boolean>(	"completableAtSpecificTime",			false)
	public completableAtSpecificTimeStart			= this.primitive<number>(	"completableAtSpecificTimeStart",			-1)
	public completableAtSpecificTimeEnd				= this.primitive<number>(	"completableAtSpecificTimeEnd",			-1)
	public limitToGroup								= this.primitive<number>(	"limitToGroup",							0)
	public minDataSetsForReward						= this.primitive<number>(	"minDataSetsForReward",					0)
	public isBackEnabled							= this.primitive<boolean>(	"isBackEnabled",							true)
	public endScriptBlock							= this.primitive<string>(	"endScriptBlock",							"")
	public virtualInputs							= this.primitiveArray<string>("virtualInputs",							[])
	
	public title									= this.translatable(		"title",									"")
	
	public actionTriggers							= this.objectArray(			"actionTriggers", ActionTrigger)
	public pages									= this.objectArray(			"pages", Page)
	public sumScores								= this.objectArray(			"sumScores", SumScore)
	
	constructor(data: TranslatableObjectDataType, parent: BaseObservable<ObservableTypes> | null) {
		super(data, parent, data["internalId"] as string)
	}
	public updateKeyName(_keyName: string, parent?: BaseObservable<ObservableTypes>) {
		super.updateKeyName(this.internalId.get().toString(), parent)
	}
	
	public getTitle(): string {
		if(this.limitToGroup.get() == 0)
			return this.title.get()
		else
			return `${this.title.get()} (${Lang.get("group")} ${this.limitToGroup.get()})`
	}
	
	public hasSchedules(): boolean {
		const schedule = this.actionTriggers.get().find((actionTrigger) => {
			return actionTrigger.get().schedules.get().length
		})
		return !!schedule
	}
	
	/**
	 * Needs to stay in sync with sharedCode.Questionnaire in kotlin
	 */
	public isActive(joinedTimestamp: number, now: number) {
		const durationCheck = (this.durationPeriodDays.get() == 0 || now <= joinedTimestamp + this.durationPeriodDays.get() * ONE_DAY_MS)
			&& (this.durationStartingAfterDays.get() == 0 || now >= joinedTimestamp + this.durationStartingAfterDays.get() * ONE_DAY_MS)
		
		return durationCheck
			// && (this.limitToGroup.get() == 0 || this.limitToGroup.get() == group)
			&& ((this.durationStart.get() == 0 || now >= this.durationStart.get())
			&& (this.durationEnd.get() == 0 || now <= this.durationEnd.get()))
			// && (!this.completableOnce.get() || lastCompleted == 0L)
	}
	
	/**
	 * Used for {@link Scheduler}
	 * Needs to stay in sync with sharedCode.Questionnaire in kotlin
	 */
	public willBeActiveIn(joinedTimestamp: number, now: number): number {
		const durationValue = this.durationStart.get() - now
		const startingAfterDaysValue = joinedTimestamp + this.durationStartingAfterDays.get() * (1000*60*60*24) - now
		
		
		let value: number
		if(durationValue <= 0)
			value = startingAfterDaysValue.coerceAtLeast(0) // durationValue is negative, so we ignore it
		else if(startingAfterDaysValue <= 0)
			value = durationValue.coerceAtLeast(0) // startingAfterDaysValue is negative, so we ignore it
		else
			value = durationValue.coerceAtLeast(startingAfterDaysValue)
		
		return value.coerceAtLeast(0)
	}
}