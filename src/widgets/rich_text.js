import {Editor} from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";
import Image from '@tiptap/extension-image'
import Link from '@tiptap/extension-link'
import Highlight from '@tiptap/extension-highlight'
import Underline from '@tiptap/extension-underline'
import TextAlign from '@tiptap/extension-text-align'
import {bindEvent, createElement} from "../js/helpers/basics";
import {Lang} from "../js/main_classes/lang";
import bold from '../imgs/rich_text_toolbar/bold.svg?raw';
import italic from '../imgs/rich_text_toolbar/italic.svg?raw';
import strikethrough from '../imgs/rich_text_toolbar/strikethrough.svg?raw';
import heading1 from '../imgs/rich_text_toolbar/heading1.svg?raw';
import heading2 from '../imgs/rich_text_toolbar/heading2.svg?raw';
import heading3 from '../imgs/rich_text_toolbar/heading3.svg?raw';
import list_ordered from '../imgs/rich_text_toolbar/list_ordered.svg?raw';
import list_unordered from '../imgs/rich_text_toolbar/list_unordered.svg?raw';
import redo from '../imgs/rich_text_toolbar/redo.svg?raw';
import undo from '../imgs/rich_text_toolbar/undo.svg?raw';
import image from '../imgs/rich_text_toolbar/image.svg?raw';
import link from '../imgs/rich_text_toolbar/link.svg?raw';
import highlight from '../imgs/rich_text_toolbar/highlight.svg?raw';
import underline from '../imgs/rich_text_toolbar/underline.svg?raw';
import align_left from '../imgs/rich_text_toolbar/align_left.svg?raw';
import align_center from '../imgs/rich_text_toolbar/align_center.svg?raw';
import align_right from '../imgs/rich_text_toolbar/align_right.svg?raw';

export function RichText(rootEl, params) {
	let value = params.value;
	let justChanged = false;
	
	let editorEl = rootEl.querySelector(".editor");
	let toolbarEl = rootEl.querySelector(".toolbar");
	
	
	let onSelectionUpdate = function() {
		for(let i=toolbar.length-1; i>=0; --i) {
			let btn = toolbar[i];
			if(!btn || (!btn.key && !btn.attr))
				continue;
			// if(editor.isActive(btn.key, btn.attr)) {
			// 	btn.el.classList.add("active");
			// 	btn.active = true;
			// }
			if(btn.key && editor.isActive(btn.key, btn.attr)) {
				btn.el.classList.add("active");
				btn.active = true;
			}
			else if(btn.attr && editor.isActive(btn.attr)) {
				btn.el.classList.add("active");
				btn.active = true;
			}
			else if(btn.active) {
				btn.el.classList.remove("active");
				btn.active = false;
				
			}
		}
	};
	let editor = new Editor({
		element: editorEl,
		extensions: [
			StarterKit,
			Image,
			Link,
			Highlight,
			Underline,
			TextAlign
		],
		content: value(),
		onUpdate({editor}) {
			justChanged = true;
			value(editor.getHTML());
		},
		onSelectionUpdate: onSelectionUpdate,
		beforeDestroy() {
			subscription.dispose();
		}
	});
	
	let subscription = value.subscribe(function(newValue) {
		if(justChanged) {
			justChanged = false;
			return;
		}
		editor.commands.setContent(newValue)
	});
	this.editor = editor;
	
	let chain = function() {
		return editor.chain().focus();
	}
	
	let toolbar = [
		{fu: function() {chain().toggleBold().run();}, html: bold, key: "bold", lang: "bold"},
		{fu: function() {chain().toggleItalic().run();}, html: italic, key: "italic", lang: "italic"},
		{fu: function() {chain().toggleUnderline().run();}, html: underline, key: "underline", lang: "underline"},
		{fu: function() {chain().toggleStrike().run();}, html: strikethrough, key: "strike", lang: "strikethrough"},
		{fu: function() {chain().toggleHighlight().run();}, html: highlight, key: "highlight", lang: "highlighted"},
		false,
		{fu: function() {chain().toggleHeading({level: 1}).run();}, html: heading1, key: "heading", attr: {level: 1}, lang: "heading"},
		{fu: function() {chain().toggleHeading({level: 2}).run();}, html: heading2, key: "heading", attr: {level: 2}, lang: "heading"},
		{fu: function() {chain().toggleHeading({level: 3}).run();}, html: heading3, key: "heading", attr: {level: 3}, lang: "heading"},
		false,
		{fu: function() {chain().setTextAlign('left').run();}, html: align_left, attr: {textAlign: 'left'}, lang: "textAlign_left"},
		{fu: function() {chain().setTextAlign('center').run();}, html: align_center, attr: {textAlign: 'center'}, lang: "textAlign_center"},
		{fu: function() {chain().setTextAlign('right').run();}, html: align_right, attr: {textAlign: 'right'}, lang: "textAlign_right"},
		false,
		{fu: function() {chain().toggleBulletList().run();}, html: list_unordered, key: "bulletList", lang: "unorderedList"},
		{fu: function() {chain().toggleOrderedList().run();}, html: list_ordered, key: "orderedList", lang: "orderedList"},
		false,
		{fu: function() {
			const url = window.prompt(Lang.get("prompt_url"), "https://");
			
			if(url)
				chain().setImage({src: url}).run();
		}, html: image, key: "image", lang: "add_image"},
		{fu: function() {
			if(editor.isActive("link"))
				chain().unsetLink().run();
			else {
				const url = window.prompt(Lang.get("prompt_url"), "https://");
				
				if(url)
					chain().toggleLink({href: url}).run();
			}
		}, html: link, key: "link", lang: "add_link"},
		false,
		{fu: function() {chain().undo().run();}, html: undo, lang: "undo"},
		{fu: function() {chain().redo().run();}, html: redo, lang: "redo"},
	];
	
	
	let currentGroup = createElement("div", false, {className: "group"});
	for(let i=0, max=toolbar.length; i<max; ++i) {
		let entry = toolbar[i];

		if(entry) {
			let el = createElement("div", false, {innerHTML: entry.html, className: "btn", title: Lang.get(entry.lang)});
			bindEvent(el, "click", function() {entry.fu(); onSelectionUpdate();});
			// bindEvent(el, "click", function() {editor.chain().focus().toggleBold().run()});
			currentGroup.appendChild(el);
			entry.el = el;
		}
		else {
			toolbarEl.appendChild(currentGroup);
			currentGroup = createElement("div", false, {className: "group"});
		}
	}
	toolbarEl.appendChild(currentGroup);
}