import {
	CONDITION_OPERATOR_EQUAL,
	CONDITION_TYPE_AND,
	STATISTICS_CHARTTYPES_BARS,
	STATISTICS_DATATYPES_DAILY,
	STATISTICS_VALUETYPES_COUNT,
} from "../variables/statistics";
import {OwnMapping} from "../helpers/knockout_own_mapping";
import {Defaults} from "../variables/defaults";
import {Lang} from "../main_classes/lang";
import {FILE_RESPONSES, FILE_STATISTICS} from "../variables/urls";
import {Requests} from "../main_classes/requests";
import {CsvLoader} from "./csv_container";
import {ChartBox} from "./chart_box";
import {Studies} from "../main_classes/studies";



export const colors = [
	//Thanks to: https://sashamaps.net/docs/resources/20-colors/
	'#e6194B',
	'#f58231',
	'#ffe119',
	'#bfef45',
	'#3cb44b',
	'#42d4f4',
	'#4363d8',
	'#911eb4',
	'#f032e6',
	'#a9a9a9',
	
	'#fabed4',
	'#ffd8b1',
	'#fffac8',
	'#aaffc3',
	'#dcbeff',
	'#ffffff',
	
	'#800000',
	'#9A6324',
	'#808000',
	'#469990',
	'#000075',
	'#000000',
];


export function combineStatistics(statistics, newStatistics) {
	for(let variableName in newStatistics) {
		if(!newStatistics.hasOwnProperty(variableName))
			continue;
		
		let newVariable = newStatistics[variableName];
		
		if(!statistics.hasOwnProperty(variableName))
			statistics[variableName] = newVariable
		else {
			let currentVariable = statistics[variableName];
			for(let i=newVariable.length-1; i>=0; --i) {
				let newEntry = newVariable[i];
				if(!newEntry)
					continue;
				currentVariable[i] = newEntry;
			}
		}
	}
}

export function drawCharts(el, charts, statistics, publicStatistics, noHiding) {
	for(let chart_i=0, chart_max=charts.length; chart_i<chart_max; ++chart_i) {
		let chart = charts[chart_i];
		
		if(!noHiding && chart.hideUntilCompletion())
			continue;
		
		new ChartBox(el, statistics, publicStatistics, chart);
	}
}

export function setup_chart(loader, elId, chart, onClick_fu) {
	let el = document.getElementById(elId);
	while(el.hasChildNodes()) {
		el.removeChild(el.firstChild);
	}
	
	return loader.get_statistics(chart.axisContainer(), chart.dataType())
		.then(function(statistics) {
			new ChartBox(
				el,
				statistics,
				false,
				chart,
				onClick_fu
			);
		});
}
export function create_perDayChartCode(loader, title, columnKey) {
	let axis = [];
	
	return loader.get_valueList(columnKey).then(function(valueList) {
		for(let i=valueList.length-1; i>=0; --i) {
			let entry = valueList[i];
			let key = entry.name;
			axis.push({
				xAxis: {
					conditions: []
				},
				yAxis: {
					conditions: [
						{
							key: columnKey,
							value: key,
							operator: CONDITION_OPERATOR_EQUAL
						}
					],
					variableName: "responseTime",
					conditionType: CONDITION_TYPE_AND,
					observedVariableIndex: i
				},
				label: Lang.get("text_with_count", key, entry.count),
				color: colors[i%colors.length]
			});
		}
		return OwnMapping.fromJS({
			title: title,
			publicVariables: [],
			axisContainer: axis,
			valueType: STATISTICS_VALUETYPES_COUNT,
			dataType: STATISTICS_DATATYPES_DAILY,
			chartType: STATISTICS_CHARTTYPES_BARS
		}, Defaults.charts);
	});
}

export function create_loaderForNeededFiles(page, study, charts) {
	let questionnaires = study.questionnaires(),
		urlStart = FILE_RESPONSES.replace('%1', study.id()),
		variableGroupIndex = {};
	
	
	
	
	for(let i=questionnaires.length-1; i>=0; --i) {
		let questionnaire = questionnaires[i];
		
		let variables = Studies.tools.get_questionnaireVariables(questionnaire);
		for(let j=variables.length-1; j>=0; --j) {
			variableGroupIndex[variables[j]] = i;
		}
	}
	let groupLoaders = [],
		addLoader = function(variableName) {
			if(!variableName)
				return;
			let questionnaireI = variableGroupIndex[variableName];
			let url = urlStart.replace('%2', questionnaires[questionnaireI].internalId());
			if(!groupLoaders[questionnaireI])
				groupLoaders[questionnaireI] = new CsvLoader(url, page);
		},
		checkAxis = function(axisContainer) {
			for(let axisI=axisContainer.length-1; axisI>=0; --axisI) {
				let xAxis = axisContainer[axisI].xAxis,
					yAxis = axisContainer[axisI].yAxis;
				
				addLoader(xAxis.variableName());
				addLoader(yAxis.variableName());
			}
		};
	
	for(let i=charts.length-1; i>=0; --i) {
		let chart = charts[i];
		
		checkAxis(chart.axisContainer());
		if(chart.displayPublicVariable())
			checkAxis(chart.publicVariables());
	}
	return groupLoaders;
}

// export function load_statisticsFromFiles(page, study, charts, username) {
export function load_statisticsFromFiles(loaderList, study, charts, username, dontLoadPublicStatistics) {
	let statistics = {},
		needsPublicStatistics = false,
		promise = Promise.resolve();
	
	let get_statistics = function(loader) {
		for(let i = charts.length - 1; i >= 0; --i) {
			let chart = charts[i];
			
			loader.get_statistics(
				chart.axisContainer(),
				chart.dataType()
			).then(function(newStatistics) {
				combineStatistics(statistics, newStatistics);
			});
			if(chart.displayPublicVariable())
				needsPublicStatistics = true;
		}
	};

	for(let i=loaderList.length-1; i>=0; --i) {
		let loader = loaderList[i];
		if(!loader)
			continue;

		promise = promise
			.then(function() {
				return loader.waitUntilReady();
			})
			.then(function() {
				if(username) {
					loader.filter_column(false, "userId");
					loader.filter(true, "userId", username);
				}

				get_statistics(loader);
				return loader.waitUntilReady();
			});
	}
	
	return promise.then(function() {
		if(needsPublicStatistics && !dontLoadPublicStatistics) {
			let accessKey = study.accessKeys().length ? study.accessKeys()[0]() : '';
			
			return Requests.load(FILE_STATISTICS.replace("%d", study.id()).replace("%s", accessKey)).then(function(publicStatistics) {
				return [statistics, publicStatistics];
			});
		}
		else
			return promise.then(function() {
				return [statistics, false];
			});
	});
}