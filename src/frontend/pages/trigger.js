import html from "./trigger.html"
import {Lang} from "../js/main_classes/lang";
import {Studies} from "../js/main_classes/studies";
import {
	option_actions
} from "../js/shared/actions";
import {Admin} from "../js/main_classes/admin";

export function ViewModel(page) {
	this.html = html;
	page.title(Lang.get("edit_actionTrigger"));
	this.promiseBundle = [Studies.init(page)];
	
	this.preInit = function({id, q, trigger}, studies) {
		let questionnaire = studies[id].questionnaires()[q];
		this.dataObj = questionnaire.actionTriggers()[trigger];
		page.title(questionnaire.title);
	};
	
	this.dayValues_of_month = [
		Lang.get("disabled"),
		1, 2, 3, 4, 5, 6, 7, 8, 9, 10,
		11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
		21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31
	];
	this.random_fixed = [
		{key: true, value: Lang.get("random_fixed_true")},
		{key: false, value: Lang.get("random_fixed_false")}
	];
	this.cues = [
		"actions_executed",
		"invitation",
		"invitation_missed",
		"joined",
		"quit",
		"questionnaire",
		"rejoined",
		"reminder",
		"schedule_changed",
		"statistic_viewed",
		"study_message",
		"study_updated"
	];
	this.actions = option_actions;
	
	let listTools = Admin.tools.get_listTools(page);
	this.ko__add_default = listTools.ko__add_default;
	this.ko__remove_from_list = listTools.ko__remove_from_list;
}