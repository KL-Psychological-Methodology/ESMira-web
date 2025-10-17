import m, {Vnode} from "mithril"
import {Lang} from "../singletons/Lang";
import addSvg from "../../imgs/icons/addCircle.svg?raw";
import changeSvg from "../../imgs/icons/change.svg?raw";
import copySvg from "../../imgs/icons/copy.svg?raw";
import downloadSvg from "../../imgs/icons/download.svg?raw"
import editSvg from "../../imgs/icons/edit.svg?raw";
import okSvg from "../../imgs/icons/ok.svg?raw";
import reloadSvg from "../../imgs/icons/reload.svg?raw";
import removeSvg from "../../imgs/icons/remove.svg?raw";
import transferSvg from "../../imgs/icons/transfer.svg?raw";
import trashSvg from "../../imgs/icons/trash.svg?raw";

export function BtnCustom(
	icon: Vnode<any, any>,
	onclick?: (e: MouseEvent) => void,
	title: string = "",
	hoverTitle: string = ""
): Vnode<any, any> {
	return (
		<div class="btn clickable" onclick={onclick} title={title || hoverTitle}>
			{icon}
			<span class="middle smallText">{title}</span>
		</div>
	)
}
export function BtnRemove(onclick: () => void, title: string = "",): Vnode<any, any> {
	return (
		<div class="btn btnDelete clickable" onclick={onclick} title={title || Lang.get("delete")}>
			{m.trust(removeSvg)}
			<span class="smallText highlight middle">{title}</span>
		</div>
	)
}
export function BtnTrash(onclick: () => void, title: string = "",): Vnode<any, any> {
	return (
		<div class="btn btnDelete clickable" onclick={onclick} title={title || Lang.get("delete")}>
			{m.trust(trashSvg)}
			<span class="highlight middle">{title}</span>
		</div>
	)
}


export function BtnAdd(onclick?: (e: MouseEvent) => void, title: string = ""): Vnode<any, any> {
	return BtnCustom(m.trust(addSvg), onclick, title, title || Lang.get("add"))
}

export function BtnCopy(onclick?: () => void, title: string = ""): Vnode<any, any> {
	return BtnCustom(m.trust(copySvg), onclick, title, title || Lang.get("copy"))
}

export function BtnChange(onclick?: () => void, title: string = ""): Vnode<any, any> {
	return BtnCustom(m.trust(changeSvg), onclick, title, title || Lang.get("change"))
}

export function BtnDownload(onclick?: () => void, title: string = ""): Vnode<any, any> {
	return BtnCustom(m.trust(downloadSvg), onclick, title, title || Lang.get("download"))
}

export function BtnEdit(onclick?: () => void, title: string = ""): Vnode<any, any> {
	return BtnCustom(m.trust(editSvg), onclick, title, title || Lang.get("change"))
}

export function BtnOk(onclick?: () => void, title: string = ""): Vnode<any, any> {
	return BtnCustom(m.trust(okSvg), onclick, title, title || Lang.get("save"))
}

export function BtnReload(onclick?: () => void, title: string = ""): Vnode<any, any> {
	return BtnCustom(m.trust(reloadSvg), onclick, title, title || Lang.get("reload"))
}

export function BtnTransfer(onclick?: () => void, title: string = ""): Vnode<any, any> {
	return BtnCustom(m.trust(transferSvg), onclick, title, title || Lang.get("transfer"))
	
}
