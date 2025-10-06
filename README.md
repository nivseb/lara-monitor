Lara-Monitor
============

[![Tests](https://img.shields.io/github/actions/workflow/status/nivseb/lara-monitor/test.yml?branch=main&label=Tests)](https://github.com/nivseb/lara-monitor/actions/workflows/tests.yml)
[![Supported PHP Version](https://badgen.net/packagist/php/nivseb/lara-monitor?color=8892bf)](https://www.php.net/supported-versions)
[![Latest Stable Version](https://poser.pugx.org/nivseb/lara-monitor/v/stable.svg)](https://packagist.org/packages/nivseb/lara-monitor)
[![Total Downloads](https://poser.pugx.org/nivseb/lara-monitor/downloads.svg)](https://packagist.org/packages/nivseb/lara-monitor)

Lara-Monitor is apm agent package for application that are build with [Laravel Framework](https://laravel.com). The
collected data are send to your [Elastic APM Server](https://github.com/elastic/apm-server). That allow you an
impressive
look to your application via kibana. This package make it easy to connect all needed information from your application
in [Elastic Kibana](https://www.elastic.co/de/kibana).
This package was developed primarily for container-based environments that
use [Laravel Octane](https://laravel.com/docs/master/octane).
But it works fine in over environments like plain laravel application as docker container or hostet plain on servers.

Lara-Monitor collect information directly from laravel events and callbacks. That allows a very easy integration,
but Lara-Monitor is also designed to be especially customizable, so that you can build that monitoring that is the
best for your application.

Feature
-------

Read more about the collecting process:

- [Recording Transactions from request, commands and jobs](./docs/Collecting/Transactions.md)
- [Collect information about exceptions and errors](./docs/Collecting/Errors.md)
- [Collect database queries from your application](./docs/Collecting/DatabaseQueries.md)
- [Collect http request information against a third-party system](./docs/Collecting/HttpRequests.md)
- [Collect redis commands from your application](./docs/Collecting/RedisCommands.md)
- [Collect information from your rendering processes](./docs/Collecting/Rendering.md)
- [Extend Logging](./docs/Collecting/Logging.md)

At the moment the only agent that is supported, sends the collected data to the
[Elastic APM Server](https://github.com/elastic/apm-server). In the feature it would be extended with other agents,
or you can build your ownagent. [Read more about the elastic agent](./docs/Agents/ElasticApm.md).

### Octane Support

The request handling in application that use [Laravel Octane](https://laravel.com/docs/master/octane), is a little
different
to normal laravel request handling. This makes it necessary to collect data for the apm on another way, to get a good
look
at the performance monitoring.

Only the task worker are not supported. Everything that is done in a task worker will not see in the collected data.

### Customizability

All parts are only connected via interfaces, that allows you easily to customize the agent for your requirements.
Overwrite the part of the agent, that need to be changed and register your variant.
If you think that this also help others, create a pull request and make it possible for everyone to get advantage of it.

Installation
------------

1. To install Lara-Monitor you can easily use composer.

    ```sh
    composer require nivseb/lara-monitor
    ```

2. Add `Nivseb\LaraMonitor\Providers\LaraMonitorStartServiceProvider` to your application
3. Add `Nivseb\LaraMonitor\Providers\LaraMonitorEndServiceProvider` to your application
4. Publish and change config or add needed environment variables

The provider should be added manuale to the loaded service provider. This allows you to get the best information from
the tracking. The `LaraMonitorStartServiceProvider` Should be loaded as early as possible and the
`LaraMonitorEndServiceProvider`
should be loaded as late as possible. That include as much as possible other event listeners to monitored for the spans.

Configuration
-------------

Lara-Monitor comes with an own config, you can publish it with the following command.

```sh
php artisan vendor:publish --tag=lara-monitor-config
```

The most configs are set by default laravel environment variables (e.g. `APP_NAME` or `APP_ENV`).
For a full documentation see the [Configuration](docs/Configuration.md).
