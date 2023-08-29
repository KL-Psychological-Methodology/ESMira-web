import {TranslatableObject} from "../../observable/TranslatableObject";
import {JsonTypes} from "../../observable/types/JsonTypes";
import {SiteTranslations} from "./SiteTranslations";
import {TranslationRootInterface} from "../../observable/interfaces/TranslationRootInterface";

export class ServerData extends TranslatableObject implements TranslationRootInterface {
	public defaultLang = this.primitive<string>("defaultLang", "en")
	public langCodes = this.primitiveArray<string>("langCodes", [])
	public siteTranslations = this.object("translationData", SiteTranslations)
	
	constructor(data: Record<string, JsonTypes>, newLang = data["defaultLang"] as string) {
		super(data, null, "serverSettings", newLang)
	}
}