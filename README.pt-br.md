# KaririCode\Exception

**Classes-base opcionais para as famílias de exceção do kariricode/contract: uma base abstrata por família, códigos de erro como enums dentro de blocos registrados e mensagens seguras para log.**

[![en](https://img.shields.io/badge/lang-en-red.svg)](README.md) [![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](README.pt-br.md)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![ARFA](https://img.shields.io/badge/ARFA-1.3%20level%201-orange)](docs/spec/SPEC-001-exception.md)
[![MSI](https://img.shields.io/badge/MSI-100%25-brightgreen)](docs/quality/QD-V433-COMPLIANCE.md)

Parte do ecossistema [KaririCode Framework](https://kariricode.org).

## Por quê

A KaririCode Spec V4.3.3 §8.4 pede que cada componente lance as próprias exceções, cada uma implementando uma das seis famílias do `kariricode/contract`, com um código numérico de um bloco que pertence ao componente. Os componentes fizeram isso à mão, cada um de um jeito: qual classe SPL estender, como marcar a mensagem com o código, como manter o valor rejeitado fora dela, como garantir que os códigos fiquem dentro do bloco. E ninguém manteve os blocos separados: sete pares de componentes disputavam os mesmos códigos.

Este pacote faz a parte repetida uma vez só:

| Peça | O que você recebe |
|---|---|
| Bases por família | `ValidationException`, `NotFoundException`, `ConfigurationException`, `ProtocolException`, `ContainerException`, `RuntimeException`: cada uma estende a classe SPL que o contract sugere e implementa só aquela família |
| Códigos | um enum com valores inteiros por componente (`ErrorCode`); o PHP recusa valores repetidos, e o `ErrorCodeTable` confere prefixo e bloco no seu teste de conformidade |
| Mensagens | `[IV_7250] The ledger store is unreachable.`, com caracteres de controle, separadores de linha e controles bidirecionais escapados e UTF-8 inválido substituído, para que nenhum valor forje uma linha de log |
| Registro | [`CODE-BLOCKS.md`](CODE-BLOCKS.md): o bloco de cada componente, conferido no CI e distribuído com o pacote |

Depende só do PHP 8.4 e do `kariricode/contract`, então qualquer componente pode usá-lo sem criar ciclo. O uso é opcional: implementar as famílias do contract nas suas próprias classes continua conforme.

## Instalação

```bash
composer require kariricode/exception
```

## Uso

### 1. Declare seus códigos

Reserve um bloco no [`CODE-BLOCKS.md`](CODE-BLOCKS.md) e liste os códigos num enum:

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

Os exemplos usam 7200–7299, o bloco que o registro reserva para documentação.

### 2. Uma classe final por falha

Estenda a base da família certa e construa a exceção com construtores nomeados. O texto diz qual regra foi quebrada e nunca repete o valor rejeitado (errata E-047). As mensagens ficam em inglês, como o resto do código.

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

### 3. Capture por família na borda

A borda mapeia famílias, não componentes:

| Base | Pai SPL | Família do contract | HTTP |
|---|---|---|---|
| `ValidationException` | `\DomainException` | `Validation` | 422 |
| `NotFoundException` | `\OutOfBoundsException` | `NotFound` | 404 |
| `ConfigurationException` | `\InvalidArgumentException` | `Configuration` | 500 |
| `ProtocolException` | `\UnexpectedValueException` | `Protocol` | 400 |
| `ContainerException` | `\LogicException` | `ContainerFailure` | 500 |
| `RuntimeException` | `\RuntimeException` | `Runtime` | 503 (pode repetir) ou 500 |

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

A mensagem vai para os logs com um ID de correlação; o usuário recebe um texto genérico (Spec V4.3.3 §23.6).

### 4. Confira a tabela na sua suíte de conformidade

```php
use KaririCode\Exception\ErrorCodeTable;

public function testTheCodeTableFollowsTheRegistry(): void
{
    $table = new ErrorCodeTable(InvoiceCode::class); // lança EX_1500–EX_1504 se uma regra falhar

    self::assertSame('IV', $table->prefix);
}
```

O `ErrorCodeTable` confere que o enum tem valores inteiros, que o prefixo é uma letra maiúscula seguida de uma a três letras maiúsculas ou dígitos e que todo caso cai dentro de `block()`. Se o seu bloco está livre é assunto do registro: o `CODE-BLOCKS.md` vem no pacote, num formato de tabela estável, caso seus testes queiram lê-lo.

## Mensagens seguras

Toda base de família monta a mensagem pelo `CarriesErrorCode`:

- `[PREFIXO_código] texto`: dá para buscar com grep em todos os componentes.
- Controles C0 e C1, DEL, U+2028/U+2029 e os controles bidirecionais viram escapes (`\n`, `\u202e`): uma quebra de linha infiltrada não inicia uma linha de log forjada (CWE-117), e um override não inverte o que o operador lê.
- UTF-8 inválido vira U+FFFD, então codificadores de log em JSON nunca falham por causa de uma mensagem.
- `self::quote($name)` mostra um identificador como literal JSON de até 128 caracteres, para os nomes que ajudam o operador (um store, uma rota, um circuito):

```php
public static function unknownStore(string $name): self
{
    return new self(InvoiceCode::InvoiceMissing, sprintf('No store is registered as %s.', self::quote($name)));
}
```

O que nenhum código faz por você: manter segredos, dados pessoais e valores rejeitados fora do texto. Essa regra continua com quem escreve (E-047).

## Um tipo de captura para o componente inteiro

Use uma interface marcadora; uma classe abstrata do componente adicionaria um terceiro nível de herança (Spec §18):

```php
use KaririCode\Contract\Exception\ExceptionInterface;

interface InvoiceException extends ExceptionInterface {}

final class NegativeAmountException extends ValidationException implements InvoiceException { /* … */ }
```

Um componente que precise manter a própria classe raiz abstrata (que estende uma exceção SPL) pode usar o trait nela em vez de uma base. Declare o construtor e chame o do trait por um alias, como as bases fazem, para o Psalm ver `$errorCode` preenchido:

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

## Atualizando da 1.x

A 2.0.0 substitui toda a API da 1.x (25 classes de domínio, `AbstractException`, `getContext()`); veja o [UPGRADE.md](UPGRADE.md). A 1.x continua instalável com `^1.2`.

## Documentação

O [docs/README.md](docs/README.md) indexa a especificação, as três ADRs e o registro de qualidade. O [CODE-BLOCKS.md](CODE-BLOCKS.md) é o registro de blocos de códigos de erro do ecossistema.

## Desenvolvimento

O pacote não mantém ferramentas de QA no `require-dev`. O [KaririCode Devkit](https://github.com/KaririCode-PHP/devkit) (`kcode`) instala as ferramentas fixadas no `devkit.php` em `.kcode/` (ignorado pelo git) e roda as configs deste repositório (`'root_configs' => true`).

Instale o `kcode` uma vez (PHP 8.4+):

```bash
wget https://github.com/KaririCode-PHP/devkit/releases/latest/download/kcode.phar
chmod +x kcode.phar
sudo mv kcode.phar /usr/local/bin/kcode
```

Na raiz do repositório:

```bash
composer install       # só o kariricode/contract
kcode init             # instala o toolchain fixado em .kcode/
composer check         # estilo, PHPStan max, Psalm 1, complexidade, as duas suítes, mutação
composer security      # auditoria do runtime e do toolchain, busca de segredos
composer sbom          # SBOM CycloneDX 1.5
```

## Licença

[MIT](LICENSE) © Walmir Silva
