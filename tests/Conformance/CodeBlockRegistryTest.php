<?php

declare(strict_types=1);

namespace KaririCode\Exception\Tests\Conformance;

use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ErrorCodeTable;
use KaririCode\Exception\ExceptionCode;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * CODE-BLOCKS.md is the ecosystem's registry of record (ADR-002); these
 * checks keep it a registry: valid blocks, no overlap, one prefix per
 * package, and this package's own row in step with ExceptionCode.
 */
#[CoversNothing]
final class CodeBlockRegistryTest extends TestCase
{
    private const string REGISTRY = __DIR__ . '/../../CODE-BLOCKS.md';

    private const array STATUSES = ['released', 'claimed', 'proposed', 'reserved'];

    #[Test]
    public function everyRowIsAValidBlockWithAKnownStatus(): void
    {
        foreach (self::rows() as $row) {
            $block = new CodeBlock($row['first'], $row['last']);

            self::assertSame($row['first'], $block->first);
            self::assertContains($row['status'], self::STATUSES, $row['package']);
            self::assertMatchesRegularExpression($row['status'] === 'reserved' ? '/\A—\z/' : '/\A[A-Z][A-Z0-9]{1,3}\z/', $row['prefix'], $row['package']);
        }

        self::assertGreaterThanOrEqual(30, count(self::rows()));
    }

    #[Test]
    public function blocksNeverOverlapAndAreListedInOrder(): void
    {
        $rows = self::rows();
        $previous = null;

        foreach ($rows as $index => $row) {
            $block = new CodeBlock($row['first'], $row['last']);

            if ($previous !== null) {
                self::assertGreaterThan($previous->last, $block->first, $row['package'] . ' follows the row above');
            }

            foreach (array_slice($rows, $index + 1) as $other) {
                self::assertFalse($block->overlaps(new CodeBlock($other['first'], $other['last'])), $row['package'] . ' and ' . $other['package']);
            }

            $previous = $block;
        }
    }

    #[Test]
    public function aPrefixBelongsToOnePackage(): void
    {
        $owners = [];

        foreach (self::rows() as $row) {
            if ($row['status'] === 'reserved') {
                continue;
            }

            $owners[$row['prefix']][$row['package']] = true;
        }

        foreach ($owners as $prefix => $packages) {
            self::assertCount(1, $packages, "prefix {$prefix}");
        }

        $byPackage = [];

        foreach (self::rows() as $row) {
            $byPackage[$row['package']][$row['prefix']] = true;
        }

        foreach ($byPackage as $package => $prefixes) {
            self::assertCount(1, $prefixes, "{$package} keeps one prefix");
        }
    }

    #[Test]
    public function thisPackageRowMatchesExceptionCode(): void
    {
        $table = new ErrorCodeTable(ExceptionCode::class);
        $own = array_values(array_filter(self::rows(), static fn(array $row): bool => $row['package'] === 'kariricode/exception'));

        self::assertCount(1, $own);
        self::assertSame($table->prefix, $own[0]['prefix']);
        self::assertSame($table->block->first, $own[0]['first']);
        self::assertSame($table->block->last, $own[0]['last']);
    }

    #[Test]
    public function theExampleBlockIsReservedAndHoldsTheTestFixtures(): void
    {
        $reserved = array_values(array_filter(self::rows(), static fn(array $row): bool => $row['status'] === 'reserved'));

        self::assertCount(1, $reserved);
        self::assertSame([7200, 7299], [$reserved[0]['first'], $reserved[0]['last']]);
    }

    /**
     * @return list<array{first: int, last: int, prefix: string, package: string, status: string}>
     */
    private static function rows(): array
    {
        $rows = [];

        foreach (file(self::REGISTRY, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (preg_match('/\A\| (\d{4})–(\d{4}) \| (\S+) \| ([^|]+?) \| (\w+) \|/u', $line, $match) === 1) {
                $rows[] = [
                    'first' => (int) $match[1],
                    'last' => (int) $match[2],
                    'prefix' => $match[3],
                    'package' => $match[4],
                    'status' => $match[5],
                ];
            }
        }

        return $rows;
    }
}
