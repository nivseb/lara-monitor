Database Queries
================

The laravel `QueryExecuted` event is used to collect information about execute database queries. The information are
collected in a `QuerySpan`. The span includes the query, connection information and bindings.

This feature can be enabled or disabled via config `laral-monitor.feature.database.enabled`.

Customization
-------------

You can write your own `trackDatabaseQuery` for the `SpanCollector` to customize the collecting data.
