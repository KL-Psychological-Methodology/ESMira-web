import {PluginData} from "../data/study/PluginContainer";
import {BaseObservable} from "../observable/BaseObservable";
import {BindObservable} from "../components/BindObservable";
import {createElement} from "./createElement";
import {safeConfirm} from "../constants/methods";

/**
 * @see https://github.com/KL-Psychological-Methodology/ESMira/wiki/ESMira-plugins#metadatajson
 */
export interface PluginMetadata {
	name: string
	pluginId: string
	version?: string
	description?: string
	website?: string
	minESMiraVersion?: string
	maxESMiraVersion?: string
	downloadUrl?: string
	metadataUrl?: string
}


/**
 * @see https://github.com/KL-Psychological-Methodology/ESMira/wiki/ESMira-plugins#sections--manipulate-page-sections
 */
export interface PluginFrontend {
	/**
	 * Used to manipulate the section view.
	 * This is called after the section view has been created and right after it has been appended to the DOM.
	 * @param element - the dom element of the section view content.
	 */
	manipulateSectionView?(element: Element): void
	
	/**
	 * Used to manipulate the section view extras (the top right area of the section).
	 * This is called after the section view has been created and right after it has been appended to the DOM.
	 * @param element - the dom element that contains all extra buttons.
	 */
	manipulateExtras?(element: Element): void
	
	/**
	 * Used to change the section view title
	 * @param title - the current title of the section view
	 * @returns the new title of the section view
	 */
	changeSectionTitle?(title: string): string
	
	/**
	 * Called when the section is removed.
	 */
	onClose?(): void
}


/**
 * @see https://github.com/KL-Psychological-Methodology/ESMira/wiki/ESMira-plugins#methods
 */
export interface PluginMethods {
	/**
	 * A {@link BaseObservable} holding JSON data object of the study that is currently being loaded.
	 * Is undefined if no study is currently loaded.
	 * @see {@link DataStructure}
	 * @see {@link BaseObservable}
	 */
	getStudyPluginData: () => Promise<PluginData | undefined>,
	
	/**
	 * Creates the hash url to a provided section. The returned hash also includes the path to the sections to the left of the current section.
	 * By default, the new section is added to the right of the current section (current depth + 1).
	 * By providing a depth, the new section is added to the specified depth instead.
	 *
	 * @param name - The name of the target section or a .
	 * @param depth - Optional. The depth to add the new section to. Defaults to the current depth + 1.
	 * @returns The full url hash.
	 */
	getHashUrl: (page: string, depth?: number) => string,
	
	/**
	 * binds the value of a form element (e.g. input, select, ...) to an observable and automatically updates the observable when the value changes.
	 * @param obs - the observable to bind to
	 * @param transformer - an optional transformer to assure that the value adheres to a certain format (e.g., number, date, ...)
	 * @param attr - the attribute of the form element to bind to (e.g., value, checked, ...). Usually the correct attribute can be inferred from the data type of the observable.
	 * @param event - which event to listen to. Uses `onchange` by default.
	 * @returns a Record with the attribute and event handler which is meant to be passed via spread operator (`...`) to the element attributes.
	 */
	bindObservable: typeof BindObservable
	
	/**
	 * Create a new element using `document.createElement()` and mark it as a plugin element to ensure proper clean-up when sections are changed.
	 * Must be used to add new elements, or the element might be left behind when the section is changed to a structurally similar one.
	 * @param tag - the HTML tag of the new element to create
	 * @param attributes - the attributes of the new element to create.
	 * @param parent - Optional. The parent element to append the new element to.
	 * @returns the new element
	 */
	createElement: typeof createElement
	
	/**
	 * A convenience function that shows a JavaScript confirm dialog but also ask to type in "ok" (via JavaScript prompt) for final confirmation.
	 */
	safeConfirm: typeof safeConfirm
}


/**
 * Format loaded from https://github.com/KL-Psychological-Methodology/ESMira/blob/main/about/plugins.json
 * For internal usage.
 */
export type SimplifiedPluginMetadata = Required<Pick<PluginMetadata, "name" | "pluginId" | "description" | "website" | "metadataUrl">>

/**
 * The frontend instructions sent from the backend.
 * For internal usage.
 */
export interface PluginFrontendInstructions {
	sections?: string[]
	studyJsonDataStructure?: Record<string, any>
}

/**
 * Additional data needed by the frontend.
 * For internal usage.
 */
export interface PluginFrontendMetadata {
	name: string
	enabled: boolean
}

/**
 * Plugin data and additional data needed by the frontend.
 * For internal usage.
 */
export type FullPluginFrontend = PluginFrontend & PluginFrontendMetadata