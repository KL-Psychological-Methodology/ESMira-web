import {bindEvent, createElement} from "../helpers/basics";
import publishSvg from "../../imgs/publish.svg?raw";
import {Lang} from "../main_classes/lang";
import {Studies} from "../main_classes/studies";


export const NavigationRowAdmin = {
	_el_saveBtn: null,
	_el_publishBtn: null,
	_observedDetector: null,
	_observedSave: null,
	_observedPublish: null,
	_saveFu: null,
	_publishFu: null,
	init(root) {
		let self = this;
		window.onbeforeunload = function() {
			return Studies.tools.any_study_changed() || (self._observedDetector && self._observedDetector.isDirty())
				? Lang.get("confirm_leave_page_unsaved_changes")
				: undefined;
		};
		
		let el_saveBtn = createElement("div", false, {id: "saveBox", className: "highlight clickable"});
		let el_publishBtn = createElement("div", false, {id: "publishBox", className: "clickable", innerHTML: publishSvg});
		this._el_saveBtn = el_saveBtn
		this._el_publishBtn = el_publishBtn
		root.appendChild(this._el_saveBtn);
		root.appendChild(this._el_publishBtn);
		
		el_saveBtn.innerText = Lang.get("save");
		bindEvent(el_saveBtn, "click", function() {
			if(this._saveFu) {
				let detector = this._observedDetector;
				let r = this._saveFu();
				if(r)
					r.then(function() {
						detector.setDirty(false);
					});
			}
		}.bind(this));
		
		
		el_publishBtn.title = Lang.get("info_publish");
		bindEvent(el_publishBtn, "click", function() {
			if(this._publishFu)
				this._publishFu();
		}.bind(this));
	},
	
	
	change_observed: function(detector, saveFu, newChanges_obj, publishFu) {
		let self = this;
		this.remove_observed();
		
		if(detector) {
			this._observedDetector = detector
			const showSave_fu = function(b) {
				if(b)
					self._el_saveBtn.classList.add("visible");
				else
					self._el_saveBtn.classList.remove("visible");
			};
			
			this._observedSave = detector.isDirty.subscribe(showSave_fu);
			showSave_fu(detector.isDirty());
		}
		if(saveFu)
			this._saveFu = saveFu;
		
		if(newChanges_obj) {
			const showPublish_fu = function(b) {
				if(b)
					self._el_publishBtn.classList.add("visible");
				else
					self._el_publishBtn.classList.remove("visible");
			};
			
			this._observedPublish = newChanges_obj.subscribe(showPublish_fu);
			showPublish_fu(newChanges_obj());
		}
		if(publishFu)
			this._publishFu = publishFu;
	},
	remove_observed: function() {
		if(this._observedSave) {
			this._observedSave.dispose();
			this._el_saveBtn.classList.remove("visible");
			this._observedSave = null;
		}
		if(this._observedPublish) {
			this._observedPublish.dispose();
			this._el_publishBtn.classList.remove("visible");
			this._observedPublish = null;
		}
	}
}