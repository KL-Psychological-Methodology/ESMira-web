import ko from "knockout";

export function ShowHide(params) {
	console.log(params);
	let shown = this.shown = ko.observable(false);
	this.title = params.title;
	this.data = params.data;
	this.toggle_show = function() {
		shown(!shown());
	}
}