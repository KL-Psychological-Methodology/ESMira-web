import {CSV_DELIMITER, DATAVIEWER_SKIPPED_COLUMNS, DATAVIEWER_TIMESTAMP_COLUMNS} from "../variables/csv";
import Papa from 'papaparse';
import {
	CONDITION_OPERATOR_EQUAL,
	CONDITION_OPERATOR_GREATER,
	CONDITION_OPERATOR_LESS,
	CONDITION_OPERATOR_UNEQUAL,
	CONDITION_TYPE_ALL,
	CONDITION_TYPE_AND,
	CONDITION_TYPE_OR,
	STATISTICS_DATATYPES_DAILY, STATISTICS_DATATYPES_FREQ_DISTR,
	STATISTICS_DATATYPES_SUM, STATISTICS_DATATYPES_XY,
	STATISTICS_STORAGE_TYPE_FREQ_DISTR,
	STATISTICS_STORAGE_TYPE_TIMED
} from "../variables/statistics";
import {ONE_DAY, SMALLEST_TIMED_DISTANCE} from "../variables/constants";
import {Defaults, fillDefaults} from "../variables/defaults";

function Cell(index, title, row, value, real_value) {
	this.index = index;
	this.row = row;
	this.value = value; //the value that is shown in the data viewer
	this.title = title;
	if(real_value === undefined) {
		this.special = false;
		this.real_value = "";
	}
	else {
		this.special = true;
		this.real_value = real_value;
	}
}
function Row(pos, cells) {
	this.pos = pos;
	this.hidden_sum = 0; //how many columns are hiding this row
	this.visible = true;
	this.columnCells = cells;
	
	this.marked = false;
}

const CsvData = {
	translations: {},
	
	loadingStarted: false,
	needsHeader: true,
	
	is_indexed: false,
	needs_index: true,
	header_names: [],
	rows_count: 0,
	timestamp_columns_numIndex: [],
	timestamp_columns_nameIndex: {},
	skipped_index: [],
	valueIndex: [],
	rowsIndex: [],
	visible_rowsIndex: [],
	visible_valueIndex: [],
	
	filteredColumnsIndex: {},
	filteredRowsIndex: {},
	not_indexed_data: [],
	
	loadRow: function(columns) {
		if(columns.length === 1) {
			let msg = columns[0];
			let errorMsg = null;
			try {
				let json = JSON.parse(msg);
				errorMsg =  json.error || msg;
			}
			catch(e) {
				console.error("Row "+this.not_indexed_data.length+" is faulty. Contents: \""+msg+"\"");
			}
			if(errorMsg != null)
				throw errorMsg;
		}
		
		if(this.needsHeader) {
			this.needsHeader = false;
			for(let column_i = 0, i = 0, max = columns.length; i < max; ++i) {
				let column_value = columns[i];
				
				if(DATAVIEWER_TIMESTAMP_COLUMNS.indexOf(column_value) !== -1) {
					this.timestamp_columns_numIndex[i] = true;
					this.timestamp_columns_nameIndex[column_value] = true;
				}
				else if(DATAVIEWER_SKIPPED_COLUMNS.indexOf(column_value) !== -1) {
					this.skipped_index[i] = true;
					continue;
				}
				this.valueIndex[column_i] = {};
				this.visible_valueIndex[column_i] = {};
				this.header_names.push(column_value);
				++column_i; //because of DATAVIEWER_SKIPPED_COLUMNS column_i may not be the same as i
			}
		}
		else {
			this.not_indexed_data.push(columns);
			if((++this.rows_count) % 1000 === 0)
				postMessage({loadingState: this.rows_count});
		}
	},
	loadData: function(url) {
		if(this.loadingStarted)
			return;
		let self = this;
		
		this.loadingStarted = true;
		return new Promise(function(complete, error) {
			Papa.parse(url + (url.indexOf("?") === -1 ? "?" : "&") + Date.now(), {
				download: true,
				step: function(rowData) {self.loadRow(rowData.data);},
				delimiter: CSV_DELIMITER,
				complete: complete,
				error: error
			});
		});
	},
	loadCsv: function(csv) {
		let rows = csv.data;
		for(let i=0, max=rows.length; i<max; ++i) {
			this.loadRow(rows[i]);
		}
	},
	
	index_row: function(raw_row, index) {
		//Note: entries are ordered in reverse. This means that index does NOT equal pos
		let header_names = this.header_names;
		let columnCells = [];
		let row_data = new Row(this.rowsIndex.length, columnCells);
		for(let column_i=0, i=0, max=raw_row.length; i<max; ++i) {
			if(this.skipped_index[i])
				continue;
			
			let value, title;
			let real_value = undefined;
			if(this.timestamp_columns_numIndex[i]) {
				let timestamp = parseInt(raw_row[i]);
				if(!timestamp) {
					value = this.translations["empty_dataSymbol"];
					timestamp = "";
				}
				else if(timestamp > 32532447600)//test if timestamp is in ms or seconds. NOTE: Exactly in the year 3000 when ducks have taken over the world, this code will stop working!!
					value = (new Date(timestamp)).toLocaleString();
				else
					value = (new Date(timestamp * 1000)).toLocaleString();
				
				title = header_names[column_i]+"\n"+this.translations["colon_timestamp"]+" "+timestamp;
				real_value = timestamp;
			}
			else if(!raw_row[i].length) {
				value = this.translations["empty_dataSymbol"];
				real_value = "";
				title = header_names[column_i];
			}
			else {
				value = raw_row[i];
				title = header_names[column_i];
			}
			
			let cell = new Cell(index, title, row_data, value, real_value);
			
			
			let column_index = this.valueIndex[column_i];
			let visible_column_index = this.visible_valueIndex[column_i];
			
			let column_value = cell.value;
			if(column_index) { //this will only be false when datasets.php has been changed after the csv was created and data has more columns than the header line
				if(!column_index.hasOwnProperty(column_value)) {
					column_index[column_value] = {cells: [cell], meta: {visible: true}};
					visible_column_index[column_value] = [cell];
				}
				else {
					column_index[column_value].cells.push(cell);
					visible_column_index[column_value].push(cell);
				}
			}
			columnCells.push(cell); //we need to use push because of skipped_index, i can be wrong
			++column_i;
		}
		this.rowsIndex.push(row_data);
		this.visible_rowsIndex.push(row_data);
	},
	index: function(until) {
		until = until || this.rows_count-1;
		if(this.is_indexed) {
			if(!this.needs_index || until < this.visible_rowsIndex.length || !this.rows_count)
				return;
			let length = this.visible_rowsIndex.length,
				i = length ? this.visible_rowsIndex[length - 1].pos + 1 : 0;
			//Note: if a filter happened, then reset_visibleFilter() should have been called and visible_rowsIndex is empty
			// if not, we continue an index-action from before
			for(; length <= until; ++i) {
				if(this.rowsIndex[i].hidden_sum)
					continue;
				
				let row_data = this.rowsIndex[i];
				this.visible_rowsIndex.push(row_data);
				
				let columns = row_data.columnCells;
				for(let column_i=this.visible_valueIndex.length-1; column_i>=0; --column_i) {
					let column = columns[column_i];
					if(!column) //this can happen in old datasets because of an old bug
						continue;
					let value = columns[column_i].value;
					if(this.visible_valueIndex[column_i].hasOwnProperty(value))
						this.visible_valueIndex[column_i][value].push(row_data);
					else
						this.visible_valueIndex[column_i][value] = [row_data];
				}
				++length;
			}
			this.needs_index = i !== this.rowsIndex.length-1;
		}
		else {
			let count = until - (this.visible_rowsIndex.length-1);
			if(count <= 0)
				return;
			
			let index_count = this.not_indexed_data.length;
			let part = index_count > count ? this.not_indexed_data.splice(index_count - count, count) : this.not_indexed_data.splice(0, index_count);
			let index_start = this.not_indexed_data.length - (this.not_indexed_data.length - index_count) - part.length;
			
			for(let i = part.length - 1; i >= 0; --i) {
				this.index_row(part[i], index_start + i);
				if(i % 1000 === 0)
					postMessage({indexingState: i});
			}
			
			
			this.needs_index = this.is_indexed = this.not_indexed_data.length === 0;
		}
	},
	
	reset: function() {
		if(!this.is_indexed)
			return;
		
		let needsCompleteReset = false;
		for(let column in this.filteredRowsIndex) {
			if(this.filteredRowsIndex.hasOwnProperty(column)) {
				needsCompleteReset = true;
				break;
			}
		}
		
		if(needsCompleteReset) {
			let rows = this.rowsIndex;
			for(let i = rows.length - 1; i >= 0; --i) {
				let row = rows[i];
				let state = row[1];
				if(!state.visible) { //if it is already visible we do nothing
					++this.rows_count;
					state.hidden_sum = 0;
					state.visible = true;
				}
			}
			for(let column in this.filteredColumnsIndex) {
				if(!this.filteredColumnsIndex.hasOwnProperty(column))
					continue;
				
				let columns = this.filteredColumnsIndex[column];
				for(let search_key in columns) {
					if(!columns.hasOwnProperty(search_key) || search_key === "~")
						continue;
					
					let columnNum = this.get_columnNum(column);
					let index = this.valueIndex[columnNum];
					if(index.hasOwnProperty(search_key))
						index[search_key][1].visible = true;
				}
			}
		}
		else {
			for(let column in this.filteredColumnsIndex) {
				if(!this.filteredColumnsIndex.hasOwnProperty(column))
					continue;
				
				let columns = this.filteredColumnsIndex[column];
				for(let search_key in columns) {
					if(columns.hasOwnProperty(search_key) && search_key !== "~") {
						this.filter(true, column, search_key);
					}
				}
			}
		}
		
		this.filteredRowsIndex = {};
		this.filteredColumnsIndex = {};
		
		this.reset_visibleFilter();
		this.index();
	},
	
	get_columnNum: function(columnName) {
		return this.header_names.indexOf(columnName);
	},
	get_storageType: function(dataType) {
		switch(dataType) {
			case STATISTICS_DATATYPES_XY:
				return [SMALLEST_TIMED_DISTANCE, STATISTICS_STORAGE_TYPE_TIMED];
			case STATISTICS_DATATYPES_FREQ_DISTR:
				return [0, STATISTICS_STORAGE_TYPE_FREQ_DISTR];
			case STATISTICS_DATATYPES_DAILY:
			case STATISTICS_DATATYPES_SUM:
			default:
				return [ONE_DAY, STATISTICS_STORAGE_TYPE_TIMED];
		}
	},
	
	get_visibleRows: function(from, to) {
		this.index(to-1);
		return this.visible_rowsIndex.slice(from, to);
	},
	get_valueIndex: function(columnNum) {
		this.index();
		return this.valueIndex[columnNum];
	},
	get_valueList: function(columnNum, sortByAmount, alsoInvisible) {
		this.index();
		
		let visibleIndex = this.visible_valueIndex[columnNum];
		let index = alsoInvisible ? this.valueIndex[columnNum] : visibleIndex;
		let valueList = Object.keys(index);
		if(sortByAmount) {
			valueList.sort(function(a, b) {
				let l1 = visibleIndex.hasOwnProperty(a) ? visibleIndex[a].length : 0;
				let l2 = visibleIndex.hasOwnProperty(b) ? visibleIndex[b].length : 0;
				return l2 - l1;
			});
		}
		else
			valueList.sort();
		
		let list = [];
		let addToList = alsoInvisible
			? function(key) {
				let valueIndexEntry = index[key];
				list.push({
					name: key,
					count: visibleIndex.hasOwnProperty(key) ? visibleIndex[key].length : 0,
					totalCount: valueIndexEntry.cells.length,
					visible: valueIndexEntry.meta.visible
				});
			}
			: function(key) {
				list.push({
					name: key,
					count: visibleIndex[key].length
				});
			};
		
		for(let i=0, max=valueList.length; i<max; ++i) {
			addToList(valueList[i]);
		}
		return list;
	},
	get_dataAsStatistics: function(axisContainerArray, dataType) {
		this.index();
		let self = this,
			statisticsObj = {},
			visible_rows = this.visible_rowsIndex,
			responseTime_index = this.get_columnNum("responseTime"),
			uploaded_index = this.get_columnNum("uploaded");
		let [timeInterval, storageType] = this.get_storageType(dataType);
		
		let create_dataFromAxis = function(axis) {
			if(!axis.variableName || axis.variableName.length === 0)
				return;
			let variable_name = axis.variableName || Defaults.axisData.variableName;
			if(variable_name.length === 0)
				return;
			
			let column_num = self.get_columnNum(variable_name),
				conditions = axis.conditions || Defaults.conditions,
				conditionType = axis.conditionType || Defaults.axisData.conditionType,
				conditionType_isAll = conditionType === CONDITION_TYPE_ALL,
				conditionType_isAnd = conditionType === CONDITION_TYPE_AND,
				conditionType_isOr = conditionType === CONDITION_TYPE_OR;
			
			let a;
			if(!statisticsObj.hasOwnProperty(variable_name))
				a = statisticsObj[variable_name] = [];
			else
				a = statisticsObj[variable_name];
			
			
			let entry;
			let index = axis.observedVariableIndex || Defaults.axisData.observedVariableIndex;
			if(a[index])
				entry = a[index];
			else {
				entry = a[index] = {
					storageType: storageType,
					timeInterval: timeInterval,
					data: {}
				};
			}
			let entry_data = entry.data;
			
			for(let i=visible_rows.length-1; i>=0; --i) {
				let cells = visible_rows[i].columnCells;
				let column = cells[column_num];
				
				if(!column) //because of a bug from the past, this can happen in old datasets
					continue;
				let value = column.special ? column.real_value : column.value;
				
				
				let condition_is_met = !conditionType_isOr;
				
				if(!conditionType_isAll) {
					for(let i_cond = conditions.length - 1; i_cond >= 0; --i_cond) {
						let condition = conditions[i_cond];
						let filter_column = cells[self.get_columnNum(condition.key || Defaults.conditions.key)];
						if(filter_column === undefined) //can happen if there was an error in dataset
							continue;
						let filter_value = filter_column.special ? filter_column.real_value : filter_column.value;
						let is_true;
						let conditionValue = condition.value || Defaults.conditions.value;
						switch(condition.operator || Defaults.conditions.operator) {
							case CONDITION_OPERATOR_EQUAL:
								is_true = filter_value === conditionValue;
								break;
							case CONDITION_OPERATOR_UNEQUAL:
								is_true = filter_value !== conditionValue;
								break;
							case CONDITION_OPERATOR_GREATER:
								is_true = filter_value >= conditionValue;
								break;
							case CONDITION_OPERATOR_LESS:
								is_true = filter_value <= conditionValue;
								break;
							default:
								is_true = true;
						}
						if(is_true) {
							if(conditionType_isOr) {
								condition_is_met = true;
								break;
							}
						}
						else if(conditionType_isAnd) {
							condition_is_met = false;
							break;
						}
					}
				}
				
				if(!condition_is_met)
					continue;
				
				switch(storageType) {
					case STATISTICS_STORAGE_TYPE_TIMED:
						value = parseInt(value);
						let day = cells[responseTime_index] === undefined
							? null //can happen if there is an error in dataset
							: Math.floor(Math.round(parseInt(cells[responseTime_index].real_value) / 1000) / timeInterval) * timeInterval;
						
						if(isNaN(day)) { //fallback
							day = cells[responseTime_index] === undefined
								? null //can happen if there is an error in dataset
								: Math.floor(Math.round(parseInt(cells[uploaded_index].real_value)) / timeInterval) * timeInterval;
							if(isNaN(day))
								continue;
						}
						if(isNaN(value))
							value = 0;
						
						if(!isNaN(day)) {
							if(!entry_data.hasOwnProperty(day))
								entry_data[day] = {sum: value, count: 1};
							else {
								entry_data[day].sum += value;
								++entry_data[day].count;
							}
						}
						break;
					case STATISTICS_STORAGE_TYPE_FREQ_DISTR:
						if(entry_data.hasOwnProperty(value))
							++entry_data[value];
						else
							entry_data[value] = 1;
						break;
				}
			}
		};
		
		for(let container_i = axisContainerArray.length - 1; container_i >= 0; --container_i) {
			let axisContainer = axisContainerArray[container_i];
			
			create_dataFromAxis(axisContainer.yAxis);
			create_dataFromAxis(axisContainer.xAxis);
		}
		
		
		for(let key in statisticsObj) {
			if(!statisticsObj.hasOwnProperty(key))
				continue;
			let a = statisticsObj[key];
			for(let i=a.length-1; i>=0; --i) {
				let o = a[i];
				if(o)
					o["entryCount"] = Object.keys(o.data).length;
			}
		}
		return statisticsObj;
	},
	get_valueCount: function(columnNum, values) {
		if(!this.is_indexed)
			this.index();
		let index = this.valueIndex[columnNum];
		let r = {};
		for(let i=values.length-1; i>=0; --i) {
			let key = values[i];
			r[key] = index.hasOwnProperty(key) ? index[key].cells.length : 0;
		}
		return r;
	},
	
	set_rowVisibility: function(row, visible) {
		if(visible) {
			if(!row.visible && !--row.hidden_sum) { //if it is already visible we do nothing
				++this.rows_count;
				row.visible = true;
			}
		}
		else {
			if(++row.hidden_sum === 1) {
				--this.rows_count;
				row.visible = false;
			}
		}
	},
	reset_visibleFilter: function() {
		this.visible_rowsIndex = [];
		
		for(let i=this.visible_valueIndex.length-1; i>=0; --i) {
			this.visible_valueIndex[i] = {};
		}
	},
	
	mark: function(enable, rowPos) {
		this.rowsIndex[rowPos].marked = enable;
	},
	
	filter: function(visible, columnNum, value) {
		if(!this.is_indexed)
			this.index();
		if(this.valueIndex[columnNum].hasOwnProperty(value)) {
			let key_index = this.valueIndex[columnNum][value];
			
			if(key_index.meta.visible === visible)
				return;
			key_index.meta.visible = visible;
			
			let cells = key_index.cells;
			for(let i = cells.length - 1; i >= 0; --i) {
				this.set_rowVisibility(cells[i].row, visible);
			}
		}
		this.reset_visibleFilter();
		
		//keep track for reset()
		if(visible) {
			if(this.filteredColumnsIndex.hasOwnProperty(columnNum)) {
				if(this.filteredColumnsIndex[columnNum].hasOwnProperty(value)) {
					delete this.filteredColumnsIndex[columnNum][value];
					--this.filteredColumnsIndex[columnNum]["~"];
				}
				if(!this.filteredColumnsIndex[columnNum]["~"])
					delete this.filteredColumnsIndex[columnNum];
			}
		}
		else {
			if(!this.filteredColumnsIndex.hasOwnProperty(columnNum))
				this.filteredColumnsIndex[columnNum] = {"~":0};
			this.filteredColumnsIndex[columnNum][value] = true;
			++this.filteredColumnsIndex[columnNum]["~"];
		}
		
		this.needs_index = true;
	},
	filter_column: function(visible, columnNum) {
		if(!this.is_indexed)
			this.index();
		let index = this.valueIndex[columnNum];
		for(let key in index) {
			if(index.hasOwnProperty(key))
				this.filter(visible, columnNum, key);
		}
		this.needs_index = true;
	},
	filter_rowsByResponseTime: function(visible, newestTimestamp) {
		let columnIndex = this.get_columnNum("responseTime");
		
		if(!visible && this.filteredRowsIndex.hasOwnProperty(columnIndex)) //we dont want to filter the same row twice
			return;
		
		let rows = this.visible_rowsIndex;
		for(let i = rows.length - 1; i >= 0; --i) {
			let row = rows[i];
			let cells = row.columnCells;
			
			if(cells.length <= columnIndex) //if there was an error in dataset row[0][columnIndex] can be undefined
				this.set_rowVisibility(row, false);
			else if(cells[columnIndex].real_value < newestTimestamp)
				this.set_rowVisibility(row, visible);
		}
		this.reset_visibleFilter();
		
		
		//keep track for reset()
		if(visible) {
			if(this.filteredRowsIndex.hasOwnProperty(columnIndex))
				delete this.filteredRowsIndex[columnIndex];
		}
		else
			this.filteredRowsIndex[columnIndex] = true;
		this.needs_index = true;
	}
};

fillDefaults();
onmessage = function(event) {
	let data = event.data;
	let id = data.id;
	
	let returnObj = {id: id};
	switch(data.type) {
		case "load":
			CsvData.translations = data.translations;
			CsvData.loadData(data.url)
				.then(function() {
					returnObj.rows_count = CsvData.rows_count;
					returnObj.header_names = CsvData.header_names;
					returnObj.timestamp_columns_nameIndex = CsvData.timestamp_columns_nameIndex;
					postMessage(returnObj);
				})
				.catch(function(error) {
					console.log(error);
					returnObj.error = error;
					postMessage(returnObj);
				});
			return;
		case "fromCsv":
			CsvData.translations = data.translations;
			CsvData.loadCsv(data.csv);
			
			returnObj.rows_count = CsvData.rows_count;
			returnObj.header_names = CsvData.header_names;
			returnObj.timestamp_columns_nameIndex = CsvData.timestamp_columns_nameIndex;
			break;
		case "reset":
			CsvData.reset();
			break;
		case "get_visible":
			returnObj.lines = CsvData.get_visibleRows(parseInt(data.from), parseInt(data.to));
			break;
		case "valueIndex":
			returnObj.valueIndex = CsvData.get_valueIndex(parseInt(data.columnIndex));
			break;
		case "valueList":
			returnObj.valueList = CsvData.get_valueList(parseInt(data.columnIndex), !!data.sortByAmount, data.alsoInvisible);
			break;
		case "mark":
			CsvData.mark(data.enable, parseInt(data.rowPos));
			break;
		case "filter":
			CsvData.filter(data.enable, parseInt(data.columnIndex), data.value);
			returnObj.rows_count = CsvData.rows_count;
			break;
		case "filter_column":
			CsvData.filter_column(data.enable, parseInt(data.columnIndex));
			returnObj.rows_count = CsvData.rows_count;
			break;
		case "filter_rowsByResponseTime":
			CsvData.filter_rowsByResponseTime(data.enable, parseInt(data.newestTimestamp));
			returnObj.rows_count = CsvData.rows_count;
			break;
		case "get_statistics":
			returnObj.statistics = CsvData.get_dataAsStatistics(data.axisContainer, data.dataType);
			break;
		case "get_valueCount":
			returnObj.valueCount = CsvData.get_valueCount(parseInt(data.columnIndex), data.values);
			break;
		default:
			returnObj.error = "Unknown error";
			break;
	}
	postMessage(returnObj);
}
