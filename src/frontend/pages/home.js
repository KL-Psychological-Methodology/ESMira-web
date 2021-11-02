import html from "./home.html"
import {Lang} from "../js/main_classes/lang";

export function ViewModel(page) {
	this.html = html;
	page.title(Lang.get("home"));
}