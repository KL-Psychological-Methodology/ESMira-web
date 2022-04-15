import ko from "knockout";
import drag_handle from "../../imgs/drag_handle.svg?raw";
import btn_ok from "../../widgets/btn_ok.html";

const {bindEvent} = require("../helpers/basics");
const {createElement} = require("../helpers/basics");

export const DragClass = {
	_dragSpacerEl: createElement("div", false, {className: "drag_spacer"}),
	
	_startEl: null,
	_startList: null,
	_startIndex: -1,
	_current_dragRoot: -1,
	
	_stopList: null,
	_stopIndex: -1,
	
	init: function() {
		let self = this;
		
		ko.bindingHandlers.dragRoot = {
			init: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				el.__dragRoot = true;
			}
		};
		ko.bindingHandlers.dragTarget = {
			init: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let list = valueAccessor();
				if(!list)
					return;
				let index = el.hasAttribute("drag-index") ? parseInt(el.getAttribute("drag-index")) : bindingContext.$index();
				self.update_el_vars(el, list, index);
				
				el.classList.add("drag_target");
				
				bindEvent(el, "dragend", self.event_dragend.bind(self));
				bindEvent(el, "dragenter", self.event_dragenter.bind(self));
			},
			update: function(el, valueAccessor, allBindings, viewModel, bindingContext) {
				let list = valueAccessor();
				if(!list)
					return;
				let index = el.hasAttribute("drag-index") ? parseInt(el.getAttribute("drag-index")) : bindingContext.$index();
				self.update_el_vars(el, list, index);
			}
		};
		
		ko.components.register('drag-start', {
			viewModel: {
				createViewModel: function(viewModel, componentInfo) {
					return function() {
						let el = componentInfo.element;
						if(el.wasInitialized)
							return;
						el.wasInitialized = true;
						el.classList.add("clickable");
						el.style.cursor = "move";
						el.setAttribute("draggable", true);
						bindEvent(el, "dragstart", self.event_dragstart.bind(self));
					}
				}
			},
			template: drag_handle
		});
	},
	
	get_collectionIndex(htmlCollection, searchEl) {
		for(let i=htmlCollection.length-1; i>=0; --i) {
			if(htmlCollection[i] === searchEl)
				return i;
		}
		return -1;
	},
	
	add_dragSpacer: function(el) {
		let insertBefore = el;
		if(this._dragSpacerEl.parentNode) {
			let children = this._current_dragRoot.getElementsByClassName("drag_target");
			let elPosition = this.get_collectionIndex(children, el);
			let spacerPosition = this.get_collectionIndex(children, this._dragSpacerEl);
			
			if(spacerPosition < elPosition) {
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
		while(el && !el.classList.contains("drag_target")) {
			el = el.parentNode;
		}
		let list = this.get_list(el);
		let index = this.get_index(el);
		let dragRoot = this.get_dragRoot(el);
		
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
	
	get_dragRoot: function(el) {
		while(!el.__dragRoot) {
			el = el.parentNode;
		}
		return el;
	},
	is_sameDragRoot: function(el) {
		return this.get_dragRoot(el) === this._current_dragRoot;
	},
};

DragClass.init();