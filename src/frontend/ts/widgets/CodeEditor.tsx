import m, { Component, Vnode, VnodeDOM } from "mithril";
import { BaseObservable } from "../observable/BaseObservable";
import { basicSetup, EditorView } from "codemirror";
import { EditorState } from "@codemirror/state"
import { ViewUpdate } from "@codemirror/view";
import { Editor } from "@tiptap/core";

interface CodeEditorComponentOptions {
    obs: BaseObservable<string>
}

class CodeEditorComponent implements Component<CodeEditorComponentOptions, any> {
    private obs?: BaseObservable<string>
    private lastValue: string = ""
    private editor?: EditorView

    private extensions = [
            basicSetup,
            EditorView.updateListener.of((update: ViewUpdate) => {
                this.obs?.set(update.state.doc.toString())
            })
        ]

    public oncreate(vNode: VnodeDOM<CodeEditorComponentOptions, any>): void {
        const obs = vNode.attrs.obs
        this.obs = obs
        this.lastValue = obs.get()
        this.createEditor(vNode.dom, this.obs)
    }

    public onupdate(vNode: VnodeDOM<CodeEditorComponentOptions, any>): void {
        const newValue = vNode.attrs.obs.get()

        if(this.obs != vNode.attrs.obs) {
            if(this.editor)
                this.editor.dom.remove()
            this.obs = vNode.attrs.obs
            this.createEditor(vNode.dom, this.obs)
        } else if (this.lastValue != newValue) {
            this.lastValue = newValue
            this.editor?.setState(EditorState.create({doc: newValue, extensions: this.extensions}))
        }
    }

    public createEditor(parent: Element, obs: BaseObservable<string>): void {
        const initialState = EditorState.create({
            doc: obs.get(),
            extensions: this.extensions
        })
        const editor = new EditorView({
            parent: parent,
            state: initialState
        })
        this.editor = editor
    }

    public view(): Vnode<any, any> {
        return <div id="codeEditor"></div>
    }
}

export function CodeEditor(obs: BaseObservable<string>): Vnode<any, any> {
    return m(CodeEditorComponent, {obs: obs})
}