import html from "./rewards.html"
import {Studies} from "../js/main_classes/studies";
import {Lang} from "../js/main_classes/lang";

export function ViewModel(page) {
	this.html = html;
	this.promiseBundle = [
		Studies.init(page)
	];
	page.title(Lang.get("reward_system"));
	this.extraContent = "<a class=\"right\" data-bind=\"navi: 'rewardCode', text: Lang.get('validate_reward_code')\"></a>";
	
	
	this.preInit = function({id}, studies) {
		this.dataObj = studies[id];
	};
}