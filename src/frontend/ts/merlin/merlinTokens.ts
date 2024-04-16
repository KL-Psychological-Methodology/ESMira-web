export class MerlinToken {
    type: MerlinTokenType
    lexeme: string
    line: number

    constructor(type: MerlinTokenType, lexeme: string, line: number) {
        this.type = type
        this.lexeme = lexeme
        this.line = line
    }
}

export enum MerlinTokenType {
    // Single character tokens
    LEFT_PAREN,
    RIGHT_PAREN,
    LEFT_BRACE,
    RIGHT_BRACE,
    LEFT_BRACKET,
    RIGHT_BRACKET,
    COMMA,
    COLON,
    SEMICOLON,
    DOT,
    MINUS,
    PLUS,
    SLASH,
    STAR,

    // One and two character tokens
    DOT_DOT,
    EXCLAMATION,
    EXCLAMATION_EQUAL,
    EQUAL,
    EQUAL_EQUAL,
    GREATER,
    GREATER_EQUAL,
    LESS,
    LESS_EQUAL,
    GREATER_GREATER,

    // Literals
    IDENTIFIER,
    STRING,
    NUMBER,

    // Keywords
    AND,
    ELSE,
    ELIF,
    FALSE,
    FUNCTION,
    FOR,
    IF,
    IN,
    INIT,
    NONE,
    OBJECT,
    OR,
    RETURN,
    TRUE,
    WHILE,

    EOF
}