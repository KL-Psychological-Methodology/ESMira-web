import {Lang} from "../main_classes/lang";

export function bindEvent(el, e, func) {
	if(el.addEventListener)
		el.addEventListener(e, func, false);
	else if(el.attachEvent)
		el.attachEvent("on" + e, func, false);
}

export function createElement(el_type, css, values, attr) {
	let el = document.createElement(el_type);
	
	if(css)
		el.style.cssText = css;
	if(values)
		for(let i in values) {
			if(values.hasOwnProperty(i))
				el[i] = values[i];
		}
	if(attr)
		for(let i in attr)
			if(attr.hasOwnProperty(i))
				el.setAttribute(i, attr[i]);
	
	return el;
}

export function createFloatingDropdown(referenceEl, className) {
	let rect = referenceEl.getBoundingClientRect();
	let x = rect.left + rect.width/2;
	let y = Math.max(0, rect.top + rect.height + 1);
	
	let dropdownEl = createElement("div", "left:" + x + "px; top:" + y + "px;", {className: "dropdown"});
	dropdownEl.classList.add(className);
	document.body.appendChild(dropdownEl);
	return dropdownEl;
}

export function filter_box(search_text, box_el) {
	let children = box_el.children;
	for(let i = children.length - 1; i >= 0; --i) {
		let el = children[i];
		let searchEl = el.childElementCount ? el.getElementsByClassName("searchTarget")[0] : el;
		if(!searchEl)
			continue;
		el.style.display = searchEl.innerText.startsWith(search_text) ? 'block' : 'none';
	}
}

export function close_on_clickOutside(el, custom_closeFu) {
	if(el.hasOwnProperty("close-outside"))
		return
	el["close-outside"] = true;
	let closeFu = function() {
		if(!el)
			return false;
		delete el["close-outside"];
		document.removeEventListener("click", click_outside);
		
		if(custom_closeFu)
			custom_closeFu(el);
		else if(el.parentElement != null)
			el.parentElement.removeChild(el);
		
		el = null;
		return true;
	};
	let click_outside = function(e) {
		let target = e.target;
		
		if(el != null) {
			if(el.contains(target))
				return;
			
			closeFu();
		}
		else
			document.removeEventListener("click", click_outside);
		e.stopPropagation();
	};
	
	window.setTimeout(function() {//if a click event called this function, it is not done bubbling. So we have to stall this listener or it will be fired immediately
		bindEvent(document, "click", click_outside);
	}, 200);
	return closeFu;
}


//for users who have cookies disabled, we at least save stuff locally:
let cookieJar = {};

export function get_cookie(key, regexp) {
	let cookie = document.cookie.match(key + "=" + (regexp || "([^;]+)"));
	return cookie ? decodeURIComponent(cookie[1]) : (cookieJar[key] || null);
}

export function save_cookie(key, value, expires) {
	if(expires < 0)
		delete cookieJar[key];
	else
		cookieJar[key] = value;
	
	document.cookie = key + "=" + encodeURIComponent(value) + "; expires=" + (new Date(expires ? Date.now() + expires : 32532447600000)).toUTCString() + "; SameSite=Lax;";
}
export function delete_cookie(key) {
	save_cookie(key, "", Date.now()-1000);
}

export function check_string(s) {
	return !s || s.match(/^[a-zA-Z0-9À-ž_\-().\s]+$/) != null;
}

export function safe_confirm(msg) {
	return confirm(msg) && (prompt(Lang.get("confirm_again")) || "").toLowerCase() === "ok"
}