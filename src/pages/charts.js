import html from "./charts.html"
import {Lang} from "../js/main_classes/lang";
import {Studies} from "../js/main_classes/studies";
import {Admin} from "../js/main_classes/admin";

export function ViewModel(page) {
	this.html = html;
	page.title(Lang.get("create_charts"));
	this.promiseBundle = [Studies.init(page)];
	
	this.preInit = function({id}, studies) {
		this.dataObj = studies[id];
	};
	
	let listTools = Admin.tools.get_listTools(page);
	this.ko__remove_from_list = listTools.ko__remove_from_list;
	this.ko__add_default = listTools.ko__add_default;
}