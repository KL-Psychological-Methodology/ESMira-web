import ko from "knockout";
import drag_handle from "../../imgs/drag_handle.svg?raw";

const {bindEvent} = require("../helpers/basics");
const {createElement} = require("../helpers/basics");

export const DragClass = {
	_dragSpacerEl: createElement("div", false, {className: "drag_spacer"}),
	
	_dragIdCount: 0,
	_startEl: null,
	_startList: null,
	_startIndex: -1,
	_current_dragRoot: -1,
	
	_stopList: null,
	_stopIndex: -1,
	
	_listEl: null,
	
	init: function() {
		let self = this;
		
		ko.bindingHandlers.dragRoot = {
			init: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				ko.applyBindingsToDescendants(bindingContext.extend({"$dragRoot": el}), el);

				return { 'controlsDescendantBindings': true };
			}
		};
		ko.bindingHandlers.dragTarget = {
			init: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let list = valueAccessor();
				if(!list)
					return;
				let index = bindingContext.$index();
				self.set_dragRoot(el, bindingContext.$dragRoot);
				self.make_dragTarget(el, list, index);
			},
			update: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let list = valueAccessor();
				if(!list)
					return;
				let index = bindingContext.$index();
				self.update_el_vars(el, list, index);
			}
		};
		ko.bindingHandlers.dragPlacer = {
			init: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let list = valueAccessor();
				if(!list)
					return;
				self.set_dragRoot(el, bindingContext.$dragRoot);
				self.make_dragPlacer(el, list);
			},
			update: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let list = valueAccessor();
				if(!list)
					return;
				self.update_el_vars(el, list);
			}
		};
		ko.bindingHandlers.dragStart = {
			init: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let list = valueAccessor();
				if(!list)
					return;
				let index = bindingContext.$index();
				self.set_dragRoot(el, bindingContext.$dragRoot);
				self.make_dragStarter(el, list, index);
				el.innerHTML = drag_handle;
			},
			update: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let list = valueAccessor();
				if(!list)
					return;
				let index = bindingContext.$index();
				self.update_el_vars(el, list, index);
			}
		};
	},
	
	add_dragSpacer: function(el) {
		let insertBefore = el;
		if(this._dragSpacerEl.parentNode) {
			if((this._dragSpacerEl.offsetTop < el.offsetTop || this._dragSpacerEl.offsetLeft < el.offsetLeft) && this._stopList().length) {
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
	
	drop: function(e) {
		e.preventDefault();
		
		if(this._startList == null || this._stopList == null)
			return;
		let drag_data = this._startList()[this._startIndex];
		
		this._startList.splice(this._startIndex, 1);
		this._stopList.splice(this._stopIndex, 0, drag_data);
		
		this._startList = this._stopList = null;
		this.event_dragend(e);
	},
	
	event_dragleave: function(e) {
		e.preventDefault();
	},
	event_dragend: function(e) {
		this.event_dragleave(e);
		e.preventDefault();
		
		this._current_dragRoot.classList.remove("is_dragging");
		this._startEl.classList.remove("drag_start");
		if(this._dragSpacerEl.parentNode)
			this._dragSpacerEl.parentNode.removeChild(this._dragSpacerEl);
	},
	event_dragover: function(e) {
		e.preventDefault();
		e.dataTransfer.dropEffect = "move";
	},
	event_dragenter: function(e) {
		let el = e.currentTarget;
		if(!this.is_sameDragRoot(el))
			return false;
		let list = this.get_list(el);
		let index = this.get_index(el);
		
		e.dataTransfer.dropEffect = "none";
		e.preventDefault();
		
		this._stopList = list;
		this._stopIndex = index;
		
		this.add_dragSpacer.call(this, el);
		return false;
	},
	event_dragstart: function(e) {
		let el = e.currentTarget;
		let list = this.get_list(el);
		let index = this.get_index(el);
		let dragRoot = this.get_dragRoot(el);
		while(el && !el.classList.contains("drag_target")) {
			el = el.parentNode;
		}
		
		this._startList = list;
		this._startIndex = index;
		this._current_dragRoot = dragRoot;
		
		
		this._dragSpacerEl = el.cloneNode(true);
		this._dragSpacerEl.classList.add("drag_spacer");
		
		bindEvent(this._dragSpacerEl, "dragover", this.event_dragover.bind(this));
		bindEvent(this._dragSpacerEl, "drop", this.drop.bind(this));
		bindEvent(this._dragSpacerEl, "dragleave", this.event_dragleave.bind(this));
		
		el.classList.add("drag_start");
		e.dataTransfer.setDragImage(el, 0, 0);
		
		
		this._startEl = el;
		
		let self = this;
		window.setTimeout( function() {
			//in firefox: setDragImage() seems to stop working when the class of document.body is changed
			//in chrome: DOM changes seem to cancel dragging altogether
			//solution: doing this stuff in a different "thread" seems to do the trick
			self.add_dragSpacer(el);
			dragRoot.classList.add("is_dragging");
		}, 0);
		return true
	},
	
	get_list: function(el) {
		return el["drag-list"];
	},
	get_index: function(el) {
		return el["drag-index"];
	},
	
	update_el_vars: function(el, list, index) {
		if(list !== undefined)
			el["drag-list"] = list;
		if(index !== undefined)
			el["drag-index"] = index;
	},
	
	set_dragRoot: function(el, dragRoot) {
		el["drag-root"] = dragRoot;
	},
	get_dragRoot: function(el) {
		return el["drag-root"];
	},
	is_sameDragRoot: function(el) {
		return this.get_dragRoot(el) === this._current_dragRoot;
	},
	
	make_dragStarter: function(el, list, index) {
		el.classList.add("clickable");
		el.style.cursor = "move";
		el.setAttribute("draggable", true);
		this.update_el_vars(el, list, index);
		
		bindEvent(el, "dragstart", this.event_dragstart.bind(this));
	},
	make_dragTarget: function(el, list, index) {
		el.classList.add("drag_target");
		this.update_el_vars(el, list, index);
		
		bindEvent(el, "dragend", this.event_dragend.bind(this));
		bindEvent(el, "dragenter", this.event_dragenter.bind(this));
	},
	
	make_dragPlacer: function(el, list) {
		el.classList.add("drag_placer");
		
		this.make_dragTarget(el, list, list().length);
		
		bindEvent(el, "drop", this.drop.bind(this));
		bindEvent(el, "dragover", this.event_dragover.bind(this));
		bindEvent(el, "dragover", this.event_dragover.bind(this));
	}
};

DragClass.init();