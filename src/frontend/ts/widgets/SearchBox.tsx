import m, {Vnode} from "mithril"
import {SearchWidget} from "./SearchWidget";
import {Lang} from "../singletons/Lang";

export interface SearchBoxEntry {
	key: string
	view: Vnode<any, any>
}
export function SearchBox(title: string, viewList: SearchBoxEntry[]): Vnode<any, any> {
	return SearchWidget((tools) =>
		<div class="searchBox">
			<h2>{title}</h2>
			<input placeholder={Lang.get("search")} class="search small vertical" type="text" onkeyup={tools.updateSearchFromEvent.bind(tools)}/>
			<div class="scrollBox noBorder">
				{viewList.map((entry) => tools.searchView(entry.key, entry.view))}
			</div>
		</div>
	)
}