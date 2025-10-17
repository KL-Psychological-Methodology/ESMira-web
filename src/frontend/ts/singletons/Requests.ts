import {Lang} from "./Lang";


export interface RequestType {
	get: string
	post: string
	file: string
}

/**
 * Bundles all backend communication
 */
export const Requests = {
	/**
	 * Loads raw textual data from a specified URL using an XMLHttpRequest.
	 *
	 * @param url - The URL from which to fetch the data.
	 * @param type - The HTTP request type to use (e.g., "get", "post").
	 * @param requestData - The data to send with the request (if using "post").
	 * @returns A promise that resolves to the raw response text received from the server.
	 * @throws error_connection_failed If the creation of the request fails.
	 */
	async loadRaw(url: string, type: keyof RequestType = "get", requestData: string | FormData = ""): Promise<string> {
		const request = await new Promise<XMLHttpRequest>((resolve) => {
			const r = new XMLHttpRequest()
			if(!r) {
				throw new Error(Lang.get("error_create_request_failed"))
			}
			
			r.open(type == "file" ? "post" : type, url)
			if(type == "post") {
				r.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			}
			
			r.onreadystatechange = function() {
				if(r.readyState !== 4)
					return false
				resolve(r)
			}
			r.send(requestData)
		})
		if(request.status !== 200) {
			console.error(request)
			throw new Error(Lang.get("error_connection_failed"))
		}
		return request.responseText
	},
	
	/**
	 * Loads and parses a JSON response from a specified URL.
	 *
	 * @param url - The URL from which to fetch the JSON data.
	 * @param type - The HTTP request type to use (e.g., "get", "post").
	 * @param requestData - The data to send with the request (if using "post").
	 * @returns A promise that resolves to the dataset from the JSON response.
	 * @throws error_connection_failed If the creation of the request fails.
	 * @throws error_from_server if the JSON contains error = true.
	 */
	async loadJson(url: string, type: keyof RequestType = "get", requestData: string | FormData = ""): Promise<any> {
		const response = await this.loadRaw(url, type, requestData)
		const obj = JSON.parse(response)
		if(obj.success) {
			return obj.dataset
		}
		else {
			const errorMessage = obj.error || response
			throw Lang.has(errorMessage) ? Lang.getDynamic(errorMessage) : Lang.get("error_from_server", errorMessage)
		}
	},
	
	/**
	 * Loads a JavaScript module from the specified URL and returns the imported module from `import()`.
	 * The external code is expected to have `export` definitions
	 *
	 * @param url - The URL of the module to be loaded.
	 * @param type - The HTTP request type to use (e.g., "get", "post").
	 * @param requestData - The data to send with the request (if using "post").
	 * @returns A promise that resolves with the imported module.
	 * @throws error_connection_failed If the creation of the request fails.
	 */
	async loadCodeModule(url: string, type: keyof RequestType = "get", requestData: string = ""): Promise<any> {
		const source = await this.loadRaw(url, type, requestData)
		const objectURL = URL.createObjectURL(new Blob([source], {type: "application/javascript"}))
		return await import(/*webpackIgnore: true*/ `${objectURL}`)
	}
}