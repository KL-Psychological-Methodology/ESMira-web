import html from "./screenshots.html"
import {
	URL_ABOUT_ESMIRA_SOURCE,
} from "../js/variables/urls";
import {Lang} from "../js/main_classes/lang";
import {bindEvent, close_on_clickOutside, createElement} from "../js/helpers/basics";
import {get_aboutESMira_json} from "../js/shared/about_esmira";

export function ViewModel(page) {
	this.html = html;
	page.title(Lang.get("screenshots"));
	
	this.URL_ABOUT_ESMIRA_SOURCE = URL_ABOUT_ESMIRA_SOURCE;
	
	this.promiseBundle = [
		get_aboutESMira_json()
	];
	this.preInit = function(index, {page_screenshots}) {
		this.page_screenshots = page_screenshots;
	}
	this.postInit = function({screenshots}) {
		window.setTimeout(function() {
			let el = document.getElementById('screenshots_' + screenshots);
			if(el)
				el.scrollIntoView({behavior: 'smooth'});
		}, 500);
	}
	
	this.clickScreenshot = function({src, desc}) {
		// let el = createElement('img', 'max-width: 90%; max-height: 90%; top: 50%; transform: translate(-50%, -50%);', {src: URL_ABOUT_ESMIRA_SOURCE + src, className: 'dropdown'});
		
		let el = createElement('div', false, {className: "screenshot_window"});
		let imgEl = createElement('img', false, {src: URL_ABOUT_ESMIRA_SOURCE + src});
		el.appendChild(imgEl);
		if(desc) {
			let descEl = createElement('div', false, {innerText: desc, className: "desc"});
			el.appendChild(descEl);
		}
		document.body.appendChild(el);
		bindEvent(el, 'click', close_on_clickOutside(el));
	}
}