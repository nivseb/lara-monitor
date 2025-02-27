Error-Handling
==============

Very important for the monitoring of your application is it, to get information about fails that happen in application.
By default, Lara-Monitor register a reportable callback in your exception handler. If you need you can also collect
errors manual.

Mapping
-------

All exceptions that are thrown and received by the exception handler are collected as unhandled errors. Each error is
connected to the current active span, that allow you to see the concrete action that led to the problem.

In case of `ModelNotFoundException` the ids are collected as additional data. To add additional data to your exception
you only need to implement the `AdditionalErrorDataContract` in that exception class.

Manual Usage
------------

If you handle a thrown exception in your code, you should also collect that exception in the monitoring. That allows
you to see how often handled fails are happen.
To collect errors you can use the LaraMonitorError facade. If you collect an error on this way, and down throw that
error to the user, you should set the `handled` parameter to `true`.

Customization
-------------

If you want to change the default handling or build your own version, you can overwrite the `ErrorCollector` or
build your own collector that implements the `ErrorCollectorContract`. The error collector that is bind for the
`ErrorCollectorContract` in your app is used.
