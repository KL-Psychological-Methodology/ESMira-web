import html from "./account.html"
import {Lang} from "../js/main_classes/lang";
import {Admin} from "../js/main_classes/admin";
import {FILE_ADMIN} from "../js/variables/urls";
import {Requests} from "../js/main_classes/requests";
import * as ko from "knockout";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("edit_user_account"));
	this.promiseBundle = [Requests.load(FILE_ADMIN+"?type=get_tokenList")];
	
	this.token = ko.observableArray();
	this.preInit = function(index, token) {
		token.sort(function(a, b) {
			if(a.lastUsed < b.lastUsed)
				return 1;
			else if(a.lastUsed === b.lastUsed)
				return 0;
			else
				return -1;
		});
		this.token(token);
	};
	
	this.remove_token = function({tokenId}) {
		if(!confirm())
			return;
		page.loader.loadRequest(FILE_ADMIN+"?type=remove_token", false, "post", "token_id="+tokenId).then(function(token) {
			self.token(token);
		})
	}
	
	this.change_password = function(accountName, password) {
		return Admin.tools.change_password(page, accountName, password);
	};
	
	this.change_accountName = function() {
		return Admin.tools.change_accountName(page, Admin.tools.accountName());
	};
}