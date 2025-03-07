Lara-Monitor
============

[![Tests](https://img.shields.io/github/actions/workflow/status/nivseb/lara-monitor/test.yml?branch=main&label=Tests)](https://github.com/nivseb/lara-monitor/actions/workflows/tests.yml)
[![Supported PHP Version](https://badgen.net/packagist/php/nivseb/lara-monitor?color=8892bf)](https://www.php.net/supported-versions)
[![Latest Stable Version](https://poser.pugx.org/nivseb/lara-monitor/v/stable.svg)](https://packagist.org/packages/nivseb/lara-monitor)
[![Total Downloads](https://poser.pugx.org/nivseb/lara-monitor/downloads.svg)](https://packagist.org/packages/nivseb/lara-monitor)

Lara-Monitor is apm agent package for application that are build with [Laravel Framework](https://laravel.com). The
collected data are send to your [Elastic APM Server](https://github.com/elastic/apm-server). That allow you an impressive
look to your application via kibana.  This package make it easy to connect all needed information from your application
in [Elastic Kibana](https://www.elastic.co/de/kibana).

Feature
-------

This package was developed primarily for container-based environments that use [Laravel Octane](https://laravel.com/docs/master/octane).
But it works also fine in over environments like plain laravel application or hostet plain on servers.

### Transactions

As default requests, commands and jobs are supported transactions. That allows you to get logging for all kinds of transaction,
that are possible in your application.

### Spans

To get a good overview, the booting phase, terminating phase and the time between are send as spans. That allow to get
a good overview, at what time what is executed and how long the respective phase is.

Of course tasks like database queries, http request, sync job handling, sync command calls also send as span
with further information will send to the [Elastic APM Server](https://github.com/elastic/apm-server).

### Error-Handling

Lara-Monitor register itself to the exception handler and report all exceptions to the [Elastic APM Server](https://github.com/elastic/apm-server).
You also can easily capture handled and unhandled exception, that should send to the [Elastic APM Server](https://github.com/elastic/apm-server),
that allows you to see all errors in [Elastic Kibana](https://www.elastic.co/de/kibana) you want.

### Logging

Lara-Monitor comes with a log processor, that add context data to your log entry. If you use [Elastic Filebeat](https://www.elastic.co/de/beats/filebeat)
to get your log files also to [Elastic Kibana](https://www.elastic.co/de/kibana), it is easy with this processor to
connect the log entries with your transaction and trace data.

### External trace context

The [W3C trace context](https://www.w3.org/TR/trace-context/) is supported for both incoming and outgoing requests.
If no trace is given by incoming request, a new strace is startet. On outgoing request, the `traceparent` header
can add via middleware. That allows you to see a request in [Elastic Kibana](https://www.elastic.co/de/kibana),
can therefore be viewed comprehensively, making it easier to find correlations between your applications.

### Octane Support

The request handling in application that use [Laravel Octane](https://laravel.com/docs/master/octane), is a little different
to normal laravel request handling. This makes it necessary to collect data for the apm on another way, to get a good look
at the performance monitoring.

Only the task worker are not supported. Everything that is done in a task worker will not see in the collected data.

### Customizability

All parts are only connected via interfaces, that allows you easily to customize the agent for your requirements.
Overwrite the part of the agent, that need to be changed and register your variant.
If you think that this also help others, create a pull request and make it possible for everyone to get advantage of it.

Installation
------------

1. To install PHP Mock Server Connector you can easily use composer.

    ```sh
    composer require nivseb/lara-monitor
    ```

2. Add `Nivseb\LaraMonitor\Providers\LaraMonitorStartServiceProvider` to your application
3. Add `Nivseb\LaraMonitor\Providers\LaraMonitorEndServiceProvider` to your application
4. Publish and change config or add needed environment variables

The provider should be added manuale to the loaded service provider. This allows you to get the best information from
the tracking. The `LaraMonitorStartServiceProvider` Should be loaded as early as possible and the `LaraMonitorEndServiceProvider`
should be loaded as late as possible. That include as much as possible other event listeners to monitored for the spans.

Configuration
-------------

Lara-Monitor comes with an own config, you can publish it with the following command.

```sh
php artisan vendor:publish --tag=lara-monitor-config
```

The most configs are set by default laravel environment variables (e.g. `APP_NAME` or `APP_ENV`).
For a full documentation see the [Configuration](docs/Configuration.md).
