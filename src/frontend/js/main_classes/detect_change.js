import {OwnMapping} from "../helpers/knockout_own_mapping";
import ko from "knockout";

export function DetectChange(obj, changedFu) {
	this.isDirty = ko.observable(false);
	let subscriptions = OwnMapping.subscribe(obj, this.isDirty, changedFu);
	
	this.setDirty = function(state) {
		OwnMapping.unsetDirty(obj);
		this.isDirty(state);
	};
	this.set_enabled = function(enabled) {
		if(enabled) {
			if(!subscriptions.length)
				subscriptions = OwnMapping.subscribe(obj, this.isDirty, changedFu);
		}
		else if(subscriptions.length)
			this.destroy();
	};
	this.destroy = function() {
		for(let i=subscriptions.length-1; i>=0; --i) {
			subscriptions[i].dispose();
		}
		subscriptions = [];
	};
}