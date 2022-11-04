import {Site} from "../main_classes/site";

export function repairStudy(study) {
	let serverVersion = Site.serverVersion;
	if(study.serverVersion > serverVersion)
		return false;
	
	else if(study.serverVersion !== serverVersion) {
		console.log("Repairing study \"" + study.title + "\" (v:" + study.serverVersion + ")");
		switch(study.serverVersion) {//we make use of fallthrough so there is no break;
			case undefined:
			case 2:
				study.studyDescription = study.description;
				
				for(let groups = study.questionnaires || study.groups, groupI = groups.length - 1; groupI >= 0; --groupI) {
					let group = groups[groupI];
					group.completeRepeatType = group.complete_repeat_type;
					group.completeRepeatMinutes = group.complete_repeat_minutes;
					group.durationPeriodDays = group.period;
					group.durationStart = group.startDate;
					group.durationEnd = group.endDate;
					group.timeConstraintType = group.timeConstraint_type;
					group.timeConstraintStart = group.timeConstraint_start;
					group.timeConstraintEnd = group.timeConstraint_end;
					group.timeConstraintPeriodMinutes = group.timeConstraint_period;
					
					for(let pages = group.pages, pageI = 0, pageMaxI = pages.length; pageI < pageMaxI; ++pageI) {
						let page = pages[pageI];
						for(let inputs = page.inputs, inputI = 0, inputMaxI = inputs.length; inputI < inputMaxI; ++inputI) {
							let input = inputs[inputI];
							input.numberHasDecimal = input.number_has_decimal;
						}
					}
					
					for(let actionTriggers = group.actionTriggers, actionTriggerI = 0, actionTriggerMaxI = actionTriggers.length; actionTriggerI < actionTriggerMaxI; ++actionTriggerI) {
						let actionTrigger = actionTriggers[actionTriggerI];
						if(actionTrigger.hasOwnProperty("cues")) {
							actionTrigger.eventTriggers = actionTrigger.cues;
							for(let eventTriggers = actionTrigger.eventTriggers, eventTriggerI = 0, eventTriggerMaxI = eventTriggers.length; eventTriggerI < eventTriggerMaxI; ++eventTriggerI) {
								let eventTrigger = eventTriggers[eventTriggerI];
								eventTrigger.cueCode = eventTrigger.cue_code;
								eventTrigger.randomDelay = eventTrigger.random_delay;
								eventTrigger.delaySec = eventTrigger.delay;
								eventTrigger.delayMinimumSec = eventTrigger.delay_min;
								eventTrigger.skipThisGroup = eventTrigger.skip_this_group;
								eventTrigger.specificGroupEnabled = eventTrigger.specific_group;
								eventTrigger.specificGroupIndex = eventTrigger.specific_group_index;
							}
						}
						if(actionTrigger.hasOwnProperty("schedules")) {
							for(let schedules = actionTrigger.schedules, scheduleI = 0, scheduleMaxI = schedules.length; scheduleI < scheduleMaxI; ++scheduleI) {
								let schedule = schedules[scheduleI];
								schedule.dailyRepeatRate = schedule.repeatRate;
								schedule.skipFirstInLoop = schedule.skip_first_in_loop;
								
								for(let signalTimes = schedule.signalTimes, signalTimeI = 0, signalTimeMaxI = signalTimes.length; signalTimeI < signalTimeMaxI; ++signalTimeI) {
									let signalTime = signalTimes[signalTimeI];
									signalTime.random = signalTime.is_random;
									signalTime.randomFixed = signalTime.is_random_fixed;
									signalTime.frequency = signalTime.random_frequency;
									signalTime.minutesBetween = signalTime.random_minutes_between;
									signalTime.startTimeOfDay = signalTime.startTime_of_day;
									signalTime.endTimeOfDay = signalTime.endTime_of_day;
								}
							}
						}
					}
				}
				
				let repairStatistics = function(statistics) {
					statistics.observedVariables = statistics.observed_variables;
					
					for(let charts = statistics.charts, chartI = 0, chartMaxI = charts.length; chartI < chartMaxI; ++chartI) {
						let chart = charts[chartI];
						if(chart.hasOwnProperty("description"))
							chart.chartDescription = chart.description;
						
						if(chart.hasOwnProperty("display_publicVariable"))
							chart.displayPublicVariable = chart.display_publicVariable;
						
						if(chart.hasOwnProperty("hide_until_completion"))
							chart.hideUntilCompletion = chart.hide_until_completion;
						
						if(chart.hasOwnProperty("in_percent"))
							chart.inPercent = chart.in_percent;
						
						if(chart.hasOwnProperty("publicVariable")) {
							chart.publicVariables = chart.publicVariable;
							for(let publicVariables = chart.publicVariables, publicVariableI = 0, publicVariableMaxI = publicVariables.length; publicVariableI < publicVariableMaxI; ++publicVariableI) {
								let variables = publicVariables[publicVariableI];
								
								let axis = variables.xAxis;
								axis.observedVariableIndex = axis.observed_variable_index;
								axis = variables.yAxis;
								axis.observedVariableIndex = axis.observed_variable_index;
								
							}
						}
						for(let axisContainerArray = chart.axisContainer, axisContainerI = 0, axisContainerMaxI = axisContainerArray.length; axisContainerI < axisContainerMaxI; ++axisContainerI) {
							let axisContainer = axisContainerArray[axisContainerI];
							
							let axis = axisContainer.xAxis;
							axis.observedVariableIndex = axis.observed_variable_index;
							axis = axisContainer.yAxis;
							axis.observedVariableIndex = axis.observed_variable_index;
						}
					}
				};
				repairStatistics(study.publicStatistics);
				repairStatistics(study.personalStatistics);
			case 3: //give all groups an internalId and remove completeRepeatType and timeConstraintType
			//TODO
			case 4: //replace specificGroupIndex with specificGroupInternalId
			//TODO
			case 5:
			case 6:
			case 7:
			case 8:
			case 9:
				if(study.groups && (!study.questionnaires || study.groups.length > study.questionnaires.length))
					study.questionnaires = study.groups;
				let questionnaires = study.questionnaires;

				for(let i=questionnaires.length-1; i>=0; --i) {
					let questionnaire = questionnaires[i];
					if(!questionnaire.title && questionnaire.name)
						questionnaire.title = questionnaire.name;
					// if(!questionnaire.internalId < 10000)
					// 	questionnaire.internalId = questionnaire.internalId*10;
				}
		}
		study.serverVersion = serverVersion;
	}
	switch(study.packageVersion) {
		case undefined:
			study.langCodes.push("unnamed");
			study.defaultLang = "unnamed";
	}
	study.packageVersion = PACKAGE_VERSION;
	
	return true;
}