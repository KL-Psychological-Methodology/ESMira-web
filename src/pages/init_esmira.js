import html from "./init_esmira.html"
import {Lang} from "../js/main_classes/lang";
import {Requests} from "../js/main_classes/requests";
import {check_string, save_cookie} from "../js/helpers/basics";
import {FILE_ADMIN} from "../js/variables/urls";
import * as ko from "knockout";
import {Admin} from "../js/main_classes/admin";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	page.title(Lang.get("init_esmira"));
	
	this.promiseBundle = [Admin.init(page)];
	
	this.server_name = ko.observable("");
	
	this.create = function(_, username, password) {
		let server_name = self.server_name();
		
		if(server_name.length < 3 || server_name.length > 30)
			throw Lang.get("error_short_serverName");
		else if(!check_string(server_name))
			throw Lang.get("error_forbidden_characters");
		else {
			return Requests.load(
				FILE_ADMIN + "?type=init_esmira",
				false,
				"post",
				"new_user=" + username + "&pass=" + password + "&server_name=" + server_name
			).then(function(hashed_pass) {
				save_cookie("user", username);
				save_cookie("pass", hashed_pass);
				page.replace("admin");
			});
		}
	}
	
}