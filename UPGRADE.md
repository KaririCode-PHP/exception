# Upgrading from 1.x to 2.0

2.0 is a different design, so there is no drop-in path. 1.x shipped final
exceptions per domain; 2.0 ships abstract bases per `kariricode/contract`
family, and each component writes its own exceptions with its own codes.
Nothing forces the move: `"kariricode/exception": "^1.2"` keeps working.

## Steps

1. Require `kariricode/exception` `^2.0` and `kariricode/contract` `^2.11`
   (PHP 8.4).
2. Claim a block for your component in [CODE-BLOCKS.md](CODE-BLOCKS.md) and
   declare an int-backed enum implementing `ErrorCode`.
3. For each 1.x class you threw, write one final class in your component
   that extends the family base below, with named constructors whose text
   states the rule and never the rejected value.
4. Replace `getContext()` reads with typed properties on your class, filled
   in the named constructor; or drop them, if they only carried what the
   message or `previous` already says.
5. Replace `getErrorCode()` (the 1.x string such as `DB_QUERY_ERROR`) with
   `$exception->errorCode`, the enum case.
6. Catch by contract family at the edge, not by class.
7. Add `new ErrorCodeTable(YourCode::class)` to your conformance suite.

## Class map

| 1.x | 2.0 base to extend | Notes |
|---|---|---|
| `Validation\ValidationException`, `Validation\RuleViolationException`, `Input\InputException` | `ValidationException` | a component that collects several errors returns a result object (Spec §8.1) |
| `Config\ConfigurationException` | `ConfigurationException` | |
| `Container\ContainerException` | `ContainerException`, or `NotFoundException` for an unbound identifier | |
| `Routing\RoutingException` | `NotFoundException` (no route), `ProtocolException` (malformed request) | |
| `Network\HttpException`, `Network\NetworkException`, `ExternalService\ExternalServiceException` | `RuntimeException` (peer down or slow), `ProtocolException` (malformed response) | the HTTP status of a peer is data for the message or a typed property |
| `Database\DatabaseException`, `Cache\CacheException`, `Queue\QueueException`, `File\FileException`, `Session\SessionException` | `RuntimeException` | chain the driver's exception as `previous` |
| `Auth\AuthenticationException`, `Auth\AuthorizationException`, `Security\SecurityException` | none by default | authentication and authorization outcomes are result objects that fail closed (Spec §8.2, §8.3); throw only for faults, as `RuntimeException` or `ConfigurationException` |
| `Security\EncryptionException` | `RuntimeException` or `ConfigurationException` (missing key) | |
| `Template\TemplateException`, `Localization\LocalizationException`, `Middleware\MiddlewareException`, `Event\EventException`, `System\SystemException`, `Runtime\RuntimeException` | the family the failure belongs to | |
| `AbstractException`, `ExceptionMessage`, `Contract\ErrorMessage`, `Contract\Throwable` | removed | `CarriesErrorCode` and `ErrorCode` replace them |

## Codes

1.x codes (1001–3102) overlap the blocks of async-primitives and eventloop
and are not carried over. Your component's codes come from its own block.
