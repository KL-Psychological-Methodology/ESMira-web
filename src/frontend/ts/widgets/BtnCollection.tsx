import m, {Vnode} from "mithril"

export function BtnCollection(
	btns: Vnode<any, any>[]
): Vnode<any, any> {
	return (
		<div class="btnCollection">
			{btns}
		</div>
	)
}