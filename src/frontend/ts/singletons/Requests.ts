import {Lang} from "./Lang";


export interface RequestType {
	get: string
	post: string
}

export const Requests = {
	loadRaw(url: string, type: keyof RequestType = "get", requestData: string | FormData = ""): Promise<string> {
		return new Promise<XMLHttpRequest>((resolve) => {
			const r = new XMLHttpRequest()
			if(!r)
				throw new Error(Lang.get("error_create_request_failed"))
			
			r.open(type, url)
			if(type == "post") {
				if(!(requestData instanceof FormData))
					r.setRequestHeader("Content-Type", "application/x-www-form-urlencoded")
			}
			
			r.onreadystatechange = function() {
				if(r.readyState !== 4)
					return false
				resolve(r)
			}
			r.send(requestData)
		}).then((r) => {
			if(r.status !== 200) {
				console.error(r)
				throw new Error(Lang.get("error_connection_failed"))
			}
			return r.responseText
		})
	},
	loadJson(url: string, type: keyof RequestType = "get", requestData: string | FormData = ""): Promise<any> {
		return this.loadRaw(url, type, requestData)
			.then((response) => {
				const obj = JSON.parse(response)
				
				if(obj.success)
					return obj.dataset
				else
					throw Lang.get("error_from_server", obj.error || response)
			})
	}
}