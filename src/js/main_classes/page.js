import ko from 'knockout';
import back_svg from '../../imgs/back.svg?raw';
import {Lang} from "./lang";
import {Site} from "./site";
import {bindEvent, createElement} from "../helpers/basics";
import {PageIndex} from "../variables/page_index";
import {Admin} from "./admin";
import {Studies} from "./studies";
import {Loader} from "./loader";

export function Page(depth, code) {
	let self = this;
	
	this.isLoading = false;
	this.codeString = code;
	this.depth = depth;
	this.index = [];
	this.viewModel = null;
	this.el_navi = null;
	this.loader = null;
	this.title = ko.observable(Lang.get("state_loading"));
	this.printTitle = ko.computed(function() {
		let s = self.title();
		return typeof s === "function" ? s() : s;
	});
	this.nextPageCode = ko.observable("");
	
	this.printTitle.subscribe(function() {
		Site.update_siteName();
		// if(!self.nextPageCode().length)
		// 	window.document.title = newValue;
		Site.update_navi_dimensions();
	});
	
	
	//
	// extract values
	//
	
	let extractValues = function(code) {
		let variables = code.split(",");
		let [pageName, pageValue] = variables[0].split(":");
		if(pageValue) //short form
			Site.valueIndex[pageName] = self.index[pageName] = pageValue;
		
		//additional values are variables:
		for(let i=variables.length-1; i>=1; --i) {
			let [key, value] = variables[i].split(":");
			Site.valueIndex[key] = self.index[key] = value === undefined ? true : value;
			
			if(Studies.tools && key === "id")
				Studies.tools.change_observed(value);
		}
		return pageName;
	};
	
	let pageName = extractValues(code);
	
	
	
	//
	// Create Page elements
	//
	
	let parentEl = createElement("div", false, {className: "page"}, {"data-bind": "let: {Lang: $root.Lang, Site: $root.Site, Admin: $root.Admin, Studies: $root.Studies}"}),
		back_el = createElement("a", false,{href: Site.get_hash(depth), className: "back", innerHTML: back_svg}),
		
		title_line_el = createElement("div", false, {className: "page_title"}),
		title_el = createElement("div", false, {className: "title"}, {"data-bind": "text: $root.page.printTitle"}),
		extra_el = createElement("div", false, {className: "extra"}),
		
		content_el = createElement("div", "opacity: 0", {className: "page_content"}, {"data-bind": "with: dataObj"});
	
	title_line_el.appendChild(title_el);
	title_line_el.appendChild(extra_el);
	parentEl.appendChild(back_el);
	parentEl.appendChild(title_line_el);
	parentEl.appendChild(content_el);
	
	bindEvent(title_el, "click", function() {
		Site.position_page(self.depth);
		Site.update_siteName(self.depth);
	});
	bindEvent(title_el, "pointerenter", function() {
		parentEl.classList.add("point_out");
	});
	bindEvent(title_el, "pointerleave", function() {
		parentEl.classList.remove("point_out");
	});
	
	if(!depth)
		parentEl.classList.add("firstPage");
	
	Site.el_pages.appendChild(parentEl);
	window.setTimeout(function() {
		parentEl.style.opacity = "1";
	}, 100);
	
	
	//
	// Find loader elements
	//
	
	this.parentEl = parentEl;
	this.contentEl = content_el;
	this.loader = new Loader(this);
	
	
	//
	// load content
	//
	
	let load = function(pageName) {
		content_el.classList.add(pageName);
		if(pageName === "admin")
			Admin.enable_adminFeatures = true; //studies.init() should load all studies (studies.php has its own login check)
		
		if(!PageIndex.hasOwnProperty(pageName)) {
			self.loader.error(Lang.get("error_pageNotFound", pageName));
			self.title(Lang.get("state_error"));
			return;
		}
		
		
		let pageInfo = PageIndex[pageName];
		self.isLoading = true;
		
		let viewModel;
		
		let promise;
		if(pageInfo[1]) {
			promise = Admin.init(self).then(function(admin) {
				if(!admin.esmira_isInit)
					return "init_esmira";
				else if(!admin.is_loggedIn())
					return "login";
				else {
					let neededPermissions = pageInfo[1];
					if(admin.tools.is_rootAdmin() || neededPermissions[0] === "*")
						return pageInfo[0];
					else {
						let tools = admin.tools;
						let studyId = parseInt(Site.valueIndex.id);
						for(let i=neededPermissions.length-1; i>=0; --i) {
							if(tools[neededPermissions[i]].indexOf(studyId) !== -1)
								return pageInfo[0];
						}
						self.contentEl.innerHTML = "";
						self.loader.error(Lang.get("error_no_permission"));
						return "login";
						// throw Lang.get("error_no_permission");
					}
				}
				// return admin.is_init ? (admin.is_loggedIn() ? pageInfo[0] : "login") : "init_esmira";
			});
		}
		else {
			promise = Promise.resolve(pageInfo[0]);
		}
		
		
		return self.loader.showLoader(Lang.get("state_loading"), promise
			.then(function(page) {
				return import("../../pages/"+page+".js");
			})
			.then(function({ViewModel}) {
				viewModel = new ViewModel(self);
				return Promise.all(viewModel.promiseBundle || []);
			})
			.then(function(responses) {
				responses.unshift(Site.valueIndex);
				if(viewModel.hasOwnProperty("preInit"))
					viewModel.preInit.apply(viewModel, responses);
				
				if(!viewModel.hasOwnProperty("dataObj"))
					viewModel.dataObj = {};
				viewModel.Site = Site;
				viewModel.Admin = Admin;
				viewModel.Studies = Studies;
				viewModel.Lang = Lang;
				viewModel.page = self;
				
				if(viewModel.hasOwnProperty("extraContent"))
					extra_el.innerHTML = viewModel.extraContent;
				
				self.viewModel = viewModel;
				if(viewModel.hasOwnProperty("html"))
					content_el.innerHTML = viewModel.html;
				else
					content_el.innerHTML = "";
				
				ko.applyBindings(viewModel, parentEl);
				
				if(viewModel.hasOwnProperty("postInit"))
					viewModel.postInit.apply(viewModel, responses);
				
				content_el.style.opacity = "1";
				self.isLoading = false;
				
			})
			.catch(function(error) {
				content_el.style.opacity = "1";
				self.isLoading = false;
				// console.error(error);
				self.title(Lang.get("state_error"));
				throw error;
			}))
			.catch(function(error) {
				console.error(error);
			});
	};
	
	
	let promise = load(pageName);
	
	
	
	
	
	//
	// public functions
	//
	
	this.reload = function() {
		ko.cleanNode(parentEl);
		promise = load(pageName);
	};
	
	this.replace = function(code) {
		promise.then(function() {
			self.codeString = code;
			pageName = extractValues(code);
			ko.cleanNode(parentEl);
			promise = load(pageName);
		})
		
		
		// let index = this.index;
		// let values = "";
		// for(let key in index) {
		// 	if(!index.hasOwnProperty(key))
		// 		continue;
		// 	values += ","+key+":"+index[key];
		// }
		// this.destroy();
		// Site.add_pageToIndex(code+values);
	};
	this.destroy = function() {
		//clean index:
		let variables = code.split(",");
		for(let i=variables.length-1; i>=1; --i) {
			let [key, value] = variables[i].split(":");
			if(Site.valueIndex.hasOwnProperty(key) && Site.valueIndex[key] === (value === undefined ? true : value)) {
				delete Site.valueIndex[key];
				
				if(Studies.tools && key === "id")
					Studies.tools.remove_observed(value);
			}
			
		}
		if(this.viewModel && this.viewModel.hasOwnProperty("destroy"))
			this.viewModel.destroy();
		
		ko.cleanNode(parentEl);
		parentEl.parentNode.removeChild(parentEl);
		
		Site.remove_navigation(this);
		Site.pages.splice(depth, 1);
	};
}