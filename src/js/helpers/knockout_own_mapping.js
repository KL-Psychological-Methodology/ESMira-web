import ko from "knockout"

export const OwnMapping = {
	indexOf: function(obj, s) {
		for(let i=obj.length-1; i>=0; --i) {
			if(obj[i]() === s)
				return i;
		}
		return -1;
	},
	_observableArray: function(a) {
		let oa = ko.observableArray(a);
		let self = this;
		oa.indexOf = function(s) {return self.indexOf(this(), s)};
		return oa;
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
					if(defaultObj.hasOwnProperty(key)) {
						let isArray = false,
							newDefaultObj;
						switch(key.charAt(0)) {
							case '$': //is an array where all entries need to be checked against parent defaultObj
								newDefaultObj = defaultObj[key];
								// newDefaultObj = {$: defaultObj[key]};
								key = key.substring(1);
								isArray = true;
								break;
							case '_': //should not be checked against defaults
								key = key.substring(1);
								newDefaultObj = undefined;
								break;
							default:
								newDefaultObj = defaultObj[key];
								break;
						}
						
						
						if(value.hasOwnProperty(key))
							r[key] = this.fromJS(value[key], newDefaultObj);
						else if(isArray)
							r[key] = this._observableArray([], newDefaultObj);
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
		else {
			let obj = ko.observable(value);
			obj.___defaultValue = defaultObj;
			return obj;
		}
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
					else if(defaultObj.hasOwnProperty("$"+key)) //all array entries need to be checked against this defaultObj
						this.update(realValue, value, defaultObj["$"+key], true);
					else
						this.update(realValue, value, defaultObj[key]);
					
					if(Array.isArray(value)) {
						if(realValue.length > value.length)
							nextValue.splice(value.length)
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
					if(old_obj[key]() !== value)
						old_obj[key](value);
				}
				else
					old_obj[key] = this.fromJS(value, defaultObj[key]); //this will return an observable with a ___defaultValue
			}
		}
		
		//there is no need to delete unneeded properties in old_obj since they will be filtered and it will happen rarely anyway
	},
	
	
	filterObj: function(obj) {
		let value = typeof obj === "function" ? obj() : obj;
		if(typeof value === "object") {
			if(Array.isArray(value)) {
				let a = [];
				for(let i=0, max=value.length; i<max; ++i) {
					let newValue = this.filterObj(value[i]);
					if(newValue !== undefined)
						a.push(newValue);
				}
				return a;
			}
			else {
				let r = {};
				for(let key in value) {
					if(value.hasOwnProperty(key)) {
						let newValue = this.filterObj(value[key]);
						if(newValue !== undefined)
							r[key] = newValue;
					}
				}
				return r;
			}
		}
		else {
			return (value !== obj.___defaultValue) ? value : undefined;
		}
	},
	toJS: function(obj) {
		return this.filterObj(obj);
	},
	toJSON: function(obj) {
		let a = this.filterObj(obj);
		return JSON.stringify(a);
	}
};