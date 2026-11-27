# SPEC-001: kariricode/exception 2.0

- **Version:** 2.0.0
- **Date:** 2026-11-27
- **Conformance:** KaririCode Specification V4.3.3, ARFA 1.3 level 1, OWASP ASVS 5.0 level 1
- **Decisions:** ADR-001 (family bases), ADR-002 (code-block registry), ADR-003 (toolchain)

## 1. Purpose and position

Optional base classes for the six exception families of
kariricode/contract (Spec V4.3.3 §8.4). A component that uses them gets the
SPL parent of each family, a `[PREFIX_code]` message tag, text that is safe
to log, and a check of its code table. The package depends on PHP `^8.4`
and kariricode/contract `^2.11`, nothing else, so it can never be part of a
dependency cycle (§31).

```
 kariricode/contract   Exception\ExceptionInterface + 6 families (interfaces)
          ▲
          │ implements
 kariricode/exception  6 abstract bases · ErrorCode · CodeBlock · ErrorCodeTable
          ▲
          │ extends (optional)
 component             InvoiceCode enum + one final class per failure
```

## 2. Public API

Thirteen types, all in `KaririCode\Exception`.

| Type | Kind | Role |
|---|---|---|
| `ErrorCode` | interface, extends `\BackedEnum` | a component's code table: `prefix(): string`, `block(): CodeBlock` |
| `CodeBlock` | final readonly class | `first`, `last`; `contains(int)`, `overlaps(CodeBlock)`; `FLOOR` 1000, `CEILING` 9999 |
| `ErrorCodeTable` | final readonly class | a verified `ErrorCode` enum: `enum`, `prefix`, `block` |
| `CarriesErrorCode` | trait | `$errorCode`, the constructor, `quote()`, message neutralisation |
| `ValidationException` | abstract class | `\DomainException` + `Validation` |
| `NotFoundException` | abstract class | `\OutOfBoundsException` + `NotFound` |
| `ConfigurationException` | abstract class | `\InvalidArgumentException` + `Configuration` |
| `ProtocolException` | abstract class | `\UnexpectedValueException` + `Protocol` |
| `ContainerException` | abstract class | `\LogicException` + `ContainerFailure` |
| `RuntimeException` | abstract class | `\RuntimeException` + `Runtime` |
| `ExceptionCode` | enum | this package's codes, EX 1500–1599 |
| `InvalidCodeBlockException` | final class | EX_1500 |
| `InvalidErrorCodeTableException` | final class | EX_1501–EX_1504 |

### 2.1 Constructor of every family base

```php
public function __construct(ErrorCode $errorCode, string $message, ?Throwable $previous = null)
```

- `getCode()` returns `$errorCode->value` when the enum is int-backed, and
  0 otherwise (`ErrorCodeTable` rejects string-backed tables).
- `$errorCode` is `public private(set)`: readable by anyone, written only
  by the base class, once, in this constructor.
- `getPrevious()` returns `$previous`.

### 2.2 Message

`getMessage()` returns `[` + `prefix()` + `_` + value + `] ` + text, after
neutralisation:

| Input | Output |
|---|---|
| U+0000–U+001F | the short JSON escape (`\n`, `\r`, `\t`, `\b`, `\f`) or `\u00XX` |
| U+007F–U+009F | `\u007f` … `\u009f` |
| U+061C, U+200E, U+200F, U+2028–U+202E, U+2066–U+2069 | `\uXXXX` |
| invalid UTF-8 | U+FFFD per invalid sequence |
| everything else | unchanged (backslashes, quotes, letters, emoji) |

The tag goes through the same filter, so a malformed `prefix()` cannot
break a log line either.

### 2.3 `quote(string $identifier): string`

Protected static, for named constructors. Returns a JSON string literal
(`JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`) of the identifier with
invalid UTF-8 replaced, cut after 128 characters with `…`. For identifiers
only: never a secret or personal data (E-047).

### 2.4 `ErrorCodeTable`

`new ErrorCodeTable(string $enum)` checks, in order, and throws at the first
failure:

| Rule | Exception | Code |
|---|---|---|
| `$enum` names an enum implementing `ErrorCode` | `InvalidErrorCodeTableException::notAnErrorCode()` | 1501 |
| `prefix()` matches `\A[A-Z][A-Z0-9]{1,3}\z` | `::invalidPrefix()` | 1502 |
| `block()` is a valid block | `InvalidCodeBlockException::invalidBounds()` | 1500 |
| every case value is an int | `::notIntBacked()` | 1503 |
| every case value lies in `block()` | `::codeOutsideBlock()` | 1504 |

An enum without cases is valid. Messages name the enum and the case, both
identifiers PHP has validated, and never the rejected prefix or value.

## 3. Invariants

1. A `CodeBlock` that exists satisfies `1000 <= first <= last <= 9999`.
2. `a->overlaps(b) === b->overlaps(a)`, and it is true exactly when some
   code lies in both blocks (property-tested over 200 generated pairs).
3. An `ErrorCodeTable` that exists describes a valid table.
4. Every exception in `src/` implements exactly one family and carries an
   `ErrorCode`.
5. No type in `src/` has static state, a writable public property, a magic
   method other than `__construct`, or a zero-argument getter.

## 4. Using it in a component

1. Claim a block in `CODE-BLOCKS.md` (ADR-002).
2. Declare the codes:

   ```php
   enum InvoiceCode: int implements ErrorCode
   {
       case AmountBelowZero = 7200;
       case LedgerUnreachable = 7250;

       public static function prefix(): string { return 'IV'; }

       public static function block(): CodeBlock { return new CodeBlock(7200, 7299); }
   }
   ```

3. One final class per failure, extending the family base, built by named
   constructors whose text states the rule:

   ```php
   final class LedgerUnavailableException extends RuntimeException
   {
       public static function unreachable(\Throwable $previous): self
       {
           return new self(InvoiceCode::LedgerUnreachable, 'The ledger store is unreachable.', $previous);
       }
   }
   ```

4. For a component-wide catch type, add a marker interface that extends
   `Contract\Exception\ExceptionInterface` and implement it on each class;
   an abstract component class would add a third level (§18).
5. In the conformance suite: `new ErrorCodeTable(InvoiceCode::class);`.

## 5. Compatibility

2.0.0 removes the whole 1.x API; `UPGRADE.md` maps it. From 2.0.0 on, the
public API of §2, the message format of §2.2 and the codes of
`ExceptionCode` follow SemVer. The neutralised character set may grow in a
minor release (new characters get escaped); it never shrinks outside a
major.

## 6. Verification

`tests/Unit` covers every type (315 tests, MSI 100% under Infection);
`tests/Conformance` enforces §29.2 over `src/` and checks the registry;
`docs/quality/QD-V433-COMPLIANCE.md` records the gate results.
