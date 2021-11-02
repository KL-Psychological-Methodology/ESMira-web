import html from "./login.html"
import {Lang} from "../js/main_classes/lang";
import {FILE_ADMIN} from "../js/variables/urls";
import ko from "knockout";
import {Admin} from "../js/main_classes/admin";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("login"));
	
	this.username = ko.observable("");
	this.password = ko.observable("");
	this.rememberMe = ko.observable(false);
	
	this.login = function() {
		let username = self.username();
		let password = self.password();
		let rememberMe = self.rememberMe() ? "&rememberMe" : "";
		
		page.loader.loadRequest(FILE_ADMIN+"?type=login", false, "post", "user="+username+"&pass="+password+rememberMe).then(function(data) {
			Admin.tools.username(username);
			Admin.tools.set_loginStatus(data);
			// page.reload();
		}).catch(function() {
			page.loader.info(Lang.get("error_wrong_login"));
		});
	};
}