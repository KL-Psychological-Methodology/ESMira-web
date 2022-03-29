import * as ko from "knockout";
import {Lang} from "../js/main_classes/lang";

export function TabBar({tabs, selectedIndex, showAllTab, tabName, addFu, draggable}) {
	let self = this;
	
	this.selectedIndex = selectedIndex || ko.observable(showAllTab ? -1 : 0);
	
	this.is_selected = function(index) {
		return self.selectedIndex() === index;
	}
	
	this.realTabs = tabs;
	this.listTabs = ko.computed( function() {
		let realTabs = (typeof tabs === "function") ? tabs() : tabs;
		let get_title = function(index) {
			let tab = index !== -1 ? realTabs[index] : null;
			return tabName ? tabName(tab) : tab;
		}
		let listTabs = [];
		
		if(showAllTab) {
			listTabs.push({
				content: showAllTab === true ? Lang.get("all") : showAllTab,
				clickFu: self.selectedIndex.bind(self,-1),
				index: -1,
				is_allTab: true,
				draggable: false
			});
		}
		
		for(let i=0, max=realTabs.length; i<max; ++i) {
			listTabs.push({
				content: get_title(i),
				clickFu: self.selectedIndex.bind(self, i),
				tabData: realTabs[i],
				index: i,
				draggable: draggable || false
			});
		}
		
		if(addFu) {
			listTabs.push({
				content: "+",
				clickFu: addFu,
				index: -2,
				is_addBtn: true,
				draggable: false
			});
		}
		return listTabs;
	});
	
	
}