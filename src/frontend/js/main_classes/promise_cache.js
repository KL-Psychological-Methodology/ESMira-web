import {Requests} from "./requests";

let promiseCache = {};

export const PromiseCache = {
	save: function(url, promise) {
		let entry = {
			promise: promise.then(function(response) {
				entry.finished = true;
				return response;
			}).catch(function(e) {
				entry.finished = true;
				self.remove(url);
				throw e;
			}),
			finished: false
		};
		
		promiseCache[url] = entry;
		return entry.promise;
	},
	
	
	load: function(url, saveThen, notJson, type, data) {
		if(promiseCache.hasOwnProperty(url))
			return promiseCache[url].promise;
		else {
			let promise = Requests.load(url, notJson, type, data);
			if(saveThen)
				promise = promise.then(saveThen);
			
			return this.save(url, promise);
		}
	},
	loadJson: function(url, saveThen, type, data) {
		return this.load(url, saveThen, false, type, data);
	},
	loadText: function(url, saveThen, type, data) {
		return this.load(url, saveThen, true, type, data);
	},
	
	getOrNull: function(url) {
		if(promiseCache.hasOwnProperty(url))
			return promiseCache[url].promise;
		else
			return null;
	},
	get: function(url, getPromise_fu) {
		if(promiseCache.hasOwnProperty(url))
			return promiseCache[url].promise;
		else
			return this.save(url, getPromise_fu());
	},
	
	remove: function(url) {
		if(promiseCache.hasOwnProperty(url)) {
			let entry = promiseCache[url];
			if(entry.finished) {
				delete promiseCache[url];
				return true;
			}
			else
				return false;
		}
	}
};
let self = PromiseCache;