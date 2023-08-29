import {DayEntry} from "./DayEntry";

export interface ServerStatistics {
	days: {
		[key: number]: DayEntry
	}
	week: {
		questionnaire: number[]
		joined: number[]
	}
	total: {
		studies: number
		users: number
		android: number
		ios: number
		web: number
		questionnaire: number
		joined: number
		quit: number
	}
	created: number
}