import {CompatibilityCheck} from "./site/CompatibilityCheck";
import {Lang} from "./singletons/Lang";
import {Site} from "./site/Site";

declare const PACKAGE_VERSION: string

let site: Site

export function init(startHash: string, serverName: string, serverVersion: number, serverAccessKey: string, langCode: string, type: string): void {
	if(type)
		document.body.classList.add(type)
	if(process.env.NODE_ENV !== 'production')
		document.body.classList.add("localhost")
	
	const check = new CompatibilityCheck()
	if(!check.isCompatible()) { //detect IE, very old browsers or IE. It also detects IE
		console.log("You shall not pass!")
		check.toggleUrl()
		return
	}
	Lang.init(langCode, PACKAGE_VERSION)
	
	site = new Site(serverName, startHash, serverVersion, PACKAGE_VERSION, serverAccessKey)
	
	document.body.classList.add("isInit")
	document.getElementById("legalLink")!.innerText = Lang.get("impressum")
}