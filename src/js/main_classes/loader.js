import {bindEvent, createElement} from "../helpers/basics";
import {Lang} from "./lang";
import loading_page from "./loader.html";
import {Requests} from "./requests";

export function Loader(page) {
	let rootEl = createElement("div", false, {className: "loader", innerHTML: loading_page});
	page.parentEl.appendChild(rootEl);
	
	let self = this,
		state_el = rootEl.querySelector(".loader_state"),
		tryAgain_el = rootEl.querySelector(".loader_retry"),
		loadingAnim_el = rootEl.querySelector(".loader_anim"),
		close_el = rootEl.querySelector(".loader_close"),
		animationId = 0,
		has_error = false,
		has_info = false,
		enabled = false,
		tryAgain_fu = null,
		enableCount = 0;
	
	
	let close_loader = function() {
		has_error = false;
		has_info = false;
		rootEl.classList.remove("isError");
		self.disable();
	};
	
	let showMessage = function(s) {
		window.clearTimeout(animationId);
		enabled = true;
		
		rootEl.classList.remove("hidden");
		loadingAnim_el.classList.add("hidden");
		close_el.classList.remove("hidden");
		
		animationId = window.setTimeout(function() { //wait until class change was registered
			rootEl.classList.add("visible");
		}, 100);
		
		if(s)
			state_el.innerText = s;
	};
	
	bindEvent(close_el, "click", close_loader.bind(this));
	
	bindEvent(tryAgain_el, "click", function() {
		if(tryAgain_fu)
			tryAgain_fu();
	}.bind(this));
	
	this.enable = function(s) {
		if(enabled || has_error)
			return;
		enabled = true;
		state_el.innerText = s;
		
		window.clearTimeout(animationId);
		
		rootEl.classList.remove("hidden");
		loadingAnim_el.classList.remove("hidden");
		tryAgain_el.classList.add("hidden");
		close_el.classList.add("hidden");
		
		animationId = window.setTimeout(function() {
			rootEl.classList.add("visible");
		}, 500);
	};
	
	this.disable = function() {
		if(!enabled || has_error || has_info)
			return;
		
		window.clearTimeout(animationId);
		enabled = false;
		has_error = false;
		
		//we wait for a short period in case another enable() happens right after the current process is done
		animationId = window.setTimeout(function() {
			rootEl.classList.remove("visible");
			animationId = window.setTimeout(function() {
				rootEl.classList.add("hidden");
			}, 200);
		}, 10);
	};
	
	this.update = function(s) {
		if(!enabled || has_error || has_info)
			return;
		
		state_el.innerText = s;
	};
	
	this.showLoader = function(msg, promise) {
		++enableCount;
		this.enable(msg);
		return promise
			.then(function(response) {
				if(--enableCount <= 0)
					self.disable();
				return response;
			})
			.catch(function(e) {
				if(--enableCount <= 0)
					self.disable();
				// console.error(e);
				self.error(e.message || e);
				throw e;
			});
	};
	
	this.info = function(s) {
		if(has_error)
			return;
		has_info = true;
		showMessage(s);
		
		let removeFu = function(e) {
			if(rootEl.contains(e.target))
				return;
			close_loader();
			document.body.removeEventListener("click", removeFu);
		}.bind(this);
		
		window.setTimeout(function() {//The click event that called this function, is not done bubbling. So we have to stall this listener or it will be fired immediately
			bindEvent(document.body, "click", removeFu);
		}, 200);
	};
	
	this.error = function(s, tryAgain) {
		has_error = true;
		showMessage(s);
		
		if(tryAgain) {
			tryAgain_fu = tryAgain;
			tryAgain_el.classList.remove("hidden");
		}
		rootEl.classList.add("isError");
	}
	
	this.loadRequest = function(url, notJson, type, data) {
		return this.showLoader(Lang.get("state_loading"), Requests.load(url, notJson, type, data));
	}
}