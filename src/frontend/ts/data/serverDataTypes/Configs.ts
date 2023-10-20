import {TranslatableObject} from "../../observable/TranslatableObject";
import {TranslationRootInterface} from "../../observable/interfaces/TranslationRootInterface";

export class Configs extends TranslatableObject implements TranslationRootInterface {
	public defaultLang	= this.primitive<string>(		"defaultLang",			"en")
	public langCodes	= this.primitiveArray<string>(	"langCodes",	[])
}