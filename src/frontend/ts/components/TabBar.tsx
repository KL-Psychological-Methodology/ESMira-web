import m, {Vnode} from "mithril"
import {ObservablePrimitive} from "../observable/ObservablePrimitive";
import {DragContainer} from "./DragContainer";
import {BaseObservable} from "../observable/BaseObservable";
import {ArrayInterface} from "../observable/interfaces/ArrayInterface";

export interface TabContent {
	title: string | Vnode<any, any>
	draggableList?: ArrayInterface<any, BaseObservable<any>>
	highlight?: boolean
	view: () => Vnode<any, any>
}

export function TabBar(selectedIndex: ObservablePrimitive<number>, tabs: (TabContent | undefined | false)[], smallBar: boolean = false, addBtnCallback?: (e: MouseEvent) => void): Vnode<any, any> {
	if(!tabs.length)
		return (<div></div>)
	
	if(selectedIndex.get() >= tabs.length)
		selectedIndex.set(0)
	return (
		DragContainer((dragTools) => {
			const selectedTab = tabs[selectedIndex.get()]
			return <div>
				<div>
					<div class={smallBar ? "tabBar smallBar" : "tabBar"}>
						{
							tabs.map((tab, index) => {
								if(!tab)
									return
								const className = `tab ${(selectedIndex.get() == index) ? "selected" : ""} ${tab.highlight ? "highlight" : ""}`
								const content = <div class={className} onclick={() => {selectedIndex.set(index)}}>
									<div class="left">
										{tab.draggableList && dragTools.getDragStarter(index, tab.draggableList)}
									</div>
									<span class="middle">{tab.title}</span>
								</div>
								return tab.draggableList
									? dragTools.getDragTarget(index, tab.draggableList, content)
									: content
							})
						}
						{addBtnCallback &&
							<div class="tab addBtn" onclick={addBtnCallback}>
								<span class="middle">+</span>
							</div>
						}
					</div>
				</div>
				<div class="tabContent">
					{selectedTab && selectedTab.view()}
				</div>
			</div>
		})
	)
}