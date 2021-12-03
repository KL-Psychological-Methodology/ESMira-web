import {filter_box} from "../js/helpers/basics";
import ko from "knockout";

export function SearchBox(el, {titleKey, count, list}) {
	
	this.titleKey = titleKey;
	this.count = count;
	this.list = list;
	
	let listEl = el.getElementsByClassName("scrollBox")[0];
	this.doSearch = function(_, e) {
		let value = e.target.value;
		filter_box(value, listEl);
	}
}