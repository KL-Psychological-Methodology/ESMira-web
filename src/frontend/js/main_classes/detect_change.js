import {OwnMapping} from "../helpers/knockout_own_mapping";
import ko from "knockout";

export function DetectChange(obj, changedFu) {
	let self = this;
	let monitoredObjects = [obj];
	let internalChangedFu = null;
	let subscriptions = ko.observableArray([]);
	let alwaysDirty = ko.observable(false);
	let setSubscriptions = function() {
		for(let i=monitoredObjects.length-1; i>=0; --i) {
			subscriptions.push(OwnMapping.subscribe(monitoredObjects[i], internalChangedFu));
		}
	};
	if(changedFu) { //when we only want the dirty state, we don't care if new objects are changed. They are still new anyway (dirty = true)
		internalChangedFu = function() {
			changedFu();
			//in case a new element was added to obj, we need to read subscriptions:
			self.destroy();
			setSubscriptions();
		}
	}
	setSubscriptions();
	
	this.isDirty = ko.pureComputed({
		read: function() {
			if(alwaysDirty())
				return true
			let a = subscriptions();
			for(let i = a.length - 1; i >= 0; --i) {
				if(a[i].isDirty())
					return true;
			}
			return false;
		},
		write: function(newValue) {
			alwaysDirty(!!newValue);
		}
	});
	
	this.setDirty = function(state) {
		if(!state) {
			for(let i = monitoredObjects.length - 1; i >= 0; --i) {
				OwnMapping.unsetDirty(monitoredObjects[i]);
			}
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
		subscriptions.push(OwnMapping.subscribe(newObj, internalChangedFu));
	}
	
	this.reload = function() {
		this.destroy();
		setSubscriptions();
	}
	
	this.destroy = function() {
		let a = subscriptions();
		for(let i=a.length-1; i>=0; --i) {
			a[i].dispose();
		}
		subscriptions([]);
	};
}