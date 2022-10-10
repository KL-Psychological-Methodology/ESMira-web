import html from "./rewards.html"
import {Studies} from "../js/main_classes/studies";
import {Lang} from "../js/main_classes/lang";
import * as ko from "knockout";

export function ViewModel(page) {
	let self = this;
	this.html = html;
	this.promiseBundle = [
		Studies.init(page)
	];
	page.title(Lang.get("reward_system"));
	this.extraContent = "<a class=\"right\" data-bind=\"navi: 'rewardCode', text: Lang.get('validate_reward_code')\"></a>";
	
	
	this.preInit = function({id}, studies) {
		let study = studies[id];
		this.dataObj = study;
		
		this.rewardEmailContentCopy = ko.pureComputed({
			read: function() {
				return study.rewardEmailContent();
			},
			write: function(newValue) {
				self.faultyEmailContent(newValue.length && newValue.indexOf("[[CODE]]") === -1);
				study.rewardEmailContent(newValue);
			}
		});
	};
	
	this.faultyEmailContent = ko.observable(false);
}