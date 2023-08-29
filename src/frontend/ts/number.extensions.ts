interface Number {
	coerceAtLeast: (minimal: number) => number
}

/**
 * Do not ever use this. This only exists to make it easier to copy from kotlin code
 * @param minimal
 */
Number.prototype.coerceAtLeast = function(minimal: number) {
	return Math.max(this as number, minimal)
}