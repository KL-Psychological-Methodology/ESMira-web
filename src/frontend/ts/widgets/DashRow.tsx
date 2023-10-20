import m, {Vnode} from "mithril"
export function DashRow(... content: Array<Vnode<any, any> | undefined | false>): Vnode<any, any> {
	return (
		<div class="dashRow">{content}</div>
	)
}