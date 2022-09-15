import {
	CONDITION_TYPE_ALL,
	STATISTICS_CHARTTYPES_LINE,
	STATISTICS_DATATYPES_DAILY,
	STATISTICS_VALUETYPES_MEAN
} from "./statistics";

export const Defaults = {};

Defaults.conditions = {
	key: "",
	value: "",
	operator: 0
};
Defaults.axisData = {
	$: {
		children: {
			conditions: Defaults.conditions
		}
	},
	variableName: "",
	observedVariableIndex: 0,
	conditionType: CONDITION_TYPE_ALL
};
Defaults.axisContainer = {
	$: {
		translated: {
			label: ""
		}
	},
	xAxis: Defaults.axisData,
	yAxis: Defaults.axisData,
	color: '#00bbff',
};
Defaults.charts = {
	$: {
		children: {
			publicVariables: Defaults.axisContainer,
			axisContainer: Defaults.axisContainer
		},
		translated: {
			title: "",
			chartDescription: "",
			xAxisLabel: "",
			yAxisLabel: "",
		}
	},
	valueType: STATISTICS_VALUETYPES_MEAN,
	dataType: STATISTICS_DATATYPES_DAILY,
	chartType: STATISTICS_CHARTTYPES_LINE,
	//storageType is set in admin.php. Default is 0, which is timed
	inPercent: false,
	xAxisIsNumberRange: false,
	maxYValue: 0,
	displayPublicVariable: false,
	hideUntilCompletion: false,
	fitToShowLinearProgression: 0
};
Defaults.statistics = {
	$: {
		children: {
			charts: Defaults.charts
		},
		noDefault: {
			observedVariables: {}
		}
	},
};
Defaults.inputs = {
	$: {
		translated: {
			defaultValue: "",
			text: "",
			url: "",
			leftSideLabel: "",
			rightSideLabel: "",
			listChoices: [],
		}
	},
	responseType: "text_input",
	name: "input",
	required: false,
	random: false,
	likertSteps: 5,
	numberHasDecimal: false,
	asDropDown: true,
	forceInt: false,
	packageId: ""
};
Defaults.inputs.$.children = {
	subInputs: JSON.parse(JSON.stringify(Defaults.inputs))
};

Defaults.pages = {
	$: {
		children: {
			inputs: Defaults.inputs
		},
		translated: {
			header: "",
			footer: ""
		}
	},
	randomized: false,
};
Defaults.actions = {
	$: {
		translated: {
			msgText: ""
		}
	},
	type: 1, //is Invitation
	timeout: 0,
	reminder_count: 0,
	reminder_delay_minu: 5
};
Defaults.signalTimes = {
	$: {
		translated: {
			label: ""
		}
	},
	startTimeOfDay: 0,
	endTimeOfDay: 0,
	random: false,
	randomFixed: false,
	frequency: 1,
	minutesBetween: 60
};
Defaults.schedules = {
	$: {
		children: {
			signalTimes: Defaults.signalTimes
		}
	},
	userEditable: true,
	dailyRepeatRate: 1,
	skipFirstInLoop: false,
	weekdays: 0,
	dayOfMonth: 0
};
Defaults.eventTriggers = {
	label: "Event",
	cueCode: "joined",
	skipThisQuestionnaire: false,
	specificQuestionnaireEnabled: false,
	specificQuestionnaireInternalId: -1,
	randomDelay: false,
	delaySec: 0,
	delayMinimumSec: 0,
};
Defaults.actionTriggers = {
	$: {
		children: {
			actions: Defaults.actions,
			schedules: Defaults.schedules,
			eventTriggers: Defaults.eventTriggers
		}
	},
};
Defaults.sumScores = {
	name: "unknown",
	addList: [],
	subtractList: []
};
Defaults.questionnaires = {
	$: {
		children: {
			actionTriggers: Defaults.actionTriggers,
			pages: Defaults.pages,
			sumScores: Defaults.sumScores,
		},
		translated: {
			title: ""
		}
	},
	internalId: -1,
	publishedAndroid: true,
	publishedIOS: true,
	publishedWeb: true,
	durationStart: 0,
	durationEnd: 0,
	durationPeriodDays: 0,
	durationStartingAfterDays: 0,
	
	completableOnce: false,
	completableOncePerNotification: false,
	completableMinutesAfterNotification: 0,
	limitCompletionFrequency: false,
	completionFrequencyMinutes: 60,
	completableAtSpecificTime: false,
	completableAtSpecificTimeStart: -1,
	completableAtSpecificTimeEnd: -1,
	limitToGroup: 0
};
Defaults.eventUploadSettings = {
	actions_executed: false,
	invitation: false,
	invitation_missed: false,
	message: false,
	notification: false,
	rejoined: false,
	reminder: false,
	schedule_changed: true,
	statistic_viewed: false,
	study_message: false,
	study_updated: false
}
Defaults.studies = {
	$: {
		children: {
			questionnaires: Defaults.questionnaires
		},
		translated: {
			title: "Error",
			studyDescription: "",
			informedConsentForm: "",
			postInstallInstructions: "",
			chooseUsernameInstructions: "",
			webQuestionnaireCompletedInstructions: "",
			webInstallInstructions: '',
			contactEmail: ''
		}
	},
	publicStatistics: Defaults.statistics,
	personalStatistics: Defaults.statistics,
	
	id: -1,
	version: -1,
	subVersion: -1,
	serverVersion: -1,
	lang: "",
	new_changes: false,
	published: false,
	publishedWeb: true,
	publishedAndroid: true,
	publishedIOS: true,
	sendMessagesAllowed: true,
	accessKeys: [],
	langCodes: [],
	eventUploadSettings: Defaults.eventUploadSettings,
	randomGroups: 0
};


Defaults.messages = {
	content: "",
	userId: "",
	appVersion: "",
	appType: "",
	distributedSince: -1
};
Defaults.user = {
	username: "Error",
	admin: false,
	read: [],
	msg: [],
	write: [],
	publish: []
};

Defaults.serverSettings = {
	langCodes: [],
	$: {
		translated: {
			impressum: "",
			serverName: "",
			privacyPolicy: "",
		}
	}
}