import {Lang} from "../js/main_classes/lang";
import {Requests} from "../js/main_classes/requests";
import {FILE_STATISTICS} from "../js/variables/urls";
import {createElement} from "../js/helpers/basics";
import {Studies} from "../js/main_classes/studies";
import {Admin} from "../js/main_classes/admin";

export function ViewModel(page) {
	this.promiseBundle = [
		Studies.init(page),
		import("../js/dynamic_imports/statistic_tools"),
		Admin.wait_ifNeeded(page)
	];
	
	this.postInit = function({id}, studies, {drawCharts}, admin) {
		let study = studies[id];
		
		page.title(study.title);
		
		
		let accessKey = study.accessKeys().length ? study.accessKeys()[0]() : '';
		page.loader.showLoader(
			Lang.get("state_loading"),
			Requests.load(
				FILE_STATISTICS.replace("%d", study.id()).replace("%s", accessKey)
			).then(function(statistics) {
				let charts = study.publicStatistics.charts();
				let el = createElement("div");
				
				page.contentEl.appendChild(el);
				drawCharts(el, charts, statistics, false, admin.is_loggedIn() ? Admin.tools.has_readPermission(study.id()) : false);
			})
		);
	};
}