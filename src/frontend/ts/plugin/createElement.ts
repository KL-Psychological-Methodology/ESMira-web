export const PLUGIN_ELEMENT_ATTR_NAME = "ESMira-plugin-element"

/**
 * Should only be used by plugins!
 * 
 * Create a new element using `document.createElement()` and mark it as a plugin element to ensure proper clean-up when sections are changed.
 * Must be used to add new elements, or the element might be left behind when the section is changed to a structurally similar one.
 * @param tag - the HTML tag of the new element to create
 * @param attributes - the attributes of the new element to create.
 * @param parent - Optional. The parent element to append the new element to.
 * @returns the new element
 */
export function createElement(tag: string, attributes: Partial<HTMLElement>, parent?: HTMLElement) {
	const element = document.createElement(tag)
	for(const key in attributes) {
		(element as any)[key] = attributes[key as keyof HTMLElement]
	}
	element.setAttribute(PLUGIN_ELEMENT_ATTR_NAME, "1")
	
	if(parent) {
		parent.appendChild(element)
	}
	return element
}