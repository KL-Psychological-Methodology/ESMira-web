import {MerlinToken, MerlinTokenType} from "./merlinTokens"

/*
A Scanner/Tokenizer for the Merlin scripting language.
It can take in the source code as a string in its constructor turn it into an array of Tokens.
Also keeps track of scanning errors. This is modeled directly after the Kotlin Scanner implementation in ESMira-apps.
*/

export class MerlinScanner {
	constructor(public source: string) {}
	
	private start = 0
	private current = 0
	private line = 1
	private tokens = Array<MerlinToken>()
	
	public errors = Array<ScanningError>()
	
	
	public scanTokens(): Array<MerlinToken> {
		while(!this.isAtEnd()) {
			this.start = this.current
			this.scanToken()
		}
		
		this.tokens.push(new MerlinToken(MerlinTokenType.EOF, "", this.line))
		return this.tokens
	}
	
	public hadErrors(): boolean {
		return this.errors.length > 0
	}
	
	private scanToken(): void {
		const c = this.advance()
		switch(c) {
			case "(":
				this.addToken(MerlinTokenType.LEFT_PAREN);
				break;
			case ")":
				this.addToken(MerlinTokenType.RIGHT_PAREN);
				break;
			case "{":
				this.addToken(MerlinTokenType.LEFT_BRACE);
				break;
			case "}":
				this.addToken(MerlinTokenType.RIGHT_BRACE);
				break;
			case "[":
				this.addToken(MerlinTokenType.LEFT_BRACKET);
				break;
			case "]":
				this.addToken(MerlinTokenType.RIGHT_BRACKET);
				break;
			case ",":
				this.addToken(MerlinTokenType.COMMA);
				break;
			case ":":
				this.addToken(MerlinTokenType.COLON);
				break;
			case ";":
				this.addToken(MerlinTokenType.SEMICOLON);
				break;
			case "-":
				this.addToken(MerlinTokenType.MINUS);
				break;
			case "+":
				this.addToken(MerlinTokenType.PLUS);
				break;
			case "*":
				this.addToken(MerlinTokenType.STAR);
				break;
			case ".":
				if(this.match(".")) {
					this.addToken(MerlinTokenType.DOT_DOT)
				} else {
					this.addToken(MerlinTokenType.DOT)
				}
				break
			case "!":
				if(this.match("=")) {
					this.addToken(MerlinTokenType.EXCLAMATION_EQUAL)
				} else {
					this.addToken(MerlinTokenType.EXCLAMATION)
				}
				break
			case "=":
				if(this.match("=")) {
					this.addToken(MerlinTokenType.EQUAL_EQUAL)
				} else {
					this.addToken(MerlinTokenType.EQUAL)
				}
				break
			case "<":
				if(this.match("=")) {
					this.addToken(MerlinTokenType.LESS_EQUAL)
				} else {
					this.addToken(MerlinTokenType.LESS)
				}
				break
			case ">":
				if(this.match("=")) {
					this.addToken(MerlinTokenType.GREATER_EQUAL)
				} else if(this.match(">")) {
					this.addToken(MerlinTokenType.GREATER_GREATER)
				} else {
					this.addToken(MerlinTokenType.GREATER)
				}
				break
			case "/":
				if(this.match("/")) {
					while(this.peek() != "\n" && !this.isAtEnd())
						this.advance()
				} else {
					this.addToken(MerlinTokenType.SLASH)
				}
				break
			case " ":
			case "\r":
			case "\t":
				break
			case "\n":
				this.line++;
				break;
			case "\"":
				this.string();
				break;
			default:
				if(this.isDigit(c)) {
					this.number()
				} else if(this.isLetter(c)) {
					this.identifier()
				} else {
					this.errors.push(new ScanningError(this.line, `Unexpected character ${c}.`))
				}
		}
	}
	
	private isAtEnd(): boolean {
		return this.current >= this.source.length
	}
	
	private advance(): string {
		return this.source.charAt(this.current++)
	}
	
	private addToken(type: MerlinTokenType) {
		const text = this.source.substring(this.start, this.current)
		this.tokens.push(new MerlinToken(type, text, this.line))
	}
	
	private match(expected: string): boolean {
		if(this.isAtEnd()) return false
		if(this.source.charAt(this.current) != expected) return false
		this.current++
		return true
	}
	
	private peek(): string {
		if(this.isAtEnd()) return "\0"
		return this.source.charAt(this.current)
	}
	
	private peekNext(): string {
		if(this.current + 1 >= this.source.length) return "\0"
		return this.source.charAt(this.current + 1)
	}
	
	private string() {
		const startLine = this.line
		while(this.peek() != "\"" && !this.isAtEnd()) {
			if(this.peek() == "\n") this.line++
			this.advance()
		}
		
		if(this.isAtEnd()) {
			this.errors.push(new ScanningError(startLine, `Unterminated string, starting at line ${startLine}`))
		}
		
		this.advance()
		this.addToken(MerlinTokenType.STRING)
	}
	
	private number(): void {
		while(this.isDigit(this.peek()))
			this.advance()
		
		if(this.peek() == "." && this.isDigit(this.peekNext())) {
			this.advance()
			while(this.isDigit(this.peek()))
				this.advance()
		}
		
		this.addToken(MerlinTokenType.NUMBER)
	}
	
	private identifier(): void {
		while(this.isLetter(this.peek()) || this.isDigit(this.peek()))
			this.advance()
		
		const text = this.source.substring(this.start, this.current)
		const type = keywords[text] || MerlinTokenType.IDENTIFIER
		this.addToken(type)
	}
	
	private isLetter(c: string): boolean {
		return /[a-z]|[A-Z]|_/.test(c)
	}
	
	private isDigit(c: string): boolean {
		return /[0-9]/.test(c)
	}
	
}

export class ScanningError {
	constructor(public line: number, public message: string) {}
}

const keywords: { [key: string]: MerlinTokenType } = {
	"and": MerlinTokenType.AND,
	"else": MerlinTokenType.ELSE,
	"elif": MerlinTokenType.ELIF,
	"false": MerlinTokenType.FALSE,
	"function": MerlinTokenType.FUNCTION,
	"for": MerlinTokenType.FOR,
	"if": MerlinTokenType.IF,
	"in": MerlinTokenType.IN,
	"init": MerlinTokenType.INIT,
	"none": MerlinTokenType.NONE,
	"object": MerlinTokenType.OBJECT,
	"or": MerlinTokenType.OR,
	"return": MerlinTokenType.RETURN,
	"true": MerlinTokenType.TRUE,
	"while": MerlinTokenType.WHILE
}