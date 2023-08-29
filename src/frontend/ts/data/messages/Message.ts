export interface Message {
	from: string
	content: string
	sent: number
	delivered: number
	read: number
	pending: boolean
	unread: boolean
	archived: boolean
}