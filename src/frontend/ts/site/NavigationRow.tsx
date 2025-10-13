import m, {Component, Vnode, VnodeDOM} from 'mithril';
import {Lang} from "../singletons/Lang";
import {SiteData} from "./SiteData";
import publishSvg from "../../imgs/icons/increaseVersion.svg?raw";
import {DropdownMenu} from "../components/DropdownMenu";
import {SectionAlternative} from "./SectionContent";
import {LoadingSpinner} from "../components/LoadingSpinner";

import {SectionData} from "./SectionData";

interface DropDownOptions {
	alternatives: SectionAlternative[] | Promise<SectionAlternative[]>
	close: () => void
}
class DropDownComponent implements Component<DropDownOptions, any> {
	private sectionAlternatives?: SectionAlternative[]
	
	public async oninit(vNode: VnodeDOM<DropDownOptions, any>): Promise<void> {
		const alternatives = vNode.attrs.alternatives
		if(alternatives instanceof Promise<SectionAlternative[]>) {
			this.sectionAlternatives = await vNode.attrs.alternatives
			m.redraw()
		}
		else
			this.sectionAlternatives = alternatives
	}
	
	public view(vNode: Vnode<DropDownOptions, any>): Vnode<any, any> {
		if(!this.sectionAlternatives)
			return <div class="center">{LoadingSpinner()}</div>
		
		return <div class="navAlternatives">
			{this.sectionAlternatives?.map((entry) => {
				if(entry.header)
					return <div class="header">{entry.title}</div>
				else
					return entry.target
						? <a class="line" href={entry.target} onclick={() => vNode.attrs.close()}>{entry.title}</a>
						: <span class="line disabled">{entry.title}</span>
				}
			)}
		</div>
	}
}

export class NavigationRow {
	private readonly view: HTMLElement
	private readonly sectionDataArray: SectionData[]
	private widthPercent: number = 100
	private posRightPercent: number = 0
	private siteData: SiteData
	
	constructor(view: HTMLElement, sectionDataArray: SectionData[], siteData: SiteData) {
		this.view = view.appendChild(document.createElement("div"))
		this.sectionDataArray = sectionDataArray
		this.siteData = siteData
		this.renderView()
		
		window.onbeforeunload = () => {
			return this.siteData.dynamicValues.getChild("showSaveButton") || siteData.studyLoader.hasUnsavedStudy()
				? Lang.get("confirm_leave_page_unsaved_changes")
				: undefined;
		};
	}
	
	public positionNavi(percent: number): void {
		if(this.sectionDataArray.length <= 1)
			return
		const visibleSectionCount = Math.min(this.sectionDataArray.length, Math.floor(100 / percent))
		this.posRightPercent = (100 - percent * visibleSectionCount) / 2
		this.widthPercent = percent
	}
	
	private eventClick(data: SectionData, e: Event): void {
		e.preventDefault();
		this.siteData.currentSection = data.depth
		m.redraw()
	}
	private eventPointerEnter(data: SectionData): void {
		data.callbacks?.setMarked(true)
	}
	private eventPointerLeave(data: SectionData): void {
		data.callbacks?.setMarked(false)
		m.redraw()
	}
	
	private getSectionEntry(data: SectionData): Vnode<any, any> {
		return <span onpointerenter={this.eventPointerEnter.bind(this, data)} onpointerleave={this.eventPointerLeave.bind(this, data)}>
			{data.callbacks?.hasAlternatives() &&
				DropdownMenu("alternative",
					<span class="dropdownOpener"></span>,
					(close) => this.getAlternativeContent(data, close),
					{ dontCenter: true }
				)
			}
			
			{this.getAlternativeLink(data)}
		</span>
	}
	private getCurrentSectionEntry(data: SectionData): Vnode<any, any> {
		return DropdownMenu("alternative",
			<span onpointerenter={this.eventPointerEnter.bind(this, data)} onpointerleave={this.eventPointerLeave.bind(this, data)}>
				<span class="dropdownOpener"></span>
				{this.getAlternativeLink(data)}
			</span>,
			(close) => this.getAlternativeContent(data, close),
			{ dontCenter: true }
		)
	}
	
	
	private getAlternativeLink(data: SectionData): Vnode<any, any> {
		return <a href={data.getHash()} onclick={this.eventClick.bind(this, data)}>
			{data.callbacks?.getSectionTitle() || Lang.get("state_loading")}
		</a>
	}
	private getAlternativeContent(data: SectionData, close: () => void): Vnode<any, any> {
		const alternatives = data.callbacks?.getAlternatives()
		if(alternatives) {
			return m(DropDownComponent, {alternatives: alternatives, close: close})
		}
		return <div></div>
	}
	
	private renderView(): void {
		const view = {
			onupdate: () => {
				//This is a dirty fix that I was the best I could come up with:
				// navMenu needs to have a fixed size that is the same as navContent, or it will not be displayed correctly
				// BUT, navContent does not have a (correct) clientWidth until it has finished rendering and its size it dependent on content
				// (which depends on font, so, to my knowledge it can not be calculated properly without rendering).
				// So what we do is wait until the page is done, and then we manually get clientWidth and manually set it in navMenu
				document.getElementById('navMenu')!.style.width = (document.getElementById('navContent')?.clientWidth || 0) + "px"
			},
			view: () => {
				const currentSelection = this.siteData.currentSection
				let dataArray: SectionData[]
				if(this.siteData.onlyShowLastSection)
					dataArray = [this.sectionDataArray[this.sectionDataArray.length-1]]
				else
					dataArray = this.sectionDataArray
				
				return (
					<div id="navigationRow" class={dataArray.length > 1 ? "visible" : ""} style={`right: ${this.posRightPercent}%; width: ${this.widthPercent}%`}>
						<div id="navigationRowPositioner">
							<div id="titleBoxRoot">
								<div id="titleBoxAbsolute">
									<div id="titleBox">
										<div id="navMenu">
											<div id="navContent">{
												dataArray.map((data) => {
													return <span onpointerenter={this.eventPointerEnter.bind(this, data)} onpointerleave={this.eventPointerLeave.bind(this, data)}>
														{data.callbacks?.hasAlternatives()
															? (currentSelection == data.depth
																? this.getCurrentSectionEntry(data)
																: this.getSectionEntry(data)
															)
															: this.getAlternativeLink(data)
														}
													</span>
												})
											}</div>
										</div>
									</div>
								</div>
								<div id="titleBoxShadow"></div>
							</div>
							
							{this.siteData.admin.isLoggedIn() &&
								<div
									onclick={() => {(this.siteData.dynamicCallbacks.save ?? (() => console.error("No save callback!")) )()}}
									id="saveBox"
									class={this.siteData.dynamicValues.getChild("showSaveButton") ? "highlight clickable visible" : "highlight clickable"}
								>{Lang.get("save")}</div>
							}
							{this.siteData.admin.isLoggedIn() &&
								<div
									onclick={() => {(this.siteData.dynamicCallbacks.publish ?? (() => console.error("No publish callback!")) )()}}
									id="publishBox"
									class={this.siteData.dynamicValues.getChild("showPublishButton") ? "clickable visible" : "clickable"}
									title={Lang.get("info_publish")}
								>{m.trust(publishSvg)}</div>
							}
						</div>
					</div>
				)
			}
		}
		m.mount(this.view, view)
	}
}