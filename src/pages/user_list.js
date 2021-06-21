import html from "./user_list.html"
import {Lang} from "../js/main_classes/lang";
import {Site} from "../js/main_classes/site";
import ko from "knockout";
import {safe_confirm} from "../js/helpers/basics";
import {Admin} from "../js/main_classes/admin";
import {FILE_ADMIN} from "../js/variables/urls";
import {OwnMapping} from "../js/helpers/knockout_own_mapping";
import {Studies} from "../js/main_classes/studies";
import {PromiseCache} from "../js/main_classes/promise_cache";
import {listUrl, load_userData} from "../js/shared/user_functions";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("edit_users"));
	this.promiseBundle = [Admin.init(page), Studies.init(page)];
	
	this.userList = ko.observableArray();
	
	let init = function() {
		load_userData().then(function(userList) {
			self.userList(userList);
		});
	};
	let reload = function() {
		PromiseCache.remove(listUrl);
		init();
	}
	
	this.preInit = function(index, admin) {
		if(!admin.tools.is_rootAdmin())
			throw new Error(Lang.get("error_no_permission"));
		
		init();
	};
	
	
	this.delete = function(userData) {
		
		let username = userData.username();
		if(!safe_confirm(Lang.get("confirm_delete_user", username)))
			return;
		page.loader.loadRequest(FILE_ADMIN+"?type=delete_user", false, "post", "user="+username).then(reload);
	};
	
	this.add = function(username, password) {
		return page.loader.loadRequest(FILE_ADMIN + "?type=create_user", false, "post", "new_user="+username + "&pass="+password).then(function(data) {
			self.userList.push(OwnMapping.fromJS(data));
			Site.add_page("user_view,user:"+btoa(data.username));
		});
	};
}