import {Site} from "./site";
import navigation_row from "./navigation_row.html";
import {
	close_on_clickOutside,
	createElement,
	createFloatingDropdown
} from "../helpers/basics";
import ko from 'knockout';
import {PromiseCache} from "./promise_cache";

export const NavigationRow = {
	view: null,
	admin: null,
	pages: null,
	navMenuWidth: ko.observable(0),
	showNavigation: ko.observable(false),
	headerWidth: ko.observable(0),
	headerPos: ko.observable(0),
	el_naviContent: null,
	el_naviRoot: null,
	_dropdownCloseFu: false,
	init: function(root, pages) {
		this.pages = pages;
		
		this.showNavigation = ko.computed({
			read: function() {
				return pages().length > 1;
			}
		});
		
		pages.subscribe(this.update_navi_dimensions.bind(this));
		let rootEl = createElement("div", false, {
			id: "current_stateInfo_el",
			innerHTML: navigation_row
		}, {
			"data-bind": "style: {opacity: $root.showNavigation() ? 1 : 0, transform: $root.showNavigation() ? 'translateY(0px)' : 'translateY(25px)', right: $root.headerPos()+'%', width: $root.headerWidth()+'%'}"
		});
		root.appendChild(rootEl);
		ko.applyBindings(this, rootEl);
		
		
		this.el_naviContent = document.getElementById('nav_content');
		this.el_naviRoot = rootEl.firstChild;
	},
	enableAdmin() {
		let self = this;
		return PromiseCache.getOrNull("navigationRowAdmin") || PromiseCache.save("navigationRowAdmin", (
			import("../dynamic_imports/navigation_row_admin.js").then(function({NavigationRowAdmin}) {
				self.admin = NavigationRowAdmin;
				NavigationRowAdmin.init(self.el_naviRoot);
		})));
	},
	
	positionNavi: function(percent, onScreenNum) {
		this.headerPos((100 - percent * onScreenNum) / 2);
		this.headerWidth(percent);
	},
	get_hash: function(depth) {
		return Site.get_hash(depth);
	},
	update_navi_dimensions: function() {
		let self = this;
		window.setTimeout(function() {
			self.navMenuWidth(self.el_naviContent.clientWidth);
		}.bind(this), 50);
	},
	
	eventClick: function(page, e) {
		e.preventDefault();
		Site.position_page(page.depth);
		Site.update_siteName(page.depth);
	},
	eventPointerenter: function(page) {
		page.parentEl.classList.add("point_out");
	},
	eventPointerleave: function(page) {
		page.parentEl.classList.remove("point_out");
	},
	
	openAlternativesDropdown: function(page, e) {
		if(this._dropdownCloseFu) {
			if(this._dropdownCloseFu()) {
				this._dropdownCloseFu = null;
				return;
			}
			this._dropdownCloseFu = null;
		}
		let target = e.target;
		let dropdownEl = createFloatingDropdown(target, "navAlternatives", true);
		let entries = page.getAlternatives();
		for(let i=0, max=entries.length; i<max; ++i) {
			let entry = entries[i];
			let el;
			
			if(entry.disabled)
				el = createElement("span", false, {innerText: entry.title, className: "line disabled"});
			else {
				el = createElement("span", false, {className: "line"});
				el.appendChild(createElement("a", false, {innerText: entry.title, href: entry.url}));
			}
			dropdownEl.appendChild(el);
		}
		
		
		this._dropdownCloseFu = close_on_clickOutside(dropdownEl, false, true);
	}
}