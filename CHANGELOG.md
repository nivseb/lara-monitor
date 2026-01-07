# Changelog

## [1.1.7](https://github.com/nivseb/lara-monitor/compare/v1.1.6...v1.1.7) (2026-01-07)


### Bug Fixes

* remove debug dumps ([#85](https://github.com/nivseb/lara-monitor/issues/85)) ([9c03542](https://github.com/nivseb/lara-monitor/commit/9c03542ccc4ebb7e1ea9addf43cef89b41249fff))

## [1.1.6](https://github.com/nivseb/lara-monitor/compare/v1.1.5...v1.1.6) (2026-01-01)


### Bug Fixes

* missing database name in query spans ([#82](https://github.com/nivseb/lara-monitor/issues/82)) ([d6db2ee](https://github.com/nivseb/lara-monitor/commit/d6db2eec29de0c30be7e8a79bafb72d2e2aadffd))
* remove debug output ([cf3090a](https://github.com/nivseb/lara-monitor/commit/cf3090a34226b3b89d515f845379c5e4775053e3))

## [1.1.5](https://github.com/nivseb/lara-monitor/compare/v1.1.4...v1.1.5) (2025-11-26)


### Bug Fixes

* correct register order for http events ([#75](https://github.com/nivseb/lara-monitor/issues/75)) ([e0c8390](https://github.com/nivseb/lara-monitor/commit/e0c839027a833f0649b29b75efdc6d06a6aba3c6))
* missing status code and outcome for http spans ([#78](https://github.com/nivseb/lara-monitor/issues/78)) ([174e6e6](https://github.com/nivseb/lara-monitor/commit/174e6e6f723f9c187398d4615b4a9f9018343f42))
* only access trace event id via accessor ([#76](https://github.com/nivseb/lara-monitor/issues/76)) ([c3d006c](https://github.com/nivseb/lara-monitor/commit/c3d006cc78ac23ec29c5bbd82e1a5cf141e9733e))
* only stop span for http events if the feature is enabled ([#80](https://github.com/nivseb/lara-monitor/issues/80)) ([3ccdd73](https://github.com/nivseb/lara-monitor/commit/3ccdd734f32390a6855c8d6c76a77fba817bfa6a))
* only store timestamps to reduce memory usage ([#77](https://github.com/nivseb/lara-monitor/issues/77)) ([3363c5b](https://github.com/nivseb/lara-monitor/commit/3363c5bb431a3d5f958d9607a9d86addb088c992))

## [1.1.4](https://github.com/nivseb/lara-monitor/compare/v1.1.3...v1.1.4) (2025-11-11)


### Bug Fixes

* captureAction should return callback result ([#73](https://github.com/nivseb/lara-monitor/issues/73)) ([2c14baa](https://github.com/nivseb/lara-monitor/commit/2c14baa4779374515b247256913332b2babefc17))

## [1.1.3](https://github.com/nivseb/lara-monitor/compare/v1.1.2...v1.1.3) (2025-11-05)


### Bug Fixes

* dont send dupplicated job information ([#67](https://github.com/nivseb/lara-monitor/issues/67)) ([cb008d1](https://github.com/nivseb/lara-monitor/commit/cb008d1d559652c0c2e35a835cf39c418551e9c8))
* report job transaction after job failed correct ([#72](https://github.com/nivseb/lara-monitor/issues/72)) ([74a32b1](https://github.com/nivseb/lara-monitor/commit/74a32b1e18f16cef6ebbe3d7c2fc1c06c0f17e35))

## [1.1.2](https://github.com/nivseb/lara-monitor/compare/v1.1.1...v1.1.2) (2025-11-05)


### Bug Fixes

* correct broken method annotation in LaraMonitorError facade ([1992984](https://github.com/nivseb/lara-monitor/commit/1992984a47a1cd2a07aeea0905c754048a629869))

## [1.1.1](https://github.com/nivseb/lara-monitor/compare/v1.1.0...v1.1.1) (2025-11-03)


### Bug Fixes

* status codes of http spans is not set ([#62](https://github.com/nivseb/lara-monitor/issues/62)) ([91a4d6f](https://github.com/nivseb/lara-monitor/commit/91a4d6fb6cb320f444e8b091ecb41d1aec3174bf))

## [1.1.0](https://github.com/nivseb/lara-monitor/compare/v1.0.4...v1.1.0) (2025-11-01)


### Features

* add custom context to transaction and error ([9840f32](https://github.com/nivseb/lara-monitor/commit/9840f3275c4076e3d81abe8d62be74a6aa53c218))
* add label to transaction, error and spans ([9840f32](https://github.com/nivseb/lara-monitor/commit/9840f3275c4076e3d81abe8d62be74a6aa53c218))
* collect more information for request transactions ([#60](https://github.com/nivseb/lara-monitor/issues/60)) ([3ffd2b7](https://github.com/nivseb/lara-monitor/commit/3ffd2b7febc97367f4c87ba7a7e8ae84e1ae6ff6))
* extend monitoring for queueing jobs ([#49](https://github.com/nivseb/lara-monitor/issues/49)) ([6129a72](https://github.com/nivseb/lara-monitor/commit/6129a726305b642bbfa53b7bbce38abacd4ab6f7))
* send label for job information on dispatch spans and job transactions ([9840f32](https://github.com/nivseb/lara-monitor/commit/9840f3275c4076e3d81abe8d62be74a6aa53c218))


### Bug Fixes

* broken context for spans without context data ([#61](https://github.com/nivseb/lara-monitor/issues/61)) ([860217d](https://github.com/nivseb/lara-monitor/commit/860217d7374038f892e3445073a5174b3eada81d))
* Don't duplicate slash on root paths ([76f3950](https://github.com/nivseb/lara-monitor/commit/76f3950399bfbfc042392cb28c83d84a4db99095))
* dont report empty name for not matching routes ([#59](https://github.com/nivseb/lara-monitor/issues/59)) ([452f779](https://github.com/nivseb/lara-monitor/commit/452f779c7d09f76647b08d899d803d1a0c26fc21))

## [1.0.4](https://github.com/nivseb/lara-monitor/compare/v1.0.3...v1.0.4) (2025-10-19)


### Bug Fixes

* improve fail handling ([#54](https://github.com/nivseb/lara-monitor/issues/54)) ([fc8e6b5](https://github.com/nivseb/lara-monitor/commit/fc8e6b5e7b7b80135ee21044e3cae75f26869a16))
* use more exact database host detection to handle config with multiple hosts ([#53](https://github.com/nivseb/lara-monitor/issues/53)) ([ab7d966](https://github.com/nivseb/lara-monitor/commit/ab7d9661bdd511fa1e99dbf26299c8f80579f76c))

## [1.0.3](https://github.com/nivseb/lara-monitor/compare/v1.0.2...v1.0.3) (2025-10-16)


### Bug Fixes

* type error on rejected guzzle request ([#51](https://github.com/nivseb/lara-monitor/issues/51)) ([1e1c77a](https://github.com/nivseb/lara-monitor/commit/1e1c77ac1ae227e4144a15f640bd89c54839b1c6))

## [1.0.2](https://github.com/nivseb/lara-monitor/compare/v1.0.1...v1.0.2) (2025-10-13)


### Bug Fixes

* passing application instead of trace parent on job start events to start transaction ([#47](https://github.com/nivseb/lara-monitor/issues/47)) ([f2eb45c](https://github.com/nivseb/lara-monitor/commit/f2eb45ce90ba908d714b6a03888d34adae85526b))

## [1.0.1](https://github.com/nivseb/lara-monitor/compare/v1.0.0...v1.0.1) (2025-10-06)


### Bug Fixes

* stop action in guzzle middleware on reject ([#42](https://github.com/nivseb/lara-monitor/issues/42)) ([4206a86](https://github.com/nivseb/lara-monitor/commit/4206a86a45b7f37c1fe4fada486a81eb2e509ea5))

### Code Refactoring

* remove jasny/persist-sql-query dependency
* increase minimum-stability
* refactor sql span naming

## [1.0.0](https://github.com/nivseb/lara-monitor/compare/v0.8.0...v1.0.0) (2025-05-31)


### ⚠ BREAKING CHANGES

* **correct config to enable trace parent middleware for http client:** change config key to enable trace parent middleware
* Remove compatibility to laravel 10
* **elastic-apm:** change config for elastic apm server from "apmServer" to "baseUrl"
* **elastic-apm:** ElasticFormater and interface changed return type
* **elastic-apm:** Properties and constructor of http span changed

### Features

* Additional data for error now possible ([#26](https://github.com/nivseb/lara-monitor/issues/26)) ([1eeb952](https://github.com/nivseb/lara-monitor/commit/1eeb952d80b6eeb7cebb41617dbd4101e0bc4459))
* **collecting:** create custom span for callback ([#23](https://github.com/nivseb/lara-monitor/issues/23)) ([2dbfca7](https://github.com/nivseb/lara-monitor/commit/2dbfca7a0e8a56e509dd0071a593c8d0c3c9a828))
* **dev:** add SECURITY.md ([#3](https://github.com/nivseb/lara-monitor/issues/3)) ([d1a7f81](https://github.com/nivseb/lara-monitor/commit/d1a7f81c9398c9e28aefa9e3334966253a99463d))
* **elastic-apm:** Add authorization with secret token for apm server ([#39](https://github.com/nivseb/lara-monitor/issues/39)) ([638900f](https://github.com/nivseb/lara-monitor/commit/638900fb0bae618c674dd1bc8486d9871403ed29))
* **elastic-apm:** handle outcome as enum ([453ff85](https://github.com/nivseb/lara-monitor/commit/453ff859a15bddc00c44276bc0c948faf8d4a50f))
* **elastic-apm:** Remove support for service node name ([ce7c3b1](https://github.com/nivseb/lara-monitor/commit/ce7c3b17dfb7f9544fe11ced69f071d816dc1482))
* more information for transactions ([#19](https://github.com/nivseb/lara-monitor/issues/19)) ([d225ee6](https://github.com/nivseb/lara-monitor/commit/d225ee604132e62458ff969a000304acb045aace))
* update laravel supported versions ([b3d26d9](https://github.com/nivseb/lara-monitor/commit/b3d26d98409c2ab162057828192596eb4d805690))


### Bug Fixes

* **collecting:** change request transaction naming to route uri ([0e4e115](https://github.com/nivseb/lara-monitor/commit/0e4e1155f180bdb4fed60db02b24b616b3bbb49c))
* **collecting:** correct stacktrace without filename and classname ([#18](https://github.com/nivseb/lara-monitor/issues/18)) ([962e54f](https://github.com/nivseb/lara-monitor/commit/962e54ffba4b629240876d3cf8c6a5739c86b647)), closes [#9](https://github.com/nivseb/lara-monitor/issues/9)
* **collecting:** error collector need to implement contract ([#24](https://github.com/nivseb/lara-monitor/issues/24)) ([be69f3b](https://github.com/nivseb/lara-monitor/commit/be69f3b876d809bbafd12fdc08a49b8789882b46))
* **collecting:** stop render span at ResponsePrepared event ([31bf969](https://github.com/nivseb/lara-monitor/commit/31bf9690464d2642ccb0081cd2d4ec156a94ffd3))
* **correct config to enable trace parent middleware for http client:** ([#35](https://github.com/nivseb/lara-monitor/issues/35)) ([2eb5ce5](https://github.com/nivseb/lara-monitor/commit/2eb5ce59ca78a8e2d0e9b849df5d384d326df06d))
* **dont collect more spans for already completed parent events:** ([#36](https://github.com/nivseb/lara-monitor/issues/36)) ([0d9acde](https://github.com/nivseb/lara-monitor/commit/0d9acde3af6ec9249a6a9b1669f5fe46299ffe27))
* **dont create trace parent header for completed trace events:** ([#37](https://github.com/nivseb/lara-monitor/issues/37)) ([35a088d](https://github.com/nivseb/lara-monitor/commit/35a088d3f0bac744dd4b1eb5c84968675ba6f7ff))
* **elastic-apm:** correct default config for elastic apm server address ([#22](https://github.com/nivseb/lara-monitor/issues/22)) ([9661269](https://github.com/nivseb/lara-monitor/commit/96612692564109cec19afec0d82c8ca5ae657971))
* **elastic-apm:** correct span destination data ([#15](https://github.com/nivseb/lara-monitor/issues/15)) ([bc9137c](https://github.com/nivseb/lara-monitor/commit/bc9137c22d5e5f7c88b3067782e42e3fbe7a18c4)), closes [#14](https://github.com/nivseb/lara-monitor/issues/14)
* **elastic-apm:** prevent fail if argv is not set in $_SERVER ([#28](https://github.com/nivseb/lara-monitor/issues/28)) ([6e3a1c7](https://github.com/nivseb/lara-monitor/commit/6e3a1c7e99bce24e5d08adfd3cabeea36e85e677))
* fix problem with empty artisan call ([#16](https://github.com/nivseb/lara-monitor/issues/16)) ([ba20fea](https://github.com/nivseb/lara-monitor/commit/ba20feab23475bd650baf44f0e6baf5c97b00d66)), closes [#8](https://github.com/nivseb/lara-monitor/issues/8)
* generate correct feature flag for W3C trace parent ([0c54743](https://github.com/nivseb/lara-monitor/commit/0c547430d2fad81b8bf6a9ba47eefd37d192efba))
* prevent fails if lara-monitor is inactive ([#20](https://github.com/nivseb/lara-monitor/issues/20)) ([5a54573](https://github.com/nivseb/lara-monitor/commit/5a545739e428457f6e8d02b8802e06a720b39e79))


### Code Refactoring

* **elastic-apm:** Change config for server address ([#27](https://github.com/nivseb/lara-monitor/issues/27)) ([d5d211f](https://github.com/nivseb/lara-monitor/commit/d5d211f3390e9607a53245c7db40e9e7a7ae39b0))

## 0.8.0 (2024-12-31)


### Features

* add log processor ([fa261f3](https://github.com/nivseb/lara-monitor/commit/fa261f382c48d8bd2df806b28531a52161714b8c))
* capture data for command transactions ([fa261f3](https://github.com/nivseb/lara-monitor/commit/fa261f382c48d8bd2df806b28531a52161714b8c))
* capture data for database queries ([fa261f3](https://github.com/nivseb/lara-monitor/commit/fa261f382c48d8bd2df806b28531a52161714b8c))
* capture data for http request ([fa261f3](https://github.com/nivseb/lara-monitor/commit/fa261f382c48d8bd2df806b28531a52161714b8c))
* capture data for job transactions ([fa261f3](https://github.com/nivseb/lara-monitor/commit/fa261f382c48d8bd2df806b28531a52161714b8c))
* capture data for request transactions ([fa261f3](https://github.com/nivseb/lara-monitor/commit/fa261f382c48d8bd2df806b28531a52161714b8c))
* create apm agent lara-monitor ([fa261f3](https://github.com/nivseb/lara-monitor/commit/fa261f382c48d8bd2df806b28531a52161714b8c))
* send data to elastic apm server ([fa261f3](https://github.com/nivseb/lara-monitor/commit/fa261f382c48d8bd2df806b28531a52161714b8c))
