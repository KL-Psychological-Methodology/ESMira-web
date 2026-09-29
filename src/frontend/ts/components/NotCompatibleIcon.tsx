import m, {Vnode} from "mithril"
import {Lang} from "../singletons/Lang";
import androidSvg from "../../imgs/devices/android.svg?raw"
import iosSvg from "../../imgs/devices/ios.svg?raw"
import {closeDropdown, openDropdown} from "./DropdownMenu";
import "./NotCompatibleIcon.css";


export type PossibleDevices = "Android" | "iOS"

const imageRecord: Record<PossibleDevices, string> = {
	Android: androidSvg,
	iOS: iosSvg
}

function onPointerEnter(title: string, e: MouseEvent) {
	openDropdown("notCompatible", e.target as HTMLElement,
		() => <div class="smallText center">{title}</div>
	)
}
function onPointerLeave() {
	closeDropdown("notCompatible")
}

export function NotCompatibleIcon(... devices: PossibleDevices[]): Vnode<any, any> {
	const translationRecord: Record<PossibleDevices, string> = {
		Android: Lang.get("Android"),
		iOS: Lang.get("iOS")
	}
	const title = Lang.get("not_compatible_with", devices.map((device) => translationRecord[device]).join(", "))
	return <div class="notCompatibleIcon" onpointerenter={onPointerEnter.bind(null, title)} onpointerleave={onPointerLeave.bind(null)}>
		{devices.map((device) =>
			<div class="deviceIcon">{m.trust(imageRecord[device])}</div>)}
	</div>

}
