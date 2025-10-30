import {Requests} from "../singletons/Requests";
import {FILE_PLUGIN_FRONTEND_CODE} from "../constants/urls";
import {
	PluginFrontendInstructions,
	FullPluginFrontend, PluginFrontend, PluginMetadata,
	PluginMethods
} from "../plugin/PluginInterfaces";
import {BindObservable} from "../components/BindObservable";
import {Lang} from "../singletons/Lang";
import {PromiseCache} from "../singletons/PromiseCache";
import {SectionData} from "../site/SectionData";
import {createElement} from "../plugin/createElement";
import {compareSemVersion, safeConfirm} from "../constants/methods";

export class PluginLoader {
	/**
	 * A map of section names to names of connected plugins
	 */
	private readonly sectionPluginIndex: Record<string, string[]> = {}
	
	constructor(plugins: Record<string, PluginFrontendInstructions>) {
		for(const name in plugins) {
			const plugin = plugins[name]
			for(const section of plugin.sections ?? []) {
				if(!this.sectionPluginIndex.hasOwnProperty(section)) {
					this.sectionPluginIndex[section] = []
				}
				this.sectionPluginIndex[section].push(name)
			}
		}
	}
	
	public sectionHasPluginFrontend(sectionName: string, pluginName: string): boolean {
		return this.sectionPluginIndex[sectionName]?.includes(pluginName)
	}
	
	/**
	 * Checks if a plugin is compatible with the current ESMira version.
	 * @param packageVersion The current ESMira version.
	 * @param metadata The plugin metadata.
	 * @returns true if the plugin is compatible, false otherwise.
	 */
	public isNotCompatible(packageVersion: string, metadata: PluginMetadata) {
		return compareSemVersion(packageVersion, metadata.minESMiraVersion ?? packageVersion)
			|| compareSemVersion(metadata.maxESMiraVersion ?? packageVersion, packageVersion)
	}
	
	/**
	 * Bundles all frontend data for a plugin.
	 *
	 * @param sectionName - The name of the section.
	 * @param sectionData - The sectionData object for the section that provides required information and methods.
	 * @param pluginName - The name of the plugin.
	 * @return A promise that resolves to the frontend data object.
	 */
	private async createPluginFrontend(sectionName: string, sectionData: SectionData, pluginName: string): Promise<FullPluginFrontend> {
		const methods: PluginMethods = {
			getStudyPluginData: async () => {
				await sectionData.getStudyPromise()
				const study = sectionData.getStudyOrNull()
				return study?.pluginData.getPluginData(pluginName)
			},
			getHashUrl: sectionData.getUrl.bind(this),
			bindObservable: BindObservable,
			createElement: createElement,
			safeConfirm: safeConfirm
		}
		const module = await PromiseCache.get(`plugin-${pluginName}-${sectionName}`, () => Requests.loadCodeModule(`${FILE_PLUGIN_FRONTEND_CODE}?plugin=${pluginName}&page=${sectionName}`))
		const frontend = await module.default(methods, sectionData, Lang) as PluginFrontend
		return {
			name: sectionData.sectionValue,
			enabled: true,
			...frontend
		};
	}
	
	/**
	 * Loads the plugins associated with a specific page. It dynamically fetches and imports the plugins,
	 * adding them to the internal plugins list of this section. Errors in loading plugins are logged to the console.
	 *
	 * @param sectionName The name of the page for which the plugins need to be loaded (can be different from section.sectionName).
	 * @param sectionData The section from which the plugin is loaded from.
	 * @return A promise that resolves to all plugins that have overrides for the provided sectionName.
	 */
	public async loadPlugins(sectionName: string, sectionData: SectionData): Promise<FullPluginFrontend[]> {
		if(!this.sectionPluginIndex.hasOwnProperty(sectionName)) {
			return []
		}
		if(sectionName == "pluginSettings") {
			const pluginName = sectionData.sectionValue;
			const frontend = await this.createPluginFrontend(sectionName, sectionData, pluginName)
			return [
				{
					changeSectionTitle: () => Lang.get("pluginSettings"),
					...frontend
				}
			];
		}
		const plugins: FullPluginFrontend[] = []
		for(const pluginName of this.sectionPluginIndex[sectionName]) {
			try {
				const frontend = await this.createPluginFrontend(sectionName, sectionData, pluginName)
				plugins.push(frontend)
			}
			catch(e) {
				this.reportPluginError(sectionData, pluginName, "Could not load plugin", e)
			}
		}
		return plugins
	}
	
	/**
	 * Reports an error encountered in a specific plugin and logs it appropriately.
	 *
	 * @param sectionData - The data of the section where the error occurred. Used to display an error message in the section.
	 * @param pluginName - The name of the plugin from where the error originated.
	 * @param msg - A descriptive error message providing context for the issue.
	 * @param error - The error object or data related to the encountered issue.
	 */
	public reportPluginError(sectionData: SectionData, pluginName: string, msg: string, error: unknown) {
		console.error(`${pluginName}: ${msg}\n`, error)
		sectionData.loader.error(`${pluginName}: ${msg}\n`)
	}
}