# ADR-003: Quality gates through KaririCode Devkit, with the root configs

- **Status:** Accepted
- **Date:** 2026-11-27
- **Component:** kariricode/exception 2.0.0
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

## Context

The ecosystem installs its QA toolchain with KaririCode Devkit (`kcode`):
components keep no QA package in `require-dev`, and kcode installs the tools
into `.kcode/vendor/`. Devkit 1.2.1 can run a component's own configs
(`'root_configs' => true`), which is what Spec V4.3.3 §29.1 needs: kcode's
generated configs run PHPStan at level 9 and Psalm at level 3, with no
mutation or conformance gate. kariricode/contract made the same move in its
ADR-008.

## Decision

| Gate (§29.1) | Command | Pass criteria |
|---|---|---|
| Code style | `composer cs-check` | PER-CS 2.0, strict types, ordered imports |
| Static analysis | `composer phpstan` | level max over src, tests and tools; cognitive complexity <= 15 per method |
| Type coverage | `composer psalm` | error level 1 over src, no baseline, no suppression |
| Complexity | `composer complexity` | cyclomatic <= 10, nesting <= 3, parameters <= 4 (§13) |
| Unit tests | `composer test-unit` | 100% green; strict coverage metadata |
| Conformance | `composer test-conformance` | every §29.2 rule over src, and the registry checks |
| Mutation | `composer mutation` | MSI 100% (the Spec asks for >= 80%) |
| Security | `composer security` | `composer audit` over the runtime lock and the toolchain, secret scan clean |

- `devkit.php` pins the tools exactly: PHPUnit 11.5.56, PHPStan 2.3.7,
  Psalm 6.19.1, PHP-CS-Fixer 3.95.27, Infection 0.35.5 and
  tomasvotruba/cognitive-complexity 1.2.0.
- `composer check` runs style, PHPStan, Psalm, complexity, the suites and
  mutation; CI runs it on PHP 8.4 after installing kcode 1.2.1 by version
  and SHA-256, then `composer security` and `composer sbom`.
- The lock file is committed; its only package is kariricode/contract.
- Actions are pinned by commit SHA; pull requests get a dependency review.

## Alternatives Considered

| Alternative | Why not |
|---|---|
| Toolchain in `require-dev` | the rest of the ecosystem installs it through kcode |
| kcode's generated configs | weaker than §29.1, and no mutation gate |
| PHPStan 2.2.16, as the contract pins | 2.3.7 is the newer release and passes; the contract can follow when it next touches its toolchain |
| MSI at the Spec's 80% | the package is small and every mutant is killed; a lower bar would hide a regression |

## Consequences

### Positive

- The gates are the ones §29.1 lists, and a reviewer reads their versions
  in one file.

### Negative

- Contributors install kcode once (README, Development).
- Transitive versions of the toolchain resolve at install time until a
  `devkit.lock` is committed.
