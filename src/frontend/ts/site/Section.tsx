import m, {Vnode} from "mithril";
import {LoaderState} from "./LoaderState";
import {SiteData} from "./SiteData";
import {Lang} from "../singletons/Lang";
import {SectionContent} from "./SectionContent";
import backSvg from "../../imgs/icons/back.svg?raw";
import starEmptySvg from "../../imgs/icons/star_empty.svg?raw";
import starFilledSvg from "../../imgs/icons/star_filled.svg?raw";
import {StaticValues} from "./StaticValues";
import {DynamicValues} from "./DynamicValues";
import { BookmarkLoader } from "../loader/BookmarkLoader";
import {SectionData} from "./SectionData";

interface Attributes {
	sectionData: SectionData
	siteData: SiteData
}

/**
 * {@link Section} is responsible for displaying the {@link LoaderState} and loading the content.
 * The content is dynamically loaded and inherits from {@link SectionContent}.
 * Each {@link Section} is mostly independent of each other. Their main source of data comes from:
 * - {@link SectionData} Holds the main section data and holds callbacks to this section
 * - {@link SiteData}: Holds the {@link StudyLoader} and other data and convenience methods. It is shared between all sections.
 * - {@link DynamicValues}: A Record saved in {@link SiteData} with observables that are shared between all sections.
 * - {@link StaticValues}: Values that are not changed (for the section) after initialisation of a section.
 * 		Each section has their own copy which includes copies of values from all previous sections.
 * 		It is not meant to store data but only as a way of accessing variables from the url hash
 *
 */
export const Section: m.ClosureComponent<Attributes> = function(vNode: Vnode<Attributes>) {
	/**
	 * Loads the required resources and dynamically imports the content for the specified or the login section if needed.
	 *
	 * @return A promise that resolves when all loading operations are successfully completed,
	 * or rejects with an appropriate error if an issue occurs during the process.
	 */
	function load(): Promise<void> {
		sectionContent = null
		setCallbacks()
		loadingPromise = sectionData.loader.showLoader(new Promise<any>(async (resolve, reject) => {
			try {
				await Lang.awaitPromise()
				
				const adminSuccessOrNotNeeded = await sectionData.getAdmin().init()
				let actualSectionName
				if(!adminSuccessOrNotNeeded) {
					sectionData.siteData.onlyShowLastSection = true
					actualSectionName = "login"
				}
				else
					actualSectionName = sectionData.sectionName
				
				let Content
				try {
					const importedContent = await import(`../sections/${actualSectionName}.tsx`)
					Content = importedContent.Content
				}
				catch(e: any) {
					reject(Lang.get("error_pageNotFound", actualSectionName))
					return
				}
				
				const loadResponses = await Promise.all(Content.preLoad(sectionData))
				const loadedSectionContent = new Content(sectionData, ...loadResponses) as SectionContent
				await loadedSectionContent.preInit(... loadResponses)
				sectionContent = loadedSectionContent
				
				resolve(loadedSectionContent.getSectionCallback())
			}
			catch(e) {
				reject(e)
			}
		}))
		
		return loadingPromise
	}
	
	function setCallbacks() {
		sectionData.callbacks = {
			hasAlternatives: () => sectionContent?.hasAlternatives() ?? false,
			getAlternatives: () => sectionContent?.getAlternatives() ?? null,
			getSectionTitle: getSectionTitle,
			getSectionCallback: () => loadingPromise,
			setMarked: isMarked => {
				isMarkedState = isMarked
				m.redraw()
			},
			reload: async () => {
				sectionContent?.destroy()
				return load()
			}
		}
	}
	
	function getBookmark(): Vnode<any, any> {
		const isLoggedIn = siteData.admin.isLoggedIn()
		if(!isLoggedIn) {
			return <div></div>
		}
		const isBookmarked = sectionData.getAdmin().getTools().bookmarksLoader.hasBookmark(sectionData.getHash())
		return <a
			class={isBookmarked ? "bookmarkActive" : "bookmarkInactive"}
			title={Lang.get(isBookmarked ? "remove_bookmark" : "create_bookmark")}
			onclick={toggleBookmark}>
			{isBookmarked ? m.trust(starFilledSvg) : m.trust(starEmptySvg)}
		</a>
	}
	
	function toggleBookmark(): void {
		const bookmarksLoader: BookmarkLoader = sectionData.getAdmin().getTools().bookmarksLoader
		const hash = sectionData.getHash()
		if(bookmarksLoader.hasBookmark(hash)){
			bookmarksLoader.deleteBookmark(hash)
		} else {
			const defaultName = sectionData.allSections.slice(1).map((section) => section.callbacks?.getSectionTitle()).join(" > ")
			const bookmarkName = prompt(Lang.get("prompt_bookmark_name"), defaultName)
			if(!bookmarkName)
				return
			bookmarksLoader.setBookmark(hash, bookmarkName)
		}
	}
	
	function getSectionContentView(): Vnode<any, any> | undefined {
		try {
			return sectionContent?.getView()
		}
		catch(e: any) {
			sectionData.loader.error(e.message || e)
			console.error(e)
		}
	}
	function getSectionTitle(): string {
		try {
			return sectionContent?.title() || Lang.get("state_loading")
		}
		catch(e: any) {
			console.error(e)
			sectionData.loader.error(e.message || e)
			return Lang.get("error_unknown")
		}
	}
	function getSectionExtras(): Vnode<any, any> | string {
		try {
			return sectionContent?.titleExtra() || ""
		}
		catch(e) {
			return ""
		}
	}

	function eventClick(): void {
		sectionData.siteData.currentSection = sectionData.depth
		m.redraw()
	}
	
	let loadingPromise: Promise<void> = Promise.resolve()
	let {sectionData, siteData} = vNode.attrs
	
	let sectionContent: SectionContent | null = null
	let isMarkedState = false
	
	load().then()
	
	return {
		view(): m.Children {
			return <div class={`section ${sectionData.sectionName} fadeIn ${isMarkedState ? "pointOut" : ""}`}>
				<div class="sectionTop">
					<a href={sectionData.backHash()} class="back">{m.trust(backSvg)}</a>
					<div class="sectionTitle">
						<div class="title" onclick={eventClick}>{getSectionTitle()}</div>
						<div>
							<div class="extra">{getSectionExtras()}{getBookmark()}</div>
						</div>
					</div>
				</div>
				<div class={`sectionContent ${sectionData.sectionName}`}>{getSectionContentView()}</div>
				{sectionData.loader.getView()}
			</div>
		},
		onremove(): any {
			sectionContent?.destroy()
		},
		onupdate(vNode: m.Vnode<Attributes>): void {
			if(vNode.attrs.sectionData.dataCode != sectionData.dataCode) {
				sectionData = vNode.attrs.sectionData
				load().then()
			}
		}
	}
}