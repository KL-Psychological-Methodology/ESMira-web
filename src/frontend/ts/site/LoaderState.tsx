import m, {Vnode} from "mithril";
import {Lang} from "../singletons/Lang";
import {Requests, RequestType} from "../singletons/Requests";
import closeX from "../../imgs/icons/closeX.svg?raw";
import {LoadingSpinner} from "../widgets/LoadingSpinner";

export class LoaderState {
	private isEnabled = false
	private isVisible = false
	private isError = false
	private hasAnimation = false
	private hasTryAgainBtn = false
	private stateMsg: string = ""
	
	private animationId: number = 0
	private tryAgainCallback: (() => void) | null = null
	private enableCount: number = 0
	
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
				{LoadingSpinner(!this.hasAnimation)}
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
		this.hasAnimation = false
		
		if(s)
			this.stateMsg = s
		
		this.updateView()
		this.animationId = window.setTimeout(() => {
			this.isVisible = true
			this.updateView()
		}, 10)
	}
	
	private disable(ignoreError: boolean = false): void {
		if(!this.isEnabled || (this.isError && !ignoreError))
			return

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
			}, 200)
		}, 10)
	}
	
	public update(s: string): void {
		if(!this.isEnabled || this.isError || this.stateMsg == s)
			return
		
		this.stateMsg = s
		this.updateView()
	}
	
	public showLoader(promise: Promise<any>, msg: string = Lang.get("state_loading")) : Promise<any> {
		if(this.isEnabled) {
			this.update(msg)
			return promise
		}
		else if(this.isError)
			return promise
		
		window.clearTimeout(this.animationId)
		
		this.isEnabled = true
		this.isVisible = false
		this.stateMsg = msg
		
		this.hasAnimation = true
		this.hasTryAgainBtn = false
		
		this.updateView()
		this.animationId = window.setTimeout(() => {
			this.isVisible = true
			this.updateView()
		}, 500)
		
		
		++this.enableCount
		
		return promise
			.then(response => {
				if(--this.enableCount <= 0)
					this.disable()
					
				return response;
			})
			.catch(e => {
				if(--this.enableCount <= 0)
					this.disable()
				
				console.error(e)
				this.error(e.message || e)
				throw e
			});
	}
	
	public info(s: string): void {
		if(this.isError)
			return
		
		this.showMessage(s)
		
		let removeFu = () => {
			this.closeLoader()
			document.removeEventListener("click", removeFu)
		};
		
		window.setTimeout(() => { //to make sure an unfinished click does not call this instantly
			document.addEventListener( "click", removeFu)
		}, 200)
	}
	
	public error(s: string, tryAgain?: () => void): void {
		if(s == this.stateMsg) //if error() was called by a view, it could cause a death loop because showMessage() calls m.redraw()
			return
		this.isError = true
		if(tryAgain) {
			this.tryAgainCallback = tryAgain;
			this.hasTryAgainBtn = true
		}
		this.showMessage(s)
	}
	
	public loadRaw(url: string, type: keyof RequestType = "get", requestData: string = ""): Promise<string> {
		return this.showLoader(Requests.loadRaw(url, type, requestData));
	}
	public loadJson(url: string, type: keyof RequestType = "get", requestData: string = ""): Promise<any> {
		return this.showLoader(Requests.loadJson(url, type, requestData));
	}
}