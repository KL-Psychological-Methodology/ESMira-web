import { Section } from "./Section";
import langIndex from "../locales.json";
import { NavigationRow } from "./NavigationRow";
import { SiteData } from "./SiteData";
import m from "mithril";
import { Lang } from "../singletons/Lang";
import { Admin } from "../admin/Admin";
import { DropdownMenu } from "../widgets/DropdownMenu";

const SECTION_MIN_WIDTH = 650;

/**
 * Root class that is responsible for all views
 * General concept.
 * {@link Site} holds an array of {@link Section} - one Section for each section you see displayed.
 * {@link Section} is responsible for displaying the {@link LoaderState} and loading the content.
 * The content is dynamically loaded and inherits from {@link SectionContent}.
 *
 * Each {@link Section} is fully independent of each other. Their main source of data comes from
 * - {@link SiteData}: Holds the {@link StudyLoader} and other data and convenience methods. It is shared between all sections.
 * - {@link DynamicValues}: A Record with observables that are shared between all sections - the only means of communicating between sections.
 * 		Should not be cached. That means the value needs to be reloaded every time the view gets recreated
 * - {@link StaticValues}: Values that are not changed (for the section) after initialisation of a section.
 * 		Each section has their own copy which includes copies of values from all previous sections.
 * 		It is not meant to store data but only as a way of accessing variables from the url hash
 *
 * Url hash-syntax:
 * Each section is separated by "/". Each section has the same structure:
 * [sectionName]:[sectionValue?],[firstKey]:[firstValue],[secondKey]:[secondValue]...
 * See implementation in {@link onhashchange()}, {@link addSectionToIndex()} and {@link Section.constructor()}
 *
 * Admin-Panel:
 * See description of {@link Admin} for more information
 */

export class Site {
	public readonly siteData: SiteData
	public readonly serverVersion: number
	private readonly startHash: string
	private readonly sectionsView: HTMLElement
	private readonly sections: Array<Section> = []
	private readonly navigationRow: NavigationRow

	private sectionWidthPercent: number = 100
	private overrideSectionWidth: boolean = false

	constructor(serverName: string, startHash: string, serverVersion: number, packageVersion: string, serverAccessKey: string) {
		this.siteData = new SiteData(
			new Admin(startHash.startsWith("admin") || window.location.hash.startsWith("#admin"), this),
			serverVersion,
			packageVersion
		)
		this.serverVersion = serverVersion
		this.startHash = startHash
		this.navigationRow = new NavigationRow(document.body, this.sections, this.siteData)
		this.sectionsView = document.getElementById("sectionContainer")!

		this.siteData.dynamicValues.getOrCreateObs("accessKey", serverAccessKey)

		//TODO: do in php
		document.getElementById("headerServerName")!.innerText = serverName

		if (this.sectionsView)
			this.sectionsView.innerHTML = ""

		window.onhashchange = this.onhashchange.bind(this)
		this.onhashchange()

		document.getElementById("sectionBoxWidthSetter")?.addEventListener("change", (e) => {
			const p = (e.target as HTMLInputElement).value
			if (this.sectionsView)
				this.sectionsView.style.width = p + "%"

			this.sectionWidthPercent = +p
			this.overrideSectionWidth = true

			this.navigationRow.positionNavi(this.sectionWidthPercent)
			m.redraw()
		});

		window.addEventListener("resize", () => {
			this.updateSectionDimensions()
			m.redraw()
		})
		this.updateSectionDimensions()
		this.renderView()

		// Language selector:
		const self = this
		const selectedEntry = langIndex[Lang.code as keyof typeof langIndex]
		m.render(
			document.getElementById("siteLangChooser")!,
			DropdownMenu("langChooserDropdown",
				<div class="line verticalPadding nowrap clickable">
					<span>{m.trust(selectedEntry.flag)}</span>
					<span class="desc">{selectedEntry.name}</span>
				</div>,
				() => {
					const hash = self.sections[self.sections.length - 1].getHash()
					return <div>{
						Object.keys(langIndex).map((code) => {
							const entry = langIndex[code as keyof typeof langIndex]
							return (
								<a class="line verticalPadding nowrap" href={"?lang=" + code + hash}>
									<span>{m.trust(entry.flag)}</span>
									<span class="desc">{entry.name}</span>
									{entry.aiTranslation && <span>({Lang.get("ai_translation")})</span>}
								</a>
							)
						})
					}</div>
				},
				{ dontCenter: true }
			)
		)
	}

	private updateSectionDimensions(): void {
		const siteWidth = window.innerWidth || document.documentElement.clientWidth;

		if (siteWidth > SECTION_MIN_WIDTH) {
			if (!this.overrideSectionWidth) {
				this.sectionWidthPercent = Math.round(100 / (siteWidth / SECTION_MIN_WIDTH))
				document.body.classList.remove("smallScreen")
			}
		}
		else {
			this.sectionWidthPercent = 93;
			document.body.classList.add("smallScreen")
			this.overrideSectionWidth = false;
		}

		const view = document.getElementById("sectionBoxWidthSetter") as HTMLInputElement
		if (view)
			view.value = Math.round(this.sectionWidthPercent).toString();

		this.navigationRow.positionNavi(this.sectionWidthPercent)
	}


	private addSectionToIndex(dataCode: string): void {
		const section = new Section(dataCode, this.siteData, this.sections)
		section.load()
		this.sections.push(section)
		this.siteData.currentSection = this.sections.length - 1
		m.redraw()
	}

	private removeSection(depth: number): void {
		this.sections[depth].destroy()
		this.sections.splice(depth, 1)
		this.siteData.currentSection = this.sections.length - 1
		m.redraw()
	}

	public renderView(): void {
		const view = {
			view: () => {
				let sections: Section[]
				let currentSection: number
				if (this.siteData.onlyShowLastSection) {
					sections = [this.sections[this.sections.length - 1]]
					currentSection = 0
				}
				else {
					sections = this.sections
					currentSection = this.siteData.currentSection
				}
				window.document.title = this.sections[currentSection]?.getSectionTitle() || Lang.get("state_loading")

				//if there are too many elements, we only divide by max number that fits on the screen:
				const visibleSectionsCount = Math.min(sections.length, Math.floor(100 / this.sectionWidthPercent))

				//we move it by -50% because section is already centered:
				const sectionPositionPercent = (currentSection + 1 - visibleSectionsCount) * 100 + (visibleSectionsCount * 50 - 50)

				return (
					<div id="sectionsView" style={`width: ${this.sectionWidthPercent}%; transform: translate(-${sectionPositionPercent}%)`}>{
						sections.map((section) => section.getView())
					}</div>
				)
			}
		}

		m.mount(this.sectionsView, view)
	}

	public async reload(): Promise<any> {
		return Promise.all(this.sections.map((section) => section.reload()))
	}

	/**
	 * replace css-rules to highlight clicked a tags
	 */
	private async updateHighlightedLinksCss(newSectionsData: string[]): Promise<void> {
		const aRules: string[] = []
		const dashRules: string[] = []
		const svgRules: string[] = []
		let connectedCode = "#" + newSectionsData[0]

		for (let i = 1; i < newSectionsData.length; ++i) {
			connectedCode += "/" + newSectionsData[i]
			const aRule = `.section.${this.sections[i - 1].sectionName} a[href="${connectedCode}"]`
			aRules.push(`${aRule}, ${aRule} span`)
			dashRules.push(`${aRule}.dashLink`)
			svgRules.push(`${aRule} svg`)
		}
		const cssRules = new CSSStyleSheet()

		await cssRules.replace(`${aRules.join(",")}{color:#dc4e9d !important; text-decoration: underline !important;}\n${svgRules.join(",")}{fill: #dc4e9d;}\n${dashRules.join(",")}{background-color:#9fe0f7;}`)
		document.adoptedStyleSheets = [cssRules]
	}

	private onhashchange(): void {
		let hash = window.location.hash
		if (hash.length === 0)
			hash = this.startHash
		else
			hash = hash.substring(1)

		if (hash.startsWith("admin"))
			this.siteData.admin.enableAdmin()

		const newSectionData = hash.split("/")
		if (hash.slice(-1) === "/")
			newSectionData.pop()

		//find unneeded sections:
		let firstI = 0
		while (firstI < newSectionData.length && firstI < this.sections.length && newSectionData[firstI] === this.sections[firstI].dataCode) {
			++firstI
		}

		//remove unneeded sections:
		for (let i = this.sections.length - 1; i >= firstI; --i)
			this.removeSection(i)

		//add new sections:
		for (let i = firstI, max = newSectionData.length; i < max; ++i) {
			this.addSectionToIndex(newSectionData[i])
		}

		this.updateSectionDimensions()
		this.updateHighlightedLinksCss(newSectionData)
	}
}