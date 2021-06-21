import {Lang} from "./lang";

function create_request() {
	if(window.XMLHttpRequest) {
		return new XMLHttpRequest(); // Mozilla, Safari, Opera
	}
	else if(window.ActiveXObject) {
		try {
			return new ActiveXObject('Msxml2.XMLHTTP'); // IE 5
		}
		catch(e) {
			try {
				return new ActiveXObject('Microsoft.XMLHTTP'); // IE 6
			}
			catch(e) {
				return false;
			}
		}
	}
	else
		return false;
}

export const Requests = {
	load: function(url, notJson, type, data) {
		return new Promise(function(resolve) {
			let r = create_request();
			if(!r)
				throw new Error(Lang.get("error_create_request_failed"));
			else {
				r.open(type || "get", url);
				if(type === "post")
					r.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				
				r.onreadystatechange = function() {
					let r = this;
					
					if(r.readyState !== 4)
						return false;
					resolve(r);
				};
				r.send(data);
			}
		}).then(function(r) {
			if(r.status !== 200) {
				console.error(r);
				throw new Error(Lang.get("error_connection_failed"));
			}
			
			if(!notJson) {
				let obj;
				try {
					obj = JSON.parse(r.responseText);
				}
				catch(e) {
					console.error(r.responseText);
					throw e;
				}
				
				if(!obj.success) {
					console.error(r);
					throw new Error(Lang.get("error_from_server", obj.error));
				}
				else
					return obj.dataset;
			}
			else
				return r.responseText;
		})
	}
};