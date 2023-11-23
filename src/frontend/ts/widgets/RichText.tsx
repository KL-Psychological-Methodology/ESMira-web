import {ChainedCommands, Editor, mergeAttributes} from "@tiptap/core";
import Document from '@tiptap/extension-document'
import Text from '@tiptap/extension-text'
import Bold from '@tiptap/extension-bold';
import Bullet from '@tiptap/extension-bullet-list';
import Dropcursor from '@tiptap/extension-dropcursor';
import Heading from '@tiptap/extension-heading';
import History from '@tiptap/extension-history';
import HorizontalRule from '@tiptap/extension-horizontal-rule';
import Italic from '@tiptap/extension-italic';
import ListItem from '@tiptap/extension-list-item';
import OrderedList from '@tiptap/extension-ordered-list';
import Paragraph from '@tiptap/extension-paragraph';
import Strike from '@tiptap/extension-strike';

import Image from '@tiptap/extension-image'
import Link from '@tiptap/extension-link'
import HardBreak from '@tiptap/extension-hard-break'
import Highlight from '@tiptap/extension-highlight'
import Underline from '@tiptap/extension-underline'
import TextAlign from '@tiptap/extension-text-align'
import bold from '../../imgs/rich_text_toolbar/bold.svg?raw';
import italic from '../../imgs/rich_text_toolbar/italic.svg?raw';
import strikethrough from '../../imgs/rich_text_toolbar/strikethrough.svg?raw';
import heading1 from '../../imgs/rich_text_toolbar/heading1.svg?raw';
import heading2 from '../../imgs/rich_text_toolbar/heading2.svg?raw';
import heading3 from '../../imgs/rich_text_toolbar/heading3.svg?raw';
import listOrdered from '../../imgs/rich_text_toolbar/list_ordered.svg?raw';
import listUnordered from '../../imgs/rich_text_toolbar/list_unordered.svg?raw';
import redo from '../../imgs/rich_text_toolbar/redo.svg?raw';
import undo from '../../imgs/rich_text_toolbar/undo.svg?raw';
import image from '../../imgs/rich_text_toolbar/image.svg?raw';
import link from '../../imgs/rich_text_toolbar/link.svg?raw';
import highlight from '../../imgs/rich_text_toolbar/highlight.svg?raw';
import underline from '../../imgs/rich_text_toolbar/underline.svg?raw';
import alignLeft from '../../imgs/rich_text_toolbar/align_left.svg?raw';
import alignCenter from '../../imgs/rich_text_toolbar/align_center.svg?raw';
import alignRight from '../../imgs/rich_text_toolbar/align_right.svg?raw';
import m, {Component, Vnode, VnodeDOM} from 'mithril'
import {Lang} from "../singletons/Lang";
import {closeDropdown, DropdownMenu} from "./DropdownMenu";
import {BaseObservable} from "../observable/BaseObservable";


const extensions = [
	Paragraph.extend({
		parseHTML() {
			return [{ tag: 'div' }]
		},
		renderHTML({ HTMLAttributes }) {
			return ['div', mergeAttributes(this.options.HTMLAttributes, HTMLAttributes), 0]
		},
	}), //We always want HardBreaks. but we need to trick tiptap to achieve it.
	//So we use normal paragraph except we use <div> instead of <p> and when exporting we replace <div></div> with <br>
	// Thanks to: https://github.com/ueberdosis/tiptap/issues/291 and https://github.com/ueberdosis/tiptap/issues/426
	
	Text, //required
	Document, //required
	HardBreak, //Shift enter for break that doesnt close current tag
	Bold,
	Italic,
	Underline,
	Strike,
	Highlight,
	Heading,
	History, //have a undo / redo history
	Dropcursor, //show a cursor when dragging something into editor
	HorizontalRule, //replace --- with <hr>
	ListItem,
	Bullet,
	OrderedList,
	Image,
	Link,
	TextAlign.configure({
		types: ['heading', 'paragraph'],
	})
]

//thanks to: https://stackoverflow.com/questions/1144783/how-do-i-replace-all-occurrences-of-a-string-in-javascript
function escapeRegExp(str: string): string {
	return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // $& means the whole matched string
}
function replaceAll(str: string, find: string, replace: string): string {
	return str.replace(new RegExp(escapeRegExp(find), 'g'), replace);
}

class MenuOptions {
	public svg: string = ""
	public title: string = ""
	public activeKey?: string
	public click?: (chain?: ChainedCommands) => void
	public enabled?: (chain?: ChainedCommands) => boolean
	public attr?: {}
}
interface RichTextComponentOptions {
	obs: BaseObservable<string>
}
class RichTextComponent implements Component<RichTextComponentOptions, any> {
	private editor?: Editor
	private lastValue: string = ""
	private obs?: BaseObservable<string>
	
	public oncreate(vNode: VnodeDOM<RichTextComponentOptions, any>): void {
		const obs = vNode.attrs.obs
		this.obs = obs
		this.lastValue = obs.get()
		this.createEditor(vNode.dom, obs)
	}
	public onupdate(vNode: VnodeDOM<RichTextComponentOptions, any>): void {
		const newValue = vNode.attrs.obs.get()
		
		if(this.obs != vNode.attrs.obs) {
			if(this.editor)
				vNode.dom.removeChild(this.editor?.options.element)
			this.createEditor(vNode.dom, vNode.attrs.obs)
			this.obs = vNode.attrs.obs
		}
		else if(this.lastValue != newValue) {
			this.lastValue = newValue
			this.editor?.commands.setContent(newValue)
		}
	}
	private createEditor(parent: Element, obs: BaseObservable<string>): void {
		const editor = new Editor({
			extensions: extensions,
			content: obs.get(),
			onBlur: ({editor: e}) => {
				let newValue = replaceAll(e.getHTML(), "<div></div>", "<br>");
				newValue = newValue === "<br>" ? "" : newValue
				
				this.lastValue = newValue
				obs.set(newValue)
			},
			onSelectionUpdate: () => {
				m.redraw()
			}
		})
		
		const domElement = editor.options.element
		domElement.classList.add("editor")
		parent.append(domElement)
		this.editor = editor
	}
	
	
	public isActive(activeKey?: string, attr?: {}): boolean {
		return (activeKey && this.editor?.isActive(activeKey, attr) || (attr && this.editor?.isActive(attr))) || false
	}
	
	private getChain(): ChainedCommands | undefined {
		return this.editor?.chain().focus()
	}
	
	public createMenuBtn(options: MenuOptions): Vnode<any, any> {
		return <div
			class={`toolbarBtn ${this.isActive(options.activeKey, options.attr) ? "active" : ""} ${options.enabled && !options.enabled(this.editor?.can().chain().focus()) ? "disabled" : ""}`}
			title={options.title}
			onclick={() => {options.click && options.click(this.getChain());}}
		>{m.trust(options.svg)}</div>
	}
	
	private loadEmbeddedImage(): void {
		const silentInput = document.createElement("input")
		silentInput.type = "file"
		silentInput.multiple = true
		
		silentInput.onchange = () => {
			const files = silentInput.files;
			let i=1;
			const reader = new FileReader();
			
			if(files == null)
				return
			
			let load = (file: File) => {
				if(file && (file.size < 150000 || confirm(Lang.get("prompt_image_fileSize", file.name))))
					reader.readAsDataURL(file);
				else if(i < files.length)
					load(files[i++]);
				else
					closeDropdown("imageSelector")
			};
			reader.onloadend = () => {
				const result = reader.result as string
				if(result)
					this.getChain()?.selectNodeForward().setImage({src: result}).run();
				load(files[i++]);
			}
			load(files[0]);
		};
		silentInput.click();
	}
	private loadExternalImage(): void {
		const url = window.prompt(Lang.get("prompt_url"), "https://");
		
		if(url)
			this.getChain()?.setImage({src: url}).run();
		closeDropdown("imageSelector")
	}
	
	public view(): Vnode<any, any> {
		return <div class="richText">
			<div class="toolbar">
				<div class="group">
					{this.createMenuBtn({svg: bold, activeKey: "bold", title: Lang.get("bold"), click: (chain) => {chain?.toggleBold().run()}})}
					{this.createMenuBtn({svg: italic, activeKey: "italic", title: Lang.get("italic"), click: (chain) => {chain?.toggleItalic().run()}})}
					{this.createMenuBtn({svg: underline, activeKey: "underline", title: Lang.get("underline"), click: (chain) => {chain?.toggleUnderline().run()}})}
					{this.createMenuBtn({svg: strikethrough, activeKey: "strike", title: Lang.get("strikethrough"), click: (chain) => {chain?.toggleStrike().run()}})}
					{this.createMenuBtn({svg: highlight, activeKey: "highlight", title: Lang.get("highlighted"), click: (chain) => {chain?.toggleHighlight().run()}})}
				</div>
				
				<div class="group">
					{this.createMenuBtn({svg: heading1, activeKey: "heading", attr: {level: 1}, title: Lang.get("heading"), click: (chain) => {chain?.toggleHeading({level: 1}).run()}})}
					{this.createMenuBtn({svg: heading2, activeKey: "heading", attr: {level: 2}, title: Lang.get("heading"), click: (chain) => {chain?.toggleHeading({level: 2}).run()}})}
					{this.createMenuBtn({svg: heading3, activeKey: "heading", attr: {level: 3}, title: Lang.get("heading"), click: (chain) => {chain?.toggleHeading({level: 3}).run()}})}
				</div>
				
				<div class="group">
					{this.createMenuBtn({svg: alignLeft, attr: {textAlign: 'left'}, title: Lang.get("textAlign_left"), click: (chain) => {chain?.setTextAlign("left").run()}})}
					{this.createMenuBtn({svg: alignCenter, attr: {textAlign: 'center'}, title: Lang.get("textAlign_center"), click: (chain) => {chain?.setTextAlign("center").run()}})}
					{this.createMenuBtn({svg: alignRight, attr: {textAlign: 'right'}, title: Lang.get("textAlign_right"), click: (chain) => {chain?.setTextAlign("right").run()}})}
				</div>
				
				<div class="group">
					{this.createMenuBtn({svg: listUnordered, activeKey: "bulletList", title: Lang.get("unorderedList"), click: (chain) => {chain?.toggleBulletList().run()}})}
					{this.createMenuBtn({svg: listOrdered, activeKey: "orderedList", title: Lang.get("orderedList"), click: (chain) => {chain?.toggleOrderedList().run()}})}
				</div>
				
				<div class="group">
					{
						DropdownMenu("imageSelector",
							this.createMenuBtn({svg: image, activeKey: "image", title: Lang.get("add_image")}),
							() => {return <div class="horizontalPadding verticalPadding">
								<a class="center line" onclick={this.loadEmbeddedImage.bind(this)}>{Lang.get("embedded")}</a>
								<a class="center line" onclick={this.loadExternalImage.bind(this)}>{Lang.get("external")}</a>
							</div>}
						)
					}
					{
						this.createMenuBtn({svg: link, activeKey: "link", title: Lang.get("add_link"), click: (chain) => {
							if(this.editor?.isActive("link"))
								chain?.unsetLink().run();
							else {
								const url = window.prompt(Lang.get("prompt_url"), "https://");
								
								if(url)
									chain?.toggleLink({href: url, target: url.startsWith("#") || url.startsWith("/") ? "_self" : "_blank"}).run();
							}
						}})}
				</div>
				
				<div class="group">
					{this.createMenuBtn({svg: undo, title: Lang.get("undo"), click: (chain) => {chain?.undo().run()}, enabled: (chain) => { return !!chain?.undo().run() }})}
					{this.createMenuBtn({svg: redo, title: Lang.get("redo"), click: (chain) => {chain?.redo().run()}, enabled: (chain) => { return !!chain?.redo().run() }})}
				</div>
			</div>
		</div>;
	}
}




export function RichText(obs: BaseObservable<string>): Vnode<any, any> {
	return m(RichTextComponent, {obs: obs})
}