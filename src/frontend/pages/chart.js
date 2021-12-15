import {Lang} from "../js/main_classes/lang";
import {createElement} from "../js/helpers/basics";
import reload_svg from "../imgs/reload.svg?raw";
import {Studies} from "../js/main_classes/studies";
import {get_chart, get_pageType} from "../js/shared/charts";
import {DetectChange} from "../js/main_classes/detect_change";
import {CsvCreator} from "../js/dynamic_imports/csv_container";
import {
	combineStatistics,
	create_loaderForNeededFiles,
	drawCharts,
	load_statisticsFromFiles
} from "../js/dynamic_imports/statistic_tools";

export function ViewModel(page) {
	let isFor_calc = get_pageType() === "calc";
	let changeDetector;
	
	page.title(isFor_calc ? Lang.get("calculate") : Lang.get("preview"));
	this.extraContent = "<div data-bind=\"click: $root.reload, attr: {title: Lang.get('reload')}\" class=\"clickable\">"+reload_svg+"</div>";
	
	let chart, graphEl;
	let draw_dataGraph = function() {
		let needsPublicStatistics = false;
		
		let charts = [chart],
			study = Studies.get_current();
		
		let fillStatistics = function(loader, containerName, allVariables) {
			let statistics = {};
			for(let i = charts.length - 1; i >= 0; --i) {
				let currentChart = charts[i];
				if(allVariables || currentChart.displayPublicVariable()) {
					loader.get_statistics(currentChart[containerName](), currentChart.dataType()).then(function(newStatistics) {
						combineStatistics(statistics, newStatistics);
					});
					if(allVariables)
						needsPublicStatistics = true;
				}
			}
			return loader.waitUntilReady().then(function() {
				return statistics;
			});
		}
		
		
		
		let promise;
		if(isFor_calc) {
			promise = load_statisticsFromFiles(
				create_loaderForNeededFiles(page, study, study.personalStatistics.charts()),
				study,
				charts
			);
		}
		else {
			let [csv, publicCsv] = create_randomCSV(study, charts, 50);
			
			let loader = new CsvCreator(csv, page);
			
			promise = loader.waitUntilReady()
				.then(function() {
					return fillStatistics(loader, "axisContainer", true);
				})
				.then(function(statistics) {
					if(!needsPublicStatistics)
						return [statistics, false];
					
					let publicLoader = new CsvCreator(publicCsv, page);
					return publicLoader.waitUntilReady()
						.then(function() {
							return fillStatistics(publicLoader, "publicVariables");
						})
						.then(function(publicStatistics) {
							return [statistics, publicStatistics];
						});
				});
		}
		
		promise.then(function(bundle) {
			let [statistics, publicStatistics] = bundle;
			while(graphEl.hasChildNodes()) {
				graphEl.removeChild(graphEl.lastChild);
			}
			drawCharts(graphEl, charts, statistics, publicStatistics);
		});
		
		page.loader.showLoader(Lang.get("state_loading"), promise);
	};
	
	this.promiseBundle = [
		Studies.init(page)
	];
	this.postInit = function(index, studies) {
		chart = get_chart();
		graphEl = createElement("div");
		page.contentEl.appendChild(graphEl);
		
		draw_dataGraph();
		
		changeDetector = new DetectChange(chart, draw_dataGraph);
	};
	
	this.destroy = function() {
		changeDetector.destroy();
	}
	
	this.reload = draw_dataGraph;
}

function randomData_for_responseType(input) {
	if(!input) {
		return Math.floor(Math.random()*99).toString();
	}
	switch(input.responseType()) {
		case "text_input":
			return ["Text 1", "Text 2", "Text 3", "Text 4", "Text 5", "Text 6", "Text 7", "Text 8", "Text 9", "text 10"][Math.floor(Math.random()*10)];
		case "binary":
		case "image":
		case "video":
			return Math.floor(Math.random()*2).toString();
		case "va_scale":
			return Math.floor(Math.random()*100).toString();
		case "likert":
			return (Math.floor(Math.random()*input.likertSteps())+1).toString();
		case "number":
			return Math.floor(Math.random()*99).toString();
		case "time":
			return input.forceInt()
				? Math.floor(Math.random()*300).toString()
				: Math.floor(Math.random()*24) + ":" + Math.floor(Math.random()*60);
		case "date":
			return ["Date 1", "Date 2", "Date 3", "Date 4", "Date 5", "Date 6", "Date 7", "Date 8", "Date 9", "Date 10"][Math.floor(Math.random()*10)];
		case "list_single":
		case "list_multiple":
			return ["Choice 1", "Choice 2", "Choice 3", "Choice 4", "Choice 5", "Choice 6", "Choice 7", "Choice 8", "Choice 9", "Choice 10"][Math.floor(Math.random()*10)];
		case "dynamic_input":
			let choices = input.subInputs();
			if(!choices.length)
				return "";
			return randomData_for_responseType(choices[Math.floor(Math.random()*(choices.length-1))]);
		case "text":
		default:
			return "";
	}
}
function create_randomCSV(study, charts, csvSize) {
	let questionnaires = study.questionnaires(),
		inputIndex = {},
		neededInputIndex = {};
	
	//collect all inputs:
	
	for(let i=questionnaires.length-1; i>=0; --i) {
		let pages = questionnaires[i].pages();
		for(let j=pages.length-1; j>=0; --j) {
			let inputs = pages[j].inputs();
			for(let k=inputs.length-1; k>=0; --k) {
				let input = inputs[k];
				inputIndex[input.name()] = input;
			}
		}
	}
	
	let addToNeeded = function(key) {
			if(!neededInputIndex.hasOwnProperty(key)) {
				if(inputIndex.hasOwnProperty(key))
					neededInputIndex[key] = inputIndex[key];
				else
					neededInputIndex[key] = false; //is a sum score
			}
		},
		checkAxis = function(axisContainer) {
			for(let axisI=axisContainer.length-1; axisI>=0; --axisI) {
				
				let axis = axisContainer[axisI],
					xAxis = axis.xAxis,
					yAxis = axis.yAxis;
				
				addToNeeded(xAxis.variableName());
				addToNeeded(yAxis.variableName());
				
				for(let conditions = xAxis.conditions(), conditionI=conditions.length-1; conditionI>=0; --conditionI) {
					addToNeeded(conditions[conditionI].key());
				}
				for(let conditions = yAxis.conditions(), conditionI=conditions.length-1; conditionI>=0; --conditionI) {
					addToNeeded(conditions[conditionI].key());
				}
			}
		};
	
	
	//check which inputs we actually need:
	
	for(let i=charts.length-1; i>=0; --i) {
		let chart = charts[i];
		checkAxis(chart.axisContainer());
		if(chart.displayPublicVariable())
			checkAxis(chart.publicVariables());
	}
	
	//create CSV:
	
	let csv = [],
		publicCsv = [],
		headerLine = [];
	
	//create headers:
	headerLine.push("responseTime");
	headerLine.push("uploaded");
	for(let key in neededInputIndex) {
		if(!neededInputIndex.hasOwnProperty(key))
			continue;
		
		headerLine.push(key);
	}
	csv.push(headerLine);
	publicCsv.push(headerLine);
	
	//create data:
	let time = Date.now(),
		oneHour = 1000*60*60;
	
	let createLine = function() {
		time -= Math.ceil(Math.random()*12)*oneHour;
		let newLine = [
			time, //responseTime
			time //uploaded
		];
		for(let key in neededInputIndex) {
			if(!neededInputIndex.hasOwnProperty(key))
				continue;
			
			newLine.push(randomData_for_responseType(neededInputIndex[key]));
		}
		return newLine;
	}
	
	//personal & public:
	for(let i=0, max=csvSize; i<max; ++i) {
		let newLine = createLine();
		csv.push(newLine);
		publicCsv.push(newLine);
	}
	
	//public:
	time = Date.now();
	for(let i=0, max=csvSize; i<max; ++i) {
		let newLine = createLine();
		publicCsv.push(newLine);
	}
	
	return [{data: csv}, {data: publicCsv}];
}
