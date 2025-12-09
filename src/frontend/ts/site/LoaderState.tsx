import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Requests, RequestType} from "../singletons/Requests";
import closeX from "../../imgs/icons/closeX.svg?raw";
import {LoadingSpinner} from "../components/LoadingSpinner";

export class LoaderState {
	private isEnabled = false
	private isVisible = false
	private isError = false
	private hasLoader = false
	private hasTryAgainBtn = false
	private stateMsg: string = ""
	
	private animationId: number = 0
	private tryAgainCallback: (() => void) | null = null
	private enableCount: number = 0
	private clickEventRemoveFu: (() => void) | null = null
	
	public getView(): Vnode<any, any> {
		if(!this.isEnabled)
			return <div class="loader hidden"></div>
		
		let className = "loader"
		if(this.isVisible)
			className += " visible"
		if(this.isError)
			className += " isError"
		
		return (
			<div class={ className }>
				{LoadingSpinner(!this.hasLoader)}
				<div class="loaderState line">{ this.stateMsg }</div>
				<a class={ `loaderRetry line clickable ${this.hasTryAgainBtn ? "" : "hidden"}` } onclick={ () => { if(this.tryAgainCallback) {this.tryAgainCallback() } } }></a>
				<a class={ `loaderClose clickable ${this.isError ? "" : "hidden"}` } onclick={ this.closeLoader.bind(this) }>{m.trust(closeX)}</a>
			</div>
		)
	}
	private updateView(): void {
		m.redraw()
	}
	
	public closeLoader(): void {
		this.disable(true)
	}
	
	public showMessage(s: string | null): void {
		window.clearTimeout(this.animationId)
		this.isEnabled = true
		this.hasLoader = false
		
		if(s)
			this.stateMsg = s
		
		this.updateView()
		this.animationId = window.setTimeout(() => {
			this.isVisible = true
			this.updateView()
		}, 10)
	}
	
	private disable(ignoreError: boolean = false): void {
		if(!this.isEnabled || (this.isError && !ignoreError)) {
			return
		}

		window.clearTimeout(this.animationId)

		//we wait for a short period in case another enable() happens right after the current process is done
		this.animationId = window.setTimeout(() => {
			this.isVisible = false
			this.updateView()
			this.animationId = window.setTimeout(() => {
				this.isEnabled = false
				this.isError = false
				this.stateMsg = ""
				this.updateView()
			}, 10)
		}, 10)
	}
	
	public update(s: string): void {
		if(!this.isEnabled || this.isError || this.stateMsg == s)
			return
		
		this.stateMsg = s
		this.updateView()
	}
	
	public async showLoader(promise: Promise<any>, msg: string = Lang.get("state_loading")) : Promise<any> {
		this.hasLoader = true
		this.hasTryAgainBtn = false
		
		if(this.isEnabled) {
			if(this.clickEventRemoveFu) {
				this.clickEventRemoveFu()
				window.clearTimeout(this.animationId)
				this.animationId = window.setTimeout(() => {
					this.showLoader(promise, msg)
				}, 30)
			}
			this.update(msg)
			return promise
		}
		else if(this.isError)
			return promise
		
		window.clearTimeout(this.animationId)
		
		this.isEnabled = true
		this.isVisible = false
		this.stateMsg = msg
		
		this.updateView()
		this.animationId = window.setTimeout(() => {
			this.isVisible = true
			this.updateView()
		}, 10)
		
		
		++this.enableCount
		
		try {
			const response = await promise
			if(--this.enableCount <= 0) {
				this.disable()
			}
			
			return response;
		}
		catch(e) {
			if(--this.enableCount <= 0) {
				this.disable()
			}
			
			console.error(e)
			this.error((e as Error).message || e as string)
			throw e
		}
	}
	
	public info(s: string): void {
		if(this.isError)
			return
		
		this.showMessage(s)
		
		let removeFu = () => {
			this.closeLoader()
			document.removeEventListener("click", removeFu)
			this.clickEventRemoveFu = null
		}
		this.clickEventRemoveFu = removeFu
		
		window.setTimeout(() => { //to make sure an unfinished click does not call this instantly
			document.addEventListener( "click", removeFu)
		}, 10)
	}
	
	public error(s: string, tryAgain?: () => void): void {
		if(this.isError) {
			//if error() was called by a view, it could cause a death loop because showMessage() calls m.redraw()
			if(s != this.stateMsg)
				this.stateMsg = s //we will not call redraw because that would cause a death loop when two different errors are caused by views
			return
		}
		this.isError = true
		if(tryAgain) {
			this.tryAgainCallback = tryAgain;
			this.hasTryAgainBtn = true
		}
		this.showMessage(s)
	}
	
	public loadRaw(url: string, type: keyof RequestType = "get", requestData: string | FormData = ""): Promise<string> {
		return this.showLoader(Requests.loadRaw(url, type, requestData));
	}
	public loadJson(url: string, type: keyof RequestType = "get", requestData: string | FormData = ""): Promise<any> {
		return this.showLoader(Requests.loadJson(url, type, requestData));
	}
	
	public loadWithSSE(url: string, progressState: (percent: number) => string): Promise<any> {
		return this.showLoader(Requests.loadWithSSE(url, percent => this.update(progressState(percent))));
	}
}