Logging
=======

If you want to connect your log entries with your collected data, for example in
[Elastic Kibana](https://www.elastic.co/de/kibana), you can use the `LogDataProcessor` from Lara-Monitor.
That Processor is an extension for a Monolog logger, that add additional data to `extra` in the log record.

Elastic APM
-----------

In case you use [Elastic Kibana](https://www.elastic.co/de/kibana) to monitor your application, you can process the
log file with filebeat. If you use the json format for your log file, you only need a small ingest pipeline definition
to get the collected data and the logging information together.

### Example for Ingest Pipeline Processors

```json
[
    {
        "rename": {
            "field": "message",
            "target_field": "event.original"
        }
    },
    {
        "json": {
            "field": "event.original",
            "target_field": "temp"
        }
    },
    {
        "rename": {
            "field": "@timestamp",
            "target_field": "event.created"
        }
    },
    {
        "rename": {
            "field": "temp.datetime",
            "target_field": "@timestamp",
            "ignore_missing": true
        }
    },
    {
        "rename": {
            "field": "temp.message",
            "target_field": "message"
        }
    },
    {
        "rename": {
            "field": "temp.extra.trace",
            "target_field": "trace",
            "ignore_missing": true
        }
    },
    {
        "rename": {
            "field": "temp.extra.transaction",
            "target_field": "transaction",
            "ignore_missing": true
        }
    },
    {
        "rename": {
            "field": "temp.extra.span",
            "target_field": "span",
            "ignore_missing": true
        }
    },
    {
        "rename": {
            "field": "temp.extra.service",
            "target_field": "service",
            "ignore_missing": true
        }
    }
]
```
