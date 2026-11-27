# Error-code blocks of the KaririCode ecosystem

This file is the registry of record for the numeric codes that KaririCode
components return from `getCode()`. KaririCode Spec V4.3.3 §8.4 identifies a
failure by its `kariricode/contract` exception family plus that code, so the
code alone must point to one component. The registry exists because it did
not: the review of the component tarball found seven pairs of components
claiming the same codes (see [Collisions](#collisions)). ADR-002 records the
decision; `tests/Conformance/CodeBlockRegistryTest.php` checks this file in
CI.

The file ships with the package, and the table format below is stable, so a
component's own conformance test may read it from
`vendor/kariricode/exception/CODE-BLOCKS.md`.

## Rules

1. A code has four digits, 1000–9999. A block is a contiguous run of codes;
   blocks never overlap.
2. A component may hold more than one block, always under one prefix. The
   prefix opens its messages, `[RL_8300] …`, and has the shape
   `[A-Z][A-Z0-9]{1,3}`; two packages never share one.
3. A component claims a block with a change to this file, before its first
   coded exception is released. Take the lowest free block that fits; a
   block of 100 codes is the norm.
4. Codes are stable API. A retired code is never reused, and a moved block
   lists the old numbers in the component's upgrade notes.
5. When two claims collide, the block stays with (a) the component that
   already released codes from it; otherwise (b) the component the tarball
   inventory marks as reviewed, over one it does not; otherwise (c) the one
   that throws more codes from it. The other component takes the nearest
   free block of the same size and shifts its codes by a constant offset, so
   its internal layout survives (ADR-002).

Status: **released**: a tagged release throws codes from the block;
**claimed**: the latest reviewed version uses or documents it, not yet
released; **proposed**: assigned by rule 5, applied when the component is
imported; **reserved**: kept free on purpose.

## Blocks

| Block | Prefix | Package | Status | Notes |
|---|---|---|---|---|
| 1000–1499 | AP | kariricode/async-primitives | released | 4.1.0 (AP_1000–AP_1450): retry and fallback, pools, time limits, process signals, configuration; 1300–1399 reserved for runtime detection |
| 1500–1599 | EX | kariricode/exception | released | 2.0.0: mistakes in a code table (`ExceptionCode`) |
| 1600–1699 | SSE | kariricode/sse | released | 4.0.0 (SSE_1600–SSE_1630): event fields, configuration, the output stream, the router normalizer |
| 1700–1799 | HTTP | kariricode/http | released | 4.0.0 (HTTP_1700–HTTP_1766): message arguments, URIs, streams, uploaded files, the SAPI emitter, configuration |
| 1800–1899 | WS | kariricode/websocket | released | 4.0.0 (WS_1800–WS_1870): frames and close payloads from the peer, the opening handshake, messages the application sends, connections and the server socket, configuration, the router normalizer |
| 1900–1999 | GRPC | kariricode/grpc | released | 4.0.0 (GRPC_1900–GRPC_1950): calls ended with a status, values from a client or the transport, length-prefixed messages, configuration, the router normalizer |
| 2000–2499 | EL | kariricode/eventloop | released | 4.2.0 (EL_2000–EL_2450): the loop and fiber suspensions, callback ids, timers, streams, signals, cancellation |
| 2500–2599 | PI | kariricode/property-inspector | released | 3.0.0 (2503–2523); 2.1.0 used 2501–2506 |
| 2600–2699 | GQL | kariricode/graphql | claimed | 4.0.0 import |
| 3000–3049 | AS | kariricode/async | claimed | 4.2.0; prefix proposed |
| 3100–3169 | DI | kariricode/dependency-injection | claimed | 4.0.0; prefix proposed |
| 4000–4099 | QU | kariricode/queue | claimed | 4.0.0; prefix proposed |
| 5000–5099 | RO | kariricode/router | released | 4.1.0 (RO_5000–RO_5052), on the family bases of this package; moved from 5100–5159, codes shift by −100 (router ADR-007) |
| 5100–5199 | DF | kariricode/dataflow | claimed | 6.0.0 (DF_5100–DF_5139) |
| 5200–5399 | ORM | kariricode/orm | claimed | 4.8.1, after retiring 9000–9499; prefix proposed |
| 6000–6199 | DB | kariricode/database | released | 4.4.0 (DB_6010–DB_6121) |
| 6200–6399 | NS | kariricode/nosql | claimed | 4.0.0 |
| 6400–6499 | SC | kariricode/service-client | proposed | from 6000–6099, codes shift by +400 |
| 7000–7099 | TM | kariricode/template | claimed | 4.0.0 (TM_7001–TM_7059) |
| 7100–7199 | CB | kariricode/circuit-breaker | claimed | 4.0.0 |
| 7200–7299 | — | examples, fixtures and documentation | reserved | never thrown by a released package; this package's tests use it |
| 7300–7499 | AZ | kariricode/authorization | claimed | 4.0 |
| 8100–8199 | ED | kariricode/event-dispatcher | claimed | 4.1.0; prefix proposed |
| 8200–8249 | OB | kariricode/observability | claimed | 4.0; prefix proposed |
| 8300–8399 | RL | kariricode/rate-limiter | claimed | 4.0.0; contract ADR-004 |
| 8400–8499 | AU | kariricode/authenticator | claimed | 4.2.0 verification layer (8400–8426); prefix proposed |
| 8500–8599 | PP | kariricode/processor-pipeline | released | 3.0.0 (8501–8530), enforced by its conformance suite; prefix proposed |
| 8600–8699 | CA | kariricode/cache | released | 4.1.0 (8600–8653); prefix proposed |
| 8700–8799 | CD | kariricode/class-discovery | released | 2.0.0 (8701–8740); prefix proposed |
| 8800–8899 | AU | kariricode/authenticator | proposed | login layer, from 8500–8599, codes shift by +300 |
| 8900–8999 | CLI | kariricode/cli | released | 4.1.0 (8900–8942, contract families; ADR-009); moved from 8600–8699 by +300 before release |
| 9000–9099 | CN | kariricode/console | released | 4.6.0 (9001–9007, contract families; ADR-003 and ADR-014); prefix proposed |
| 9100–9199 | I18N | kariricode/i18n | claimed | 4.0.1 (9100–9135) |
| 9200–9299 | TN | kariricode/tenancy | claimed | 4.1.0 (TN_9200–TN_9230) |
| 9300–9399 | FW | kariricode/framework | proposed | from 9000–9099, codes shift by +300 |
| 9400–9499 | RT | kariricode/runtime | proposed | from 9100–9199, codes shift by +300 |
| 9500–9999 | PS | kariricode/parser | proposed | from 9000–9999; passes shift by +500 (lexer 9500–9599 … grammar 9900–9999), see Collisions |

Free blocks of 100 or more codes: 2700–2999, 3200–3999, 4100–4999,
5400–5999, 6500–6999, 7500–8099.

## Collisions

Found in the tarball review of 2026-11-27, over the latest version of each
component. "Moves" applies rule 5.

| Claim | Claimed by | Collides with | Resolution |
|---|---|---|---|
| 5100–5159 | router 4.0.0 (02-ok) | dataflow 6.0.0 (02-ok), DF_5100–DF_5139 | both reviewed, dataflow throws more codes: router moves to 5000–5099 |
| 6000–6099 | service-client 4.0.1 (unmarked) | database 4.4.0 (02-ok) | database is reviewed: service-client moves to 6400–6499 |
| 8500–8599 | authenticator 4.2.0 login layer (02-ok) | processor-pipeline 4.0.0 (01-ok-gh) | both reviewed; processor-pipeline throws 8500–8599, the login layer only reserves it: the login layer moves to 8800–8899 |
| 8600–8699 | cli 4.1.0 (02-ok), CLI_8600–CLI_8642 | cache 4.1.0 (02-ok), 8600–8659 | both reviewed, cache throws more codes: cli moves to 8900–8999 |
| 9000–9099 | framework 4.0.1 (unmarked), FW_9000–FW_9059 | console 4.5.0 (02-ok) | console is reviewed: framework moves to 9300–9399 |
| 9100–9199 | runtime 4.0.5 (unmarked), RT_9100–RT_9130 | i18n 4.0.1 (unmarked), 9100–9135 | neither reviewed, i18n throws more codes: runtime moves to 9400–9499 |
| 9000–9999 | parser 4.1.0 (unmarked) | console, i18n, tenancy, framework, runtime | parser moves to 9500–9999; its five passes keep 100 codes each, and the codes it now keeps in 9500–9999 (9500, 9501, 9999) move inside that layout on import |
| 7000–7299 | authenticator 4.1 drafts | template 4.0.0 | void: authenticator 4.2.0 left the block |
| 8400–8599 | cache 4.1.0 drafts | authenticator 4.2.0 | void: cache 4.1.0 moved to 8600–8699 |
| 9200–9399 | cli 4.0 | tenancy 4.1.0 | void: cli 4.1.0 left the block |
| 9000–9499 | orm up to 4.8.0 | console, i18n, tenancy, cli | void: orm 4.8.1 moved to 5200–5399 |
| 4290–4299 | early rate-limiter notes | none | void: rate-limiter 4.0.0 uses 8300–8399 |
