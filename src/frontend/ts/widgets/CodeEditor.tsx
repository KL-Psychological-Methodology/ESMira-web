import m, { Component, Vnode, VnodeDOM } from "mithril"
import { BaseObservable } from "../observable/BaseObservable"
import { basicSetup, EditorView } from "codemirror"
import { EditorState } from "@codemirror/state"
import { ViewUpdate } from "@codemirror/view"
import { linter, Diagnostic, lintGutter } from "@codemirror/lint"
import { MerlinScanner } from "../merlin/merlinScanner"
import { MerlinParser } from "../merlin/merlinParser"

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
            }),
            lintGutter(),
            merlinLinter
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
            this.editor?.state.update({changes: {from: 0, to: this.editor.state.doc.length, insert: newValue}})
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

const merlinLinter = linter(view => {
     let diagnostics: Diagnostic[] = []
     const doc = view.state.doc
     const source = doc.toString()
     const scanner = new MerlinScanner(source)
     const tokens = scanner.scanTokens()
     if (scanner.hadErrors()) {
        scanner.errors.forEach(e => {
            const lineOffsets = doc.line(e.line)
            diagnostics.push({
                from: lineOffsets.from,
                to: lineOffsets.to,
                severity: "error",
                message: e.message
            })
        })
        return diagnostics
    }
    const parser = new MerlinParser(tokens)
    parser.parse()
    if (parser.hadErrors()) {
        parser.errors.forEach(e => {
            const lineOffsets = doc.line(e.token.line)
            diagnostics.push({
                from: lineOffsets.from,
                to: lineOffsets.to,
                severity: "error",
                message: e.message
            })
        })
    }
     return diagnostics
})

export function CodeEditor(obs: BaseObservable<string>): Vnode<any, any> {
    return m(CodeEditorComponent, {obs: obs})
}