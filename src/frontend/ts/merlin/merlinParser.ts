import { MerlinToken, MerlinTokenType } from "./merlinTokens"

export class MerlinParser {
    constructor(private tokens: Array<MerlinToken>) {}

    public errors = Array<ParseError>()
    private current = 0


    public parse(): Array<Stmt | null> {
        const statements = Array<Stmt | null>()
        while (!this.isAtEnd())
            statements.push(this.declaration())

        return statements
    }

    public hadErrors(): boolean {
        return this.errors.length > 0
    }
    
    private declaration(): Stmt | null {
        try {
            if (this.match([MerlinTokenType.FUNCTION]))
                return this.function()
            return this.statement()
        } catch (e: unknown) {
            if (e instanceof ParseError) {    
                this.errors.push(e)
                this.synchronize()
            }
            return null
        }
    }

    private function(): FunctionStmt {
        const name = this.consume(MerlinTokenType.IDENTIFIER, "Expecting a function name.")
        this.consume(MerlinTokenType.LEFT_PAREN, "Expect '(' after function name.")
        const parameters = Array<MerlinToken>()
        if (!this.check(MerlinTokenType.RIGHT_PAREN)) {
            do {
                parameters.push(this.consume(MerlinTokenType.IDENTIFIER, "Expect parameter name."))
            } while (this.match([MerlinTokenType.COMMA]))
        }
        this.consume(MerlinTokenType.RIGHT_PAREN, "Expect ')' after function parameters.")
        this.consume(MerlinTokenType.LEFT_BRACE, "Expect '{' before function body.")
        const body = this.block()
        return new FunctionStmt(name, parameters, body)
    }

    private block(): Array<Stmt | null> {
        const statements = Array<Stmt | null>()

        while (!this.check(MerlinTokenType.RIGHT_BRACE) && !this.isAtEnd())
            statements.push(this.declaration())
        this.consume(MerlinTokenType.RIGHT_BRACE, "Expect '}' after block.")
        return statements
    }

    private statement(): Stmt {
        if (this.match([MerlinTokenType.FOR])) return this.forStatement()
        if (this.match([MerlinTokenType.IF])) return this.ifStatement()
        if (this.match([MerlinTokenType.RETURN])) return this.returnStatement()
        if (this.match([MerlinTokenType.WHILE])) return this.whileStatement()
        if (this.match([MerlinTokenType.INIT])) return this.initStatement()
        if (this.match([MerlinTokenType.LEFT_BRACE])) return new BlockStmt(this.block())

        return this.assignmentStatement()
    }

    private forStatement(): Stmt {
        this.consume(MerlinTokenType.LEFT_PAREN, "Expect '(' after 'for'.")

        const variable = this.consume(MerlinTokenType.IDENTIFIER, "Expect variable name for 'for' loop.")
        this.consume(MerlinTokenType.IN, "Expect 'in' after variable name in 'for' loop.")
        const iterable = this.expression()
        this.consume(MerlinTokenType.RIGHT_PAREN, "Expect ')' after 'for' range.")
        const body = this.statement()

        return new ForStmt(variable, iterable, body)
    }

    private ifStatement(): Stmt {
        const branches = Array<Branch>()
        do {
            this.consume(MerlinTokenType.LEFT_PAREN, "Expect '(' after 'if'.")
            const condition = this.expression()
            this.consume(MerlinTokenType.RIGHT_PAREN, "Expect ')' after 'if' condition.")
            const thenBranch = this.statement()
            branches.push(new Branch(condition, thenBranch))
        } while (this.match([MerlinTokenType.ELIF]))
        let elseBranch = undefined
        if (this.match([MerlinTokenType.ELSE]))
            elseBranch = this.statement()
        return new IfStmt(branches, elseBranch)
    }

    private returnStatement(): Stmt {
        const keyword = this.previous()
        let value = undefined
        if (!this.check(MerlinTokenType.SEMICOLON))
            value = this.expression()
        this.consume(MerlinTokenType.SEMICOLON, "Expect ';' after return statement.")
        return new ReturnStmt(keyword, value)
    }

    private whileStatement(): Stmt {
        this.consume(MerlinTokenType.LEFT_PAREN, "Expect '(' after 'while'.")
        const condition = this.expression()
        this.consume(MerlinTokenType.RIGHT_PAREN, "Expect ')' after 'while' condition.")
        const body = this.statement()

        return new WhileStmt(condition, body)
    }

    private assignmentStatement(): Stmt {
        const expr = this.expression()

        if (this.match([MerlinTokenType.EQUAL])) {
            const equals = this.previous()
            const value = this.expression()

            if (expr instanceof VariableExpr) {
                this.consume(MerlinTokenType.SEMICOLON, "Expect ';' after assign statement.")
                return new AssignStmt(expr.name, value)
            } else if (expr instanceof ObjectGetExpr) {
                this.consume(MerlinTokenType.SEMICOLON, "Expect ';' after assgin statement.")
                return new ObjectSetStmt(expr.obj, expr.name, value)
            } else if (expr instanceof ArrayGetExpr) {
                this.consume(MerlinTokenType.SEMICOLON, "Expect ';' after assign statement.")
                return new ArraySetStmt(expr.bracket, expr.arr, expr.index, value)
            } else {
                throw new ParseError(equals, "Invalid assignment target.")
            }
        }

        this.consume(MerlinTokenType.SEMICOLON, "Expect ';' after expression statement.")
        return new ExpressionStmt(expr)
    }

    private initStatement(): Stmt {
        this.consume(MerlinTokenType.LEFT_BRACE, "Expect '{' after 'init'.")
        const statements = this.block()
        return new InitStmt(statements)
    }

    private expression(): Expr {
        return this.or()
    }

    private or(): Expr {
        let expr = this.and()

        while (this.match([MerlinTokenType.OR])) {
            const operator = this.previous()
            const right = this.and()
            expr = new LogicalExpr(expr, operator, right)
        }

        return expr
    }

    private and(): Expr {
        let expr = this.equality()

        while (this.match([MerlinTokenType.AND])) {
            const operator = this.previous()
            const right = this.equality()
            expr = new LogicalExpr(expr, operator, right)
        }

        return expr
    }

    private equality(): Expr {
        let expr = this.comparison()

        while (this.match([MerlinTokenType.EXCLAMATION_EQUAL, MerlinTokenType.EQUAL_EQUAL])) {
            const operator = this.previous()
            const right = this.comparison()
            expr = new BinaryExpr(expr, operator, right)
        }

        return expr
    }

    private comparison(): Expr {
        let expr = this.term()

        while (this.match([MerlinTokenType.GREATER, MerlinTokenType.GREATER_EQUAL, MerlinTokenType.LESS, MerlinTokenType.LESS_EQUAL])) {
            const operator = this.previous()
            const right = this.term()
            expr = new BinaryExpr(expr, operator, right)
        }

        return expr
    }

    private term(): Expr {
        let expr = this.factor()

        while (this.match([MerlinTokenType.MINUS, MerlinTokenType.PLUS, MerlinTokenType.DOT_DOT])) {
            const operator = this.previous()
            const right = this.factor()
            expr = new BinaryExpr(expr, operator, right)
        }

        return expr
    }

    private factor(): Expr {
        let expr = this.sequence()

        while (this.match([MerlinTokenType.SLASH, MerlinTokenType.STAR])) {
            const operator = this.previous()
            const right = this.sequence()
        }

        return expr
    }

    private sequence(): Expr {
        let expr = this.unary()

        if (this.match([MerlinTokenType.COLON])) {
            const colon = this.previous()
            const sequenceEnd = this.unary()
            let sequenceStep = undefined
            if (this.match([MerlinTokenType.COLON]))
                sequenceStep = this.unary()
            
            return new SequenceExpr(expr, colon, sequenceEnd, sequenceStep)
        }

        return expr
    }

    private unary(): Expr {
        if (this.match([MerlinTokenType.EXCLAMATION, MerlinTokenType.MINUS])) {
            const operator = this.previous()
            const right = this.unary()
            return new UnaryExpr(operator, right)
        }

        return this.callChain()
    }

    private callChain(): Expr {
        let expr = this.call()

        while (this.match([MerlinTokenType.GREATER_GREATER])) {
            const nextFunc = this.call()
            if (!(nextFunc instanceof CallExpr)) {
                throw new ParseError(this.previous(), "Expecting function call after '>>'.")
            }
            expr = new CallExpr(nextFunc.callee, nextFunc.paren, [expr, ...nextFunc.args])
        }

        return expr
    }

    private call(): Expr {
        let expr = this.value()

        while (true) {
            if (this.match([MerlinTokenType.LEFT_PAREN])) {
                expr = this.finishCall(expr)
            } else if (this.match([MerlinTokenType.DOT])) {
                const name = this.consume(MerlinTokenType.IDENTIFIER, "Exping a property name after '.'.")
                expr = new ObjectGetExpr(expr, name)
            } else if (this.match([MerlinTokenType.LEFT_BRACKET])) {
                const bracket = this.previous()
                const index = this.expression()
                this.consume(MerlinTokenType.RIGHT_BRACKET, "Expecting ']' after array index.")
                expr = new ArrayGetExpr(bracket, expr, index)
            } else {
                break
            }
        }

        return expr
    }

    private finishCall(callee: Expr): Expr {
        if (!(callee instanceof VariableExpr)) {
            throw new ParseError(this.previous(), "Invalid identifier for function call.")
        }
        const args = Array<Expr>()
        if (!this.check(MerlinTokenType.RIGHT_PAREN)) {
            do {
                args.push(this.expression())
            } while (this.match([MerlinTokenType.COMMA]))
        }
        const paren = this.consume(MerlinTokenType.RIGHT_PAREN, "Expect ')' after function call arguments.")

        return new CallExpr(callee.name, paren, args)
    }

    private value(): Expr {
        if (this.match([MerlinTokenType.LEFT_BRACKET])) {
            const elements = Array<Expr>()
            if (!this.check(MerlinTokenType.RIGHT_BRACKET)) {
                do {
                    elements.push(this.expression())
                } while (this.match([MerlinTokenType.COMMA]))
            }
            const bracket = this.consume(MerlinTokenType.RIGHT_BRACKET, "Expect ']' after array expression.")

            return new ArrayExpr(bracket, elements)
        }

        return this.primary()
    }

    private primary(): Expr {
        if (this.match([
            MerlinTokenType.FALSE,
            MerlinTokenType.TRUE,
            MerlinTokenType.NUMBER,
            MerlinTokenType.STRING,
            MerlinTokenType.NONE
        ])) return new LiteralExpr(this.previous())
        if (this.match([MerlinTokenType.OBJECT])) return new ObjectExpr(this.previous())
        if (this.match([MerlinTokenType.IDENTIFIER])) return new VariableExpr(this.previous())
        
        if (this.match([MerlinTokenType.LEFT_PAREN])) {
            const expr = this.expression()
            this.consume(MerlinTokenType.RIGHT_PAREN, "Unmatched '('. Expecting ')' after expression.")
            return new GroupingExpr(expr)
        }

        throw new ParseError(this.peek(), "Expecting expression.")
    }

    private advance(): MerlinToken {
        if (!this.isAtEnd())
            this.current++
        return this.previous()
    }

    private isAtEnd(): boolean {
        return this.peek().type == MerlinTokenType.EOF
    }

    private peek(): MerlinToken {
        return this.tokens[this.current]
    }

    private previous(): MerlinToken {
        return this.tokens[this.current -1]
    }

    private match(types: Array<MerlinTokenType>): boolean {
        return types.some(element => {
            if (this.check(element)) {
                this.advance()
                return true
            }
            return false
        })
    }

    private check(type: MerlinTokenType): boolean {
        if (this.isAtEnd()) return false
        return this.peek().type == type
    }

    private consume(type: MerlinTokenType, message: string): MerlinToken {
        if (this.check(type)) return this.advance()
        throw new ParseError(this.peek(), message)
    }

    private synchronize(): void {
        this.advance()

        while (!this.isAtEnd()) {
            if (this.previous().type == MerlinTokenType.SEMICOLON) return

            switch (this.peek().type) {
                case MerlinTokenType.FUNCTION:
                case MerlinTokenType.FOR:
                case MerlinTokenType.IF:
                case MerlinTokenType.WHILE:
                case MerlinTokenType.RETURN:
                    return
            }
            this.advance()
        }
    }


}

export class ParseError {
    constructor(public token: MerlinToken, public message: string) {}
}

// Instructions

class Expr {}
class Stmt {}

class ArraySetStmt extends Stmt {
    constructor(public bracket: MerlinToken, public arr: Expr, public index: Expr, public value: Expr) { super() }
}

class AssignStmt extends Stmt {
    constructor(public name: MerlinToken, public value: Expr) { super() }
}

class BlockStmt extends Stmt {
    constructor(public statements: Array<Stmt | null>) { super() }
}

class ExpressionStmt extends Stmt {
    constructor(public expression: Expr) { super() }
}

class ForStmt extends Stmt {
    constructor(public varName: MerlinToken, public iterable: Expr, public body: Stmt) { super() }
}

class FunctionStmt extends Stmt {
    constructor(public name: MerlinToken, public params: Array<MerlinToken>, public body: Array<Stmt | null>) { super() }
}

class Branch {
    constructor(public condition: Expr, public thenBranch: Stmt) {}
}

class IfStmt extends Stmt {
    constructor(public branches: Array<Branch>, public elseBranch?: Stmt) { super() }
}

class InitStmt extends Stmt {
    constructor(public statements: Array<Stmt | null>) { super() }
}

class ObjectSetStmt extends Stmt {
    constructor(public object: Expr, public name: MerlinToken, public value: Expr) { super() }
}

class ReturnStmt extends Stmt {
    constructor(public keyword: MerlinToken, public value?: Expr) { super() }
}

class WhileStmt extends Stmt {
    constructor(public condition: Expr, public body: Stmt) { super() }
}

class ArrayExpr extends Expr {
    constructor(public bracket: MerlinToken, public elements: Array<Expr>) { super() }
}

class ArrayGetExpr extends Expr {
    constructor(public bracket: MerlinToken, public arr: Expr, public index: Expr) { super() }
}

class BinaryExpr extends Expr {
    constructor(public left: Expr, public operator: MerlinToken, public right: Expr) { super() }
}

class CallExpr extends Expr {
    constructor(public callee: MerlinToken, public paren: MerlinToken, public args: Array<Expr>) { super() }
}

class GroupingExpr extends Expr {
    constructor(public expression: Expr) { super() }
}

class LiteralExpr extends Expr {
    constructor(public value: MerlinToken) { super() }
}

class LogicalExpr extends Expr {
    constructor(public left: Expr, public operator: Expr, public right: Expr) { super() }
}

class ObjectExpr extends Expr {
    constructor(public keyword: MerlinToken) { super() }
}

class ObjectGetExpr extends Expr {
    constructor(public obj: Expr, public name: MerlinToken) { super() }
}

class SequenceExpr extends Expr {
    constructor(public start: Expr, public colon: MerlinToken, public end: Expr, public step?: Expr) { super() }
}

class UnaryExpr extends Expr {
    constructor(public operator: MerlinToken, public right: Expr) { super() }
}

class VariableExpr extends Expr {
    constructor(public name: MerlinToken) { super() }
}