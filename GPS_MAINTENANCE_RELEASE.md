# GPS shared-library maintenance release — 20 November 2026

This branch reconciles the seven locally patched GPS library files on DEV and
production. It starts at deployed `fogito/core` 1.8.17, commit
`d5b59a4fecbdd9360980131a33e26c7acd218c98`, and keeps PHP 7.3 compatibility.
It is a scoped GPS release, not a replacement for the PHP 8.1-only core 2.x line.
No Composer-wide update or global package publication is part of this release.

## Behavior

- Authentication accepts existing native-array and JSON-string cache entries.
  Invalid cached payloads are refreshed once; failures remain denied. Cache keys
  and expiry periods are retained, and new entries use JSON.
- Permission checks retain selected-ID matching and return contracts, guard
  missing values, and avoid recursive permission validation.
- Requests preserve parameter precedence and values containing equals signs.
  Scalar or invalid JSON bodies do not generate array/iteration warnings.
- Outbound service URLs default to HTTPS, retaining explicitly requested HTTP.
  Existing fixed environment domains remain. TLS peer/host verification and the
  DEV environment header are used consistently on both servers.
- Remote calls handle omitted options, malformed responses and missing response
  fields safely. Named and legacy positional filters retain their behavior.
- Company filters retain scoping for missing, false, null and empty values;
  optional model filters are normalized once before merging.
- MongoDB timestamps use integer milliseconds while preserving the existing
  seconds/milliseconds input contract. User mapping and namespaced controller
  strings include the compatible upstream corrections.

Relevant upstream corrections were reviewed from `1ca6ec7`, `ee4b7e6`,
`f2a4c4f`, `98ab49f` and `2078c06`. New 2.x query APIs, pooling, bulk operations,
transport hooks and domain/on-premise routing were deliberately not backported.

## Verification and deployment

Focused suites run without real HTTP/database mutations:

```sh
php tests/gps-auth-request.php
php tests/gps-model-compatibility.php
php tests/gps-remote-config.php
```

The model checks require the MongoDB BSON extension. Run all suites on the
target PHP versions and run the GPS isolated framework/history integration tests
with this candidate library selected by the autoloader. Verify real HTTPS
connectivity from both servers before activating certificate verification.

Deploy only the seven reviewed `src/` files listed by the GPS backend's
`runtime-libraries/fogito-core.json` manifest. Guard the entire installed package
and environment configuration against concurrent changes; retain file backups
and verify the exact installed hashes. The GPS workspace records environment
receipts and ownership. Do not overwrite the remaining package, vendor metadata,
application code, settings or scheduling configuration.

Rollback needs cache-format awareness: an older production Auth implementation
does not accept JSON entries written by this release. Before restoring it,
review affected cache expiry periods or retain the compatible cache reader until
those entries expire; do not flush unrelated shared caches. Source rollback must
first verify the installed release hashes. No tracking-data migration is involved.
