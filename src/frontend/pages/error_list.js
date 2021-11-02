import html from "./error_list.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {FILE_ADMIN} from "../js/variables/urls";
import {safe_confirm} from "../js/helpers/basics";
import {Admin} from "../js/main_classes/admin";


function get_name(timestamp, note) {
	let date = new Date(parseInt(timestamp)).toLocaleString();
	return note ? note + " (" + date + ")" : date;
}

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("errorReports"));
	this.promiseBundle = [Admin.init(page)];
	
	this.list_known = ko.observableArray();
	this.list_new = ko.observableArray();
	
	
	let load = function() {
		page.loader.loadRequest(FILE_ADMIN+"?type=list_errors").then(function(data) {
			data = data.sort();
			
			let list_known = [];
			let list_new = [];
			
			for(let i=data.length-1; i>=0; --i) {
				let errorData = data[i];
				let {seen, note, timestamp} = errorData;
				errorData.print_name = get_name(timestamp, note);
				
				if(seen)
					list_known.push(errorData);
				else
					list_new.push(errorData);
			}
			self.list_known(list_known);
			self.list_new(list_new);
			
			Admin.tools.has_newErrors(!!list_new.length);
		});
	}
	this.preInit = function(index, admin) {
		if(!admin.tools.is_rootAdmin())
			throw new Error(Lang.get("error_no_permission"));
		
		load();
	};
	
	
	
	this.delete_error = function({timestamp, note, seen}) {
		if(!confirm(Lang.get("confirm_delete_error", get_name(timestamp, note))))
			return;
		
		page.loader.loadRequest(
			FILE_ADMIN+"?type=delete_error",
			false,
			"post",
			"timestamp="+timestamp+"&note="+note+"&seen="+(!!seen ? 1 : 0)
		).then(load);
	};
	this.mark_error_seen = function(error) {
		page.loader.loadRequest(
			FILE_ADMIN+"?type=change_error",
			false,
			"post",
			"timestamp="+error.timestamp+"&note="+error.note+"&seen=0&new_seen=1"
		).then(load);
	};
	this.error_add_note = function({timestamp, note, seen}) {
		let new_note = prompt(Lang.get("prompt_comment"), note);
		if(!new_note)
			return;
		
		page.loader.loadRequest(
			FILE_ADMIN+"?type=change_error",
			false,
			"post",
			"timestamp="+timestamp+"&note="+note+"&seen="+(!!seen ? 1 : 0)+"&new_note="+new_note
		).then(load);
	};
}