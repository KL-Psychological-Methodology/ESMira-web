class Expr {}
class Stmt {}

class ArraySetStmt extends Stmt {
    constructor(public bracket: MerlinToken, public arr: Expr, public index: Expr, public value: Expr) { super() }
}

class AssignStmt extends Stmt {
    constructor(public name: MerlinToken, public value: Expr) { super() }
}

class BlockStmt extends Stmt {
    constructor(public statements: Array<Stmt>) { super() }
}

class ExpressionStmt extends Stmt {
    constructor(public expression: Expr) { super() }
}

class ForStmt extends Stmt {
    constructor(public varName: MerlinToken, public iterable: Expr, public body: Stmt) { super() }
}

class FunctionStmt extends Stmt {
    constructor(public name: MerlinToken, public params: Array<MerlinToken>, public body: Array<Stmt>) { super() }
}

class Branch {
    constructor(public condition: Expr, public thenBranch: Stmt) {}
}

class IfStmt extends Stmt {
    constructor(public branches: Array<Branch>, public elseBranch?: Stmt) { super() }
}

class InitStmt extends Stmt {
    constructor(public statements: Array<Stmt>) { super() }
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