<?php

declare(strict_types=1);

/*
 * KaririCode Spec V4.3.3 §13 complexity budgets for src/, measured on the
 * AST (nikic/php-parser, installed by kcode with the toolchain). Cognitive
 * complexity (<= 15) is a PHPStan rule (tomasvotruba/cognitive-complexity,
 * phpstan.neon); this script checks the other budgets:
 *
 *   cyclomatic complexity per method <= 10  (McCabe; a match counts once)
 *   nesting depth per method         <= 3
 *   parameters per method            <= 4
 *   public methods per class         <= 10  (soft: reported, not failing)
 *
 * Usage: php tools/complexity.php src. Exit code 1 on a hard violation.
 */

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

require dirname(__DIR__) . '/.kcode/vendor/autoload.php';

const MAX_CYCLOMATIC = 10;
const MAX_NESTING = 3;
const MAX_PARAMETERS = 4;
const MAX_PUBLIC = 10;

const DECISIONS = [
    Node\Stmt\If_::class, Node\Stmt\ElseIf_::class, Node\Stmt\For_::class, Node\Stmt\Foreach_::class,
    Node\Stmt\While_::class, Node\Stmt\Do_::class, Node\Stmt\Catch_::class, Node\Stmt\Case_::class,
    Node\Expr\Ternary::class, Node\Expr\Match_::class, Node\Expr\BinaryOp\Coalesce::class,
    Node\Expr\BinaryOp\BooleanAnd::class, Node\Expr\BinaryOp\BooleanOr::class,
    Node\Expr\BinaryOp\LogicalAnd::class, Node\Expr\BinaryOp\LogicalOr::class, Node\Expr\BinaryOp\LogicalXor::class,
];

const NESTERS = [
    Node\Stmt\If_::class, Node\Stmt\For_::class, Node\Stmt\Foreach_::class, Node\Stmt\While_::class,
    Node\Stmt\Do_::class, Node\Stmt\Switch_::class, Node\Stmt\TryCatch::class, Node\Expr\Match_::class,
];

function cyclomatic(ClassMethod $method): int
{
    $nodes = (new NodeFinder())->find($method->stmts ?? [], static fn(Node $node): bool => in_array($node::class, DECISIONS, true));

    return 1 + count($nodes);
}

/**
 * @param array<mixed> $nodes
 */
function nesting(array $nodes, int $depth = 0): int
{
    $deepest = $depth;

    foreach ($nodes as $node) {
        if (is_array($node)) {
            $deepest = max($deepest, nesting($node, $depth));

            continue;
        }

        if (!$node instanceof Node) {
            continue;
        }

        $inner = in_array($node::class, NESTERS, true) ? $depth + 1 : $depth;
        $deepest = max($deepest, $inner);

        foreach ($node->getSubNodeNames() as $name) {
            $deepest = max($deepest, nesting([$node->{$name}], $inner));
        }
    }

    return $deepest;
}

$directory = $argv[1] ?? 'src';
$parser = (new ParserFactory())->createForHostVersion();
$violations = [];
$warnings = [];

foreach (glob($directory . '/*.php') ?: [] as $file) {
    $ast = $parser->parse((string) file_get_contents($file)) ?? [];

    foreach ((new NodeFinder())->findInstanceOf($ast, Node\Stmt\ClassLike::class) as $type) {
        $name = $type->name?->toString() ?? '(anonymous)';
        $public = 0;

        foreach ($type->getMethods() as $method) {
            $where = sprintf('%s::%s()', $name, $method->name->toString());
            $public += $method->isPublic() ? 1 : 0;

            $metrics = [
                'cyclomatic' => [cyclomatic($method), MAX_CYCLOMATIC],
                'nesting' => [nesting($method->stmts ?? []), MAX_NESTING],
                'parameters' => [count($method->params), MAX_PARAMETERS],
            ];

            foreach ($metrics as $metric => [$value, $budget]) {
                if ($value > $budget) {
                    $violations[] = sprintf('%s %s %d > %d', $where, $metric, $value, $budget);
                }
            }
        }

        if ($public > MAX_PUBLIC) {
            $warnings[] = sprintf('%s has %d public methods (soft budget %d)', $name, $public, MAX_PUBLIC);
        }
    }
}

foreach ($warnings as $warning) {
    fwrite(STDERR, "warning: {$warning}\n");
}

foreach ($violations as $violation) {
    fwrite(STDERR, "violation: {$violation}\n");
}

echo $violations === [] ? "complexity: within the §13 budgets\n" : sprintf("complexity: %d violation(s)\n", count($violations));

exit($violations === [] ? 0 : 1);
