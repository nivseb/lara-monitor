Transactions
============

Transactions are the base of collecting information from your application. A transaction represents a blocking process
in your application. That can be a command, request or job.

Transaction Types
-----------------

Basically Lara-Monitor comes with four types of transactions

- `RequestTransaction`, that represents a transaction that is created from an incoming request
- `OctaneRequestTransaction`, like the `RequestTransaction` but is only used for octane based application
- `CommandTransaction`, that represents a transaction that is created for a command execution
- `JobTransaction`, that represents a transaction that is created for a job that is executed by a queue command

Transaction Lifetime
--------------------

By default, Lara-Monitor collect information for a transaction from laravel application and some events.
The transaction will be started in the booting phase of your application, that allows you to collect information from
that phase. In the server of octane base application, that handle your request, the transaction starts from
the `Laravel\Octane\Events\RequestReceived` event, that is fired by octane. For jobs that are executed from a queue
command, the transaction starts from the `Illuminate\Queue\Events\JobPopped` event.

The transaction will be finished with the terminating of your application. For octane request handling that is handled
by the `Laravel\Octane\Events\RequestHandled` event. And job transactions are finished
`Illuminate\Queue\Events\JobProcessed` event.

> ### Recommended
>
> Register the `LaraMonitorStartServiceProvider` as early as possible
> and the `LaraMonitorEndServiceProvider` as late as possible.
> That collect the most possible information and get you the best insights of your application.

After the transaction is finished, the collected data will be analyzed and send with the configured agent to your
monitoring system.

Basic Spans
-----------

Spans build a representation of different parts in your application. By default the transaction is created with the
three spans `booting` , `run`  and `terminating`. The `booting` span, defines the process from start of the transaction
up to the `booted` phase of your application or no later than the start of the main action (`run` span), that action
for your main code execution. With the `Illuminate\Foundation\Http\Events\RequestHandled`,
`Laravel\Octane\Events\RequestHandled` (for octane), `Illuminate\Queue\Events\JobProcessed` and
`Illuminate\Console\Events\CommandFinished`, the main action will be finished and the `terminating` span starts.
The `terminating` span is finished at the same time with the transaction.

Tracing
-------

Tracing allows you, to track a request over multiple of your systems. For example your web server (nginx,
traefik or other) to your application and also to services that are called by your application. For that Lara-Monitor
supports the [W3C trace context](https://www.w3.org/TR/trace-context/). From incoming requests the `traceparent` header
is used to get a connection to an already started tracing, if no header is found Lara-Monitor creates an own
trace id and use that instead. You can force that own trace id via the config `laral-monitor.ignoreExternalTrace`,
that will ignore all external received `traceparent` header.

For outgoing requests the trace information can be added to the request header with the `TraceParentMiddleware`.
By default, that middleware is added to the guzzle client that is used in the laravel http facade. That can be disabled
by configuration (`laral-monitor.feature.http.traceParent`). But you can use that middleware also with your own
guzzle client instances.

> ### Caution
>
> You should only use tracing in your applications. Don't use `traceparent` header from incoming request that
> are outside your systems and also don't send that header to systems that are not yours.

Manual Usage
------------

If you need the transaction of your own usage you can access that with the `LaraMonitorTransaction` facade. With this
facade you can interact with the current transaction or start a new own. 

Customization
-------------

All parts of the transaction handling can be customized. Each transaction struct must extend the `AbstractTransaction`.
The collector, that collects the data and write it to the struct, must include the `TransactionCollectorContract` and
bind for that interface in the app.
If you only want to change a small detail, its recommend to extend the existing class and change that. For that no
class is final and no private methods are used.
