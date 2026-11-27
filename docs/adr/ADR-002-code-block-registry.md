# ADR-002: One registry of error-code blocks, kept with this package

- **Status:** Accepted
- **Date:** 2026-11-27
- **Component:** kariricode/exception 2.0.0
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

## Context

Spec V4.3.3 §8.4 identifies a failure by its contract family plus
`getCode()`, and asks each component to allocate a code sub-range "documented
in its ADR/SPEC". Components did, each in its own documents, and several
cite "the ecosystem registry" (dependency-injection SPEC-001, observability,
processor-pipeline ADR-007, cli ADR-009, cache QD-V433 item 4). That
registry was never written down: cache and cli both ask for one "owned by
the contract or the Specification".

Without it the blocks drifted. Reading the latest version of every
component in the tarball:

| Disputed codes | Claimants |
|---|---|
| 5100–5159 | router 4.0.0 and dataflow 6.0.0, both in code |
| 6000–6099 | service-client 4.0.1 and database 4.4.0 |
| 8500–8599 | authenticator 4.2.0 (login layer, reserved) and processor-pipeline 4.0.0 (in code, enforced) |
| 8600–8699 | cli 4.1.0 and cache 4.1.0, both in code |
| 9000–9099 | framework 4.0.1 and console 4.5.0 |
| 9100–9199 | runtime 4.0.5 and i18n 4.0.1 |
| 9000–9999 | parser 4.1.0, over console, i18n, tenancy, framework and runtime |

Five more collisions were already void: the component had moved, but older
notes still cite the old block.

## Decision

1. **`CODE-BLOCKS.md`, at the root of this package, is the registry of
   record.** It lists every block, its prefix, package and status, the
   collisions found and their resolution. It ships in the dist archive, in
   a stable table format, so a component's own test can read it from
   `vendor/kariricode/exception/CODE-BLOCKS.md`. A conformance test here
   (`CodeBlockRegistryTest`) fails when two blocks overlap, a prefix
   belongs to two packages, or this package's row disagrees with
   `ExceptionCode`.

2. **Why here.** The registry is data about codes, and this package owns
   the code machinery (`CodeBlock`, `ErrorCode`, `ErrorCodeTable`). The
   contract stays interfaces only (contract ADR-001); the Specification is
   normative text the author amends, not a list that changes with every new
   component. When the Specification adopts a registry, this file moves
   there.

3. **Resolution rule.** When two claims collide, the block stays with (a)
   the component that already released codes from it; otherwise (b) the
   component the tarball inventory marks as reviewed (`01-ok-gh`, `02-ok`)
   over one it does not; otherwise (c) the component that throws more codes
   from it. The other moves to the nearest free block of the same size and
   shifts every code by one constant, so its sub-layout survives and the
   upgrade note is a single offset. No V4 block has been released yet, so
   (a) decided nothing this time.

4. **Applying it.** Moves are recorded as **proposed** and applied when the
   component is imported into its repository, with an ADR and an upgrade
   table there. This package does not edit other components.

5. **Shape rules.** Codes have four digits (1000–9999); the norm is a block
   of 100; a prefix is a capital letter followed by one to three capital
   letters or digits (`RL`, `CLI`, `I18N`), one per package. 7200–7299 is
   reserved for examples, fixtures and documentation; this package's tests
   use it. kariricode/exception holds 1500–1599 (`EX`), the lowest free
   block.

### Resulting moves

| Component | From | To | Offset | Rule |
|---|---|---|---|---|
| router | 5100–5159 | 5000–5099 | −100 | (c): dataflow throws more codes |
| service-client | 6000–6099 | 6400–6499 | +400 | (b): database is reviewed |
| authenticator, login layer | 8500–8599 | 8800–8899 | +300 | (c): processor-pipeline throws codes there, the login layer only reserves them |
| cli | 8600–8699 | 8900–8999 | +300 | (c): cache throws more codes |
| framework | 9000–9099 | 9300–9399 | +300 | (b): console is reviewed |
| runtime | 9100–9199 | 9400–9499 | +300 | (c): i18n throws more codes |
| parser | 9000–9999 | 9500–9999 | +500 | (b): every other claimant was there first or is reviewed; its five passes keep 100 codes each, and the three codes it now keeps in 9500–9999 move inside that layout on import |

## Alternatives Considered

| Alternative | Why not |
|---|---|
| Keep blocks in each component only | that is how the seven collisions happened; nobody sees a neighbour's block |
| Registry in kariricode/contract | the contract holds interfaces only, and a registry would make it change for every new component |
| Registry in the Specification | normative text, amended by the author on its own cycle; the ecosystem needed the list now. It can move there later |
| First dated claim wins | the dates come from changelogs written in parallel sessions, three days apart; they measure nothing about maturity or cost of change |
| Renumber everything into a fresh plan | every reviewed component would change; the rule moves seven, by one offset each |
| Five- or six-digit codes | HTTP-like four digits are what every component already uses; 9,000 codes are enough at 100 per component |

## Consequences

### Positive

- A code identifies one component again, and the claims are checked in CI.
- The upgrade note of a moved component is one offset.

### Negative

- Seven components renumber when they are imported; their changelogs and
  conformance tests change with them.
- The registry needs a pull request here before a component releases its
  first coded exception (rule 3 of `CODE-BLOCKS.md`).
- Prefixes marked "proposed" were chosen here for components that do not
  tag their messages yet; a component may pick another free one when it is
  imported.
