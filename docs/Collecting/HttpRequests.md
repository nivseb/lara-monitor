Http Request
============

If you make a request from your application, you can collect that information. For this you have two options you can
use the laravel http facade with the config `lara-monitor.feature.http.collecting.events`, the events from laravel
are used to collect the information. If you use the guzzle client direct, you can add
the middleware `CollectingMiddleware` to your client.

If you want to use tracing for that request, is there also a middleware, [read more here](./Transactions.md#tracing).

Customization
-------------

You can write your own `startHttpAction` for the `SpanCollector` to customize the collecting data.
