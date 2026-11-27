# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the package
follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Changed

- Registry: the blocks of database 4.4.0 (DB_6010–DB_6121) and console
  4.6.0 (9001–9007) are released.
- Registry: the block of cli 4.1.0 (CLI_8900–CLI_8942) is released.
- Registry: the protocol packages claim the lowest free blocks before their
  first release: kariricode/sse 1600–1699 (SSE), kariricode/http 1700–1799
  (HTTP), kariricode/websocket 1800–1899 (WS), kariricode/grpc 1900–1999
  (GRPC) and kariricode/graphql 2600–2699 (GQL).

## [2.0.1] — 2026-11-27

Registry update only; the code is the same as in 2.0.0.

### Changed

- Registry: kariricode/router claims 5000–5099 under RO with its 4.1
  import (RO_5000–RO_5052); kariricode/console's row points at 4.6.0.
- Registry: the blocks of property-inspector 3.0.0, processor-pipeline
  3.0.0, class-discovery 2.0.0, router 4.1.0 and cache 4.1.0 are released;
  each row names the version and the codes it throws.

## [2.0.0] — 2026-11-27

A new package under the old name: optional bases for the exception families
of `kariricode/contract` (ADR-001), the ecosystem's registry of error-code
blocks (ADR-002), and the KaririCode Spec V4.3.3 gates through kcode
(ADR-003). **Breaking:** nothing of 1.x remains; [UPGRADE.md](UPGRADE.md)
maps it.

### Added

- Six abstract family bases, each extending the SPL class the contract
  suggests and implementing one family: `ValidationException`,
  `NotFoundException`, `ConfigurationException`, `ProtocolException`,
  `ContainerException`, `RuntimeException`.
- `ErrorCode`: a component's codes as an int-backed enum with `prefix()` and
  `block()`; exceptions expose the case as `$errorCode`.
- `CodeBlock`: a validated block of four-digit codes, with `contains()` and
  `overlaps()`.
- `ErrorCodeTable`: checks a code enum (int-backed, prefix shape, cases
  inside the block) in a component's conformance test.
- `CarriesErrorCode`: the `[PREFIX_code]` message tag; escapes for control
  characters, line separators and bidirectional controls; U+FFFD for
  invalid UTF-8; `quote()` for identifiers.
- This package's own codes, `ExceptionCode` (EX_1500–EX_1504), with
  `InvalidCodeBlockException` and `InvalidErrorCodeTableException`.
- `CODE-BLOCKS.md`: the registry of every component's block, the seven
  collisions found in the tarball review and their resolution.
- Unit and conformance suites (315 tests, MSI 100%), SPEC-001, ADR-001 to
  ADR-003, QD-V433-COMPLIANCE, SECURITY.md.
- Toolchain through KaririCode Devkit with the root configs: PHPStan max,
  Psalm level 1, PER-CS 2.0, complexity budgets, Infection, `composer
  audit` and a secret scan; CycloneDX SBOM; CI with SHA-pinned actions.

### Changed

- Requires PHP `^8.4` and `kariricode/contract` `^2.11`.

### Removed

- `AbstractException`, `ExceptionMessage`, `Contract\ErrorMessage`,
  `Contract\Throwable` and `getContext()`.
- The 25 domain classes of 1.x (`Auth\*`, `Cache\*`, `Config\*`,
  `Container\*`, `Database\*`, `Event\*`, `ExternalService\*`, `File\*`,
  `Input\*`, `Localization\*`, `Middleware\*`, `Network\*`, `Queue\*`,
  `Routing\*`, `Runtime\*`, `Security\*`, `Session\*`, `System\*`,
  `Template\*`, `Validation\*`) and their codes 1001–3102.

## 1.x

The 1.x line, up to 1.2.4, was published from
`KaririCode-Framework/kariricode-exception`; see its tags and the git
history. It stays installable with `^1.2`; 2.0.0 supersedes it.

[2.0.1]: https://github.com/KaririCode-PHP/exception/releases/tag/v2.0.1
[2.0.0]: https://github.com/KaririCode-PHP/exception/releases/tag/v2.0.0
