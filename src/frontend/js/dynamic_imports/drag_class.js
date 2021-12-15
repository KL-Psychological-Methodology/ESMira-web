import ko from "knockout";

const {bindEvent} = require("../helpers/basics");
const {createElement} = require("../helpers/basics");

export const DragClass = {
	_dragSpacerEl: createElement("div", false, {className: "drag_spacer"}),
	
	_startEl: null,
	_startList: null,
	_startIndex: -1,
	
	_stopList: null,
	_stopIndex: -1,
	
	_listEl: null,
	
	init: function() {
		let self = this;
		ko.bindingHandlers.dragTarget = {
			init: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let [list, index] = valueAccessor();
				self.make_dragTarget(el, list, index);
			},
			update: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let [list, index] = valueAccessor();
				self.update_el_vars(el, list, index);
			}
		};
		ko.bindingHandlers.dragPlacer = {
			init: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let list = valueAccessor();
				self.make_dragPlacer(el, list);
			},
			update: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let list = valueAccessor();
				self.update_el_vars(el, list);
			}
		};
		ko.bindingHandlers.dragStart = {
			init: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let [list, index] = valueAccessor();
				self.make_dragStarter(el, list, index);
			},
			update: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let [list, index] = valueAccessor();
				self.update_el_vars(el, list, index);
			}
		};
	},
	
	_add_dragSpacer: function(el) {
		let insertBefore = el;
		if(this._dragSpacerEl.parentNode) {
			if(this._dragSpacerEl.offsetTop < el.offsetTop && el !== this._stopList.__dragClass_placer__) {
				insertBefore = el.nextElementSibling;
				if(this._startIndex > this._stopIndex) //When mouse was moved up and then down again
					++this._stopIndex;
			}
			else if(this._startIndex < this._stopIndex && this._startList === this._stopList) //when mouse was moved down and then up again
				--this._stopIndex;
			
			this._dragSpacerEl.parentNode.removeChild(this._dragSpacerEl);
		}
		el.parentNode.insertBefore(this._dragSpacerEl, insertBefore);
	},
	
	_drop: function(e) {
		e.preventDefault();
		
		if(this._startList == null || this._stopList == null)
			return;
		let drag_data = this._startList()[this._startIndex];
		
		this._startList.splice(this._startIndex, 1);
		this._stopList.splice(this._stopIndex, 0, drag_data);
		
		this._event_dragend(e);
	},
	
	_event_dragleave: function(e) {
		e.preventDefault();
		// document.body.classList.remove("is_dragging");
		// this._startEl.classList.remove("drag_start");
		// if(this._dragSpacerEl.parentNode)
		// 	this._dragSpacerEl.parentNode.removeChild(this._dragSpacerEl);
	},
	_event_dragend: function(e) {
		this._event_dragleave(e);
		e.preventDefault();
		document.body.classList.remove("is_dragging");
		this._startEl.classList.remove("drag_start");
		if(this._dragSpacerEl.parentNode)
			this._dragSpacerEl.parentNode.removeChild(this._dragSpacerEl);
	},
	_event_dragover: function(e) {
		e.preventDefault();
		e.dataTransfer.dropEffect = "move";
	},
	_event_dragenter: function(e) {
		let el = e.currentTarget;
		let list = this._get_list(el);
		let index = this._get_index(el);
		
		e.dataTransfer.dropEffect = "none";
		e.preventDefault();
		
		this._stopList = list;
		this._stopIndex = index;
		
		this._add_dragSpacer.call(this, el);
		return false
	},
	_event_dragstart: function(e) {
		let el = e.currentTarget;
		let list = this._get_list(el);
		let index = this._get_index(el);
		
		while(el && !el.classList.contains("drag_target")) {
			el = el.parentNode;
		}
		
		this._startList = list;
		this._startIndex = index;
		
		
		this._dragSpacerEl = el.cloneNode(true);
		this._dragSpacerEl.classList.add("drag_spacer");
		// self._dragSpacerEl.style.height= el.style.height;
		
		bindEvent(this._dragSpacerEl, "dragover", this._event_dragover.bind(this));
		bindEvent(this._dragSpacerEl, "drop", this._drop.bind(this));
		bindEvent(this._dragSpacerEl, "dragleave", this._event_dragleave.bind(this));
		
		el.classList.add("drag_start");
		e.dataTransfer.setDragImage(el, 0, 0);
		
		
		this._startEl = el;
		
		let self = this;
		window.setTimeout( function() {
			//in firefox: setDragImage() seems to stop working when the class of document.body is changed
			//in chrome: DOM changes seem to cancel dragging altogether
			//solution: doing this stuff in a different "thread" seems to do the trick
			self._add_dragSpacer(el);
			document.body.classList.add("is_dragging");
		}, 0);
		return true
	},
	
	_get_list: function(el) {
		return el["drag-list"];
	},
	_get_index: function(el) {
		return el["drag-index"];
	},
	
	update_el_vars: function(el, list, index) {
		if(list !== undefined)
			el["drag-list"] = list;
		if(index !== undefined)
			el["drag-index"] = index;
	},
	
	make_dragStarter: function(el, list, index) {
		el.classList.add("clickable");
		el.style.cursor = "move";
		el.setAttribute("draggable", true);
		this.update_el_vars(el, list, index);
		
		bindEvent(el, "dragstart", this._event_dragstart.bind(this));
	},
	make_dragTarget: function(el, list, index) {
		el.classList.add("drag_target");
		this.update_el_vars(el, list, index);
		
		bindEvent(el, "dragend", this._event_dragend.bind(this));
		bindEvent(el, "dragenter", this._event_dragenter.bind(this));
	},
	
	make_dragPlacer: function(el, list) {
		el.classList.add("drag_placer");
		
		this.make_dragTarget(el, list, list().length);
		list.__dragClass_placer__ = el;
		// el["drag-list"] = list;
		
		bindEvent(el, "drop", this._drop.bind(this));
		bindEvent(el, "dragover", this._event_dragover.bind(this));
		bindEvent(el, "dragover", this._event_dragover.bind(this));
	}
};

DragClass.init();