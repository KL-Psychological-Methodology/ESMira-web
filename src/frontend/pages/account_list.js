import html from "./account_list.html"
import {Lang} from "../js/main_classes/lang";
import {Site} from "../js/main_classes/site";
import ko from "knockout";
import {safe_confirm} from "../js/helpers/basics";
import {Admin} from "../js/main_classes/admin";
import {FILE_ADMIN} from "../js/variables/urls";
import {OwnMapping} from "../js/helpers/knockout_own_mapping";
import {Studies} from "../js/main_classes/studies";
import {PromiseCache} from "../js/main_classes/promise_cache";
import {listUrl, load_accountData} from "../js/shared/account_functions";
import {Defaults} from "../js/variables/defaults";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("edit_users"));
	this.promiseBundle = [Admin.init(page), Studies.init(page)];
	
	this.accountList = ko.observableArray();
	
	let init = function() {
		load_accountData().then(function(accountList) {
			self.accountList(accountList);
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
		
		let accountName = userData.accountName();
		if(!safe_confirm(Lang.get("confirm_delete_account", accountName)))
			return;
		page.loader.loadRequest(FILE_ADMIN+"?type=delete_account", false, "post", "accountName="+accountName).then(reload);
	};
	
	this.add = function(accountName, password) {
		return page.loader.loadRequest(FILE_ADMIN + "?type=create_account", false, "post", "new_account="+accountName + "&pass="+password).then(function(account) {
			self.accountList.push(OwnMapping.fromJS(account, Defaults.account));
			Site.add_page("accountView,accountI:"+(self.accountList().length-1));
		});
	};
}