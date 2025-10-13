import {DataStructure} from "../DataStructure";
import {JsonTypes} from "../../observable/types/JsonTypes";
import {SiteTranslations} from "./SiteTranslations";
import {TranslatableRootInterface} from "../../observable/interfaces/TranslatableRootInterface";

export class ServerData extends DataStructure implements TranslatableRootInterface {
	public defaultLang = this.primitive<string>("defaultLang", "en")
	public langCodes = this.primitiveArray<string>("langCodes", [])
	public siteTranslations = this.object("translationData", SiteTranslations)
	
	constructor(data: Record<string, JsonTypes>, newLang = data["defaultLang"] as string, oldServerData?: ServerData) {
		super(data, null, "serverSettings", newLang)
		if(oldServerData) {
			this.sharedMemory = oldServerData.sharedMemory
		}
	}
}