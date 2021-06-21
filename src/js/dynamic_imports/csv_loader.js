import Papa from 'papaparse';
import {Lang} from "../main_classes/lang";
import {CSV_DELIMITER, DATAVIEWER_SKIPPED_COLUMNS, DATAVIEWER_TIMESTAMP_COLUMNS} from "../variables/csv";




export function get_fromData(csvData) {
	return Promise.resolve(new CSV_loader(csvData));
}
export function get_fromUrl(url) {
	let promise = new Promise(function(complete, error) {
		Papa.parse(url + "?" + Date.now(), {
			// worker: true,
			download: true,
			delimiter: CSV_DELIMITER,
			complete: complete,
			error: error
		});
	});
	
	return promise.then(function(csvData) {
		return new CSV_loader(csvData);
	});
}

function CSV_loader(csvData) {
	this.header_names = null;
	this.rows_count = 0;
	
	let self = this,
		is_indexed = false,
		
		timestamp_columns_numIndex = [],
		timestamp_columns_nameIndex = {},
		skipped_index = [],
		valueIndex = [],
		rowsIndex = [],
		visible_rowsIndex = [],
		visible_valueIndex = [],
		
		filteredColumnsIndex = {},
		filteredRowsIndex = {},
		not_indexed_data,
		
		index_row = function(raw_row, index) {
			//Note: entries are ordered in reverse. This means that index does NOT equal pos
			let header_names = self.header_names;
			let columns = [];
			let row_data = [columns, {hidden_sum: 0, pos: rowsIndex.length, visible: true}];
			for(let column_i=0, i=0, max=raw_row.length; i<max; ++i) {
				if(skipped_index[i])
					continue;
				
				let set = {index: index, row: row_data};
				if(timestamp_columns_numIndex[i]) {
					let timestamp = parseInt(raw_row[i]);
					if(!timestamp) {
						set.value = Lang.get("empty_dataSymbol");
						timestamp = "";
					}
					else if(timestamp > 32532447600)//test if timestamp is in ms or seconds. NOTE: In the year 3000 when ducks have taken over the world, this code will stop working!!
						set.value = (new Date(timestamp)).toLocaleString();
					else
						set.value = (new Date(timestamp * 1000)).toLocaleString();
					
					set.title = header_names[column_i]+"\n"+Lang.get("colon_timestamp")+" "+timestamp;
					set.real_value = timestamp;
					set.special = true;
				}
				else if(!raw_row[i].length) {
					set.value = Lang.get("empty_dataSymbol");
					set.special = true;
					set.real_value = "";
					set.title = header_names[column_i];
				}
				else {
					set.value = raw_row[i];
					set.title = header_names[column_i];
				}
				
				
				let column_index = valueIndex[column_i];
				let visible_column_index = visible_valueIndex[column_i];
				
				let column_value = set.value;
				if(column_index) { //this will only be false when datasets.php has been changed after the csv was created and data has more columns than the header line
					if(!column_index.hasOwnProperty(column_value)) {
						column_index[column_value] = [[set], {visible: true}];
						visible_column_index[column_value] = [set];
					}
					else {
						column_index[column_value][0].push(set);
						
						visible_column_index[column_value].push(set);
					}
				}
				columns.push(set); //we need to use push because of skipped_index, i can be wrong
				++column_i;
			}
			rowsIndex.push(row_data);
			visible_rowsIndex.push(row_data);
		},
		set_rowVisibility = function(row, visible) {
			let state = row[1];
			if(visible) {
				if(!state.visible && !--state.hidden_sum) { //if it is already visible we do nothing
					++self.rows_count;
					state.visible = true;
				}
			}
			else {
				if(++state.hidden_sum === 1) {
					--self.rows_count;
					state.visible = false;
				}
			}
		},
		reset_visibleFilter = function() {
			visible_rowsIndex = [];
			
			for(let i=visible_valueIndex.length-1; i>=0; --i) {
				visible_valueIndex[i] = {};
			}
		};
	
	//This function is prepared for using a Web Worker.
	//But we would have to serialize very big objects and so far it does not seem that an additional thread is needed.
	//So actually everything is done in the main thread synchronously
	
	this.index_data_async = function(until) {
		return new Promise(function(resolve) {
			until = until || self.rows_count-1;
			if(is_indexed) {
				if(until >= visible_rowsIndex.length) {
					//Note: if a filter happened, then reset_visibleFilter() should have been called and visible_rowsIndex is empty
					// if not, we continue an index-action from before
					for(let length = visible_rowsIndex.length, j = length ? visible_rowsIndex[length - 1][1].pos + 1 : 0; length <= until; ++j) {
						if(!rowsIndex[j][1].hidden_sum) {
							let row_data = rowsIndex[j];
							visible_rowsIndex.push(row_data);
							
							let columns = row_data[0];
							for(let column_i=visible_valueIndex.length-1; column_i>=0; --column_i) {
								let column = columns[column_i];
								if(!column) //this can happen in old datasets because of an old bug
									continue;
								let value = columns[column_i].value;
								if(visible_valueIndex[column_i].hasOwnProperty(value))
									visible_valueIndex[column_i][value].push(row_data);
								else
									visible_valueIndex[column_i][value] = [row_data];
							}
							++length;
						}
					}
				}
			}
			else {
				let count = until - (visible_rowsIndex.length-1);
				if(count > 0) {
					let index_count = not_indexed_data.length;
					let part = index_count > count ? not_indexed_data.splice(index_count - count, count) : not_indexed_data.splice(0, index_count);
					let index_start = not_indexed_data.length - (not_indexed_data.length - index_count) - part.length;
					
					for(let i = part.length - 1; i >= 0; --i) {
						index_row(part[i], index_start + i);
					}
					
					is_indexed = not_indexed_data.length === 0;
				}
			}
			resolve(self);
		});
	};
	
	this.set_columnVisibility = function(column, visible) {
		if(column === -1) {
			console.warn(column + " does not exist. Aborting set_columnVisibility()");
			return;
		}
		
		let index = valueIndex[column];
		for(let key in index) {
			if(index.hasOwnProperty(key))
				self.filter_column(visible, column, key);
		}
	};
	this.filter_column = function(visible, column, search_key) {
		let columnNum = self.get_columnNum(column);
		if(columnNum === -1) {
			console.warn(column + " does not exist. Aborting filter_column()");
			return;
		}
		if(valueIndex[columnNum].hasOwnProperty(search_key)) {
			let key_index = valueIndex[columnNum][search_key];
			
			if(visible === key_index[1].visible)
				return;
			key_index[1].visible = visible;
			
			let rows = key_index[0];
			for(let i = rows.length - 1; i >= 0; --i) {
				set_rowVisibility(rows[i].row, visible);
			}
		}
		reset_visibleFilter();
		
		//keep track for reset()
		if(visible) {
			if(filteredColumnsIndex.hasOwnProperty(columnNum)) {
				if(filteredColumnsIndex[columnNum].hasOwnProperty(search_key)) {
					delete filteredColumnsIndex[columnNum][search_key];
					--filteredColumnsIndex[columnNum]["~"];
				}
				if(!filteredColumnsIndex[columnNum]["~"])
					delete filteredColumnsIndex[columnNum];
			}
		}
		else {
			if(!filteredColumnsIndex.hasOwnProperty(columnNum))
				filteredColumnsIndex[columnNum] = {"~":0};
			filteredColumnsIndex[columnNum][search_key] = true;
			++filteredColumnsIndex[columnNum]["~"];
		}
	};
	this.filter_rows = function(visible, column, filterThisRow_fu) {
		if(!visible && filteredRowsIndex.hasOwnProperty(column)) //we dont want to filter the same row twice
			return;
		
		let columnIndex = self.get_columnNum(column);
		let rows = self.get_visible_rows();
		for(let i = rows.length - 1; i >= 0; --i) {
			let row = rows[i];
			
			if(row[0].length <= columnIndex) //if there was an error in dataset row[0][columnIndex] can be undefined
				set_rowVisibility(row, false);
			else if(filterThisRow_fu(row[0][columnIndex].real_value)) {
				set_rowVisibility(row, visible);
			}
		}
		reset_visibleFilter();
		
		
		//keep track for reset()
		if(visible) {
			if(filteredRowsIndex.hasOwnProperty(column)) {
				delete filteredRowsIndex[column];
			}
		}
		else {
			filteredRowsIndex[column] = true;
		}
	};
	
	this.reset = function() {
		if(is_indexed) {
			let needsCompleteReset = false;
			for(let column in filteredRowsIndex) {
				if(filteredRowsIndex.hasOwnProperty(column)) {
					needsCompleteReset = true;
					break;
				}
			}
			
			if(needsCompleteReset) {
				let rows = self.get_rows();
				for(let i = rows.length - 1; i >= 0; --i) {
					let row = rows[i];
					let state = row[1];
					if(!state.visible) { //if it is already visible we do nothing
						++self.rows_count;
						state.hidden_sum = 0;
						state.visible = true;
					}
					// set_rowVisibility(row, true);
				}
				for(let column in filteredColumnsIndex) {
					if(filteredColumnsIndex.hasOwnProperty(column)) {
						let columns = filteredColumnsIndex[column];
						for(let search_key in columns) {
							if(columns.hasOwnProperty(search_key) && search_key !== "~") {
								let columnNum = self.get_columnNum(column);
								let index = valueIndex[columnNum];
								if(index.hasOwnProperty(search_key))
									index[search_key][1].visible = true;
							}
						}
					}
				}
			} else {
				for(let column in filteredColumnsIndex) {
					if(filteredColumnsIndex.hasOwnProperty(column)) {
						let columns = filteredColumnsIndex[column];
						for(let search_key in columns) {
							if(columns.hasOwnProperty(search_key) && search_key !== "~") {
								self.filter_column(true, column, search_key);
							}
						}
					}
				}
			}
			
			filteredRowsIndex = {};
			filteredColumnsIndex = {};
			
			reset_visibleFilter();
			return self.index_data_async(false);
		}
		else
			return Promise.resolve(this);
	};
	
	this.get_column_valueList = function(column, sortFu) {
		column = self.get_columnNum(column);
		let keys = Object.keys(valueIndex[column]);
		if(sortFu)
			keys.sort(sortFu);
		else
			keys.sort();
		
		return keys;
	};
	this.get_columnIndex = function(column) {
		column = self.get_columnNum(column);
		return valueIndex[column];
	};
	this.get_visible_columnIndex = function(column) {
		column = self.get_columnNum(column);
		return visible_valueIndex[column];
	};
	this.get_rows = function() {
		return rowsIndex;
	};
	this.get_visible_rows = function() {
		return visible_rowsIndex;
	};
	this.get_columnNum = function(column) {
		if(isNaN(column)) {
			let r = self.header_names.indexOf(column);
			if(r === -1) {
				console.warn(column + " does not exist in get_columnNum()");
				console.trace();
			}
			return r;
		}
		else
			return column;
	};
	
	this.is_timestampColumn = function(column_value) {
		return isNaN(column_value) ? timestamp_columns_nameIndex[column_value] : timestamp_columns_numIndex[column_value];
	};
	
	
	this.rows_count = csvData.data.length - 1;
	let header_names = csvData.data.splice(0, 1)[0];
	
	this.header_names = [];
	for(let column_i = 0, i = 0, max = header_names.length; i < max; ++i) {
		// for(let i=header_names.length-1; i>=0; --i) {
		let column_value = header_names[i];
		
		if(DATAVIEWER_TIMESTAMP_COLUMNS.indexOf(column_value) !== -1) {
			timestamp_columns_numIndex[i] = true;
			timestamp_columns_nameIndex[column_value] = true;
		}
		else if(DATAVIEWER_SKIPPED_COLUMNS.indexOf(column_value) !== -1) {
			skipped_index[i] = true;
			continue;
		}
		valueIndex[column_i] = {};
		visible_valueIndex[column_i] = {};
		this.header_names.push(column_value);
		++column_i; //because of DATAVIEWER_SKIPPED_COLUMNS column_i may not be the same as i
	}
	
	
	not_indexed_data = csvData.data;
}