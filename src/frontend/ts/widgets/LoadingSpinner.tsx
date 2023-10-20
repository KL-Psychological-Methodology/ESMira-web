import m, {Vnode} from "mithril"

export function LoadingSpinner(
	hidden: boolean = false
): Vnode<any, any> {
	return (
		<div class={ `loaderAnimation ${hidden ? "hidden" : ""}` }></div>
	)
}