import m, {Vnode} from "mithril"
import {NotCompatibleIcon, PossibleDevices} from "./NotCompatibleIcon";

type DashTemplateOptions = {
	title: string
	icon?: Vnode<any, any>
	noCompatibilityIcon?: PossibleDevices[]
	msg?: string | Vnode<any, any>
	innerLinkTitle?: string
	innerLinkHref?: string
}

type DashContainerOptions = "stretched" | "cramped" | "vertical" | "horizontal" | null
export type DashViewOptions = {
	disabled?: boolean
	onclick?: (e: MouseEvent) => void
	href?: string
	showAsClickable?: boolean
	floating?: boolean
	floatingRight?: boolean
	highlight?: boolean
	small?: boolean
	template?: DashTemplateOptions
	content?: Vnode<any, any>
}

function DashTemplateView(options: DashTemplateOptions): Vnode<any, any> {
	return (
		<div>
			{(options.icon || options.noCompatibilityIcon) &&
				<div class="dashIcon">
					{options.icon}
					{options.noCompatibilityIcon && NotCompatibleIcon(... options.noCompatibilityIcon)}
				</div>
			}
			
			<div class="dashTitle">{options.title}</div>
			{options.msg && <p class="dashMsg">{options.msg}</p>}
			{options.innerLinkTitle && options.innerLinkHref && <a class="link" href={options.innerLinkHref}>{options.innerLinkTitle}</a>}
		</div>
	)
}

function DashElementView(options: DashViewOptions): Vnode<any, any> {
	let classString = "dashEl"
	if(options.floatingRight)
		classString += " floating right"
	else if(options.floating)
		classString += " floating"
	
	if(options.small)
		classString += " small"
	if(options.highlight)
		classString += " highlight"
	if(options.disabled)
		classString += " disabled"
	
	let view: Vnode<any, any>
	if(options.content)
		view = options.content
	else if(options.template)
		view = DashTemplateView(options.template)
	else
		view = <div class="highlight">Missing Dash Information</div>
	
	if(options.href) {
		return <a target={options.href.startsWith("http") ? "_blank" : ""} class={`${classString} dashLink`} href={options.href}>{view}</a>
	}
	else if(options.onclick)
		return <a class={`${classString} dashLink`} onclick={options.onclick}>{view}</a>
	else if(options.showAsClickable)
		return <a class={`${classString} dashLink`}>{view}</a>
	else {
		view.attrs["className"] = `${classString} ${view.attrs.hasOwnProperty("className") ? view.attrs["className"] : ""}`
		return view
	}
}

function getNewClassString(size: DashContainerOptions, oldClassString: string): string {
	if(size)
		oldClassString += " " + size
	return oldClassString
}

export function DashElement(
	size: DashContainerOptions,
	... options: (DashViewOptions | false)[]
): Vnode<any, any> {
	if(options.length == 1) {
		if(!options[0])
			return <div></div>
		
		const view =  DashElementView(options[0])
		view.attrs["className"] = getNewClassString(size, view.attrs["className"])
		return view
	}
	else {
		const classString = getNewClassString(size, "multipleChildren")
		return <div class={classString}>
			{options.map((option) => option && DashElementView(option))}
		</div>
	}
}