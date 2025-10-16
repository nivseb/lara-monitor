# Changelog

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
