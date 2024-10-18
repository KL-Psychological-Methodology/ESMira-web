import {ObservableStructure} from "../../observable/ObservableStructure";
import {TranslationRootInterface} from "../../observable/interfaces/TranslationRootInterface";

export class Configs extends ObservableStructure implements TranslationRootInterface {
	public defaultLang	= this.primitive<string>(		"defaultLang",			"en")
	public langCodes	= this.primitiveArray<string>(	"langCodes",	[])
}