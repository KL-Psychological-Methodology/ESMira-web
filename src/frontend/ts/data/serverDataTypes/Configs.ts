import {DataStructure} from "../DataStructure";
import {TranslatableRootInterface} from "../../observable/interfaces/TranslatableRootInterface";

export class Configs extends DataStructure implements TranslatableRootInterface {
	public defaultLang	= this.primitive<string>(		"defaultLang",			"en")
	public langCodes	= this.primitiveArray<string>(	"langCodes",	[])
}