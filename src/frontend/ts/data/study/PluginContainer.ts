import {DataStructure, DataStructureInputType} from "../DataStructure";
import {BaseObservable, ObserverKeyType} from "../../observable/BaseObservable";
import {ObservableTypes} from "../../observable/types/ObservableTypes";

export class PluginData extends DataStructure {
	constructor(structure: Record<string, any>, data: DataStructureInputType, parent: BaseObservable<ObservableTypes> | null, key: ObserverKeyType, newLang?: string) {
		super(data, parent, key, newLang);
		const thisAny = this as any
		
		for(const key in structure) {
			const entry = structure[key]
			
			if(key.startsWith("$")) {
				if(Array.isArray(entry)) {
					if(entry.find(e => typeof e != "string")) {
						throw new Error("Only strings can be translated")
					}
					thisAny[key] = this.translatableArray(key, entry as string[])
				}
				else {
					if(typeof entry != "string") {
						throw new Error("Only strings can be translated")
					}
					thisAny[key] = this.translatable(key, entry)
				}
			}
			switch(typeof entry) {
				case "object":
					if(Array.isArray(entry)) {
						if(entry.length == 0 || typeof entry[0] != "object") {
							thisAny[key] = this.primitiveArray(key, entry)
							break;
						}
						else if(Array.isArray(entry[0])) {
							throw new Error("Array of arrays not supported")
						}
						thisAny[key] = this.objectArray(key, PluginData.bind(this, entry[0]))
						break;
					}
					else {
						thisAny[key] = this.object(key, PluginData.bind(this, entry))
						break;
					}
				case "string":
				case "number":
				case "boolean":
					thisAny[key] = this.primitive(key, entry)
					break;
			}
		}
	}
}


export class PluginContainer extends PluginData {
	constructor(data: DataStructureInputType, parent: BaseObservable<ObservableTypes> | null, key: ObserverKeyType, newLang?: string) {
		super({}, data, parent, key, newLang);
	}
	
	public addPluginData(pluginName: string, structure: Record<string, any>): void {
		(this as any)[pluginName] = this.object(pluginName, PluginData.bind(this, structure))
	}
	
	public getPluginData(pluginName: string): PluginData {
		return (this as any)[pluginName]
	}
}