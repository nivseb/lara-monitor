Redis Commands Queries
================

The laravel `CommandExecuted` event is used to collect information about execute redis command. The information are
collected in a `RedisCommandSpan`. The span includes the command and connection information.

This feature can be enabled or disabled via config `laral-monitor.feature.enabled.enabled`.
To enable that feature you also need to enable the redis events (`Redis::enableEvents()`).

Customization
-------------

You can write your own `buildRedisSpanFromExecuteEvent` for the `SpanCollector` to customize the collecting data.
