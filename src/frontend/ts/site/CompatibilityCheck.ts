export class CompatibilityCheck {
	public toggleUrl(): void {
		const match = window.location.href.match(/([^?]+\/)(index\.php|index_nojs\.php|)(\?.*|)/)
		if(match == null) {
			console.error("Url has unexpected format!")
			return
		}
		const base = match[1]
		const file = match[2]
		const query = match[3]
		window.location.href = base + (file === "index_nojs.php" ? "index.php" : "index_nojs.php") + query
	}
	
	public isCompatible(): boolean {
		return !!(window.Promise && window.Promise.all);
	}
}