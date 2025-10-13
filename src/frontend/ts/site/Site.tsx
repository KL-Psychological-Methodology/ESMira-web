import langIndex from "../locales.json";
import {NavigationRow} from "./NavigationRow";
import {SiteData} from "./SiteData";
import m from "mithril";
import {HashData} from "./HashData";
import {Lang} from "../singletons/Lang";
import {Admin} from "../admin/Admin";
import {DropdownMenu} from "../components/DropdownMenu";
import {SectionData} from "./SectionData";
import {Section} from "./Section";

const SECTION_MIN_WIDTH = 650;

/**
 * Root class that is responsible for all views
 * General concept:
 * {@link Site} holds an array of {@link Section} - one Section for each page you see displayed.
 * {@link onhashchange()} creates (or removes) sections using {@link HashData}
 *
 * Admin-Panel:
 * See description of {@link Admin} for more information
 */

export class Site {
	public readonly siteData: SiteData
	public readonly serverVersion: number
	private readonly sectionsView: HTMLElement
	private readonly navigationRow: NavigationRow
	private readonly hashData: HashData

	private sectionWidthPercent: number = 100
	private overrideSectionWidth: boolean = false

	constructor(serverName: string, startHash: string, serverVersion: number, packageVersion: string, serverAccessKey: string) {
		this.siteData = new SiteData(
			new Admin(HashData.needsAdmin(startHash), this),
			serverVersion,
			packageVersion
		)
		this.hashData = new HashData(startHash, this.siteData)
		this.serverVersion = serverVersion
		this.navigationRow = new NavigationRow(document.body, this.hashData.getAllSectionData(), this.siteData)
		this.sectionsView = document.getElementById("sectionContainer")!

		this.siteData.dynamicValues.getOrCreateObs("accessKey", serverAccessKey)

		//TODO: do in php
		document.getElementById("headerServerName")!.innerText = serverName

		if(this.sectionsView)
			this.sectionsView.innerHTML = ""

		window.onhashchange = this.onhashchange.bind(this)
		this.onhashchange()

		document.getElementById("sectionBoxWidthSetter")?.addEventListener("change", (e) => {
			const p = (e.target as HTMLInputElement).value
			if(this.sectionsView)
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
		const selectedEntry = langIndex[Lang.code as keyof typeof langIndex]
		m.render(
			document.getElementById("siteLangChooser")!,
			DropdownMenu("langChooserDropdown",
				<div class="line verticalPadding nowrap clickable">
					<span>{m.trust(selectedEntry.flag)}</span>
					<span class="desc">{selectedEntry.name}</span>
				</div>,
				() => {
					const hash = this.hashData.getCurrentHash()
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

		if(siteWidth > SECTION_MIN_WIDTH) {
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
		if(view)
			view.value = Math.round(this.sectionWidthPercent).toString();

		this.navigationRow.positionNavi(this.sectionWidthPercent)
	}

	public renderView(): void {
		const view = {
			view: () => {
				let data: SectionData[]
				let currentSection: number
				if(this.siteData.onlyShowLastSection) {
					data = [this.hashData.getLastSectionData()]
					currentSection = 0
				}
				else {
					data = this.hashData.getAllSectionData()
					currentSection = this.siteData.currentSection
				}
				window.document.title = this.hashData.getSectionData(currentSection)?.callbacks?.getSectionTitle() || Lang.get("state_loading")

				//if there are too many elements, we only divide by max number that fits on the screen:
				const visibleSectionsCount = Math.min(data.length, Math.floor(100 / this.sectionWidthPercent))

				//we move it by -50% because section is already centered:
				const sectionPositionPercent = (currentSection + 1 - visibleSectionsCount) * 100 + (visibleSectionsCount * 50 - 50)

				return (
					<div id="sectionsView" style={`width: ${this.sectionWidthPercent}%; transform: translate(-${sectionPositionPercent}%)`}>{
						data.map((sectionData) => m(Section, {
							sectionData: sectionData,
							siteData: this.siteData,
						}))
					}</div>
				)
			}
		}

		m.mount(this.sectionsView, view)
	}

	public async reload(): Promise<any> {
		return Promise.all(this.hashData.getAllSectionData().map((section) => section.callbacks?.reload()))
	}

	/**
	 * replace css-rules to highlight clicked a tags
	 */
	private async updateHighlightedLinksCss(): Promise<void> {
		const cssRules = new CSSStyleSheet()
		const slice = this.hashData.getAllSectionData().slice(1)
		
		await cssRules.replace(
			`${slice.map(data => data.cssRules.aHeader).join(",")}{color:#dc4e9d !important; text-decoration: underline !important;}
			${slice.map(data => data.cssRules.dashHeader).join(",")}{background-color:#9fe0f7;}
			${slice.map(data => data.cssRules.svgHeader).join(",")}{fill: #dc4e9d;}`
		)
		// we have to fully replace adoptedStyleSheets because mutability was only added around 2022 (https://caniuse.com/mdn-api_document_adoptedstylesheets_mutable)
		document.adoptedStyleSheets = [cssRules]
	}
	
	private onhashchange(): void {
		if(this.hashData.needsAdmin()) {
			this.siteData.admin.enableAdmin()
		}
		this.hashData.reapplyHash()
		
		this.updateSectionDimensions()
		this.updateHighlightedLinksCss()
	}
}