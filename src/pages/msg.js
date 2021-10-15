import html from "./msg.html"
import {Lang} from "../js/main_classes/lang";
import {Site} from "../js/main_classes/site";
import {Studies} from "../js/main_classes/studies";
import {reloadMessages} from "../js/shared/messages";
import ko from "knockout";
import {close_on_clickOutside, filter_box, safe_confirm} from "../js/helpers/basics";
import {Requests} from "../js/main_classes/requests";
import {FILE_ADMIN} from "../js/variables/urls";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	this.promiseBundle = [Studies.init(page)];
	
	
	this.preInit = function() {
		this.recipient(recipient); //we want to trigger its subscribe
	};
	this.postInit = function({id}) {
		if(!recipient.length) {
			Requests.load(FILE_ADMIN + "?type=list_usernames&study_id=" + id).then(function(user) {
				user.sort();
				self.userList(user);
			});
		}
	};
	
	let recipient = Site.valueIndex.hasOwnProperty("recipient") ? atob(Site.valueIndex.recipient) : "";
	
	this.app_types = [
		"Android",
		"iOS"
	];
	
	this.fixedRecipient = !!recipient;
	this.recipient = ko.observable();
	this.toAll = ko.observable(false);
	this.appVersion = ko.observable("");
	this.appType = ko.observable();
	this.content = ko.observable("");
	this.userList = ko.observableArray();
	
	this.currentArchive = ko.observableArray([]);
	this.currentPending = ko.observableArray([]);
	this.currentUnread = ko.observableArray([]);
	
	this.recipient.subscribe(function(recipient) {
		page.title(recipient || Lang.get("message"));
		load_recipientMessages(recipient);
	});
	
	
	let load_recipientMessages = function(recipient) {
		page.loader.loadRequest(FILE_ADMIN+"?type=list_messages&study_id="+Site.valueIndex.id+"&user="+recipient).then(function({archive, pending, unread}) {
			self.currentArchive(archive);
			self.currentPending(pending);
			self.currentUnread(unread);
			window.setTimeout(function() {
				document.getElementById("msgSendBtn").scrollIntoView({behavior: 'smooth'});
			}, 100);
		});
	};
	
	let get_timestampBox = function() {
		let timestamps = [];
		let list_unread = self.currentUnread();
		if(!list_unread.length)
			return {user: self.recipient(), timestamps: []};
		
		for(let i = list_unread.length-1; i>=0; --i) {
			timestamps.push(list_unread[i]["sent"]);
		}
		
		return {
			user: self.recipient(),
			timestamps: timestamps
		};
	};
	
	this.filter_recipientList = function(_, e) {
		let value = e.target.value;
		let el = document.getElementById('recipientList');
		el.classList.remove('hidden');
		close_on_clickOutside(el, function() {el.classList.add("hidden")});
		filter_box(value, el);
	};
	
	this.setMessagesAsRead = function() {
		let obj = get_timestampBox();
		let study_id = Site.valueIndex.id;
		
		page.loader.loadRequest(
			FILE_ADMIN + "?type=messages_setRead&study_id=" + study_id,
			false,
			"post",
			JSON.stringify(obj)
		).then(function() {
			load_recipientMessages(recipient);
			
			reloadMessages(study_id);
			
			let newMessages = Studies.tools.newMessages();
			if(newMessages[study_id]) {
				delete newMessages[study_id];
				newMessages.count(newMessages.count() - 1);
			}
		});
	}
	
	this.remove = function(sentTimestamp) {
		if(!safe_confirm(Lang.get("confirm_delete_message")))
			return;
		
		let study_id = Site.valueIndex.id;
		
		Requests.load(
			FILE_ADMIN + "?type=delete_message",
			false,
			"post",
			"study_id=" + study_id + "&user=" + self.recipient() + "&sent=" + sentTimestamp
		).then(function(newPendingList) {
			console.log(newPendingList);
			self.currentPending(newPendingList);
			reloadMessages(study_id);
		});
	}
	
	this.send = function() {
		let content = self.content();
		let recipient = self.recipient();
		let toAll = self.toAll();
		let appVersion = self.appVersion();
		let appType = self.appType();
		let study_id = Site.valueIndex.id;
		
		if(content.length < 2) {
			page.loader.info(Lang.get("error_short_message"));
			return;
		}
		else if(!toAll && (!recipient || !recipient.length)) {
			page.loader.info(Lang.get("error_not_selected_recipient"));
			return;
		}
		if(!confirm(Lang.get("confirm_distribute_message", content)))
			return;
		
		let box = get_timestampBox();
		
		box.toAll = toAll;
		box.appVersion = appVersion;
		box.appType = appType;
		box.content = content;
		
		Requests.load(
			FILE_ADMIN+"?type=send_message&study_id="+study_id,
			false,
			"post",
			JSON.stringify(box)
		).then(function() {
			reloadMessages(study_id);
			
			if(!toAll)
				load_recipientMessages(recipient);
			
			self.content("");
		});
	};
}