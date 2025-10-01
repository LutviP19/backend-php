# Open Swoole Dashboard

This is the example repo of using Open Swoole Metrics in `v4.9.0` to bootstrap an [Open Swoole Dashboard](https://openswoole.com/dashboard) with [Metrics in Open Swoole](https://openswoole.com/docs/modules/swoole-server-stats), [Grafana](https://grafana.com/) and [Prometheus](https://prometheus.io/).

Docker and Docker Compose are requried.

### Original Github Source Code
[Github Open Swoole Dashboard](https://github.com/openswoole/dashboard)


### Open Swoole Metrics

Find /metrics output apiserver at `http://127.0.0.1:8080/metrics`

Find /metrics output httpserver at `http://127.0.0.1:9501/metrics`


### Prometheus UI

Find Prometheus server at `http://127.0.0.1:9090/`

<img width="400" alt="Open Swoole Metrics" src="https://user-images.githubusercontent.com/313478/146695579-78be99a2-5fad-4c25-a70a-97319133e921.png">

### Grafana Dashboard

1. Find `http://127.0.0.1:3000/login`, the default username and password are `openswoole:openswoole`.
2. Import Open Swoole Dashboard at `http://127.0.0.1:3000/dashboard/import`, enter ID `15418` and hit load button.
3. Send some traffic with wrk using `wrk -t4 -c16 -d5 --latency http://127.0.0.1:9501/`
4. Have fun with OpenSwoole Dashboard

<img width="400" alt="Open Swoole Dashboard" src="https://user-images.githubusercontent.com/313478/146695592-5500860d-59d2-4583-8a3b-1b08e0f98c7f.png">



