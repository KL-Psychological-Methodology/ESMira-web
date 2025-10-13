import {SectionData} from "./SectionData";
import {SiteData} from "./SiteData";
import m from "mithril";

export const SECTION_DELIMITER = "/"

/**
 * Url hash-syntax:
 * Each section is separated by "/". Each section has the same structure:
 * [sectionName]:[sectionValue?],[firstKey]:[firstValue],[secondKey]:[secondValue]...
 */
export class HashData {
	private readonly startHash: string
	private readonly sectionDataArray: SectionData[] = []
	private readonly siteData: SiteData
	
	constructor(startHash: string, siteData: SiteData) {
		this.startHash = startHash
		this.siteData = siteData
	}
	
	public reapplyHash() {
		let hash = window.location.hash
		if(hash.length === 0) {
			hash = this.startHash
		}
		else {
			hash = hash.substring(1)
		}
		
		const sectionCodes = hash.split(SECTION_DELIMITER)
		if(hash.slice(-1) === SECTION_DELIMITER) {
			sectionCodes.pop()
		}
		const newLength = sectionCodes.length
		
		
		//find unneeded sections:
		let firstI = 0
		while(firstI < newLength && firstI < this.sectionDataArray.length && sectionCodes[firstI] === this.sectionDataArray[firstI].dataCode) {
			++firstI
		}
		
		//remove unneeded sections:
		this.sectionDataArray.splice(firstI)
		
		//add new sections:
		for(let i = firstI, max = newLength; i < max; ++i) {
			const sectionData = new SectionData(i, sectionCodes[i], this.sectionDataArray, this.siteData)
			this.sectionDataArray[i] = sectionData
			this.siteData.currentSection = sectionData.depth
		}
		
		//Fix currentSection
		if(this.siteData.currentSection >= this.sectionDataArray.length) { //happens when sections were removed
			this.siteData.currentSection = this.sectionDataArray.length -1
		}
		m.redraw()
	}
	public getAllSectionData(): SectionData[] {
		return this.sectionDataArray
	}
	public getSectionData(index: number): SectionData | null {
		return this.sectionDataArray[index] ?? null
	}
	public getLastSectionData(): SectionData {
		return this.sectionDataArray[this.sectionDataArray.length - 1]
	}
	public getCurrentHash(): string {
		return window.location.hash
	}
	public needsAdmin(): boolean {
		return HashData.needsAdmin(this.startHash)
	}
	
	public static needsAdmin(startHash: string): boolean {
		return startHash.startsWith("admin") || window.location.hash.startsWith("#admin")
	}
}