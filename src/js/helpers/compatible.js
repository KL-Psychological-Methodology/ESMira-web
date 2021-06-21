export function toggleUrl() {
	let [all, base, file, query] = window.location.href.match(/([^?]+\/)(index\.php|index_nojs\.php|)(\?.*|)/);
	if(all)
		window.location.href = base + (file === "index_nojs.php" ? "index.php" : "index_nojs.php") + query;
	else
		console.error("Url has unexpected format!");
}

export function isCompatible() {
	return !!(window.Promise && window.Promise.all);
}