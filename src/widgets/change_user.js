import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {close_on_clickOutside} from "../js/helpers/basics";

export function ChangeUser_viewModel(page, rootEl, params) {
	let self = this;
	
	this.stayOpen = params.stayOpen || false;
	
	this.isOpen = ko.observable(false);
	this.passwordValid = ko.observable(false);
	this.password = ko.observable('');
	this.passwordRepeat = ko.observable('');
	//knockout wraps the username in an observable not matter what - I dont really understand why...
	this.username = ko.observable(typeof params.username === "function" ? params.username() : params.username);
	this.needsUsername = !this.username;
	
	this.open = function() {
		self.isOpen(true);
		close_on_clickOutside(rootEl, function() {self.isOpen(false);});
	}
	
	this.save = function() {
		if(self.needsUsername && self.username().length < 3)
			page.loader.error(Lang.get('error_short_username'));
		else if(self.check_badPassword(self.password()))
			page.loader.error(Lang.get('error_bad_password'));
		else {
			params.finish(self.username(), self.password()).then(function() {
				self.password('');
				self.passwordRepeat('');
				self.isOpen(false);
				page.loader.info(Lang.get("info_successful"));
			}).catch(function(e) {
				page.loader.info(e.message || e);
			});
		}
	}
	
	this.check_badPassword = function(pass) {
		let l = pass.length;
		return 3 - [l >= 4, l >= 8, l >= 12].filter(Boolean).length;
	}
	this.visualize_password = function(el) {
		let pass = el.value;
		if(pass.length === 0) {
			el.style.borderBottom = "";
			return;
		}
		
		let color;
		let l = pass.length;
		if(l >= 12)
			color = 'lightgreen';
		else if(l >= 8)
			color = 'yellow';
		else if(l >= 4)
			color = 'orange';
		else
			color = 'orangered';
		
		el.style.borderBottom = "3px solid "+color;
	}
}