import {Defaults} from "../variables/defaults";
import {
	STATISTICS_CHARTTYPES_BARS,
	STATISTICS_CHARTTYPES_LINE,
	STATISTICS_CHARTTYPES_LINE_FILLED,
	STATISTICS_CHARTTYPES_PIE,
	STATISTICS_CHARTTYPES_SCATTER,
	STATISTICS_DATATYPES_DAILY,
	STATISTICS_DATATYPES_FREQ_DISTR, STATISTICS_DATATYPES_SUM, STATISTICS_DATATYPES_XY,
	STATISTICS_VALUETYPES_COUNT,
	STATISTICS_VALUETYPES_MEAN,
	STATISTICS_VALUETYPES_SUM
} from "../variables/statistics";
import {CHART_MIN_ENTRY_WIDTH, ONE_DAY} from "../variables/constants";
import {Lang} from "../main_classes/lang";
import {createElement} from "../helpers/basics";
import {colors} from "./statistic_tools";
import {
	Chart,
	ArcElement,
	LineElement,
	BarElement,
	PointElement,
	BarController,
	BubbleController,
	DoughnutController,
	LineController,
	PieController,
	PolarAreaController,
	RadarController,
	ScatterController,
	CategoryScale,
	LinearScale,
	LogarithmicScale,
	RadialLinearScale,
	TimeScale,
	TimeSeriesScale,
	Decimation,
	Filler,
	Legend,
	Title,
	Tooltip
} from 'chart.js';
import ChartDataLabels from "chartjs-plugin-datalabels";

const BACKGROUND_ALPHA = 0.7;

let isInitialized = false;
function init_ChartBox() {
	Chart.register(
		ArcElement,
		LineElement,
		BarElement,
		PointElement,
		BarController,
		BubbleController,
		DoughnutController,
		LineController,
		PieController,
		PolarAreaController,
		RadarController,
		ScatterController,
		CategoryScale,
		LinearScale,
		LogarithmicScale,
		RadialLinearScale,
		TimeScale,
		TimeSeriesScale,
		Decimation,
		Filler,
		Legend,
		Title,
		Tooltip
	);
	Chart.register(ChartDataLabels);
	// Chart.plugins.register(ChartDataLabels);
}

function last_value(a, num) {
	return a[a.length-(num || 1)];
}

export function ChartBox(parent, statistics, publicStatistics, chart, onClick_fu, noSort) {
	if(!isInitialized) {
		init_ChartBox();
		isInitialized = true;
	}
	
	let chartType = chart.hasOwnProperty("chartType") ? chart.chartType() : Defaults.charts.chartType,
		dataType = chart.hasOwnProperty("dataType") ? chart.dataType() : Defaults.charts.dataType,
		valueType = chart.hasOwnProperty("valueType") ? chart.valueType() : Defaults.charts.valueType,
		
		labels = [],
		option_fill = false,
		option_line_curvature = 0,
		forScatterPlot = false,
		
		drawnChartType;
	
	switch(chartType) {
		case STATISTICS_CHARTTYPES_LINE_FILLED:
			drawnChartType = "line";
			option_fill = "origin";
			option_line_curvature = 0.5;
			Chart.defaults.elements.line.spanGaps = true;
			break;
		case STATISTICS_CHARTTYPES_LINE:
			drawnChartType = "line";
			Chart.defaults.elements.line.spanGaps = true;
			break;
		default:
		case STATISTICS_CHARTTYPES_BARS:
			drawnChartType = "bar";
			break;
		case STATISTICS_CHARTTYPES_SCATTER:
			drawnChartType = "scatter";
			forScatterPlot = true;
			break;
		case STATISTICS_CHARTTYPES_PIE:
			drawnChartType = "pie";
			break;
	}
	
	let hexToRGB = function(hex, alpha) {
			let r = parseInt(hex.slice(1, 3), 16),
				g = parseInt(hex.slice(3, 5), 16),
				b = parseInt(hex.slice(5, 7), 16);
			
			return "rgba(" + r + ", " + g + ", " + b + ", " + alpha + ")";
		},
		create_dataset = function(label, data, color) {
			let backgroundColor,
				borderColor;
			
			if(chartType === STATISTICS_CHARTTYPES_PIE && data.length > 1) {
				backgroundColor = [];
				borderColor = [];
				for(let i=0, max=labels.length; i<max; ++i) {
					let color = colors[i%colors.length];
					
					backgroundColor.push(hexToRGB(color, BACKGROUND_ALPHA));
					borderColor.push(color);
				}
			}
			else {
				backgroundColor = hexToRGB(color, BACKGROUND_ALPHA);
				borderColor = hexToRGB(color, 1);
			}
			
			return {
				label: label,
				data: data,
				backgroundColor: backgroundColor,
				// borderColor: hexToRGB(color, 1),
				borderColor: borderColor,
				borderWidth: 1,
				fill: option_fill,
				lineTension: option_line_curvature
			};
		},
		
		create_sum_dataSet = function(statistics, publicStatistics) {
			let backgroundColor = [],
				borderColors = [],
				data = [];
			
			let addVars = function(container, statistics) {
				for(let axisContainer_i=container.length-1; axisContainer_i>=0; --axisContainer_i) {
					let axisContainer = container[axisContainer_i],
						yAxis = axisContainer.yAxis,
						variable_name = yAxis.variableName(),
						rawData = statistics[variable_name][yAxis.observedVariableIndex()].data,
						
						count = 0,
						num = 0,
						statistic, day;
					switch(valueType) {
						case STATISTICS_VALUETYPES_MEAN:
							for(day in rawData) {
								if(!rawData.hasOwnProperty(day))
									continue;
								statistic = rawData[day];
								
								count += statistic.count;
								num += statistic.sum;
								// if(statistic.count !== 0) {
								// 	++count;
								// 	num += Math.round(statistic.sum / statistic.count * 100) / 100;
								// }
							}
							if(count)
								num = Math.round((num / count)*100)/100;
							break;
						case STATISTICS_VALUETYPES_SUM:
							for(day in rawData) {
								if(!rawData.hasOwnProperty(day))
									continue;
								statistic = rawData[day];
								num += statistic.sum;
							}
							break;
						case STATISTICS_VALUETYPES_COUNT:
							for(day in rawData) {
								if(!rawData.hasOwnProperty(day))
									continue;
								statistic = rawData[day];
								num += statistic.count;
							}
							break;
					}
					
					if(forScatterPlot)
						data.push({x:axisContainer_i, y:num});
					else
						data.push(num);
					backgroundColor.push(hexToRGB(axisContainer.color(), 0.5));
					borderColors.push(hexToRGB(axisContainer.color(), 1));
					labels.push(axisContainer.label());
				}
			};
			
			addVars(chart.axisContainer(), statistics);
			if(chart.displayPublicVariable())
				addVars(chart.publicVariables(), publicStatistics);
			
			
			return [{
				data: data,
				backgroundColor: backgroundColor,
				borderColor: borderColors,
				borderWidth: 1,
				fill: option_fill,
				lineTension: option_line_curvature
			}];
		},
		create_daily_dataSet = function(statistics, publicStatistics) {
			let dataSets = [],
				first_day = Number.MAX_VALUE,
				last_day = 0;
			
			let get_firstLast_day = function(container, statistics) {
					for(let axisContainer_i=container.length-1; axisContainer_i>=0; --axisContainer_i) {
						let axisContainer = container[axisContainer_i],
							yAxis = axisContainer.yAxis,
							variable_name = yAxis.variableName(),
							keys;
						
						if(statistics.hasOwnProperty(variable_name) && statistics[variable_name][yAxis.observedVariableIndex()]) {
							let rawYData = statistics[variable_name][yAxis.observedVariableIndex()].data;
							if(!rawYData)
								continue;
							keys = Object.keys(rawYData).sort(); //should be sorted already but lets make sure
						}
						else
							continue;
						
						if(keys[0] < first_day)
							first_day = parseInt(keys[0]);
						if(last_value(keys) > last_day)
							last_day = parseInt(last_value(keys));
					}
				},
				addVars = function(container, statistics) {
					for(let axisContainer_i=0, max=container.length; axisContainer_i<max; ++axisContainer_i) {
						let axisContainer = container[axisContainer_i],
							yAxis = axisContainer.yAxis,
							variable_name = yAxis.variableName(),
							rawYData = statistics[variable_name][yAxis.observedVariableIndex()].data,
							data = [];
						
						for(let current_day=first_day, i=0; current_day<=last_day; current_day+=ONE_DAY, ++i) {
							let yValue;
							
							
							let rawY = rawYData[current_day];
							switch(valueType) {
								case STATISTICS_VALUETYPES_MEAN:
									yValue = rawY === undefined ? 0 : Math.round(rawY.sum / rawY.count * 100)/100;
									break;
								case STATISTICS_VALUETYPES_SUM:
									yValue = rawY === undefined ? 0 : rawY.sum;
									break;
								case STATISTICS_VALUETYPES_COUNT:
									yValue = rawY === undefined ? 0 : rawY.count;
									break;
							}
							if(forScatterPlot)
								data.push({x: i, y: yValue});
							else
								data.push(yValue);
						}
						
						dataSets.push(create_dataset(
							axisContainer.label(),
							data,
							axisContainer.color()
						));
					}
				};
			
			//get first and last day:
			get_firstLast_day(chart.axisContainer(), statistics);
			if(chart.displayPublicVariable())
				get_firstLast_day(chart.publicVariables(), publicStatistics);
			
			//create data array:
			addVars(chart.axisContainer(), statistics);
			if(chart.displayPublicVariable())
				addVars(chart.publicVariables(), publicStatistics);
			
			
			//create labels:
			let now = Date.now() / 1000;
			let cutoff_today = now - ONE_DAY;
			let cutoff_yesterday = cutoff_today - ONE_DAY;
			let cutoff_week = now - (ONE_DAY*7);
			for(let i_label=first_day; i_label<=last_day; i_label+=ONE_DAY) {
				if(i_label < cutoff_week)
					labels.push(new Date(i_label*1000).toLocaleDateString());
				else if(i_label < cutoff_yesterday)
					labels.push(Lang.get("x_days_ago", Math.floor((now - i_label) / ONE_DAY)));
				else if(i_label < cutoff_today)
					labels.push(Lang.get("yesterday"));
				else
					labels.push(Lang.get("today"));
			}
			return dataSets;
		},
		create_freqDistr_dataSet = function(statistics, publicStatistics) {
			let datasets = [];
			
			
			
			if(chart.xAxisIsNumberRange()) {
				let addVars = function(container, statistics) {
					for(let axisContainer_i=container.length-1; axisContainer_i>=0; --axisContainer_i) {
						let axisContainer = container[axisContainer_i];
						let yAxis = axisContainer.yAxis;
						let variable_name = yAxis.variableName();
						if(!statistics.hasOwnProperty(variable_name) || yAxis.observedVariableIndex() === -1)
							continue;
						let rawData = statistics[variable_name][yAxis.observedVariableIndex()].data;
						let xMin = Number.MAX_SAFE_INTEGER;
						let xMax = Number.MIN_SAFE_INTEGER;
						
						for(let key in rawData) {
							if(!rawData.hasOwnProperty(key) || isNaN(key))
								continue;
							let num = parseInt(key);
							if(num < xMin)
								xMin = num;
							else if(num > xMax)
								xMax = num;
						}
						if(xMax === Number.MIN_SAFE_INTEGER) //happens when there is only one entry
							xMax = xMin;
						
						let data = [];
						if(xMin !== Number.MAX_SAFE_INTEGER) {
							for(; xMin <= xMax; ++xMin) {
								if(forScatterPlot)
									data.push({x: axisContainer_i, y: rawData[xMin]});
								else
									data.push(rawData[xMin]);
								labels.push(xMin);
							}
						}
						
						
						datasets.push(create_dataset(
							axisContainer.label(),
							data,
							axisContainer.color()
						));
					}
				};
				
				addVars(chart.axisContainer(), statistics);
				if(chart.displayPublicVariable())
					addVars(chart.publicVariables(), publicStatistics);
			}
			else {
				let labelsIndex = {};
				let addLabels = function(container, statistics) {
					for(let container_i=container.length-1; container_i>=0; --container_i) {
						let axisContainer = container[container_i];
						let yAxis = axisContainer.yAxis;
						let variable_name = yAxis.variableName();
						if(!statistics.hasOwnProperty(variable_name))
							continue;
						if(yAxis.observedVariableIndex() === -1)
							continue;
						let rawData = statistics[variable_name][yAxis.observedVariableIndex()].data;
						
						for(let key in rawData) {
							if(!rawData.hasOwnProperty(key) || !key.length || labelsIndex.hasOwnProperty(key))
								continue;
							
							labelsIndex[key] = true;
							labels.push(key);
						}
					}
				};
				let addVars = function(container, statistics) {
					let labelsMax = labels.length;
					for(let container_i=container.length-1; container_i>=0; --container_i) {
						let axisContainer = container[container_i];
						let yAxis = axisContainer.yAxis;
						let variable_name = yAxis.variableName();
						if(!statistics.hasOwnProperty(variable_name) || yAxis.observedVariableIndex() === -1)
							continue;
						let rawData = statistics[variable_name][yAxis.observedVariableIndex()].data;
						
						//add data in order of labels:
						let data = [];
						for(let i=0; i < labelsMax; ++i) {
							let key = labels[i];
							
							let value = rawData.hasOwnProperty(key) ? rawData[key] : 0;
							data.push(forScatterPlot ? {x: i, y: value} : value);
						}
						
						datasets.push(create_dataset(
							axisContainer.label(),
							data,
							axisContainer.color()
						));
					}
				};
				
				//create labels first, so we know the order to add data in:
				
				addLabels(chart.axisContainer(), statistics);
				if(chart.displayPublicVariable())
					addLabels(chart.publicVariables(), publicStatistics);
				
				if(!noSort) {
					labels.sort(function(a, b) {
						let r = a - b;
						if(isNaN(r)) {
							let sa = a.toLowerCase(), sb = b.toLowerCase();
							
							if(sa < sb)
								return -1;
							else if(sa === sb)
								return 0;
							else
								return 1;
						}
						else
							return r;
					});
				}
				
				addVars(chart.axisContainer(), statistics);
				if(chart.displayPublicVariable())
					addVars(chart.publicVariables(), publicStatistics);
			}
			
			return datasets;
		},
		create_xy_dataSet = function(statistics, publicStatistics) {
			let calcValues = function(rawX, rawY) {
				switch(valueType) {
					case STATISTICS_VALUETYPES_MEAN:
						return [
							rawX === undefined ? 0 : Math.round(rawX.sum / rawX.count),
							rawY === undefined ? 0 : Math.round(rawY.sum / rawY.count * 100)/100
						];
					case STATISTICS_VALUETYPES_SUM:
						return [
							rawX === undefined ? 0 : Math.round(rawX.sum),
							rawY === undefined ? 0 : rawY.sum
						];
					case STATISTICS_VALUETYPES_COUNT:
						return [
							rawX === undefined ? 0 : rawX.count,
							rawY === undefined ? 0 : rawY.count
						];
				}
			};
			
			
			let dataSets = [];
			
			if(forScatterPlot) {
				let fitToShowLinearProgression = chart.fitToShowLinearProgression();
				
				let addVars = function(container, statistics) {
					for(let axisContainer_i=container.length-1; axisContainer_i>=0; --axisContainer_i) {
						let axisContainer = container[axisContainer_i],
							yAxis = axisContainer.yAxis,
							xAxis = axisContainer.xAxis,
							xMinValue = Number.MAX_SAFE_INTEGER,
							xMaxValue = -Number.MAX_SAFE_INTEGER,
							
							rawYData = statistics[yAxis.variableName()][yAxis.observedVariableIndex()].data,
							rawXData = statistics[xAxis.variableName()][xAxis.observedVariableIndex()].data,
							
							data = [],
							xSum = 0, ySum = 0, xySum = 0, xxSum = 0, yySum = 0;
						
						
						//add data:
						
						for(let day in rawXData) {
							if(!rawXData.hasOwnProperty(day))
								continue;
							
							let [xValue, yValue] = calcValues(rawXData[day], rawYData[day]);
							
							if(xValue < xMinValue)
								xMinValue = xValue;
							if(xValue > xMaxValue)
								xMaxValue = xValue;
							
							xSum += xValue;
							ySum += yValue;
							xySum += xValue*yValue;
							xxSum += xValue*xValue;
							yySum += yValue*yValue;
							data.push({x: xValue, y: yValue});
						}
						
						dataSets.push(create_dataset(
							axisContainer.label(),
							data,
							axisContainer.color()
						));
						
						
						//create regression line:
						
						let n = data.length;
						if(n >= 2) {
							let r2 = Math.pow((n*xySum - xSum*ySum)/Math.sqrt((n*xxSum - xSum*xSum)*(n*yySum - ySum*ySum)),2);
							
							if(r2*100 < fitToShowLinearProgression)
								continue;
							let slope = (n*xySum - xSum*ySum) / (n*xxSum - xSum*xSum);
							let intercept = (ySum - slope*xSum) / n;
							let regressionData = create_dataset(
								"",
								[{x:xMinValue, y:intercept + slope * xMinValue}, {x:xMaxValue, y:intercept + slope * xMaxValue}],
								axisContainer.color(),
								false,
								false
							);
							regressionData.type = "line";
							dataSets.push(regressionData);
						}
					}
				};
				
				addVars(chart.axisContainer(), statistics);
				if(chart.displayPublicVariable())
					addVars(chart.publicVariables(), publicStatistics);
			}
			else {
				let general_xMinValue = Number.MAX_SAFE_INTEGER,
					general_xMaxValue = -Number.MAX_SAFE_INTEGER,
					axisIndex = [];
				
				let addVars = function(container, statistics) {
					for(let axisContainer_i=0, axisContainer_max=container.length; axisContainer_i<axisContainer_max; ++axisContainer_i) {
						let axisContainer = container[axisContainer_i],
							yAxis = axisContainer.yAxis,
							xAxis = axisContainer.xAxis,
							rawYData = statistics[yAxis.variableName()][yAxis.observedVariableIndex()].data,
							rawXData = statistics[xAxis.variableName()][xAxis.observedVariableIndex()].data;
						
						let newIndex = {
							label: axisContainer.label(),
							color: axisContainer.color(),
							index: {}
						};
						axisIndex.push(newIndex);
						let index = newIndex.index;
						
						for(let day in rawXData) {
							if(!rawXData.hasOwnProperty(day))
								continue;
							let [xValue, yValue] = calcValues(rawXData[day], rawYData[day]);
							
							if(xValue < general_xMinValue)
								general_xMinValue = xValue;
							if(xValue > general_xMaxValue)
								general_xMaxValue = xValue;
							
							index[xValue] = yValue;
						}
					}
				};
				
				//get general_xMinValue, general_xMaxValue and create a data index:
				
				addVars(chart.axisContainer(), statistics);
				if(chart.displayPublicVariable())
					addVars(chart.publicVariables(), publicStatistics);
				
				
				//create labels:
				
				for(let i = general_xMinValue; i <= general_xMaxValue; ++i) {
					labels.push(i);
				}
				
				//add data:
				
				// for(let axisContainer_i = axisContainerArray.length - 1; axisContainer_i >= 0; --axisContainer_i) {
				for(let axisContainer_i = axisIndex.length - 1; axisContainer_i >= 0; --axisContainer_i) {
					let indexInfo = axisIndex[axisContainer_i];
					let index = indexInfo.index;
					let data = [];
					for(let i = general_xMinValue; i <= general_xMaxValue; ++i) {
						data.push(index.hasOwnProperty(i) ? index[i] : null);
					}
					dataSets.push(create_dataset(
						indexInfo.label,
						data,
						indexInfo.color
					));
				}
			}
			
			
			return dataSets;
		};
	
	
	let datasets;
	switch(dataType) {
		case STATISTICS_DATATYPES_DAILY:
			datasets = create_daily_dataSet(statistics, publicStatistics);
			break;
		case STATISTICS_DATATYPES_FREQ_DISTR:
			datasets = create_freqDistr_dataSet(statistics, publicStatistics);
			break;
		case STATISTICS_DATATYPES_SUM:
			datasets = create_sum_dataSet(statistics, publicStatistics);
			break;
		case STATISTICS_DATATYPES_XY:
			datasets = create_xy_dataSet(statistics, publicStatistics);
			break;
	}
	
	
	
	//create chart:
	if(chart.title().length)
		parent.appendChild(createElement("h2", false, {innerText: chart.title(), className: "center"}));
	if(chart.chartDescription().length)
		parent.appendChild(createElement("p", false, {innerHTML: chart.chartDescription()}));
	
	let window_div = createElement("div", false, {className: "chartWindow"}),
		legendBox = createElement("div", false, {className: "legend"}),
		scroll_div = createElement("div", false, {className: chartType === STATISTICS_CHARTTYPES_PIE ? "scrollEl pie" : "scrollEl"}),
		scrollable = chartType !== STATISTICS_CHARTTYPES_PIE && chart.dataType() !== STATISTICS_DATATYPES_XY && parent.clientWidth / labels.length < CHART_MIN_ENTRY_WIDTH,
		width = (chartType === STATISTICS_CHARTTYPES_PIE) ? "200px" : (scrollable ? (labels.length*CHART_MIN_ENTRY_WIDTH)+"px" : "100%"),
		
		chart_div = createElement("div", "width: "+width, {className: "chartEl"}),
		el = createElement("canvas", "height: 200px; width: "+width);
	
	chart_div.appendChild(el);
	scroll_div.appendChild(chart_div);
	window_div.appendChild(legendBox);
	window_div.appendChild(scroll_div);
	
	parent.appendChild(window_div);
	
	if(scrollable)
		chart_div.classList.add("scrollable");
	
	let verticalPadding = chartType === STATISTICS_CHARTTYPES_PIE ? 20 : 0;
	let chart_js = new Chart(el.getContext('2d'), {
		type: drawnChartType,
		data: {
			labels: labels,
			datasets: datasets
		},
		options: {
			layout: {
				padding: {
					left: verticalPadding,
					right: verticalPadding,
					top: 20,
					bottom: 0
				}
			},
			responsive: true,
			scales: {
				y: {
					display: chartType === STATISTICS_CHARTTYPES_SCATTER
				}
			},
			legend: {
				display: false
			},
			plugins: {
				datalabels: {
					anchor: "end",
					align: "end",
					// align: "end",
					offset: 0,
					display: chartType === STATISTICS_CHARTTYPES_SCATTER ? false : function({datasetIndex, dataIndex}) {
						return datasets[datasetIndex].data[dataIndex] !== 0 ? "auto" : false;
					}
				},
				legend: {
					display: false
				}
			},
			onClick: onClick_fu ? function(event, array) {
				if(array.length) {
					let num = array[0]._index;
					onClick_fu(labels[num]);
				}
			} : undefined,
			onHover: onClick_fu ? function(event, chartElement) {
				event.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
			} : undefined
		},
		plugins: [{ //legend
			afterUpdate: function(chart_js, args, options) {
				while(legendBox.hasChildNodes()) {
					legendBox.removeChild(legendBox.firstChild);
				}
				const legendItems = chart_js.options.plugins.legend.labels.generateLabels(chart_js);
				for(let i = 0, max = legendItems.length; i < max; ++i) {
					let item = legendItems[i];
					if(item.text === undefined || item.text === "")
						continue;
					let line = createElement("div", false, {className: "line"});
					line.appendChild(createElement("span", "background-color:"+item.fillStyle, {className: "colorRect"}));
					line.appendChild(createElement("small", false, {innerText: item.text}));
					legendBox.appendChild(line);
				}
			},
		}]
	});
}
