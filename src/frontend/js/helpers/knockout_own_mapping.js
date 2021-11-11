import ko from "knockout"
import {Studies} from "../main_classes/studies";

export const OwnMapping = {
	indexOf: function(obj, s) {
		for(let i=obj.length-1; i>=0; --i) {
			if(obj[i]() === s)
				return i;
		}
		return -1;
	},
	_observableArray: function(a) {
		let copyArray = function() {
			let r = [];
			for(let i=0, max=a.length; i<max; ++i) {
				let value = a[i];
				r.push(typeof value === "function" ? value() : value);
			}
			return r;
		}
		let compareArray = function() {
			if(originalArray.length !== a.length)
				return true;
			for(let i=0, max=originalArray.length; i<max; ++i) {
				let value = a[i];
				if(originalArray[i] !== (typeof value === "function" ? value() : value))
					return true;
			}
			return false;
		}
		
		let obs = ko.observableArray(a);
		let originalArray = copyArray();
		obs.___defaultValue = [];
		obs.___isDirty = function() {
			return compareArray();
		}
		obs.___unsetDirty = function() {
			originalArray = copyArray();
		};
		let self = this;
		obs.indexOf = function(s) {return self.indexOf(this(), s);};
		return obs;
	},
	
	_translatableObservableValue: function(value, langCode, defaultValue) {
		let self = this;
		let isArray = Array.isArray(defaultValue);
		let data;
		if(isArray)
			data = {_: this.fromJS(value.hasOwnProperty(langCode) ? value[langCode] : defaultValue[langCode], defaultValue)};
		else
			data = {_: ko.observable(value.hasOwnProperty(langCode) ? value[langCode] : defaultValue)};
		
		let changedValues = {};
		let thisObj = ko.pureComputed({
			read: function() {
				let code = Studies.tools ? Studies.tools.currentLang() : "_";
				return data.hasOwnProperty(code) ? data[code]() : data._();
			},
			write: function(newValue) {
				let code = Studies.tools ? Studies.tools.currentLang() : "_";
				
				let value = data.hasOwnProperty(code) ? data[code] : data._;
				if(!changedValues.hasOwnProperty(code))
					changedValues[code] = value();
				else if(changedValues[code] === newValue)
					delete changedValues[code];
				
				value(newValue);
			}
		});
		thisObj.___defaultValue = defaultValue;
		thisObj.___isDirty = function() {
			for(let k in changedValues) {
				if(changedValues.hasOwnProperty(k))
					return true;
			}
			return false;
		};
		thisObj.___unsetDirty = function() {
			changedValues = {};
		};
		thisObj.___setLang = function(code, newValue) {
			if(!data.hasOwnProperty(code)) {
				if(isArray)
					data[code] = self.fromJS(newValue);
				else
					data[code] = ko.observable(newValue);
			}
			else
				data[code](newValue);
			
			if(Studies.tools && Studies.tools.currentLang() === code) { //the new language needs to be displayed right now
				//the lang was not there before, you _() was used instead. So we tell knockout that _ has changed to force a reread:
				data._.valueHasMutated();
			}
		};
		thisObj.___getLang = function(code) {
			return data.hasOwnProperty(code) ? data[code]() : thisObj.___defaultValue;
		};
		
		if(isArray) { //it is not an observableArray. So we "fake" it by adding all necessary methods:
			let getObs = function() {
				let code = Studies.tools ? Studies.tools.currentLang() : "_";
				return data.hasOwnProperty(code) ? data[code] : data._;
			}
			
			//for a list of methods see: https://knockoutjs.com/documentation/observableArrays.html
			//"remove" breaks things for some reason. But since we dont use it anyway we just dont include it
			let methods = ["valueHasMutated", "slice", "push", "pop", "unshift", "shift", "reverse", "sort", "splice", "sorted", "removeAll", "destroy", "destroyAll"];
			methods.forEach(function(fu) {
				thisObj[fu] = function() {
					let obj = getObs();
					obj[fu].apply(obj, arguments);
				};
			});
			thisObj.indexOf =  function(s) {return self.indexOf(getObs()(), s);};
		}
		
		return thisObj;
	},
	_observableValue: function(value, defaultValue) {
		let obs = ko.observable(value);
		this._toOwnObservableValue(obs, value, defaultValue);
		return obs;
	},
	_toOwnObservableValue: function(obs, value, defaultValue) {
		if(!obs.hasOwnProperty("___defaultValue")) {
			obs.___defaultValue = defaultValue;
			obs.___isDirty = function() {
				return obs() !== value;
			}
			obs.___unsetDirty = function() {
				value = obs();
			}
		}
	},
	fromJS: function(value, defaultObj) {
		let type;
		if(defaultObj !== undefined) {
			type = typeof defaultObj;
			if(typeof value !== type)
				value = defaultObj;
		}
		else
			type = typeof value;
		
		if(type === "object") {
			if(Array.isArray(value)) {
				let a = [];
				// let newDefaultObj = defaultObj ? defaultObj.$ : undefined;
				
				//Note: when defaultObj is an array, it is only meant to replace value in case value is undefined. Here it is unneeded
				// If not then value has object entries that need to be checked further against defaultObj
				let newDefaultObj = Array.isArray(defaultObj) ? undefined : defaultObj;
				for(let i=0, max=value.length; i<max; ++i) {
					a.push(this.fromJS(value[i], newDefaultObj));
				}
				return this._observableArray(a);
			}
			else if(defaultObj) {
				let r = {};
				for(let key in defaultObj) {
					if(!defaultObj.hasOwnProperty(key))
						continue;
					
					if(key === "$") {
						if(defaultObj.$.hasOwnProperty("children")) {
							let children = defaultObj.$.children;
							for(let realKey in children) {
								if(!children.hasOwnProperty(realKey))
									continue;
								
								if(value.hasOwnProperty(realKey))
									r[realKey] = this.fromJS(value[realKey], children[realKey]);
								else
									r[realKey] = this._observableArray([], children[realKey]);
							}
						}
						if(defaultObj.$.hasOwnProperty("translated")) {
							let defaultTranslations = defaultObj.$.translated;
							for(let realKey in defaultTranslations) {
								if(!defaultTranslations.hasOwnProperty(realKey))
									continue;
								
								r[realKey] = this._translatableObservableValue(value, realKey, defaultTranslations[realKey]);
							}
						}
						if(defaultObj.$.hasOwnProperty("noDefault")) {
							let children = defaultObj.$.noDefault;
							for(let realKey in children) {
								if(!children.hasOwnProperty(realKey))
									continue;
								
								r[realKey] = this.fromJS(value.hasOwnProperty(realKey) ? value[realKey] : null);
							}
						}
					}
					else {
						let newDefaultObj = defaultObj[key];
						
						if(value.hasOwnProperty(key))
							r[key] = this.fromJS(value[key], newDefaultObj);
						else
							r[key] = this.fromJS(newDefaultObj, newDefaultObj); //the new value may have $... values from Defaults. But they will be filtered anyway
					}
				}
				
				return r;
			}
			else {
				let r = {};
				for(let key in value) {
					if(value.hasOwnProperty(key)) {
						r[key] = this.fromJS(value[key]);
					}
				}
				return r;
			}
		}
		else
			return this._observableValue(value, defaultObj)
	},
	
	update: function(old_obj, new_obj, defaultObj, isDefaultArray) {
		for(let key in new_obj) {
			if(!new_obj.hasOwnProperty(key))
				continue;
			let value = new_obj[key];
			
			
			if(typeof value === "object") {
				if(old_obj.hasOwnProperty(key)) {
					let nextValue = old_obj[key];
					let realValue = (typeof nextValue === "function") ? nextValue() : nextValue;
					
					if(realValue === undefined) { //nextValue exists, so it has to be a function
						nextValue(this.fromJS(value, defaultObj[key]));
						continue;
					}
					else if(isDefaultArray) //we are in an array where all entries need to be checked against parent defaultObj
						this.update(realValue, value, defaultObj);
					else if(!defaultObj)
						this.update(realValue, value, defaultObj);
					else if(defaultObj.hasOwnProperty("$") && defaultObj.$.hasOwnProperty("children") && defaultObj.$.children.hasOwnProperty(key)) //all array entries need to be checked against this defaultObj
						this.update(realValue, value, defaultObj.$.children[key], true);
					// else if(defaultObj.hasOwnProperty("$"+key)) //all array entries need to be checked against this defaultObj
					// 	this.update(realValue, value, defaultObj["$"+key], true);
					else
						this.update(realValue, value, defaultObj[key]);
					
					if(Array.isArray(value)) {
						if(realValue.length > value.length)
							nextValue.splice(value.length);
						nextValue.valueHasMutated();
					}
				}
				else {
					if(isDefaultArray || !defaultObj) //if defaultObj != false: we are in an array where all entries need to be checked against parent defaultObj
						old_obj[key] = this.fromJS(value, defaultObj);
					else
						old_obj[key] = this.fromJS(value, defaultObj[key]); //this will loop through the obj and add defaults
				}
			}
			else {
				if(old_obj.hasOwnProperty(key)) {
					let obs = old_obj[key];
					if(obs.hasOwnProperty("___setLang"))
						obs.___setLang(value, "_");
					else
						obs(value);
				}
				else //lang-objects are always set after OwnMapping.fromJS
					old_obj[key] = this.fromJS(value, defaultObj[key]); //this will return an observable with a ___defaultValue
			}
		}
		
		//there is no need to delete unneeded properties in old_obj since they will be filtered and it will happen rarely anyway
	},
	
	add_lang: function(obj, defaultObj, langObj, code) {
		if(defaultObj.hasOwnProperty("$")) {
			let $ = defaultObj.$;
			if($.hasOwnProperty("translated")) {
				let defaultTranslations = $.translated;
				for(let key in defaultTranslations) {
					if(!defaultTranslations.hasOwnProperty(key))
						continue;
					
					let value;
					if(langObj && langObj.hasOwnProperty(key))
						value = langObj[key];
					else
						value = defaultTranslations[key];
					obj[key].___setLang(code, value);
				}
			}
			
			if($.hasOwnProperty("children")) {
				let defaultChildren = $.children;
				for(let key in defaultChildren) {
					if(!defaultChildren.hasOwnProperty(key))
						continue;
					let array = obj[key]();
					let langArray = (langObj && langObj.hasOwnProperty(key)) ? langObj[key] : [];
					for(let i = array.length - 1; i >= 0; --i) {
						this.add_lang(array[i], defaultChildren[key], langArray[i], code);
					}
				}
			}
		}
		
	},
	
	
	loopAll: function(obj, valueFu, arrayFu) {
		let value = typeof obj === "function" ? obj() : obj;
		if(typeof value === "object") {
			if(Array.isArray(value)) {
				let a = [];
				for(let i=0, max=value.length; i<max; ++i) {
					let v = this.loopAll(value[i], valueFu, arrayFu);
					if(v !== undefined)
						a.push(v);
				}
				if(arrayFu)
					arrayFu(obj);
				return a;
			}
			else {
				let o = {};
				for(let key in value) {
					if(value.hasOwnProperty(key)) {
						let v = this.loopAll(value[key], valueFu, arrayFu);
						if(v !== undefined)
							o[key] = v;
					}
				}
				return o;
			}
		}
		else
			return valueFu(obj);
	},
	subscribe: function(obj, dirtyObj, changedFu) {
		let self = this;
		let subscriptions = []; //used to keep track how many variables are dirty (and when dirty can be false again)
		let dirtyTrack = {};
		let subscribeFu = function(currentObj) {
			self._toOwnObservableValue(currentObj);
			let index = subscriptions.length;
			subscriptions.push(currentObj.subscribe(function() {
				if(dirtyObj) {
					let dirty = currentObj.___isDirty();
					if(dirty) {
						if(!dirtyTrack.hasOwnProperty(index))
							dirtyTrack[index] = true;
						dirtyObj(true); //dirtyObj could have been changed from the outside. So we set it to true even if it already exists in dirtyTrack
					}
					else {
						if(dirtyTrack.hasOwnProperty(index))
							delete dirtyTrack[index];
						for(let k in dirtyTrack) { //check if dirtyTrack is empty now / this was the last variable that was dirty: if not dirtyObj stays true
							if(dirtyTrack.hasOwnProperty(k))
								return
						}
						dirtyObj(false);
					}
				}
				if(changedFu)
					changedFu();
			}));
		};
		this.loopAll(obj, subscribeFu, subscribeFu);
		return subscriptions;
	},
	unsetDirty: function(obj) {
		let self = this;
		let unsetFu = function(currentObj) {
			self._toOwnObservableValue(currentObj);
			currentObj.___unsetDirty();
		};
		this.loopAll(obj, unsetFu, unsetFu);
	},
	
	// filterObj: function(obj) {
		// let value = typeof obj === "function" ? obj() : obj;
		// if(typeof value === "object") {
		// 	if(Array.isArray(value)) {
		// 		let a = [];
		// 		for(let i=0, max=value.length; i<max; ++i) {
		// 			let newValue = this.filterObj(value[i]);
		// 			if(newValue !== undefined)
		// 				a.push(newValue);
		// 		}
		// 		return a;
		// 	}
		// 	else {
		// 		let r = {};
		// 		for(let key in value) {
		// 			if(value.hasOwnProperty(key)) {
		// 				let newValue = this.filterObj(value[key]);
		// 				if(newValue !== undefined)
		// 					r[key] = newValue;
		// 			}
		// 		}
		// 		return r;
		// 	}
		// }
		// else {
		// 	return (value !== obj.___defaultValue) ? value : undefined;
		// }
	// },
	toLangJs: function(obj, code) {
		return this.loopAll(obj, function(currentObj) {
			let value = currentObj.hasOwnProperty("___getLang") ? currentObj.___getLang(code) : currentObj();
			return value !== currentObj.___defaultValue ? value : undefined;
		});
	},
	toJS: function(obj) {
		return this.loopAll(obj, function(currentObj) {
			return (currentObj() !== currentObj.___defaultValue) ? currentObj() : undefined;
		});
	},
	toJSON: function(obj) {
		let a = this.toJS(obj);
		return JSON.stringify(a);
	}
};