Elastic APM Agent
=================

Lara-Monitor is build to support different systems as target for the collected data. For each monitoring system
an agent is build to send the data in the needed format. For [Elastic Kibana](https://www.elastic.co/de/kibana),
the `ElasticAgent` is added. As all agents the collected data are send at the end of the terminating app phase to
the [Elastic APM Server](https://github.com/elastic/apm-server).
To use that agent, you on only need to set the two configurations `lara-monitor.elasticApm.enabled` and
`lara-monitor.elasticApm.baseUrl`. By default, both configurations set from the environment variable `ELASTIC_APM_URL`.

Customization
-------------

You are free to add your own agent, or customize the existing one. Your agent need to be implement
the interface `ApmAgentContract` and register for that interface in the app.
