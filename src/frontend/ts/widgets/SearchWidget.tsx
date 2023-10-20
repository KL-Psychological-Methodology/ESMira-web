import m, {Component, Vnode} from "mithril"

export interface SearchTools {
	searchView(key: string, content: Vnode<any, any>): Vnode<any, any> | null
	updateSearchFromEvent(e: InputEvent): void
	updateSearch(s: string): void
}
interface SearchBarComponentOptions {
	content: (tools: SearchTools) => Vnode<any, any>
	className?: string //in case the parent has added a className that we need to include
}
class SearchBarComponent implements SearchTools, Component<SearchBarComponentOptions, any> {
	private currentSearchValue: string = ""
	
	public updateSearch(s: string): void {
		this.currentSearchValue = s
	}
	public updateSearchFromEvent(e: InputEvent): void {
		this.updateSearch((e.target as HTMLInputElement).value)
	}
	public searchView(key: string, content: Vnode<any, any>): Vnode<any, any> | null {
		return key.search(this.currentSearchValue) != -1 ? content : null
	}
	
	public view(vNode: Vnode<SearchBarComponentOptions, any>): Vnode<any, any> {
		const view = vNode.attrs.content(this)
		view.attrs.className = `${vNode.attrs.className ?? ""} ${view.attrs.className ?? ""}`
		return view
	}
}

export function SearchWidget(content: (tools: SearchTools) => Vnode<any, any>): Vnode<any, any> {
	return m(SearchBarComponent, { content: content })
}