import ko from "knockout";


let copyArray = function(a) {
	let r = [];
	for(let i=0, max=a.length; i<max; ++i) {
		let value = a[i];
		r.push(typeof value === "function" ? value() : value);
	}
	return r;
}
let compareArray = function(a, originalArray) {
	if(originalArray.length !== a.length)
		return true;
	for(let i=0, max=originalArray.length; i<max; ++i) {
		let value = a[i];
		if(originalArray[i] !== (typeof value === "function" ? value() : value))
			return true;
	}
	return false;
}
let _uniqueIdCounter = 100;
let getUniqueId = function() {
	return ++_uniqueIdCounter;
}

export const OwnMapping = {
	indexOf: function(obj, s) {
		for(let i=obj.length-1; i>=0; --i) {
			if(obj[i]() === s)
				return i;
		}
		return -1;
	},
	_observableArray: function(a, defaultValue, translationId) {
		let obs = ko.observableArray(a);
		let originalArray = copyArray(a);
		obs.___defaultValue = defaultValue || [];
		obs.___isDirty = function() {
			return compareArray(a, originalArray);
		}
		obs.___unsetDirty = function() {
			originalArray = copyArray(a);
		};
		if(translationId)
			obs.___translationId = translationId;
		let self = this;
		obs.indexOf = function(s) {return self.indexOf(this(), s);};
		return obs;
	},
	
	_observableValue: function(value, defaultValue, translationId) {
		let obs = ko.observable(value);
		this._toOwnObservableValue(obs, value, defaultValue, translationId);
		return obs;
	},
	_toOwnObservableValue: function(obs, value, defaultValue, translationId) {
		if(!obs.hasOwnProperty("___defaultValue")) {
			obs.___defaultValue = defaultValue;
			obs.___isDirty = function() {
				return obs() !== value;
			}
			obs.___unsetDirty = function() {
				value = obs();
			}
			if(translationId)
				obs.___translationId = translationId;
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
		
		
		// non iterable:
		if(type !== "object")
			return this._observableValue(value, defaultObj);
		
		// array:
		else if(Array.isArray(value)) {
			let a = [];
			for(let i=0, max=value.length; i<max; ++i) {
				//its entries should only have a default if it is a container:
				a.push(this.fromJS(value[i], (defaultObj && (typeof (defaultObj[i])) === "object") ? defaultObj[i] : undefined));
			}
			return this._observableArray(a, defaultObj);
		}
		
		
		let r = {};
		
		// object, but no default information:
		if(!defaultObj) {
			for(let key in value) {
				if(value.hasOwnProperty(key)) {
					r[key] = this.fromJS(value[key]);
				}
			}
			return r;
		}
		
		//loop all normal values
		for(let key in defaultObj) {
			if(!defaultObj.hasOwnProperty(key) || key === "$")
				continue;
			
			let newDefaultObj = defaultObj[key];
			
			r[key] = value.hasOwnProperty(key)
				? this.fromJS(value[key], newDefaultObj)
				: this.fromJS(newDefaultObj, newDefaultObj); //the new value may have $... values from Defaults. But they will be filtered anyway
		}
		
		if(!defaultObj.hasOwnProperty("$"))
			return r;
		
		// handle special $ value:
		if(defaultObj.$.hasOwnProperty("children")) {
			let children = defaultObj.$.children;
			for(let realKey in children) {
				if(!children.hasOwnProperty(realKey))
					continue;
				
				if(value.hasOwnProperty(realKey)) {
					//all entries need to use the same default:
					let a = [];
					let entries = value[realKey];
					let newDefault = children[realKey];
					for(let i=0, max=entries.length; i<max; ++i) {
						a.push(this.fromJS(entries[i], newDefault));
					}
					r[realKey] = this._observableArray(a);
				}
				else
					r[realKey] = this._observableArray([]);
			}
		}
		if(defaultObj.$.hasOwnProperty("translated")) {
			let defaultTranslations = defaultObj.$.translated;
			for(let realKey in defaultTranslations) {
				if(!defaultTranslations.hasOwnProperty(realKey))
					continue;
				
				let newValue = value.hasOwnProperty(realKey) ? value[realKey] : defaultTranslations[realKey];
				let defaultValue = defaultTranslations[realKey];
				if(Array.isArray(defaultValue)) {
					let obs = this.fromJS(newValue, defaultTranslations[realKey]); // this will be an _observableArray filled with observables
					obs.___translationId = getUniqueId();
					r[realKey] = obs;
				}
				else
					r[realKey] = this._observableValue(newValue, defaultTranslations[realKey], getUniqueId());
			}
		}
		
		return r;
	},
	
	bindNewLanguageContainer: function(obs, translationJs, translations) {
		// Assumption: objs has the same structure as translationJs
		// So translatable values are ordered the same
		
		translations = translations || {};
		let value = typeof obs === "function" ? obs() : obs;
		if(typeof value !== "object")
			return;
		
		if(Array.isArray(value)) {
			for(let i=0, max=value.length; i<max; ++i) {
				this.bindNewLanguageContainer(value[i], translationJs ? translationJs[i] : null, translations);
			}
			return;
		}
		
		for(let key in value) {
			if(!value.hasOwnProperty(key))
				continue
			
			let currentObs = value[key];
			
			if(!currentObs.hasOwnProperty("___translationId")) {
				this.bindNewLanguageContainer(value[key], translationJs ? translationJs[key] : null, translations);
				continue;
			}
			
			let id = currentObs.___translationId;
			let translatedValue = translationJs && translationJs.hasOwnProperty(key)
				? translationJs[key]
				: currentObs.___defaultValue
			
			if(Array.isArray(currentObs())) {
				let obsArray = this.fromJS(translatedValue || [], currentObs.___defaultValue || []); // this will be an _observableArray filled with observables
				obsArray.___translationId = id;
				translations[id] = obsArray;
			}
			else
				translations[id] = this._observableValue(translatedValue || "", currentObs.___defaultValue || "", id);
			
			// translations[id] = Array.isArray(currentObs)
			// 	? this._observableArray(translatedValue || [], currentObs.___defaultValue || [], id)
			// 	: this._observableValue(translatedValue || "", currentObs.___defaultValue || "", id);
		}
		return translations;
	},
	
	switchLanguage(obs, fromLanguageContainer, toLanguageContainer) {
		// Rationale:
		// loop obs. When encountering a translated entry, we:
		// - check if its in fromLanguageContainer. If not, copy the observable over (only happens, when a new element was added or default lang was not initialized yet)
		// - check if its in toLanguageContainer. If not, we create an empty observable (only happens, when a new element was added or lang is new)
		// - copy the observable of target lang into obs
		
		let checkTranslation = function(currentObs, parent, key) {
			if(!currentObs.hasOwnProperty("___translationId"))
				return;
			
			let id = currentObs.___translationId;
			if(fromLanguageContainer && !fromLanguageContainer.hasOwnProperty(id))
				fromLanguageContainer[id] = currentObs;
			
			if(!toLanguageContainer.hasOwnProperty(id)) {
				toLanguageContainer[id] = Array.isArray(currentObs)
					? self._observableArray([], currentObs.___defaultValue || [], id)
					: self._observableValue(currentObs() || "", currentObs.___defaultValue || "", id);
			}
			parent[key] = toLanguageContainer[id];
			
		}
		
		let self = this;
		this.loopAll(obs, checkTranslation, checkTranslation);
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
					else if(defaultObj.hasOwnProperty("$") && defaultObj.$.hasOwnProperty("translated") && defaultObj.$.translated.hasOwnProperty(key)) //is a translated array
						this.update(realValue, value, defaultObj.$.translated[key]);
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
					obs(value);
				}
				else { //lang-objects are always set after OwnMapping.fromJS
					old_obj[key] = this.fromJS(value, defaultObj[key]); //this will return an observable with a ___defaultValue
				}
			}
		}
		
		//there is no need to delete unneeded properties in old_obj since they will be filtered and it will happen rarely anyway
	},
	
	loopAll: function(obj, valueFu, arrayFu, currentParent, currentKey) {
		let value = typeof obj === "function" ? obj() : obj;
		
		if(typeof value !== "object")
			return valueFu(obj, currentParent, currentKey);
		
		if(Array.isArray(value)) {
			for(let i=0, max=value.length; i<max; ++i) {
				this.loopAll(value[i], valueFu, arrayFu, value, i);
			}
			if(arrayFu)
				arrayFu(obj, currentParent, currentKey);
			return;
		}
		
		for(let key in value) {
			if(!value.hasOwnProperty(key))
				continue;
			
			this.loopAll(value[key], valueFu, arrayFu, value, key);
		}
	},
	subscribe: function(obj, changedFu) {
		let dirtyObj = ko.observable(false);
		let self = this;
		let subscriptions = []; //used to keep track how many variables are dirty (and when dirty can be false again)
		let dirtyTrack = {};
		let subscribeFu = function(currentObj) {
			self._toOwnObservableValue(currentObj); //in case we added new elements as ko.observable() instead of _observableValue()
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
		
		
		return {
			dispose: function() {
				for(let i=subscriptions.length-1; i>=0; --i) {
					subscriptions[i].dispose();
				}
			},
			isDirty: dirtyObj
		};
		// return subscriptions;
	},
	unsetDirty: function(obj) {
		let self = this;
		let unsetFu = function(currentObj) {
			self._toOwnObservableValue(currentObj); //in case we added new elements as ko.observable() instead of _observableValue()
			currentObj.___unsetDirty();
			currentObj.notifySubscribers(currentObj());
		};
		this.loopAll(obj, unsetFu, unsetFu);
	},
	
	toLangJs: function(obj, code) {
		return this.loopAll(obj, function(currentObj) {
			let value = currentObj.hasOwnProperty("___getLang") ? currentObj.___getLang(code) : currentObj();
			
			return value !== currentObj.___defaultValue ? value : undefined; //by setting it to undefined, we effectively strip the whole variable away
		});
	},
	toJS: function(obj) {
		let value = typeof obj === "function" ? obj() : obj;
		
		if(typeof value !== "object")
			return (value !== obj.___defaultValue) ? value : undefined; //by setting it to undefined, we effectively strip the whole variable away
		
		if(Array.isArray(value)) {
			let a = [];
			for(let i=0, max=value.length; i<max; ++i) {
				let v = this.toJS(value[i], value, i);
				if(v !== undefined)
					a.push(v);
			}
			if(!a.length && value.length) // if all entries were default values then they were stripped while creating the array - leaving n empty array
				return obj.___defaultValue;
			return a;
		}
		
		let o = {};
		for(let key in value) {
			if(!value.hasOwnProperty(key))
				continue;
			
			let v = this.toJS(value[key], value, key);
			if(v !== undefined)
				o[key] = v;
		}
		return o;
	},
	// toJS: function(obj) {
	// 	let sanitizeValue = function(currentObj) {
	// 		return (currentObj() !== currentObj.___defaultValue) ? currentObj() : undefined; //by setting it to undefined, we effectively strip the whole variable away
	// 	};
	// 	return this.loopAll(obj, sanitizeValue);
	// },
	toJSON: function(obj) {
		let a = this.toJS(obj);
		return JSON.stringify(a);
	}
};