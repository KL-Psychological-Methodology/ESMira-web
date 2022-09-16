import {Lang} from "../js/main_classes/lang";
import ko from "knockout";
import {close_on_clickOutside} from "../js/helpers/basics";

const PASSWORD_MIN_LENGTH = 12;

export function ChangeAccount_viewModel(page, rootEl, params) {
	let self = this;
	
	this.stayOpen = params.stayOpen || false;
	
	this.isOpen = ko.observable(false);
	this.password = ko.observable('');
	this.passwordRepeat = ko.observable('');
	//knockout wraps the accountName in an observable not matter what - I dont really understand why...
	this.accountName = ko.observable(typeof params.accountName === "function" ? params.accountName() : params.accountName);
	this.needsAccountName = !this.accountName();
	this.enableForm = ko.pureComputed(function() {
		return self.password().length >= PASSWORD_MIN_LENGTH && self.password() === self.passwordRepeat();
	});
	this.passMsg = ko.observable();
	
	this.open = function() {
		self.isOpen(true);
		close_on_clickOutside(rootEl, function() {self.isOpen(false);});
	}
	
	this.save = function() {
		if(self.needsAccountName && self.accountName().length < 3)
			page.loader.error(Lang.get('error_short_username'));
		else if(self.password() < PASSWORD_MIN_LENGTH)
			page.loader.error(Lang.get('error_bad_password'));
		else {
			let promise = params.finish(self.accountName(), self.password());
			if(promise) {
				promise.then(function() {
					self.password('');
					self.passwordRepeat('');
					self.isOpen(false);
					page.loader.info(Lang.get("info_successful"));
				}).catch(function(e) {
					page.loader.info(e.message || e);
				});
			}
		}
	}
	
	this.change_password = function(data, e) {
		let el = e.target;
		let pass = el.value;
		let l = pass.length;
		
		self.password(pass);
		
		if(!l) {
			self.passMsg("");
			el.style.outline = "0px solid transparent";
		}
		else if(l >= PASSWORD_MIN_LENGTH) {
			self.passMsg("");
			el.style.outline = "3px solid lightgreen";
		}
		else {
			self.passMsg(Lang.get("minimal_length", PASSWORD_MIN_LENGTH));
			el.style.outline = "3px solid orangered";
		}
	}
	this.change_passwordRepeat = function(data, e) {
		self.passwordRepeat(e.target.value);
	}
}