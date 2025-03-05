<?php

declare(strict_types=1);

namespace Inpsyde\WpStubs;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;

class NodeVisitor extends \StubsGenerator\NodeVisitor
{
    private array $constants = [];
    private array $fixtures;

    /**
     * @param array $fixtures
     * @return NodeVisitor
     */
    public static function new(array $fixtures): NodeVisitor
    {
        return new self($fixtures);
    }

    /**
     * @param array $fixtures
     */
    private function __construct(array $fixtures)
    {
        $this->fixtures = $fixtures;
    }

    /**
     * @param Node $node
     * @return int|void|null
     */
    public function enterNode(Node $node)
    {
        if ($this->shouldParseNestedConstants($node)) {
            foreach ($this->parseNestedConstants($node) as $constant) {
                $constantName = $constant->expr->args[0]->value->value ?? '';
                if ($constantName && empty($this->constants[$constantName])) {
                    $this->constants[$constantName] = $constant;
                }
            }
        }

        return parent::enterNode($node);
    }

    /**
     * @param Node $node
     * @param bool $preserveStack
     * @return array|int|void|null
     */
    public function leaveNode(Node $node, bool $preserveStack = false)
    {
        if ($node instanceof Node\Stmt) {
            $node = $this->maybeReplaceWithFixture($node);
        }

        return parent::leaveNode($node, $preserveStack);
    }

    /**
     * @return Node[]
     */
    public function getStubStmts(): array
    {
        return array_merge(
            parent::getStubStmts(),
            $this->constants ? [new Namespace_(null, array_values($this->constants))] : [],
        );
    }

    /**
     * @param Node $node
     * @return bool
     */
    private function shouldParseNestedConstants(Node $node): bool
    {
        return ($node instanceof Function_ || $node instanceof ClassMethod);
    }

    /**
     * @param Node $node
     * @return Node[]
     */
    private function parseNestedConstants(Node $node): array
    {
        $constants = [];

        if (
            $node instanceof Expression &&
            $node->expr instanceof FuncCall &&
            $node->expr->name instanceof Name &&
            $node->expr->name->parts[0] === 'define'
        ) {
            $constants[] = $node;
        }

        foreach ((array) ($node->stmts ?? []) as $subNode) {
            $constants = array_merge($constants, $this->parseNestedConstants($subNode));
        }

        return $constants;
    }

    /**
     * @param Node\Stmt $stmt
     * @return Node\Stmt
     */
    private function maybeReplaceWithFixture(Node\Stmt $stmt): Node\Stmt
    {
        switch (true) {
            case ($stmt instanceof Function_):
                return $this->maybeReplaceFunctionWithFixture($stmt);
            case ($stmt instanceof Class_):
                return $this->maybeReplaceClassWithFixture($stmt);
            case ($stmt instanceof Interface_):
                return $this->maybeReplaceInterfaceWithFixture($stmt);
        }

        return $stmt;
    }

    /**
     * @param Function_ $stmt
     * @return Node\Stmt
     */
    private function maybeReplaceFunctionWithFixture(Function_ $stmt): Node\Stmt
    {
        $name = ltrim($stmt->name->toString(), '\\');
        $namespace = $this->currentNamespace();
        $functions = $this->fixtures[$namespace]['functions'] ?? [];
        if (($functions[$name] ?? null) instanceof Function_) {
            return $functions[$name];
        }

        return $stmt;
    }

    /**
     * @param Class_ $stmt
     * @return Node\Stmt
     */
    private function maybeReplaceClassWithFixture(Class_ $stmt): Node\Stmt
    {
        $name = ltrim($stmt->name->toString(), '\\');
        $namespace = $this->currentNamespace();
        $classes = $this->fixtures[$namespace]['classes'] ?? [];
        if (($classes[$name] ?? null) instanceof Class_) {
            return $classes[$name];
        }

        return $stmt;
    }

    /**
     * @param Interface_ $stmt
     * @return Node\Stmt
     */
    private function maybeReplaceInterfaceWithFixture(Interface_ $stmt): Node\Stmt
    {
        $name = ltrim($stmt->name->toString(), '\\');
        $namespace = $this->currentNamespace();
        $interfaces = $this->fixtures[$namespace]['interfaces'] ?? [];
        if (($interfaces[$name] ?? null) instanceof Interface_) {
            return $interfaces[$name];
        }

        return $stmt;
    }

    /**
     * @return string
     */
    private function currentNamespace(): string
    {
        $parent = $this->stack[count($this->stack) - 1] ?? null;

        return ($parent instanceof Node\Stmt\Namespace_)
            ? trim($parent->name->toString(), '\\')
            : '$global';
    }
}
