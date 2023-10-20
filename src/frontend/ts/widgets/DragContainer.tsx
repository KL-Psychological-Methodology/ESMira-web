import m, {Component, Vnode, VnodeDOM} from "mithril"
import addSvg from "../../imgs/icons/dragHandle.svg?raw"
import {BaseObservable} from "../observable/BaseObservable";
import {ArrayInterface} from "../observable/interfaces/ArrayInterface";

export interface DragTools {
	getDragTarget(index: number, targetList: ArrayType, content?: Vnode<any, any>): Vnode<any, any>
	getDragStarter(index: number, dragTarget: ArrayType): Vnode<any, any>
}

type ArrayType = ArrayInterface<any, BaseObservable<any>>

interface DragContainerImpOptions {
	content: (container: DragTools) => Vnode<any, any>
}
class DragContainerImp implements DragTools, Component<DragContainerImpOptions, any> {
	private contentVNode?: VnodeDOM<any, any>
	private currentStartList?: ArrayType
	private currentTargetList?: ArrayType
	private currentStartIndex: number = 0
	private currentTargetIndex: number = 0
	private currentDragClone?: HTMLElement
	private currentStartElement?: HTMLElement
	private startIndex: number = 0
	
	public getDragTarget(index: number, targetList: ArrayType, content: Vnode<any, any> = <div></div>): Vnode<any, any> {
		content.attrs["className"] = `dragTarget ${content.attrs["className"] ?? ""}`
		content.attrs["ondragend"] = this.ondragend.bind(this)
		content.attrs["ondragenter"] = this.ondragenter.bind(this, index, targetList)
		return content
	}
	
	public getDragStarter(index: number, targetList: ArrayType): Vnode<any, any> {
		return <div class="btn clickable dragStarter" draggable="true" ondragstart={this.ondragstart.bind(this, index, targetList)}>
			{m.trust(addSvg)}
		</div>
	}
	
	
	private getIndexInContainer(searchEl: HTMLElement): number {
		const htmlCollection = this.contentVNode?.dom.getElementsByClassName("dragTarget")
		if(htmlCollection) {
			for(let i = htmlCollection.length - 1; i >= 0; --i) {
				if(htmlCollection[i] === searchEl)
					return i
			}
		}
		return -1
	}
	
	private addDragSpacer(locationEl: HTMLElement): void {
		let insertBefore: Element | null = locationEl //when insertBefore is null, currentDragClone will be placed at the end
		if(!this.currentDragClone)
			return
		if(this.currentDragClone.parentNode) {
			const locationIndex = this.getIndexInContainer(locationEl)
			const spacerIndex = this.getIndexInContainer(this.currentDragClone)
			
			if(spacerIndex < this.startIndex) { //mouse moved up (/right) from starting point
				if(spacerIndex < locationIndex) { //mouse is moving down (/left) again
					insertBefore = locationEl.nextElementSibling
					++this.currentTargetIndex
				}
			}
			else { //mouse moved down (/left) from starting point
				
				//we need to add one to targetIndex.
				// But when we are in the same list, removing the item (before moving it) means that targetIndex points to the element afterwards anyway.
				// But when we are in another list, we need to correct manually:
				if(this.currentStartList != this.currentTargetList)
					++this.currentTargetIndex
				
				if(spacerIndex > locationIndex) //mouse is moving up (/right) again
					--this.currentTargetIndex
				else
					insertBefore = locationEl.nextElementSibling
			}
			this.currentDragClone.parentNode.removeChild(this.currentDragClone)
		}
		locationEl.parentNode?.insertBefore(this.currentDragClone, insertBefore)
	}
	
	private ondragstart(index: number, targetList: ArrayType, e: DragEvent): boolean {
		let targetElement = e.currentTarget as HTMLElement
		while(targetElement && !targetElement.classList.contains("dragTarget")) {
			targetElement = targetElement.parentNode as HTMLElement
		}
		const dragRoot = this.contentVNode?.dom
		if(!dragRoot)
			return false
		
		
		this.startIndex = this.getIndexInContainer(targetElement)
		
		this.currentStartList = targetList
		this.currentStartIndex = index
		
		this.currentDragClone = targetElement.cloneNode(true) as HTMLElement
		this.currentDragClone?.classList.add("drag_spacer")
		
		this.currentDragClone?.addEventListener("dragover", this.ondragover.bind(this))
		this.currentDragClone?.addEventListener("dragleave", this.ondragleave.bind(this))
		this.currentDragClone?.addEventListener("drop", this.ondrop.bind(this))
		
		targetElement.classList.add("dragStarted")
		e.dataTransfer?.setDragImage(targetElement, 0, 0)
		
		
		this.currentStartElement = targetElement
		
		window.setTimeout( () => {
			//in firefox: setDragImage() seems to stop working when the class of document.body is changed
			//in chrome: DOM changes seem to cancel dragging altogether
			//solution: doing this stuff in a different "thread" seems to do the trick
			this.addDragSpacer(targetElement)
			dragRoot.classList.add("is_dragging")
		}, 0)
		return true
	}
	private ondragend(e: DragEvent): void {
		this.ondragleave(e)
		e.preventDefault()
		
		this.contentVNode?.dom.classList.remove("is_dragging")
		this.currentStartElement?.classList.remove("dragStarted")
		if(this.currentDragClone?.parentNode)
			this.currentDragClone.parentNode.removeChild(this.currentDragClone)
		
		this.currentDragClone = undefined
	}
	private ondragenter(index: number, targetList: ArrayType, e: DragEvent): false {
		const targetElement = e.currentTarget as HTMLElement
		if(!this.contentVNode?.dom.contains(targetElement))
			return false
		
		if(e.dataTransfer)
			e.dataTransfer.dropEffect = "none"
		e.preventDefault()
		
		this.currentTargetList = targetList
		this.currentTargetIndex = index
		
		this.addDragSpacer(targetElement)
		return false
	}
	
	private ondragover(e: DragEvent): void {
		e.preventDefault()
		if(e.dataTransfer)
			e.dataTransfer.dropEffect = "move"
	}
	private ondragleave(e: DragEvent): void {
		e.preventDefault()
	}
	private ondrop(e: DragEvent): void {
		e.preventDefault()
		
		if(this.currentStartList == null || this.currentTargetList == null)
			return
		
		if(this.currentTargetList == this.currentStartList)
			this.currentTargetList.move(this.currentStartIndex, this.currentTargetIndex)
		else
			this.currentTargetList.moveFromOtherList(this.currentStartList, this.currentStartIndex, this.currentTargetIndex)
		
		this.currentStartList = this.currentTargetList = undefined
		this.ondragend(e)
	}
	
	public onupdate(vNode: VnodeDOM<DragContainerImpOptions, any>): void {
		this.contentVNode = vNode
	}
	
	public view(vNode: Vnode<DragContainerImpOptions, any>): Vnode<any, any> {
		return vNode.attrs.content(this)
	}
}


export function DragContainer(
	content: (container: DragTools) => Vnode<any, any>
): Vnode<any, any> {
	return m(DragContainerImp, { content: content })
}