import html from "./error_list.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {FILE_ADMIN} from "../js/variables/urls";
import {safe_confirm} from "../js/helpers/basics";
import {Admin} from "../js/main_classes/admin";


function get_name_data(id) {
	let match = id.match(/_?(\d+)~?(.*)$/);
	
	let date = new Date(parseInt(match[1]) * 1000).toLocaleString();
	
	return {
		print_name: match[2].length ? match[2]+" ("+date+")" : date,
		note: match[2],
		id: id
	}
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
				let id = data[i];
				
				if(id.substr(0, 1) === "_")
					list_known.push(get_name_data(id));
				else {
					list_new.push(get_name_data(id));
				}
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
	
	
	
	this.delete_error = function(error) {
		if(!safe_confirm(Lang.get("confirm_delete_error", error.print_name)))
			return;
		
		page.loader.loadRequest(FILE_ADMIN+"?type=delete_error", false, "post", "error_id="+error.id).then(load);
	};
	this.mark_error_seen = function(error) {
		page.loader.loadRequest(FILE_ADMIN+"?type=mark_error_seen", false, "post", "error_id="+error.id).then(load);
	};
	this.error_add_note = function(error) {
		let comment = prompt(Lang.get("prompt_comment"), error.note);
		if(!comment)
			return;
		
		page.loader.loadRequest(FILE_ADMIN+"?type=error_add_note", false, "post", "error_id="+error.id+"&note="+comment).then(load);
	};
}