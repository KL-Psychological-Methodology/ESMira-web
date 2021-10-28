import html from "./error_view.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {FILE_ADMIN} from "../js/variables/urls";
import {bindEvent, createElement} from "../js/helpers/basics";
import {Admin} from "../js/main_classes/admin";


function get_name(timestamp, note) {
	let date = new Date(parseInt(timestamp)).toLocaleString();
	return note ? note + " (" + date + ")" : date;
}

export function ViewModel(page) {
	this.html = html;
	this.promiseBundle = [Admin.init(page)];
	
	this.list_known = ko.observableArray();
	this.list_new = ko.observableArray();
	
	this.preInit = function({timestamp, note, seen}, admin) {
		if(!admin.tools.is_rootAdmin())
			throw new Error(Lang.get("error_no_permission"));
		
		note = atob(note);
		page.title(get_name(timestamp, note));
		page.loader.loadRequest(FILE_ADMIN+"?type=get_error&timestamp="+timestamp+"&note="+note+"&seen="+(!!seen ? 1 : 0), true).then(function(data) {
			let update_sticky_els = function() {
				sticky_els.sort(function(a,b) {
					let indexA = a["line-index"];
					let indexB = b["line-index"];
					
					return indexA>=indexB ? (indexA>indexB ? 1 : 0) : -1;
				});
				
				
				let height = -10;
				let el, i;
				for(i=sticky_els.length-1; i>=0; --i) {
					el = sticky_els[i];
					
					el.style.bottom = height+"px";
					height += el.clientHeight;
				}
				
				let height2 = 0;
				for(i=sticky_els.length-1; i>=0; --i) {
					el = sticky_els[i];
					
					height2 += el.clientHeight;
					el.style.top = (height-height2)+"px";
				}
			};
			
			let lines = data.split("\n\n");
			
			let sticky_els = [];
			
			let error_box = document.getElementById("error_box");
			let menu = document.getElementById("line_menu");
			
			let error_lines = [];
			let warning_lines = [];
			let log_lines = [];
			for(let i=0, max=lines.length; i<max; ++i) {
				let text = lines[i].trim();
				let el = createElement("div", false, {className: "line", "line-index": i});
				let pre = createElement("pre");
				if(text.startsWith("Error:")) {
					error_lines.push(el);
					pre.appendChild(createElement("span", "color: red; font-weight: bold;", {innerText: "Error:"}));
					text = text.substring(6);
				}
				else if(text.startsWith("Warning:")) {
					warning_lines.push(el);
					pre.appendChild(createElement("span", "color: orange;", {innerText: "Warning:"}));
					text = text.substring(8);
				}
				else if(text.startsWith("Log:")) {
					log_lines.push(el);
					pre = createElement("pre");
					pre.appendChild(createElement("span", "font-weight: bold;", {innerText: "Log:"}));
					text = text.substring(4);
				}
				else {
					log_lines.push(el);
				}
				
				if(text.endsWith("Cold starting app")) {
					el.classList.add("divider");
				}
				pre.appendChild(createElement("span", false, {innerText: text}));
				
				el.appendChild(pre);
				// el.appendChild(menu.cloneNode(true));
				bindEvent(el, "mouseenter", function() {
					if(menu.parentNode)
						menu.parentNode.removeChild(menu);
					this.appendChild(menu);
				});
				bindEvent(el, "mouseleave", function() {
					if(menu.parentNode)
						menu.parentNode.removeChild(menu);
				});
				bindEvent(pre, "click", function() {
					let current_line = menu.parentNode;
					if(current_line.classList.contains("sticky")) {
						current_line.classList.remove("sticky");
						current_line.style.top = current_line.style.bottom = "0";
						
						for(let i=sticky_els.length-1; i>=0; --i) {
							let el = sticky_els[i];
							if(el===current_line) {
								sticky_els.splice(i,1);
								break;
							}
						}
					}
					else {
						sticky_els.push(current_line);
						current_line.classList.add("sticky");
					}
					update_sticky_els();
				});
				
				error_box.appendChild(el);
				
			}
			let error_count_el = document.getElementById("error_count_el");
			bindEvent(error_count_el.parentNode, "click", function() {
				if(error_lines.length)
					error_lines[0].scrollIntoView({behavior: "smooth", block: "center"});
			});
			error_count_el.innerText = error_lines.length.toString();
			
			let warning_count_el = document.getElementById("warning_count_el");
			bindEvent(warning_count_el.parentNode, "click", function() {
				if(warning_lines.length)
					warning_lines[0].scrollIntoView({behavior: "smooth", block: "center"});
			});
			warning_count_el.innerText = warning_lines.length.toString();
			
			let log_count_el = document.getElementById("log_count_el");
			bindEvent(log_count_el.parentNode, "click", function() {
				if(log_lines.length)
					log_lines[0].scrollIntoView({behavior: "smooth", block: "center"});
			});
			log_count_el.innerText = log_lines.length.toString();
			
			menu.parentNode.removeChild(menu);
		});
	};
}