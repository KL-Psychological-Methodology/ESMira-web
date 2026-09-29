import {Input} from "../data/study/Input";
import m, {Component, Vnode, VnodeDOM} from "mithril";
import { Page } from "../data/study/Page";
import { Lang } from "../singletons/Lang";
import okSvg from "../../imgs/icons/ok.svg?raw";
import "./Preview.css";


export function previewPage(page: Page) {
	return m(PagePreviewComponent, {page: page})
}

export function previewInput(input: Input) {
	return m(getPreviewClass(input), {input: input})
}

function getPreviewClass(input: Input): m.ComponentTypes<PreviewComponentOptions, any> {
	switch(input.responseType.get()) {
		case "ambient_light":
			return AmbientLightComponent
		case "app_usage":
			return AppUsageComponent
		case "battery_level":
			return BatteryLevelComponent
		case "binary":
			return BinaryComponent
		case "bluetooth_devices":
			return BluetoothComponent
		case "compass":
			return CompassComponent
		case "countdown":
			return CountdownComponent
		case "duration":
			return DurationInputComponent
		case "date":
			return DateInputComponent
		case "dynamic_input":
			return DynamicComponent
		case "file_upload":
			return FileUploadComponent
		case "image":
			return ImageComponent
		case "likert":
			return LikertComponent
		case "list_single":
			return ListSingleComponent
		case "list_multiple":
			return ListMultipleComponent
		case "location":
			return LocationComponent
		case "noise_level":
			return NoiseLevelComponent
		case "number":
			return NumberInputComponent
		case "photo":
			return PhotoComponent
		case "record_audio":
			return RecordAudioComponent
		case "share":
			return ShareComponent
		case "text":
			return TextComponent
		case "text_input":
			return TextInputComponent
		case "time":
			return TimeInputComponent
		case "va_scale":
			return VasComponent
		case "video":
			return VideoComponent
		default:
			return NotImplementedComponent
	}
}

interface PagePreviewComponentOptions {
	page: Page
}

class PagePreviewComponent implements Component<PagePreviewComponentOptions, any> {
	private keys: number[] = []
	private isRandomized = false
	oninit(vNode: Vnode<PagePreviewComponentOptions, any>): void {
		const page = vNode.attrs.page
		this.initializeKeys(page)
	}
	
	onbeforeupdate(vNode: VnodeDOM<PagePreviewComponentOptions, any>): void {
		const page = vNode.attrs.page
		if(page.inputs.get().length != this.keys.length || page.randomized.get() != this.isRandomized) {
			this.initializeKeys(page)
		}
	}
	
	private initializeKeys(page: Page) {
		this.keys = Array.from(page.inputs.get().keys())
		if(page.randomized.get()) {
			for (let i = this.keys.length - 1; i > 0; i--) {
				const j = Math.floor(Math.random() * (i + 1));
				[this.keys[i], this.keys[j]] = [this.keys[j], this.keys[i]];
			}
			this.isRandomized = true
		}
		else {
			this.isRandomized = false
		}
	}
	
	public view(vNode: VnodeDOM<PagePreviewComponentOptions, any>): Vnode<any, any> {
		const page = vNode.attrs.page
		const inputs = page.inputs.get()
		return <div class="coloredLines">
			{page.header.get() && <div class="line pageHeader">{m.trust(page.header.get())}</div>}
			{this.keys.map(index =>
				previewInput(inputs[index])
			)}
			{page.footer.get() && <div class="line pageFooter">{m.trust(page.footer.get())}</div>}
		</div>
	}
}

interface PreviewComponentOptions {
	input: Input
}

class TextComponent implements Component<PreviewComponentOptions, any> {
	public view(vNode: VnodeDOM<PreviewComponentOptions, any>): Vnode<any, any> {
		const input = vNode.attrs.input
		return <div class={`previewInput line ${input.responseType.get()}`}>
			<div class="header">{m.trust(input.text.get())}</div>
			<div class="content">{this.viewContent(input, vNode)}</div>
		</div>
	}
	
	public viewContent(_input: Input, _vNode: VnodeDOM<PreviewComponentOptions, any>): Vnode<any, any> | null {
		return null
	}
}

class NotImplementedComponent extends TextComponent {
	public viewContent(input: Input): Vnode<any, any> {
		return <div class="highlight">No preview for this item ({input.responseType.get()})</div>
	}
}

class AmbientLightComponent extends TextComponent {
	private value = 0
	
	public viewContent(_: Input): Vnode<any, any> {
		return <div class="vertical hAlignCenter">
			{this.value || "-"} lx
			<input type="button" value={Lang.get("measure")} onclick={() => {this.value = Math.round(Math.random() * 100)}}/>
		</div>
	}
}

class AppUsageComponent extends TextComponent {
	private static readonly EXAMPLE_DETAILED_DATA = `${Lang.get("app_usage_sessions_yesterday")}
10:23 - 10:26
11:05 - 11:10
11:51 - 11:52
13:33 - 13:34
19:19 - 19:20
19:23 - 19:25
21:48 - 21:51

${Lang.get("app_usage_sessions_today")}
15:35 - 15:37
16:59 - 17:03
17:05 - 17:06
`
	
	public viewContent(input: Input): Vnode<any, any> {
		const isAppUsage = !!input.packageId.get()
		return <div class="horizontal hAlignCenter">
			<table>
				<thead>
				<tr>
					<th colspan={3}>{Lang.getWithColon(isAppUsage ? "app_usage" : "total_screenTime")}</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td></td>
					<th>{Lang.get("yesterday")}</th>
					<th>{Lang.get("today")}</th>
				</tr>
				<tr>
					<td>{Lang.getWithColon("usageTime")}</td>
					<td>00:16:20</td>
					<td>00:07:06</td>
				</tr>
				<tr>
					<td>{Lang.getWithColon("usageCount")}</td>
					<td>7</td>
					<td>3</td>
				</tr>
				{isAppUsage &&
					<tr>
						<td></td>
						<td cosSpan={2}>
							<input type="button" value={Lang.get("showDetailedData")} onclick={() => alert(`${input.packageId.get()}\n\n${AppUsageComponent.EXAMPLE_DETAILED_DATA}`)}/>
						</td>
					</tr>
				}
				</tbody>
			</table>
		</div>
	}
}

class BatteryLevelComponent extends TextComponent {
	private value = 0
	
	public viewContent(_: Input): Vnode<any, any> {
		return <div class="vertical hAlignCenter">
			{this.value || "-"} %
			<input type="button" value={Lang.get("measure")} onclick={() => {this.value = Math.round(Math.random() * 100)}}/>
		</div>
	}
}

class BinaryComponent extends TextComponent {
	public viewContent(input: Input): Vnode<any, any> {
		return <div class="horizontal">
			<label class="spacingRight noDesc noTitle">
				{input.leftSideLabel.get()}
				<input type="radio" name={input.name.get()} value="0"/>
			</label>
			<label class="spacingLeft noDesc noTitle">
				<input type="radio" name={input.name.get()} value="1"/>
				{input.rightSideLabel.get()}
			</label>
		</div>
	}
}

class BluetoothComponent extends TextComponent {
	private static readonly TIMEOUT_MS = 7000
	private static readonly DEVICES_FOUND = 3
	
	private static readonly EXAMPLE_LIST = `
1275736909		~10,0m (-94)
3476526058		~1,0m (-78)
2658773209		~4,0m (-86)
`
	
	private countdownState = "initial"
	private timeoutId = 0
	private currentValue = 0
	private endTime = 0
	
	private startCountdown() {
		window.clearTimeout(this.timeoutId)
		this.countdownState = "running"
		this.endTime = Date.now() + BluetoothComponent.TIMEOUT_MS
		this.timeoutId = window.setInterval(this.updateValue.bind(this), 500)
		this.updateValue()
	}
	private stopCountdown () {
		window.clearTimeout(this.timeoutId)
		this.countdownState = "done"
		m.redraw()
	}
	
	private updateValue() {
		const now = Date.now()
		if(now >= this.endTime) {
			this.stopCountdown()
			return
		}
		this.currentValue = 100 - Math.floor(100 / (BluetoothComponent.TIMEOUT_MS / (this.endTime - now)))
		m.redraw()
	}
	
	onremove(): void {
		window.clearInterval(this.timeoutId)
	}
	
	public viewContent(_: Input): Vnode<any, any> {
		switch(this.countdownState) {
			default:
				return <input type="button" value={`🛜 ${Lang.get("start_scanning")}`} onclick={this.startCountdown.bind(this)} />
			case "running":
				return <div class="vertical hAlignCenter">
					{this.currentValue}%
					<input type="range" value={this.currentValue}/>
				</div>
			case "done":
				return <div class="vertical hAlignCenter">
					<input type="button" value={Lang.get("list_devices", BluetoothComponent.DEVICES_FOUND)} onclick={() => alert(BluetoothComponent.EXAMPLE_LIST)} />
					<input type="button" value={`🛜 ${Lang.get("start_scanning")}`} onclick={this.startCountdown.bind(this)} />
				</div>
		}
	}
}

class CompassComponent extends TextComponent {
	private isRunning = false
	private value?: number = undefined
	private timeoutId = 0
	
	private toggleTurning() {
		if(this.isRunning) {
			clearInterval(this.timeoutId)
			this.isRunning = false
		}
		else {
			if(this.value == undefined) {
			this.value = Math.random() * 360
				}
			this.timeoutId = window.setInterval(this.updateValue.bind(this), 2000)
			this.isRunning = true
		}
	}
	
	private updateValue() {
		this.value = ((this.value ?? 0) + Math.random() * 180 - 90) % 360
		m.redraw()
	}
	
	onremove(): void {
		window.clearInterval(this.timeoutId)
	}
	
	public viewContent(input: Input): Vnode<any, any> {
		return <div class="item horizontal hAlignCenter">
			<div class="circle" style={{transform: `rotate(${this.value}deg)`}}>
				<div class="arrow">⏶</div>
			</div>
			<div class="result vertical hAlignCenter">
				{input.showValue.get() && this.value != undefined && <div>{input.numberHasDecimal.get() ? this.value : Math.round(this.value)}°</div>}
				<input type="button" value={Lang.get(this.isRunning ? "stop_scanning" : "start_scanning")} onclick={this.toggleTurning.bind(this)}/>
			</div>
		</div>
	}
}

class CountdownComponent extends TextComponent {
	private countdownState = "initial"
	private timeoutId = 0
	private currentValue = ""
	private endTime = 0
	private showValue = true
	private timeoutSec = 0
	
	private startCountdown() {
		window.clearTimeout(this.timeoutId)
		this.countdownState = "running"
		this.endTime = Date.now() + this.timeoutSec * 1000
		this.timeoutId = window.setInterval(this.updateValue.bind(this), 1000)
		this.updateValue()
	}
	private stopCountdown () {
		window.clearTimeout(this.timeoutId)
		this.countdownState = "done"
		m.redraw()
	}
	
	private updateValue() {
		if(Date.now() >= this.endTime) {
			this.stopCountdown()
			return
		}
		this.currentValue = this.showValue ? `${Math.floor((this.endTime - Date.now()) / 1000)}` : Lang.get("countdown_running")
		m.redraw()
	}
	
	public oninit(vNode: VnodeDOM<PreviewComponentOptions, any>): void {
		const input = vNode.attrs.input
		this.showValue = input.showValue.get()
		this.timeoutSec = input.timeoutSec.get()
	}
	
	public onbeforeupdate(vNode: VnodeDOM<PreviewComponentOptions, any>): void {
		const input = vNode.attrs.input
		if(input.showValue.get() != this.showValue || input.timeoutSec.get() != this.timeoutSec) {
			window.clearTimeout(this.timeoutId)
			this.showValue = input.showValue.get()
			this.timeoutSec = input.timeoutSec.get()
			this.countdownState = "initial"
		}
	}
	
	onremove(): void {
		window.clearInterval(this.timeoutId)
	}
	
	public viewContent(_: Input): Vnode<any, any> {
		switch(this.countdownState) {
			default:
				return <input type="button" value={`⏵ ${Lang.get("start_timer")}`} onclick={this.startCountdown.bind(this)} />
			case "running":
				return <div class="center">{this.currentValue}</div>
			case "done":
				return m.trust(okSvg)
		}
	}
}

class DateInputComponent extends TextComponent {
	public viewContent(_: Input): Vnode<any, any> {
		return <input type="date"/>
	}
}

class DurationInputComponent extends TextComponent {
	public viewContent(_: Input): Vnode<any, any> {
		return <div class="horizontal">
			H: <input type="number"/>
			M: <input type="number"/>
		</div>
	}
}

class DynamicComponent extends TextComponent {
	private index = 0
	private length = 0
	
	private updateData(input: Input) {
		const subInputs = input.subInputs.get()
		this.index = Math.floor(Math.random() * subInputs.length)
		this.length = subInputs.length
	}
	
	public oninit(vNode: VnodeDOM<PreviewComponentOptions, any>): void {
		const input = vNode.attrs.input
		this.updateData(input)
		m.redraw()
	}
	
	public onbeforeupdate(vNode: VnodeDOM<PreviewComponentOptions, any>): void {
		const input = vNode.attrs.input
		const subInputs = input.subInputs.get()
		if(subInputs.length != this.length) {
			this.updateData(input)
		}
	}
	public viewContent(input: Input): Vnode<any, any> {
		const subInputs = input.subInputs.get()
		const currentSub = subInputs[this.index]
		
		return <div class="line horizontal hAlignSpaced">
			<div class="fillFlexSpace">{m(getPreviewClass(currentSub), {input: currentSub})}</div>
		</div>
	}
}

class FileUploadComponent extends TextComponent {
	private imageUrl?: string = undefined
	
	private uploadImage(e: Event) {
		const input = e.target as HTMLInputElement
		if(!input.files || !input.files[0]) {
			return
		}
		const file = input.files[0]
		const fileReader = new FileReader();
		fileReader.readAsDataURL(file);
		
		fileReader.onload = (fileReaderEvent) => {
			this.imageUrl = fileReaderEvent.target?.result as string
			m.redraw()
		}
	}
	
	private selectImage() {
		const el = document.createElement("input")
		el.type = "file"
		el.accept = "image/*"
		el.addEventListener("change", this.uploadImage.bind(this))
		el.click()
	}
	
	public viewContent(_: Input): Vnode<any, any> {
		return <div class="vertical hAlignCenter">
			{this.imageUrl && <img src={this.imageUrl} alt=""/>}
			<input type="button" accept="image/*" value={`📷 ${Lang.get("select_picture")}`} onclick={this.selectImage.bind(this)}/>
		</div>
	}
}

class ImageComponent extends TextComponent {
	public viewContent(input: Input): Vnode<any, any> {
		return <img src={input.url.get()} alt=""/>
	}
}

class LikertComponent extends TextComponent {
	public viewContent(input: Input): Vnode<any, any> {
		return <div class="center">
			<div class="horizontal flexBlock">&nbsp;
				<div class="smallText">{input.leftSideLabel.get()}</div>
				<div class="fillFlexSpace"></div>
				<div class="smallText">{input.rightSideLabel.get()}</div>
			</div>
			<div>
				{[... Array(input.likertSteps.get())].map(() =>
					<input type="radio" name={input.name.get()}/>
				)}
			</div>
		</div>
	}
}

class ListSingleComponent extends TextComponent {
	public viewContent(input: Input): Vnode<any, any> {
		return <div class="center">
			{input.asDropDown.get()
				? <div class="center">
					<select value="select">
						<option disabled="disabled" value="select">{Lang.get("please_select")}</option>
						{input.listChoices.get().map((choice) =>
							<option>{choice.get()}</option>
						)}
					</select>
				</div>
				: <div class="vertical hAlignStart">
					{input.listChoices.get().map((choice) =>
						<label class="noDesc noTitle">
							<input type="radio" name={input.name.get()}/>
							<span>{choice.get()}</span>
						</label>
					)}
				</div>
			}
			
		</div>
	}
}

class ListMultipleComponent extends TextComponent {
	public viewContent(input: Input): Vnode<any, any> {
		return <div class="center">
			<div class="vertical hAlignStart">
				{input.listChoices.get().map((choice) =>
					<label class="noDesc noTitle">
						<input type="checkbox" name={input.name.get()}/>
						<span>{choice.get()}</span>
					</label>
				)}
			</div>
		</div>
	}
}

class LocationComponent extends TextComponent {
	private static readonly URL = "https://wolf-h3-viewer.glitch.me/?h3="
	private static readonly EXAMPLE_LOCATION = [
		"801ffffffffffff",
		"811e3ffffffffff",
		"821e37fffffffff",
		"831e33fffffffff",
		"841e333ffffffff",
		"851e332ffffffff",
		"861e3328fffffff",
		"871e33289ffffff",
		"881e33289bfffff",
		"891e33289a3ffff",
		"8a1e33289a17fff",
		"8b1e33289a12fff",
		"8c1e33289a12bff",
		"8d1e33289a12abf",
		"8e1e33289a12a8f",
		"8f1e33289a12a8b"
	]
	private hasScanned = false
	
	public viewContent(input: Input): Vnode<any, any> {
		const resolution = input.resolution.get()
		const location = LocationComponent.EXAMPLE_LOCATION[resolution]
		
		return <div class="vertical hAlignCenter">
			{this.hasScanned &&
				<input
					type="button"
					value={Lang.get("found_location", location)}
					onclick={() => window.open(LocationComponent.URL + location)}
				/>
			}
			<input type="button" value={`📍 ${Lang.get("start_scanning")}`} onclick={() => this.hasScanned = true} />
		</div>
	}
}

class NumberInputComponent extends TextComponent {
	public viewContent(input: Input): Vnode<any, any> {
		return <input type="number" step={input.numberHasDecimal.get() ? 0.1 : 1}/>
	}
}

class NoiseLevelComponent extends TextComponent {
	private static readonly TIMEOUT_MS = 600
	
	private isRunning = false
	private value?: number = undefined
	private timeoutId = 0
	private currentProgress = 0
	private endTime = 0
	
	private toggleRecording() {
		window.clearTimeout(this.timeoutId)
		if(this.isRunning) {
			this.isRunning = false
		}
		else {
			this.isRunning = true
			this.endTime = Date.now() + NoiseLevelComponent.TIMEOUT_MS
			this.timeoutId = window.setInterval(this.updateValue.bind(this), 500)
			this.updateValue()
		}
		m.redraw()
	}
	
	private updateValue() {
		const now = Date.now()
		if(now >= this.endTime) {
			window.clearTimeout(this.timeoutId)
			this.isRunning = false
			this.value = -Math.round(Math.random() * 144)
			m.redraw()
			return
		}
		this.currentProgress = 100 - Math.floor(100 / (NoiseLevelComponent.TIMEOUT_MS / (this.endTime - now)))
		m.redraw()
	}
	
	onremove(): void {
		window.clearInterval(this.timeoutId)
	}
	
	public viewContent(_: Input): Vnode<any, any> {
		return <div class="vertical hAlignCenter">
			{this.isRunning
				? <input type="range" value={this.currentProgress}/>
				: this.value &&
				<div>{Lang.get("measured_noise_level", this.value)}</div>
			}
			<input
				type="button"
				value={this.isRunning ? `⏹ ${Lang.get("stop_audio_record")}` : `⏺ ${Lang.get("start_audio_record")}`}
				onclick={this.toggleRecording.bind(this)}
			/>
		</div>
	}
}

class PhotoComponent extends TextComponent {
	private imageUrl?: string = undefined
	
	private createImage() {
		const canvas = document.createElement("canvas")
		const ctx = canvas.getContext('2d')!
		
		function randomInt(min: number, max: number) {
			return Math.floor(Math.random() * (max - min + 1)) + min
		}
		
		ctx.fillStyle = `hsl(${randomInt(0,360)}, 70%, 10%)`;
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		
		const rectCount = randomInt(5, 20);
		for (let i = 0; i < rectCount; i++) {
			ctx.fillStyle = `hsla(${randomInt(0,360)}, ${randomInt(40,90)}%, ${randomInt(30,80)}%, ${Math.random().toFixed(2)})`;
			const w = randomInt(20, canvas.width / 2);
			const h = randomInt(20, canvas.height / 2);
			const x = randomInt(0, canvas.width - w);
			const y = randomInt(0, canvas.height - h);
			ctx.fillRect(x, y, w, h);
		}
		
		this.imageUrl = canvas.toDataURL()
	}
	
	public viewContent(_: Input): Vnode<any, any> {
		return <div class="vertical hAlignCenter">
			{this.imageUrl && <img src={this.imageUrl} alt="" />}
			<input type="button" accept="image/*" value={`📷 ${Lang.get("take_picture")}`} onclick={this.createImage.bind(this)}/>
		</div>
	}
}

class RecordAudioComponent extends TextComponent {
	private static readonly EXAMPLE_AUDIO = "data:audio/wav;base64,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"
	private isRecording = false
	private isPlaying = false
	private hasRecording = false
	private audio: HTMLAudioElement
	
	constructor() {
		super();
		this.audio = new Audio();
		this.audio.src = RecordAudioComponent.EXAMPLE_AUDIO;
		this.audio.addEventListener("ended", () => {
			this.isPlaying = false
			m.redraw()
		});
	}
	
	private toggleRecord() {
		this.isRecording = !this.isRecording
		if(!this.isRecording) {
			this.hasRecording = true
		}
	}
	private toggleAudio() {
		if(this.isPlaying) {
			this.isPlaying = false
			this.audio.pause();
			return;
		}
		else {
			this.isPlaying = true
			this.audio.play();
		}
	}
	
	public viewContent(_: Input): Vnode<any, any> {
		return <div class="vertical hAlignStretched">
			<input
				type="button"
				value={this.isRecording ? `⏹ ${Lang.get("stop_audio_record")}` : `⏺ ${Lang.get("start_audio_record")}`}
				onclick={this.toggleRecord.bind(this)}
			/>
			<input
				type="button"
				value={this.isPlaying ? `⏹ ${Lang.get("stop_playing_audio")}` : `▶ ${Lang.get("start_playing_audio")}`}
				disabled={!this.hasRecording}
				onclick={this.toggleAudio.bind(this)}
			/>
		</div>
	}
}

class ShareComponent extends TextComponent {
	private static readonly USER_ID = "fgR5-sdAM-USKD";
	public viewContent(input: Input): Vnode<any, any> {
		return <input type="button" value={`🔗${Lang.get("open_url")}`} onclick={() => window.open(input.url.get().replace("[[USER_ID]]", ShareComponent.USER_ID))}/>
	}
}

class TextInputComponent extends TextComponent {
	public viewContent(_: Input): Vnode<any, any> {
		return <input type="text"/>
	}
}

class TimeInputComponent extends TextComponent {
	public viewContent(_: Input): Vnode<any, any> {
		return <input type="time"/>
	}
}

class VasComponent extends TextComponent {
	public viewContent(input: Input): Vnode<any, any> {
		return <div class="center">
			<div class="horizontal flexBlock">&nbsp;
				<div class="smallText">{input.leftSideLabel.get()}</div>
				<div class="fillFlexSpace"></div>
				<div class="smallText">{input.rightSideLabel.get()}</div>
			</div>
			<div class="center">
				<input type="range" min="0" max={input.maxValue.get() || 100}/>
			</div>
		</div>
	}
}

class VideoComponent extends TextComponent {
	public viewContent(input: Input): Vnode<any, any> {
		return <iframe src={input.url.get()}></iframe>
	}
}