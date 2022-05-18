<?php

declare(strict_types=1);

namespace Inpsyde\WpStubs;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;

class NodeVisitor extends \StubsGenerator\NodeVisitor
{
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