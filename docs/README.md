# kariricode/exception — Documentation

Index of the design records for version 2.0.0. The specification states
what the package contains and promises, the ADRs state why, and the quality
record states what has been verified.

| Document | Kind | Subject |
|---|---|---|
| [SPEC-001](spec/SPEC-001-exception.md) | Specification | public API, message format, `ErrorCodeTable` rules, invariants, use in a component |
| [ADR-001](adr/ADR-001-family-bases-over-the-contract.md) | Decision | optional abstract bases per contract family, codes as enums, safe messages, no context bag |
| [ADR-002](adr/ADR-002-code-block-registry.md) | Decision | `CODE-BLOCKS.md` as the registry of record; resolution rule; the seven moves |
| [ADR-003](adr/ADR-003-toolchain-through-kcode.md) | Decision | Spec V4.3.3 gates through KaririCode Devkit with the root configs |
| [QD-V433-COMPLIANCE](quality/QD-V433-COMPLIANCE.md) | Quality record | gate results and the evidence behind them |
| [CODE-BLOCKS.md](../CODE-BLOCKS.md) | Registry | every component's block of error codes |
| [UPGRADE.md](../UPGRADE.md) | Guide | from 1.x to 2.0 |

## Architecture at a glance

```
 kariricode/contract    ExceptionInterface ─┬─ Validation ─ NotFound ─ Configuration
 (interfaces only)                          └─ Protocol ─ ContainerFailure ─ Runtime
          ▲ implements
 kariricode/exception   ValidationException … RuntimeException   (abstract, SPL parent + 1 family)
                        CarriesErrorCode    [PREFIX_code] tag, neutralised text, quote()
                        ErrorCode · CodeBlock · ErrorCodeTable    codes as enums, checked blocks
          ▲ extends (optional)
 component              <Component>Code enum + one final class per failure
```
