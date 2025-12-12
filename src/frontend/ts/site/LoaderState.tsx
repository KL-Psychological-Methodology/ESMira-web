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
		if(!this.isEnabled) {
			return <div class="loader hidden"></div>
		}
		
		let className = "loader"
		if(this.isVisible) {
			className += " visible"
		}
		if(this.isError) {
			className += " isError"
		}
		
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
	
	private displayMessage(msg: string): void {
		window.clearTimeout(this.animationId)
		this.isEnabled = true
		this.hasLoader = false
		
		this.stateMsg = msg
		
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
	
	/**
	 * Updates the displayed message if the loader is currently visible and no error is active.
	 *
	 * @param msg - The new message.
	 */
	public update(msg: string): void {
		if(!this.isEnabled || this.isError || this.stateMsg == msg) {
			return
		}
		
		this.stateMsg = msg
		this.updateView()
	}
	
	/**
	 * Shows a loader animation.
	 * Also keeps track of successive {@link showLoader()} calls and only hides the view when all loaders are finished
	 * @param promise - the promise the loader should wait for
	 * @param msg - The message to show while the loader is active. Defaults to Lang.get("state_loading")
	 */
	public async showLoader(promise: Promise<any>, msg: string = Lang.get("state_loading")) : Promise<any> {
		if(this.isError) {
			return promise
		}
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
		
		this.displayMessage(msg)
		
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
	
	/**
	 * Shows a simple message and adds a click event to the page to automatically remove the message.
	 * @param msg
	 */
	public info(msg: string): void {
		if(this.isError) {
			return
		}
		
		this.hasLoader = false
		this.displayMessage(msg)
		
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
	
	/**
	 * Shows an error message.
	 * As long as the error is not clicked via close button, it will persist (and cancel any other calls to {@link info()} {@link showLoader()} or {@link update()})
	 * If the error is caused by a view, the error() call will be ignored because it would cause a death loop.
	 * @param msg - the error message to be shown.
	 * @param tryAgain - if defined, displays a try again button is shown which calls the callback function when clicked.
	 */
	public error(msg: string, tryAgain?: () => void): void {
		if(this.isError) {
			//if error() was called by a view, it could cause a death loop because showMessage() calls m.redraw()
			if(msg != this.stateMsg) {
				this.stateMsg = msg //we will not call redraw because that would cause a death loop when two different errors are caused by views
			}
			return
		}
		this.isError = true
		if(tryAgain) {
			this.tryAgainCallback = tryAgain;
			this.hasTryAgainBtn = true
		}
		this.hasLoader = false
		this.displayMessage(msg)
	}
	
	/**
	 * Calls {@link showLoader()} with the promise from {@link Requests.loadRaw()} to load the given url.
	 * @param url - the url to load
	 * @param type - the request type. Defaults to "get"
	 * @param requestData - the request data to send. Only needed when {@link type} is "post" or "file".
	 */
	public loadRaw(url: string, type: keyof RequestType = "get", requestData: string | FormData = ""): Promise<string> {
		return this.showLoader(Requests.loadRaw(url, type, requestData));
	}
	
	/**
	 * Calls {@link showLoader()} with the promise from {@link Requests.loadJson()} to load and validate the JSON from the given url.
	 * @param url - the url to load the JSON from.
	 * @param type - the request type. Defaults to "get"
	 * @param requestData - the request data to send. Only needed when {@link type} is "post" or "file".
	 */
	public loadJson(url: string, type: keyof RequestType = "get", requestData: string | FormData = ""): Promise<any> {
		return this.showLoader(Requests.loadJson(url, type, requestData));
	}
	
	/**
	 * Calls {@link showLoader()} with the promise from {@link Requests.loadWithSSE()}.
	 * @param url - the url to load
	 * @param progressState - a callback function that returns a string to be displayed in the loader.
	 */
	public loadWithSSE(url: string, progressState: (percent: number) => string): Promise<any> {
		return this.showLoader(Requests.loadWithSSE(url, percent => this.update(progressState(percent))));
	}
}