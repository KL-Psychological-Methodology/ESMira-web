export const SECTION_DELIMITER = "/"

export class SectionData {
	public readonly dataCode: string
	
	constructor(dataCode: string) {
		this.dataCode = dataCode
	}
	
	public getValues(existingValues: Record<string, string>): [string, string] {
		const variables = this.dataCode.split(",")
		
		for (let i = variables.length - 1; i >= 1; --i) {
			const [key, value] = variables[i].split(":")
			existingValues[key] = value ?? "1"
		}
		
		return variables[0].split(":") as [string, string]
	}
}

/**
 * Url hash-syntax:
 * Each section is separated by "/". Each section has the same structure:
 * [sectionName]:[sectionValue?],[firstKey]:[firstValue],[secondKey]:[secondValue]...
 */
export class HashData {
	private readonly sectionCodes: string[] = []
	
	constructor(startHash: string) {
		let hash = window.location.hash
		if(hash.length === 0)
			hash = startHash
		else
			hash = hash.substring(1)
		
		this.sectionCodes = hash.split(SECTION_DELIMITER)
		if(hash.slice(-1) === SECTION_DELIMITER)
			this.sectionCodes.pop()
	}
	public needsAdmin() {
		return window.location.hash.startsWith("admin")
	}
	
	public getSectionCount(): number {
		return this.sectionCodes.length
	}
	
	public getSectionCode(index: number): string {
		return this.sectionCodes[index]
	}
	
	public getSectionData(index: number): SectionData {
		return new SectionData(this.sectionCodes[index])
	}
}