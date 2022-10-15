# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.3.1] - 2022-10-15
### Fixed
 - added Composer plugin to **composer.json** to fix CI.
 - FIX sessionID attribute case format: [PR #4](https://github.com/AymDev/MessengerAzureBundle/pull/4)

## [1.3.0] - 2022-06-18
### Changed
 - Generate SAS tokens per request to avoid authentication failures: [PR #1](https://github.com/AymDev/MessengerAzureBundle/pull/1)

## [1.2.0] - 2022-02-28
### Added
 - Converts the `MessageDecodingFailedException` to a new `SerializerDecodingException` containing an envelope with an empty message for logging purposes.

### Fixed
 - Deletes messages for consumers when the serializer throws a `MessageDecodingFailedException` to avoid retying them forever.

## [1.1.1] - 2022-02-15
### Fixed
 - **AzureBrokerPropertiesStamp** **DateTime** properties timezones are now set to the current default timezone.

## [1.1.0] - 2022-02-14
### Added
 - **AzureMessageStamp** stamp for sent/received messages with *queue*/*topic* name, message, subscription name and delete location

### Deprecated
 - **AzureReceivedStamp** in favor of the new **AzureMessageStamp**

## [1.0.0] - 2022-02-10
### Added
 - **Symfony Messenger** transport for **Azure Service Bus** *queues* and *topics*

[Unreleased]: https://github.com/AymDev/MessengerAzureBundle/compare/v1.3.1...HEAD
[1.3.1]: https://github.com/AymDev/MessengerAzureBundle/releases/tag/v1.3.1
[1.3.0]: https://github.com/AymDev/MessengerAzureBundle/releases/tag/v1.3.0
[1.2.0]: https://github.com/AymDev/MessengerAzureBundle/releases/tag/v1.2.0
[1.1.1]: https://github.com/AymDev/MessengerAzureBundle/releases/tag/v1.1.1
[1.1.0]: https://github.com/AymDev/MessengerAzureBundle/releases/tag/v1.1.0
[1.0.0]: https://github.com/AymDev/MessengerAzureBundle/releases/tag/v1.0.0

