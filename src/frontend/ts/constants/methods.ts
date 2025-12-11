import {Lang} from "../singletons/Lang";
import {lt} from "semver";

export function safeConfirm(msg: string): boolean {
	return confirm(msg) && (prompt(Lang.get("confirm_again")) || "").toLowerCase() === "ok"
}
export function checkString(s: string): boolean {
	return !s || s.match(/^[a-zA-Z0-9À-ž_\-().\s]+$/) != null;
}

export function getBaseDomain(): string {
	return location.host + location.pathname.replace(/[^/]+$/, "");
}

export function getBaseUrl(protocol: string = "https"): string {
	return (protocol || location.protocol) + '://' + getBaseDomain();
}

export function createAppUrl(accessKey: string, id: number, alwaysAddId: boolean = false, protocol: string = "https", encodedFallbackUrl: string = ""): string {
	const fallbackSuffix = encodedFallbackUrl ? "?fallback=" + encodedFallbackUrl : ""
	return alwaysAddId && accessKey
		? getBaseUrl(protocol) + "app-" + id + "-" + accessKey + fallbackSuffix
		: getBaseUrl(protocol) + "app-" + (accessKey || id) + fallbackSuffix;
}
export function createStudyUrl(accessKey: string, id: number, alwaysAddId: boolean = false, protocol: string = "https"): string {
	return alwaysAddId && accessKey
		? getBaseUrl(protocol) + id + "-" + accessKey
		: getBaseUrl(protocol) + (accessKey || id);
}
export function createFallbackAppUrl(accessKey: string, id: number, encodedFallbackUrl: string, protocol: string = "https") {
	const fallbackUrl = atob(encodedFallbackUrl)
	const encodedFromUrl = btoa(getBaseUrl(protocol))
	return fallbackUrl + 'fallback-' + id + (accessKey ? "-" + accessKey : '') + "-" + encodedFromUrl
}
export function createQuestionnaireUrl(accessKey: string, qId: number, protocol: string = "https"): string {
	return getBaseUrl(protocol) + "survey-" + qId + (accessKey ? "-" + accessKey : "");
}

export function getMidnightMillis(timestamp: number = Date.now()): number {
	const date = new Date(timestamp)
	date.setHours(0)
	date.setMinutes(0)
	date.setSeconds(0)
	date.setMilliseconds(0)
	return date.getTime()
}
export function timeStampToTimeString(timestamp: number) {
	const d = new Date(timestamp)
	return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
}

export function getFromUrlFriendly(s: string): string {
	return atob(s.replace(/\-/g, '+').replace(/\_/g, '/'))
}

export function makeUrlFriendly(s: string): string {
	return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/\=/g, '')
}

export function compareSemVersion(smaller: string, bigger: string) {
	if(smaller.startsWith("pre.")) {
		smaller = smaller.substring(4);
	}
	if(bigger.startsWith("pre.")) {
		bigger = bigger.substring(4);
	}
	
	return lt(smaller, bigger)
}

export function getReadableByteSize(bytes: number): string {
	if(bytes > 1000000000)
		return `${Math.round(bytes / 10000000) / 100} Gb`
	else if(bytes > 1000000)
		return `${Math.round(bytes / 10000) / 100} Mb`
	else
		return `${Math.round(bytes / 1000)} Kb`
}