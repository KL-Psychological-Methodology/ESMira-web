import html from "./user_view.html"
import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {bindEvent, close_on_clickOutside, createElement, save_cookie} from "../js/helpers/basics";
import {Admin} from "../js/main_classes/admin";
import {FILE_ADMIN} from "../js/variables/urls";
import {Studies} from "../js/main_classes/studies";
import {load_userData} from "../js/shared/user_functions";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	this.promiseBundle = [Admin.init(page), Studies.init(page), load_userData()];
	
	this.userData = null;
	
	this.preInit = function({user}, admin, studies, userList) {
		self.userData = userList[user];
		self.dataObj = self.userData;
		if(self.userData)
			page.title(self.userData.username);
		else
			throw new Error(Lang.get("error_unknown"));
	};
	
	
	this.user_toggle_admin = function() {
		let admin = self.userData.admin();
		if(self.userData.username() === Admin.tools.username()) {
			self.userData.admin(!admin);
			Admin.tools.is_rootAdmin(!admin);
			return;
		}
		page.loader.loadRequest(
			FILE_ADMIN + "?type=toggle_admin",
			false,
			"post",
			"user="+self.userData.username() + (admin ? "&admin" : "")
		).then(function() {
			page.loader.info(Lang.get("info_successful"));
		});
	};
	
	this.add_permission = function(permission, el) {
		let list_el = createElement("pre", false, {className: "dropdown"});
		list_el.appendChild(createElement("div", "margin-bottom: 10px; font-weight: bold", {innerText: Lang.get("colon_select_study")}));
		
		let studies = Studies.list();
		for(let i=0, max = studies.length; i<max; ++i) {
			let study_id = studies[i].id();
			if(self.userData.hasOwnProperty(permission)) {
				if(self.userData[permission].indexOf(parseInt(study_id)) !== -1)
					continue;
			}
			else
				continue;
			
			let line = createElement("div", false, {innerText: studies[i].title(), className: "clickable"});
			bindEvent(line, "click", function() {
				page.loader.loadRequest(
					FILE_ADMIN + "?type=add_userPermission",
					false,
					"post",
					"user="+self.userData.username() + "&permission="+permission + "&study_id="+study_id
				).then(function() {
					if(self.userData.hasOwnProperty(permission))
						self.userData[permission].push(ko.observable(study_id));
					if(permission === "publish" && self.userData.write.indexOf(study_id) === -1)
						self.userData.write.push(ko.observable(study_id));
					
					if(list_el.parentNode)
						list_el.parentNode.removeChild(list_el);
					page.loader.info(Lang.get("info_successful"));
				});
			});
			list_el.appendChild(line);
		}
		
		close_on_clickOutside(list_el);
		el.parentNode.insertBefore(list_el, el);
	}
	this.remove_permission = function(index, study_id, permission) {
		page.loader.loadRequest(
			FILE_ADMIN + "?type=delete_userPermission",
			false,
			"post",
			"user="+self.userData.username() + "&permission="+permission + "&study_id="+study_id
		).then(function() {
			if(self.userData.hasOwnProperty(permission))
				self.userData[permission].splice(index, 1);
			page.loader.info(Lang.get("info_successful"));
		});
	};
	
	this.change_username = function() {
		return Admin.tools.change_username(page, self.userData.username()).then(function(newUsername) {
			self.userData.username(newUsername);
		}, function() {/*not needed*/});
	};
	this.change_password = function(username, password) {
		return Admin.tools.change_password(page, username, password);
	};
}