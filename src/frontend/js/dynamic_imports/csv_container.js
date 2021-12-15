import {Lang} from "../main_classes/lang";
import {OwnMapping} from "../helpers/knockout_own_mapping";

export function CsvLoader(url, page) {
	let loader = new CsvContainer(page);
	loader.loadUrl(url);
	return loader;
}
export function CsvCreator(csv, page) {
	let loader = new CsvContainer(page);
	loader.loadCsv(csv);
	return loader;
}

function CsvContainer(page) {
	let self = this;
	
	this.header_names = [];
	this.rows_count = 0;
	
	let queueCount = 0;
	let resolveQueue = {};
	let actions = Promise.resolve();
	
	let timestamp_columns_nameIndex;
	
	
	//
	// init
	//
	let csvWorker = new Worker(new URL('../dynamic_imports/csv_worker.js', import.meta.url));
	csvWorker.onmessage = function(e) {
		let response = e.data;
		
		if(response.loadingState)
			page.loader.update(Lang.get("state_loading_entryNum", response.loadingState));
		if(response.indexingState)
			page.loader.update(Lang.get("state_creatingIndex_entryNum", response.indexingState));
		
		else {
			let id = response.id;
			if(resolveQueue.hasOwnProperty(id)) {
				if(response.hasOwnProperty("error"))
					resolveQueue[id].error(response.error);
				else
					resolveQueue[id].success(response);
				delete resolveQueue[id];
			}
		}
	}
	
	let addPromise = function(data, state) {
		actions = page.loader.showLoader(Lang.get(state), actions
			.then(function() {
				return new Promise(function(resolve, reject) {
					resolveQueue[++queueCount] = {success: resolve, error: reject};
					data = data || {};
					data.id = queueCount;
					csvWorker.postMessage(data);
				})
			})
			.catch(function(error) {
				console.trace(error);
			}));
		
		return actions;
	}
	
	let load = function(obj) {
		obj.translations = {
				empty_dataSymbol: Lang.get("empty_dataSymbol"),
				colon_timestamp: Lang.get("colon_timestamp")
			};
		addPromise(obj, "state_downloading")
			.then(function(data) {
				self.header_names = data.header_names;
				self.rows_count = data.rows_count;
				timestamp_columns_nameIndex = data.timestamp_columns_nameIndex;
			});
	};
	
	
	//
	// load functions
	//
	
	this.loadUrl = function(url) {
		load({
			type: "load",
			url: location.origin + location.pathname + url,
		});
	};
	this.loadCsv = function(csv) {
		load({
			type: "fromCsv",
			csv: csv,
		});
	};
	
	this.waitUntilReady = function() {
		return actions;
	};
	this.reset = function() {
		return addPromise({type: "reset"}, "state_loading");
	};
	
	this.close = function() {
		csvWorker.terminate();
	}
	
	//
	// convenience functions
	//
	this.get_columnNum = function(column) {
		if(isNaN(column)) {
			let r = self.header_names.indexOf(column);
			if(r === -1)
				console.trace(column + " does not exist in get_columnNum()", self.header_names);
			return r;
		}
		else
			return column;
	};
	
	//
	// data functions
	//
	
	this.get_visibleRows = function(from, to) {
		return addPromise({type: "get_visible", from: from, to: to}, "state_loading").then(function(data) {
			return data.lines;
		});
	};
	this.get_valueIndex = function(columnName) {
		return addPromise(
			{type: "valueIndex", columnIndex: self.get_columnNum(columnName)},
			"state_creatingIndex"
		).then(function(data) {
			return data.valueIndex;
		});
	};
	this.get_valueList = function(columnName, sortByAmount, alsoInvisible) {
		return addPromise(
			{type: "valueList", columnIndex: self.get_columnNum(columnName), sortByAmount: sortByAmount, alsoInvisible: alsoInvisible},
			"state_creatingIndex"
		).then(function(data) {
			return data.valueList;
		});
	};
	this.get_statistics = function(axisContainer, dataType) {
		return addPromise(
			{type: "get_statistics", axisContainer: OwnMapping.toJS(axisContainer), dataType: dataType},
			"state_creatingStatistics"
		).then(function(data) {
			return data.statistics;
		});
	};
	this.get_valueCount = function(columnName, values) {
		return addPromise(
			{type: "get_valueCount", columnIndex: self.get_columnNum(columnName), values: values},
			"state_loading"
		).then(function(data) {
			return data.valueCount;
		});
	};
	
	//
	// change functions
	//
	
	this.filter = function(enable, column, value) {
		let columnIndex = this.get_columnNum(column);
		if(columnIndex === -1) {
			console.error(column + " does not exist. Aborting");
			return;
		}
		return addPromise({type: "filter", columnIndex: columnIndex, value: value, enable: enable}, "state_applyingFilter").then(function(data) {
			self.rows_count = data.rows_count;
		});
	};
	this.filter_column = function(enable, column) {
		let columnIndex = this.get_columnNum(column);
		if(columnIndex === -1) {
			console.error(column + " does not exist. Aborting");
			return;
		}
		return addPromise({type: "filter_column", columnIndex: columnIndex, enable: enable}, "state_applyingFilter").then(function(data) {
			self.rows_count = data.rows_count;
		});
	};
	this.filter_rowsByResponseTime = function(enable, newestTimestamp) {
		return addPromise({type: "filter_rowsByResponseTime", newestTimestamp: newestTimestamp, enable: enable}, "state_applyingFilter").then(function(data) {
			self.rows_count = data.rows_count;
		});
	};
	this.mark = function(enable, rowPos) {
		return addPromise({type: "mark", enable: enable, rowPos: rowPos}, "state_loading");
	};
	
	//
	// data functions
	//
	this.is_timestampColumn = function(column_value) {
		return timestamp_columns_nameIndex[column_value];
	};
}