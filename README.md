# KaririCode\Exception

**Optional base classes for the exception families of kariricode/contract: one abstract base per family, error codes as enums inside registered blocks, and messages that are safe to log.**

[![en](https://img.shields.io/badge/lang-en-red.svg)](README.md) [![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](README.pt-br.md)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![ARFA](https://img.shields.io/badge/ARFA-1.3%20level%201-orange)](docs/spec/SPEC-001-exception.md)
[![MSI](https://img.shields.io/badge/MSI-100%25-brightgreen)](docs/quality/QD-V433-COMPLIANCE.md)

Part of the [KaririCode Framework](https://kariricode.org) ecosystem.

## Why

KaririCode Spec V4.3.3 §8.4 asks every component to throw its own exceptions, each implementing one of the six families of `kariricode/contract`, with a numeric code from a block the component owns. Components did that by hand, each differently: which SPL class to extend, how to tag the message with the code, how to keep a rejected value out of it, how to check that codes stay inside the block. And nobody kept the blocks apart: seven pairs of components claimed the same codes.

This package does the repeated part once:

| Piece | What you get |
|---|---|
| Family bases | `ValidationException`, `NotFoundException`, `ConfigurationException`, `ProtocolException`, `ContainerException`, `RuntimeException`: each extends the SPL class the contract suggests and implements that family alone |
| Codes | an int-backed enum per component (`ErrorCode`); PHP rejects duplicate values, and `ErrorCodeTable` checks prefix and block in your conformance test |
| Messages | `[IV_7250] The ledger store is unreachable.`, with control characters, line separators and bidirectional controls escaped and invalid UTF-8 replaced, so no value can forge a log line |
| Registry | [`CODE-BLOCKS.md`](CODE-BLOCKS.md): every component's block, checked in CI, shipped with the package |

It depends on PHP 8.4 and `kariricode/contract` only, so any component can use it without creating a cycle. Using it is optional: implementing the contract families on your own classes stays conformant.

## Installation

```bash
composer require kariricode/exception
```

## Usage

### 1. Declare your codes

Claim a block in [`CODE-BLOCKS.md`](CODE-BLOCKS.md), then list the codes in one enum:

```php
use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ErrorCode;

enum InvoiceCode: int implements ErrorCode
{
    case AmountBelowZero = 7200;
    case InvoiceMissing = 7210;
    case LedgerUnreachable = 7250;

    public static function prefix(): string
    {
        return 'IV';
    }

    public static function block(): CodeBlock
    {
        return new CodeBlock(7200, 7299);
    }
}
```

The examples use 7200–7299, the block the registry reserves for documentation.

### 2. One final class per failure

Extend the base of the right family and build the exception with named constructors. The text states the rule that was broken; it never repeats the rejected value (errata E-047).

```php
use KaririCode\Exception\NotFoundException;
use KaririCode\Exception\RuntimeException;
use KaririCode\Exception\ValidationException;

final class NegativeAmountException extends ValidationException
{
    public static function belowZero(): self
    {
        return new self(InvoiceCode::AmountBelowZero, 'An invoice amount cannot be negative.');
    }
}

final class InvoiceNotFoundException extends NotFoundException
{
    public static function forNumber(): self
    {
        return new self(InvoiceCode::InvoiceMissing, 'No invoice has that number.');
    }
}

final class LedgerUnavailableException extends RuntimeException
{
    public static function unreachable(\Throwable $previous): self
    {
        return new self(InvoiceCode::LedgerUnreachable, 'The ledger store is unreachable.', $previous);
    }
}
```

```php
$e = LedgerUnavailableException::unreachable($driverError);

$e->getMessage();  // "[IV_7250] The ledger store is unreachable."
$e->getCode();     // 7250
$e->errorCode;     // InvoiceCode::LedgerUnreachable
$e->getPrevious(); // $driverError
```

### 3. Catch by family at the edge

The edge maps families, not components:

| Base | SPL parent | Contract family | HTTP |
|---|---|---|---|
| `ValidationException` | `\DomainException` | `Validation` | 422 |
| `NotFoundException` | `\OutOfBoundsException` | `NotFound` | 404 |
| `ConfigurationException` | `\InvalidArgumentException` | `Configuration` | 500 |
| `ProtocolException` | `\UnexpectedValueException` | `Protocol` | 400 |
| `ContainerException` | `\LogicException` | `ContainerFailure` | 500 |
| `RuntimeException` | `\RuntimeException` | `Runtime` | 503 (retryable) or 500 |

```php
use KaririCode\Contract\Exception\NotFound;
use KaririCode\Contract\Exception\Runtime;
use KaririCode\Contract\Exception\Validation;

try {
    $handler->handle($request);
} catch (Validation $e) {
    return $problem(422, $e);
} catch (NotFound $e) {
    return $problem(404, $e);
} catch (Runtime $e) {
    return $problem(503, $e);
}
```

The message goes to the logs with a correlation ID; users get a generic text (Spec V4.3.3 §23.6).

### 4. Check the table in your conformance suite

```php
use KaririCode\Exception\ErrorCodeTable;

public function testTheCodeTableFollowsTheRegistry(): void
{
    $table = new ErrorCodeTable(InvoiceCode::class); // throws EX_1500–EX_1504 on a broken rule

    self::assertSame('IV', $table->prefix);
}
```

`ErrorCodeTable` checks that the enum is int-backed, that the prefix is a capital letter followed by one to three capital letters or digits, and that every case lies inside `block()`. Whether your block is free is the registry's job: `CODE-BLOCKS.md` ships in the package, in a stable table format, if your tests want to read it.

## Safe messages

Every family base builds the message through `CarriesErrorCode`:

- `[PREFIX_code] text`: greppable across components.
- C0 and C1 controls, DEL, U+2028/U+2029 and the bidirectional controls become escapes (`\n`, `\u202e`): a newline smuggled into a message cannot start a forged log line (CWE-117), and an override cannot reverse what an operator reads.
- Invalid UTF-8 becomes U+FFFD, so JSON log encoders never fail on a message.
- `self::quote($name)` renders an identifier as a JSON literal capped at 128 characters, for the names that help an operator (a store, a route, a circuit):

```php
public static function unknownStore(string $name): self
{
    return new self(InvoiceCode::InvoiceMissing, sprintf('No store is registered as %s.', self::quote($name)));
}
```

What no code can do for you: keep secrets, personal data and rejected values out of the text. That rule stays with the author (E-047).

## A component-wide catch type

Use a marker interface; an abstract component class would add a third level of inheritance (Spec §18):

```php
use KaririCode\Contract\Exception\ExceptionInterface;

interface InvoiceException extends ExceptionInterface {}

final class NegativeAmountException extends ValidationException implements InvoiceException { /* … */ }
```

A component that must keep its own abstract root class (one extending an SPL exception) can use the trait there instead of a base. Declare the constructor and call the trait's under an alias, as the bases do, so Psalm sees `$errorCode` set:

```php
use KaririCode\Exception\CarriesErrorCode;
use KaririCode\Exception\ErrorCode;

abstract class InvoiceFailure extends \RuntimeException implements ExceptionInterface
{
    use CarriesErrorCode {
        CarriesErrorCode::__construct as private carry;
    }

    public function __construct(ErrorCode $errorCode, string $message, ?\Throwable $previous = null)
    {
        $this->carry($errorCode, $message, $previous);
    }
}
```

## Upgrading from 1.x

2.0.0 replaces the whole 1.x API (25 domain classes, `AbstractException`, `getContext()`); see [UPGRADE.md](UPGRADE.md). 1.x stays installable with `^1.2`.

## Documentation

[docs/README.md](docs/README.md) indexes the specification, the three ADRs and the quality record. [CODE-BLOCKS.md](CODE-BLOCKS.md) is the ecosystem's registry of error-code blocks.

## Development

The package keeps no QA tool in `require-dev`. [KaririCode Devkit](https://github.com/KaririCode-PHP/devkit) (`kcode`) installs the tools pinned in `devkit.php` into `.kcode/` (git-ignored) and runs this repository's own configs (`'root_configs' => true`).

Install `kcode` once (PHP 8.4+):

```bash
wget https://github.com/KaririCode-PHP/devkit/releases/latest/download/kcode.phar
chmod +x kcode.phar
sudo mv kcode.phar /usr/local/bin/kcode
```

From the repository root:

```bash
composer install       # kariricode/contract only
kcode init             # install the pinned toolchain into .kcode/
composer check         # style, PHPStan max, Psalm 1, complexity, both suites, mutation
composer security      # audit of the runtime and the toolchain, secret scan
composer sbom          # CycloneDX 1.5 SBOM
```

## License

[MIT](LICENSE) © Walmir Silva
