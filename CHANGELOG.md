# Changelog

## 1.0.1 - 2026-09-20

- Refresh the tested framework and development dependency baseline.
- Publish the PHP SDK maintenance release.

## 1.0.0 - 2026-08-28

- Publish the first stable Symfony SDK on PHP Core 1.x.
- Test supported combinations through PHP 8.5 and PHPUnit 13.
- Report the published SDK version in telemetry.

## 0.2.1

- Use the active `https://apirelio.com` ingestion endpoint by default.

## 0.2.0

- Rebrand the bundle, namespace, extension and commands to Apirelio.
- Require the new `apirelio/php-core` package.
- Replace `tracium` configuration with `apirelio`.

## 0.1.0

- Initial Symfony bundle for PHP 8.2+ and Symfony 6.4, 7.4 and 8.x.
- Automatic request tracking with normalized routes and privacy-safe metadata.
- Customer and application resolver contracts.
- Messenger, synchronous HTTP and local file-buffer transports.
- Fail-safe error handling, retryable worker delivery and manual buffer flush.
- Shared event, privacy and delivery implementation through `apirelio/php-core`.
