import html from "./admin_home.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {Admin} from "../js/main_classes/admin";
import {FILE_ADMIN} from "../js/variables/urls";

export function ViewModel(page) {
	this.html = html;
	page.title(Lang.get("admin"));
	this.promiseBundle = [Admin.init(page)];
	
	this.changePw = ko.observable(false);
	this.preInit = function(index, admin) {
		let tools = admin.tools;
		if(tools.is_rootAdmin()) {
			this.isAdmin = true;
			this.canRead = true;
			this.canWrite = true;
			this.canCreate = true;
			this.canMsg = true;
		}
		else {
			this.isAdmin = false;
			this.canRead = tools.read().length;
			this.canWrite = tools.write().length;
			this.canCreate = tools.canCreate();
			this.canMsg = tools.msg().length;
		}
	};
	
	this.logout = function() {
		page.loader.loadRequest(FILE_ADMIN+"?type=logout").then(function() {
			Admin.tools.set_loginStatus({});
		});
	};
	
	this.change_password = function(accountName, password) {
		return Admin.tools.change_password(page, accountName, password);
	};
}