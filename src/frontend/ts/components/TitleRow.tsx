import m, {Vnode} from "mithril"
export function TitleRow(
	title: string | Vnode<any, any>
): Vnode<any, any> {
	if(typeof title == "string") {
		return <div class="titleRow">
			<span class="title">{title}</span>
		</div>
	}
	else {
		title.attrs["className"] = `titleRow ${title.attrs["className"] ?? ""}`
		return title
	}
}