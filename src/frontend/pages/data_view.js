import html from "./data_view.html"
import {Lang} from "../js/main_classes/lang";
import {FILE_ADMIN, FILE_RESPONSES} from "../js/variables/urls";
import reload_svg from '../imgs/reload.svg?raw';
import {
	bindEvent,
	close_on_clickOutside,
	createElement,
	createFloatingDropdown,
	filter_box
} from "../js/helpers/basics";
import {Studies} from "../js/main_classes/studies";
import {Admin} from "../js/main_classes/admin";
import {CsvLoader} from "../js/dynamic_imports/csv_loader";

const ROW_HEIGHT = 25;
function get_backupTitle(s) {
	if(parseInt(s)) {
		let questionnaire = Studies.get_questionnaireByInternalId(Studies.get_current(), s);
		return questionnaire ? questionnaire.title() : s;
	}
	let match = s.match(/^(\d{4}-\d{2}-\d{2})_(\d+)$/);
	
	if(match != null) {
		let questionnaire = Studies.get_questionnaireByInternalId(Studies.get_current(), s);
		return questionnaire ? match[1] + " " + questionnaire.title() : s;
	}
	else
		return s;
}

export function ViewModel(page) {
	let self = this;
	let loader;
	let selectedRows = 0;
	
	let table, contentSize_el, selectedRowCount_el, scroll_el, header, body,
		max_elements_displayed;
	
	let empty_table = function() {
		while(body.hasChildNodes()) {
			body.removeChild(body.firstChild);
		}
	};
	let display_rows = function() {
		let bottomIndex = Math.min(loader.rows_count, Math.ceil(scroll_el.scrollTop / ROW_HEIGHT) + max_elements_displayed -1); //-1 index starts with 0
		
		return loader.get_visibleRows(Math.max(bottomIndex-max_elements_displayed+1, 0), bottomIndex)
			.then(function(rows) {
				table.style.display = "none";
				
				empty_table();
				
				for(let i=rows.length-1; i>=0; --i) {
					let row = rows[i];
					
					let cells = row.columnCells;
					let tr = createElement("tr", "height:"+ROW_HEIGHT+"px", {"row-index": i});
					if(row.marked)
						tr.className = "marked";
					
					bindEvent(tr, "click", self.markRow.bind(self, row, tr));
					
					tr.appendChild(createElement("td", false, {innerText: cells[0].index+1, className: "index_column"}));
					
					for(let column_i=0, column_max=cells.length; column_i<column_max; ++column_i) {
						let set = cells[column_i];
						let td;
						if(set.special && set.real_value.toString().length) {
							td = createElement("td", set.special ? "font-style: italic" : false, {title: set.title});
							td.appendChild(createElement("div", false, {innerText: set.value, className: "pretty_value"}));
							td.appendChild(createElement("div", false, {innerText: set.real_value, className: "real_value"}));
						}
						else
							td = createElement("td", set.special ? "font-style: italic" : false, {innerText: set.value, title: set.title});
						tr.appendChild(td);
					}
					
					if(body.firstElementChild)
						body.insertBefore(tr, body.firstElementChild);
					else
						body.appendChild(tr);
				}
				
				table.style.display = "table";
			});
	};
	let update_table = function() {
		scroll_el.scrollTo(scroll_el.scrollLeft, 0);
		contentSize_el.style.height = ((loader.rows_count+1) * ROW_HEIGHT) + "px"; //+1: for header line
		table.style.top = "0px";
		
		let promise = display_rows();
		page.title(title + " (" + Lang.get("colon_entry_count") + " " + loader.rows_count + ")");
		return promise;
	};
	
	this.reload = function() {
		
		//reset header in case we are reloading:
		selectedRowCount_el.classList.add("hidden");
		while(header.hasChildNodes()) {
			header.removeChild(header.firstChild);
		}
		
		selectedRows = 0;
		
		loader = new CsvLoader(fileUrl, page);
		loader.waitUntilReady().then(function() {
			//create header line:
			
			let header_names = loader.header_names;
			let tr = createElement("tr", "height:"+ROW_HEIGHT+"px");
			tr.appendChild(createElement("th")); //add empty element to left top
			for(let i=0, max=header_names.length; i<max; ++i) {
				let column_value = header_names[i];
				
				let el = createElement("th");
				let span = createElement("span", false, {
					innerText: loader.is_timestampColumn(column_value) ? column_value+"*" : column_value,
					"column-index": i
				});
				bindEvent(span, "click", self.clickHeader);
				el.appendChild(span);
				tr.appendChild(el);
			}
			header.appendChild(tr);
			
			
			//fill table:
			
			update_table().then(function() {
				let th_row_els = header.firstElementChild.children;
				for(let i=th_row_els.length-1; i>=0; --i) {
					th_row_els[i].style.minWidth = th_row_els[i].offsetWidth+"px";
				}
			});
			
			
			//add scroll event:
			
			let last_scrollY = 0;
			let scrollUpdate = function() {
				let current_scrollY = scroll_el.scrollTop;
				if(current_scrollY !== last_scrollY)
					display_rows();
				last_scrollY = current_scrollY;
			};
			scroll_el.removeEventListener("scroll", scrollUpdate);
			bindEvent(scroll_el, "scroll", scrollUpdate);
			
			
			table.style.display = "table";
		});
	};
	
	this.html = html;
	this.promiseBundle = [Studies.init(page), Admin.init(page)];
	this.extraContent = "<div data-bind=\"click: $root.reload, attr: {title: Lang.get('reload')}\" class=\"clickable\">"+reload_svg+"</div>";
	
	let title, fileName, fileUrl;
	
	
	this.preInit = function({id, fName, fMode}) {
		switch(fMode) {
			case '2':
				title = fileName = Lang.get("login_history");
				fileUrl = FILE_ADMIN + "?type=get_loginHistory";
				break;
			case '1':
			default:
				fileName = atob(fName);
				fileUrl = FILE_RESPONSES.replace('%1', id).replace('%2', fileName);
				title = get_backupTitle(fileName);
				break;
		}
	};
	this.postInit = function() {
		if(!window.Worker) {
			page.loader.error(Lang.get('error_no_webWorkers'));
			return;
		}
		
		//get existing elements:
		table = document.getElementById("data_table");
		contentSize_el = document.getElementById("contentSize_el");
		selectedRowCount_el = document.getElementById("selectedRowCount");
		scroll_el = table.parentNode;
		
		//create new elements:
		header = createElement("thead");
		body = createElement("tbody");
		
		//add new elements:
		table.appendChild(header);
		table.appendChild(body);
		
		
		max_elements_displayed = Math.floor(scroll_el.clientHeight / ROW_HEIGHT - 1); //-1: header
		
		this.reload();
	}
	
	let currentDropdownCloseFu = null;
	let currentIndex = null;
	let unchecked_sum = 0;
	
	this.clickHeader = function(e) {
		unchecked_sum = 0;
		let target = e.currentTarget,
			parent = target.parentNode,
			index = target["column-index"];
		
		
		if(currentDropdownCloseFu) {
			if(currentDropdownCloseFu()) {
				currentDropdownCloseFu = null;
				if(index === currentIndex)
					return;
			}
			else
				currentDropdownCloseFu = null;
		}
		
		loader.get_valueList(index, false, true).then(function(valueList) {
			let dropdown_el = createFloatingDropdown(parent, "valueList");
			currentIndex = index;
			
			
			//search field:
			let search_label = createElement("div", false, { className: "center"});
			let search = createElement("input", false, {type: "text", className: "small search"});
			bindEvent(search, "keyup", function() {
				filter_box(this.value, dropdown_el);
			});
			search_label.appendChild(search);
			dropdown_el.appendChild(search_label);
			
			
			//check all:
			let label = createElement("label", "margin-top:10px; margin-bottom: 10px");
			let toggle = createElement("input", false, {type: "checkbox"});
			bindEvent(toggle, "click", self.toggleAllValuesFilter.bind(self, dropdown_el, index));
			label.appendChild(toggle);
			label.appendChild(createElement("span", false, {innerText: Lang.get("toggle_all", valueList.length)}));
			dropdown_el.appendChild(label);
			
			
			
			for(let i=0, max=valueList.length; i<max; ++i) {
				let entry = valueList[i];
				let value = entry.name;
				
				let checked = entry.visible;
				if(!checked)
					++unchecked_sum;
				
				label = createElement("label");
				let input = createElement("input", false, {type: "checkbox", checked: checked, "data-key": value});
				bindEvent(input, "click", self.toggleValueFilter.bind(self, index, entry, toggle));
				label.appendChild(input);
				
				let label_extra;
				label_extra = entry.totalCount === entry.count ? (" (" + entry.totalCount + ")") : (" (" + entry.count + "/" + entry.totalCount + ")");
				
				label.appendChild(createElement("span", false, {innerText: value + label_extra, className: "searchTarget"}));
				dropdown_el.appendChild(label);
				toggle.checked = !unchecked_sum;
			}
			
			
			currentDropdownCloseFu = close_on_clickOutside(dropdown_el);
		});
	};
	
	this.toggleAllValuesFilter = function(dropdown_el, index, e) {
		let checked = e.currentTarget.checked;
		let promise = Promise.resolve();
		
		//toggle checkboxes:
		let children = dropdown_el.getElementsByTagName("input");
		for(let i = children.length - 1; i > 1; --i) { //excluding search box and toggle all
			let checkbox = children[i];
			let value = checkbox["data-key"];
			checkbox.checked = checked;
			promise = promise.then(loader.filter.bind(loader, checked, index, value));
		}
		promise.then(update_table);
		
		e.stopPropagation();
	};
	
	this.toggleValueFilter = function(index, entry, toggle, e) {
		let checkbox = e.currentTarget;
		let value = checkbox["data-key"];
		loader.filter(checkbox.checked, index, value)
			.then(update_table)
			.then(function() {
				if(checkbox.checked) {
					--unchecked_sum;
					checkbox.nextElementSibling.innerText = value+" (" + entry.totalCount + ")";
				}
				else {
					++unchecked_sum;
					checkbox.nextElementSibling.innerText = value+" (0/" + entry.totalCount + ")";
				}
				if(unchecked_sum) {
					// parent.firstElementChild.style.textDecoration = "underline";
					toggle.checked = false;
				}
				else {
					// parent.firstElementChild.style.textDecoration = "none";
					toggle.checked = true;
				}
			});
	};
	
	this.markRow = function(row, el_row, e) {
		if(el_row.classList.contains("marked")) {
			el_row.classList.remove("marked");
			loader.mark(false, row.pos);
			row.marked = false;
			--selectedRows;
		}
		else {
			el_row.classList.add("marked");
			loader.mark(true, row.pos);
			row.marked = true;
			++selectedRows;
		}
		if(selectedRows) {
			selectedRowCount_el.innerText = Lang.get("selected_rows_x", selectedRows);
			selectedRowCount_el.classList.remove("hidden");
		}
		else
			selectedRowCount_el.classList.add("hidden");
	}
}