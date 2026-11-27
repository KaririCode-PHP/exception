# QD-V433-COMPLIANCE: kariricode/exception 2.0.0

Quality record against KaririCode Specification V4.3.3. Every result below
comes from a command, not from review alone (§29: "score is computed from
automated checks"). Local evidence: 2026-11-27, PHP 8.4.28, toolchain as
pinned in `devkit.php`. CI repeats the same commands on every push.

## 1. Automated gates (§29.1)

| Gate | Command | Result |
|---|---|---|
| Code style | `composer cs-check` | 0 of 43 files to fix (PER-CS 2.0) |
| Static analysis | `composer phpstan` | PHPStan 2.3.7, level max over src, tests and tools: no errors |
| Cognitive complexity | (PHPStan rule) | every method <= 15 |
| Type coverage | `composer psalm` | Psalm 6.19.1, error level 1: no errors, no baseline, no suppression |
| Complexity | `composer complexity` | within §13: cyclomatic <= 10, nesting <= 3, parameters <= 4 |
| Unit tests | `composer test-unit` | 297 tests, 634 assertions |
| Conformance | `composer test-conformance` | 18 tests, 943 assertions |
| Mutation | `composer mutation` | Infection 0.35.5: 71 mutants, 71 killed, MSI 100%, covered MSI 100% |
| Security | `composer security` | no advisory in the runtime or the toolchain; secret scan clean |

## 2. Conformance rules (§29.2)

`tests/Conformance/SpecConformanceTest.php` runs each rule over every type
in `src/` with no tolerated deviation:

| Rule | Status |
|---|---|
| Strict types; one type per file, named after it | pass |
| Classes final, except the six family bases | pass |
| No `Interface`, `Manager`, `Helper`, `Util` names; exceptions end in `Exception` (§16.3) | pass |
| No zero-argument getters, no `toArray`/`fromArray`/`jsonSerialize`, no magic methods but `__construct` (§18) | pass |
| No static state; public properties readonly or `private(set)`; no hook on a readonly property (E-040) | pass |
| Typed constants | pass |
| Inheritance depth: at most one user class above any class in `src/` | pass |
| Each exception implements one contract family and carries an `ErrorCode` | pass |
| Concrete exceptions built by static named constructors only | pass |
| Docblocks meet C1–C5 (§17), with an `@see` link and no authorship tags | pass |
| Runtime requires PHP and kariricode/contract only (§31); `src/` imports no other KaririCode package | pass |
| `arfa` and `asvs` levels declared (§29.3 G4) | pass |

`tests/Conformance/CodeBlockRegistryTest.php` keeps `CODE-BLOCKS.md` a
registry: 32 valid blocks in order, none overlapping, one prefix per
package, and this package's row equal to `ExceptionCode`.

## 3. Production-readiness gates (§29.3)

| Gate | Status |
|---|---|
| G1 all §29.1 checks pass | pass |
| G2 docs meet C1–C5 | pass for every type (checked by the conformance suite) |
| G3 no P0 in manual review | pass: no open P0 |
| G4 `arfa.level` and `asvs.level` declared | pass: ARFA 1.3 level 1, ASVS 5.0 level 1 |
| G5 no known antipattern (§18) | pass |
| G6 security gate (§24) | pass: audit and secret scan clean; log-injection neutralisation tested (CWE-117) |

## 4. Open items

| Item | Owner |
|---|---|
| Signed tag for v2.0.0 (§32) | author: releases are created from the GitHub UI without a signature for now |
| `devkit.lock` to pin the toolchain's transitive versions | Devkit |
| The seven proposed block moves (ADR-002) | each component, when it is imported |
