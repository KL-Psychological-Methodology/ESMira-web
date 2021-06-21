export function get_base_url(protocol) {
	return (protocol || location.protocol) + '//' + location.host + location.pathname.replace(/[^/]+$/, "");
}

export function create_appUrl(key, id, long, protocol) {
	return long && key
		? get_base_url(protocol)+"app-"+id+"-"+key
		: get_base_url(protocol)+"app-"+(key || id);
}
export function create_studyUrl(key, id, long, protocol) {
	return long && key
		? get_base_url(protocol)+id+"-"+key
		: get_base_url(protocol)+(key || id);
}
export function create_questionnaireUrl(key, qId, protocol) {
	return get_base_url(protocol)+"survey-"+qId+(key ? "-"+key : "");
}

export function check_accessKeyFormat(s) {
	return s.match(/^([a-zA-Z][a-zA-Z0-9]+)$/);
}