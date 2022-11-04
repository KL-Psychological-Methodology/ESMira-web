import {OwnMapping} from "../helpers/knockout_own_mapping";
import ko from "knockout";

export function DetectChange(obj, changedFu) {
	let self = this;
	let monitoredObjects = [obj];
	this.isDirty = ko.observable(false);
	let internalChangedFu = null;
	let subscriptions = [];
	let setSubscriptions = function() {
		for(let i=monitoredObjects.length-1; i>=0; --i) {
			subscriptions.concat(OwnMapping.subscribe(monitoredObjects[i], self.isDirty, internalChangedFu));
		}
	};
	if(changedFu) { //when we only want the dirty state, we dont care if new objects are changed. They are still new anyway (dirty = true)
		internalChangedFu = function() {
			changedFu();
			//in case a new element was added to obj, we need to readd subscriptions:
			self.destroy();
			setSubscriptions();
		}
	}
	setSubscriptions();
	
	this.setDirty = function(state) {
		if(this.isDirty() === state)
			return
		// No matter if state is true or false, we unsetDirty in obj. This is the right approach because:
		// if state == true:
		// isDirty will be true. It will be set to false as soon as any change happens in obj EXCEPT because of that change, isDirty will stay true UNTIL that change is reversed again
		// if state == false:
		// Then we want to unsetDirty on obj anyway
		
		for(let i=monitoredObjects.length-1; i>=0; --i) {
			OwnMapping.unsetDirty(monitoredObjects[i]);
		}
		this.isDirty(state);
	};
	this.set_enabled = function(enabled) {
		if(enabled) {
			if(!subscriptions.length)
				setSubscriptions();
		}
		else if(subscriptions.length)
			this.destroy();
	};
	
	this.addMonitored = function(newObj) {
		monitoredObjects.push(newObj);
		subscriptions.concat(OwnMapping.subscribe(newObj, self.isDirty, internalChangedFu));
	}
	
	this.destroy = function() {
		for(let i=subscriptions.length-1; i>=0; --i) {
			subscriptions[i].dispose();
		}
		subscriptions = [];
	};
}