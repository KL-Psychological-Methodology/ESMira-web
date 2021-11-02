import html from "./action.html"
import {Lang} from "../js/main_classes/lang";
import {Studies} from "../js/main_classes/studies";
import {ACTION_INVITATION, ACTION_MESSAGE, ACTION_NOTIFICATION} from "../js/variables/constants";
import {option_actions} from "../js/shared/actions";

export function ViewModel(page) {
	this.html = html;
	page.title(Lang.get("edit_action"));
	this.promiseBundle = [Studies.init(page)];
	
	this.preInit = function({id, q, trigger, action}, studies) {
		this.dataObj = studies[id].questionnaires()[q].actionTriggers()[trigger].actions()[action];
	};
	
	this.actions = option_actions;
	this.ACTION_INVITATION = ACTION_INVITATION;
	this.ACTION_MESSAGE = ACTION_MESSAGE;
	this.ACTION_NOTIFICATION = ACTION_NOTIFICATION;
}