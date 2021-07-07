import html from "./data_view.html"
import {Lang} from "../js/main_classes/lang";
import {FILE_ADMIN, FILE_RESPONSES} from "../js/variables/urls";
import reload_svg from '../imgs/reload.svg?raw';
import {bindEvent, close_on_clickOutside, createElement, filter_box} from "../js/helpers/basics";
import {PromiseCache} from "../js/main_classes/promise_cache";
import {Studies} from "../js/main_classes/studies";
import {Admin} from "../js/main_classes/admin";

export function ViewModel(page) {
	let get_backupTitle = function(s) {
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
	};
	
	let load_data = function() {
		let table = document.getElementById("data_table");
		let selectedRowCount_el = document.getElementById("selectedRowCount");
		selectedRowCount_el.classList.add("hidden"); //needed, when file was reloaded
		while(table.hasChildNodes()) {
			table.removeChild(table.firstChild);
		}
		
		let header = createElement("thead");
		table.appendChild(header);
		let body = createElement("tbody");
		table.appendChild(body);
		
		let scroll_el = table.parentNode;
		let contentSize_el = document.getElementById("contentSize_el");
		
		let row_height = 25;
		let max_elements_displayed = Math.floor(scroll_el.clientHeight / row_height - 2); //-2: header and padding
		let selectedRows = 0;
		
		
		let empty_table = function() {
			while(body.hasChildNodes()) {
				body.removeChild(body.firstChild);
			}
		};
		
		
		return PromiseCache.loadCSV(fileUrl).then(function(loader) {
			let display_rows = function() {
				// let rounding_error = scroll_el.scrollTop % row_height;
				let bottomIndex = Math.min(loader.rows_count-1, Math.ceil(scroll_el.scrollTop / row_height) + max_elements_displayed -1); //-1 index starts with 0
				
				return loader.index_data_async(bottomIndex).then(function() {
					table.style.display = "none";
					
					empty_table();
					
					let rows = loader.get_visible_rows();
					let i=bottomIndex;
					
					for(let min=Math.max(i-max_elements_displayed+1, 0); i>=min; --i) {
						let row = rows[i];
						let data = row[0];
						let options = row[1];
						let tr = createElement("tr", "height:"+row_height+"px", {"row-index": i});
						if(options.marked)
							tr.className = "marked";
						
						bindEvent(tr, "click", function() {
							let el = this;
							if(el.classList.contains("marked")) {
								el.classList.remove("marked");
								rows[el["row-index"]][1].marked = false;
								--selectedRows;
							}
							else {
								el.classList.add("marked");
								rows[el["row-index"]][1].marked = true;
								++selectedRows;
							}
							if(selectedRows) {
								selectedRowCount_el.innerText = Lang.get("selected_rows_x", selectedRows);
								selectedRowCount_el.classList.remove("hidden");
							}
							else
								selectedRowCount_el.classList.add("hidden");
						});
						
						tr.appendChild(createElement("td", false, {innerText: data[0].index+1, className: "index_column"}));
						
						for(let column_i=0, column_max=data.length; column_i<column_max; ++column_i) {
							let set = data[column_i];
							// let td = createElement("td", set.special ? "font-style: italic" : false, {innerText: set.value, title: set.title});
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
					
					// table.style.top = ((bottomIndex - (max_elements_displayed-1))*row_height + rounding_error) + "px";
					
					table.style.display = "table";
				});
			};
			let update_table = function() {
				empty_table();
				scroll_el.scrollTo(scroll_el.scrollLeft, 0);
				contentSize_el.style.height = ((loader.rows_count+1) * row_height) + "px"; //+1: for header line
				table.style.top = "0px";
				
				let promise = display_rows();
				page.title(title + " (" + Lang.get("colon_entry_count") + " " + loader.rows_count + ")");
				return promise;
			};
			loader.update_view = update_table;
			
			let last_scrollY = 0;
			let scrollUpdate = function() {
				let current_scrollY = scroll_el.scrollTop;
				if(current_scrollY !== last_scrollY)
					display_rows();
				last_scrollY = current_scrollY;
			};
			scroll_el.removeEventListener("scroll", scrollUpdate);
			bindEvent(scroll_el, "scroll", scrollUpdate);
			
			let header_names = loader.header_names;
			let tr = createElement("tr", "height:"+row_height+"px");
			tr.appendChild(createElement("th"));
			
			for(let i=0, max=header_names.length; i<max; ++i) {
				let column_value = header_names[i];
				
				let el = createElement("th");
				let span = createElement("span", false, {innerText: loader.is_timestampColumn(column_value) ? column_value+"*" : column_value, "column-index": i});
				bindEvent(span, "click", function(e) {
					// self.open_dataDropdown(target.parentNode, target["column-index"], loader);
					let target = e.currentTarget,
						parent = target.parentNode,
						index = target["column-index"];
					
					if(parent.lastElementChild && parent.lastElementChild.classList.contains("dropdown")) {
						let old_index = parent.lastElementChild["column-index"];
						parent.removeChild(parent.lastElementChild);
						if(index === old_index)
							return;
					}
					
					loader.index_data_async().then(function() {
						//all checkboxes are created live
						
						let column_index = loader.get_columnIndex(index);
						let visible_columnIndex = loader.get_visible_columnIndex(index);
						
						
						let dropdown_el = createElement("div", false, {className: "dropdown valueList", "column-index":index});
						
						let search_label = createElement("label", false, {innerText: Lang.get("colon_search"), className: "small_text vertical"});
						let search = createElement("input", false, {type: "text", className: "small"});
						bindEvent(search, "keyup", function() {
							filter_box(this.value, dropdown_el);
						});
						
						search_label.appendChild(search);
						dropdown_el.appendChild(search_label);
						
						let label = createElement("label", "margin-top:10px; margin-bottom: 10px");
						let toggle = createElement("input", false, {type: "checkbox"});
						bindEvent(toggle, "click", function(e) {
							let checked = e.currentTarget.checked;
							
							//toggle checkboxes:
							let children = dropdown_el.getElementsByTagName("input");
							for(let i = children.length - 1; i > 1; --i) { //excluding search box and toggle all
								let checkbox = children[i];
								let value = checkbox["data-key"];
								loader.filter_column(checked, index, value);
								checkbox.checked = checked;
								checkbox.nextElementSibling.innerText = value+(checked ? " (" : " (0/") + column_index[value][0].length + ")";
							}
							if(loader.update_view)
								loader.update_view();
							
							e.stopPropagation();
						});
						label.appendChild(toggle);
						
						let values = loader.get_column_valueList(index);
						let unchecked_sum = 0;
						
						label.appendChild(createElement("span", false, {innerText: Lang.get("toggle_all", values.length)}));
						dropdown_el.appendChild(label);
						
						
						//create checkboxes for all keys:
						for(let i=0, max=values.length; i<max; ++i) {
							let value = values[i];
							if(!column_index.hasOwnProperty(value))
								continue; //this should never happen
							
							let checked = column_index[value][1].visible;
							if(!checked)
								++unchecked_sum;
							
							label = createElement("label");
							let input = createElement("input", false, {type: "checkbox", checked: checked, "data-key": value});
							bindEvent(input, "click", function(e) {
								let checkbox = e.currentTarget;
								let value = checkbox["data-key"];
								loader.filter_column(checkbox.checked, index, value);
								
								if(checkbox.checked) {
									--unchecked_sum;
									checkbox.nextElementSibling.innerText = value+" (" + column_index[value][0].length + ")";
								}
								else {
									++unchecked_sum;
									checkbox.nextElementSibling.innerText = value+" (0/" + column_index[value][0].length + ")";
								}
								if(unchecked_sum) {
									// parent.firstElementChild.style.textDecoration = "underline";
									toggle.checked = false;
								}
								else {
									// parent.firstElementChild.style.textDecoration = "none";
									toggle.checked = true;
								}
								
								// toggle.checked = !unchecked_sum;
								
								if(loader.update_view)
									loader.update_view();
								// e.stopPropagation();
							});
							label.appendChild(input);
							
							let label_extra;
							
							let max_num = column_index[value][0].length;
							if(visible_columnIndex.hasOwnProperty(value)) {
								let current_num = visible_columnIndex[value].length;
								label_extra = max_num === current_num ? " (" + max_num + ")" : " (" + current_num + "/" + max_num + ")";
							}
							else
								label_extra = " (0/" + max_num + ")";
							
							label.appendChild(createElement("span", false, {innerText: value + label_extra, className: "searchTarget"}));
							dropdown_el.appendChild(label);
							toggle.checked = !unchecked_sum;
						}
						
						close_on_clickOutside(dropdown_el);
						parent.appendChild(dropdown_el);
					});
				});
				el.appendChild(span);
				tr.appendChild(el);
			}
			header.appendChild(tr);
			
			update_table().then(function() {
				let th_row_els = header.firstElementChild.children;
				for(let i=th_row_els.length-1; i>=0; --i) {
					th_row_els[i].style.minWidth = th_row_els[i].offsetWidth+"px";
				}
			});
			
			table.style.display = "table";
		}, true);
	};
	
	this.html = html;
	this.promiseBundle = [Studies.init(page), Admin.init(page)];
	this.extraContent = "<div data-bind=\"click: $root.reload, attr: {title: Lang.get('reload')}\" class=\"clickable\">"+reload_svg+"</div>";
	
	let title, fileName, fileUrl;
	
	
	this.preInit = function({id, fName, fMode}, studies) {
		switch(fMode) {
			case '2':
				title = fileName = Lang.get("login_history");
				fileUrl = FILE_ADMIN + "?csv&type=get_loginHistory";
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
		page.loader.showLoader(Lang.get("state_loading_file", fileName), load_data());
	}
	
	this.reload = function() {
		PromiseCache.remove(fileUrl);
		return page.loader.showLoader(Lang.get("state_loading_file", fileName), load_data());
	};
}