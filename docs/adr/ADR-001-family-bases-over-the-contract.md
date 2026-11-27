# ADR-001: Optional family bases over kariricode/contract

- **Status:** Accepted
- **Date:** 2026-11-27
- **Component:** kariricode/exception 2.0.0
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

## Context

kariricode/exception 1.x shipped 25 final exception classes for domains
such as `Database`, `Network` or `Validation`, each with hard-coded factories
and codes (1001–3102), on top of an `AbstractException` with a
`getContext()` array. No V4 component uses it: the only user left,
property-inspector, drops it in 3.0.

Meanwhile kariricode/contract 2.11.0 defined what an ecosystem exception is
(Spec V4.3.3 §8.4): a member-free root, `ExceptionInterface`, and six
families: `Validation`, `NotFound`, `Configuration`, `Protocol`,
`ContainerFailure` and `Runtime`. Each component throws its own exceptions,
implements exactly one family per class, returns a code from a block it
owns, and never repeats a rejected value in a message (errata E-047).

The review of the tarball (51 package versions of 45 components; 44 of
them declare exceptions) found the V4 components rebuilding the same pieces
by hand:

| Piece | How components do it today |
|---|---|
| SPL parent per family | each picks one; most extend `\RuntimeException` for every family |
| `[PREFIX_code]` message tag | `tagged()` copied into rate-limiter, circuit-breaker, template; dataflow formats with `sprintf('[DF_%04d] …')`; others write the tag by hand in each factory |
| Identifier quoting | circuit-breaker's `quote()`; elsewhere raw interpolation |
| Code inside the block | a conformance test per component (processor-pipeline, console, cli), each written differently; most have none |
| Code uniqueness | none, or a reflection loop over constants |

And seven pairs of components claim the same codes (ADR-002).

## Decision

kariricode/exception 2.0.0 becomes the optional base layer on top of the
contract families. It depends on PHP 8.4 and kariricode/contract `^2.11`
only, so a component can adopt it without any risk of a cycle (§31).

1. **Six abstract family bases**, one per contract family. Each extends the
   SPL class the contract's docblock example names and implements that
   family alone:

   | Base | SPL parent | Family | HTTP at the edge |
   |---|---|---|---|
   | `ValidationException` | `\DomainException` | `Validation` | 422 |
   | `NotFoundException` | `\OutOfBoundsException` | `NotFound` | 404 |
   | `ConfigurationException` | `\InvalidArgumentException` | `Configuration` | 500 |
   | `ProtocolException` | `\UnexpectedValueException` | `Protocol` | 400 |
   | `ContainerException` | `\LogicException` | `ContainerFailure` | 500 |
   | `RuntimeException` | `\RuntimeException` | `Runtime` | 503 or 500 |

   They are abstract: a thrown exception always belongs to a component and
   carries that component's code. A component's concrete class extends a
   base directly, so its chain of user classes stays at two (§18).

2. **Codes as int-backed enums.** A component declares its codes once, in
   an enum implementing `ErrorCode` (`prefix()`, `block()`). PHP refuses
   two cases with the same value, so uniqueness costs nothing, and the
   enum is the component's code table in one place (§18: enums over magic
   numbers). The exception exposes the case as `$errorCode`; `getCode()`
   returns its value.

3. **Safe messages by construction** (`CarriesErrorCode`). The message is
   `[PREFIX_code] text`, the format rate-limiter, circuit-breaker,
   database, dataflow and cli already use. Control characters, C1 controls,
   U+2028/U+2029 and the bidirectional controls become escapes, and invalid
   UTF-8 becomes U+FFFD, so nothing that slips into a message can forge a
   log line (CWE-117) or break a JSON encoder. `quote()` renders an
   identifier as a JSON literal capped at 128 characters. E-047 (never
   repeat the rejected value) stays a rule for the author: no code can tell
   a value from a rule.

4. **The trait is public.** A component that keeps its own abstract root
   class (cache 4.1.0 made `CacheException` a marker interface instead, the
   recommended shape) can use `CarriesErrorCode` directly.

5. **`ErrorCodeTable`** checks a component's enum in its conformance test:
   int-backed, prefix shape, every case inside the block. It throws this
   package's own coded exceptions (EX_1500–EX_1504).

6. **No context bag.** 1.x's `getContext(): array` is gone: untyped context
   is stringly-typed coupling, contract ADR-005 already rejected it, and
   §18 forbids zero-argument getters. A subclass that needs structured data
   declares typed properties and fills them in its named constructor.

Using the package stays optional: a component may keep implementing the
contract families on its own classes, as every V4 component does today.

## Alternatives Considered

| Alternative | Why not |
|---|---|
| Keep 1.x (25 domain classes) | domains are not families; codes 1001–3102 collide with async-primitives and eventloop; final classes cannot carry a component's own code |
| Concrete family classes, thrown directly | an exception without a component code defeats §8.4; components need their own classes for named constructors |
| One base class for all families | PHP has single inheritance: either every family shares one SPL parent (losing the contract's guidance) or the base sits outside the family hierarchy |
| Int constants per class, as today | uniqueness and range checks need reflection; the table is spread over classes |
| Neutralise nothing, document E-047 only | E-047 is about values; log forging also comes from identifiers and from previous messages copied into text |
| `CodeBlock` built inside the tag on every throw | an invalid block would throw from inside an exception constructor and hide the original failure; prefix() and block() stay separate, and blocks are checked in tests |
| A readonly `$errorCode` | PHPStan rejects the readonly write in the trait method the bases call; Psalm does not follow a constructor that a parent takes from a trait. `private(set)` satisfies both, and only the base class can write it (§18 accepts private(set)) |

## Consequences

### Positive

- A component gets the family mapping, the tag, safe text and a code-table
  check by extending one class and declaring one enum.
- Messages look the same across components, and the codes behind them come
  from blocks the registry keeps disjoint (ADR-002).

### Negative

- 2.0.0 breaks 1.x completely; UPGRADE.md maps the old classes. 1.x stays
  installable (`^1.2`), and no published package requires 2.x yet.
- Each family base declares a three-line constructor that calls the trait's
  under an alias, so both analysers see `$errorCode` set; a component root
  class that uses the trait directly needs the same lines for Psalm.
- `RuntimeException` shares its short name with SPL's; components import it
  explicitly.
