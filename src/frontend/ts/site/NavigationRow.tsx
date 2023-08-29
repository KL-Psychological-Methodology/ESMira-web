import {Section} from "./Section";
import m, {Vnode} from 'mithril';
import {Lang} from "../singletons/Lang";
import {SiteData} from "./SiteData";
import publishSvg from "../../imgs/icons/increaseVersion.svg?raw";
import {DropdownMenu} from "../widgets/DropdownMenu";

export class NavigationRow {
	private readonly view: HTMLElement
	private readonly pages: Array<Section>
	private widthPercent: number = 100
	private posRightPercent: number = 0
	private siteData: SiteData
	
	constructor(view: HTMLElement, pages: Array<Section>, siteData: SiteData) {
		this.view = view.appendChild(document.createElement("div"))
		this.pages = pages
		this.siteData = siteData
		this.renderView()
		
		window.onbeforeunload = () => {
			return this.siteData.dynamicValues.get("showSaveButton") || siteData.studyLoader.hasUnsavedStudy()
				? Lang.get("confirm_leave_page_unsaved_changes")
				: undefined;
		};
	}
	
	public positionNavi(percent: number): void {
		if(this.pages.length <= 1)
			return
		const visiblePagesCount = Math.min(this.pages.length, Math.floor(100 / percent))
		this.posRightPercent = (100 - percent * visiblePagesCount) / 2
		this.widthPercent = percent
	}
	
	private eventClick(section: Section, e: Event): void {
		e.preventDefault();
		this.siteData.currentSection = section.depth
		m.redraw()
	}
	private eventPointerEnter(section: Section): void {
		section.isMarked = true
		m.redraw()
	}
	private eventPointerLeave(section: Section): void {
		section.isMarked = false
		m.redraw()
	}
	
	private getSectionEntry(section: Section): Vnode<any, any> {
		return <span onpointerenter={this.eventPointerEnter.bind(this, section)} onpointerleave={this.eventPointerLeave.bind(this, section)}>
			{section.sectionContent?.hasAlternatives() &&
				DropdownMenu("alternative",
					<span class="dropdownOpener"></span>,
					(close) => this.getAlternativeContent(section, close),
					{ dontCenter: true }
				)
			}
			
			{this.getAlternativeLink(section)}
		</span>
	}
	private getCurrentSectionEntry(section: Section): Vnode<any, any> {
		return DropdownMenu("alternative",
			<span onpointerenter={this.eventPointerEnter.bind(this, section)} onpointerleave={this.eventPointerLeave.bind(this, section)}>
				<span class="dropdownOpener"></span>
				{this.getAlternativeLink(section)}
			</span>,
			(close) => this.getAlternativeContent(section, close),
			{ dontCenter: true }
		)
	}
	
	
	private getAlternativeLink(section: Section): Vnode<any, any> {
		return <a href={section.getHash()} onclick={this.eventClick.bind(this, section)}>
			{section.getSectionTitle() || Lang.get("state_loading")}
		</a>
	}
	private getAlternativeContent(section: Section, close: () => void): Vnode<any, any> {
		return <div class="navAlternatives">
			{section.sectionContent?.getAlternatives()?.map((entry) =>
				entry.target
					? <a class="line" href={section.sectionContent?.getUrl(entry.target, section.depth - 1)} onclick={() => close()}>{entry.title}</a>
					: <span class="line disabled">{entry.title}</span>
			)}
		</div>
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
				let sections: Section[]
				if(this.siteData.onlyShowLastSection)
					sections = [this.pages[this.pages.length-1]]
				else
					sections = this.pages
				
				return (
					<div id="navigationRow" class={sections.length > 1 ? "visible" : ""} style={`right: ${this.posRightPercent}%; width: ${this.widthPercent}%`}>
						<div id="navigationRowPositioner">
							<div id="titleBoxRoot">
								<div id="titleBoxAbsolute">
									<div id="titleBox">
										<div id="navMenu">
											<div id="navContent">{
												sections.map((section) => {
													return <span onpointerenter={this.eventPointerEnter.bind(this, section)} onpointerleave={this.eventPointerLeave.bind(this, section)}>
														{section.sectionContent?.hasAlternatives()
															? (currentSelection == section.depth
																? this.getCurrentSectionEntry(section)
																: this.getSectionEntry(section)
															)
															: this.getAlternativeLink(section)
															
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
									class={this.siteData.dynamicValues.get("showSaveButton") ? "highlight clickable visible" : "highlight clickable"}
								>{Lang.get("save")}</div>
							}
							{this.siteData.admin.isLoggedIn() &&
								<div
									onclick={() => {(this.siteData.dynamicCallbacks.publish ?? (() => console.error("No publish callback!")) )()}}
									id="publishBox"
									class={this.siteData.dynamicValues.get("showPublishButton") ? "clickable visible" : "clickable"}
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