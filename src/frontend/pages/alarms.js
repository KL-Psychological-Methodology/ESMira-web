import html from "./alarms.html"
import {Lang} from "../js/main_classes/lang";
import {Studies} from "../js/main_classes/studies";
import {Admin} from "../js/main_classes/admin";
import {Defaults} from "../js/variables/defaults";

export function ViewModel(page) {
	this.html = html;
	page.title(Lang.get("edit_actionTrigger"));
	this.promiseBundle = [Studies.init(page)];
	
	this.preInit = function({id}, studies) {
		this.dataObj = studies[id];
	};
	
	
	let listTools = Admin.tools.get_listTools(page);
	this.add_schedule = function(qIndex, questionnaire) {
		let item = listTools.add_obj(questionnaire.actionTriggers, Defaults.actionTriggers, 'trigger,q:'+qIndex+",trigger:%");
		listTools.add_obj(item.schedules, Defaults.schedules);
		listTools.add_obj(item.schedules()[0].signalTimes, Defaults.signalTimes);
		listTools.add_obj(item.actions, Defaults.actions);
	};
	this.add_event = function(qIndex, questionnaire) {
		let item = listTools.add_obj(questionnaire.actionTriggers, Defaults.actionTriggers, "trigger,q:"+qIndex+",trigger:%");
		listTools.add_obj(item.eventTriggers, Defaults.eventTriggers);
		listTools.add_obj(item.actions, Defaults.actions);
	};
	this.ko__remove_from_list = listTools.ko__remove_from_list;
}