# Security Policy

## Supported versions

| Version | Supported |
|---|---|
| 2.x | yes |
| 1.x | no: superseded by 2.x (see UPGRADE.md) |

## Reporting a vulnerability

Report it privately through GitHub's
[security advisories](https://github.com/KaririCode-PHP/exception/security/advisories/new),
or by e-mail to walmir.silva@kariricode.org. Please do not open a public
issue.

## Scope

The package builds exception messages. Its security properties are those
of SPEC-001 §2.2: control characters, line separators and bidirectional
controls are escaped and invalid UTF-8 is replaced, so text placed in a
message cannot forge a log line (CWE-117) or break a JSON log encoder. A
way around that filter is in scope.

Out of scope, by design: secrets, personal data or rejected values that a
component writes into its own message text. That is a rule for the author
(KaririCode Spec V4.3.3, errata E-047), not something code can detect.
