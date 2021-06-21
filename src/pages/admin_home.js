import html from "./admin_home.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {delete_cookie} from "../js/helpers/basics";
import {Admin} from "../js/main_classes/admin";

export function ViewModel(page) {
	this.html = html;
	page.title(Lang.get("admin"));
	this.promiseBundle = [Admin.init(page)];
	
	this.changePw = ko.observable(false);
	this.preInit = function(index, admin) {
		let tools = admin.tools;
		this.is_admin = tools.is_rootAdmin();
		this.can_read = tools.is_rootAdmin() || tools.read().length;
		this.can_write = tools.is_rootAdmin() || tools.write().length;
		this.can_msg = tools.is_rootAdmin() || tools.msg().length;
	};
	
	this.logout = function() {
		delete_cookie("user");
		delete_cookie("pass");
		Admin.tools.set_loginStatus(false);
	};
	
	this.change_password = function(username, password) {
		return Admin.tools.change_password(page, username, password);
	};
}