---
order: 4
---

# Deploy for Production

The MicroPowerManager is distributed as pre-compile Docker images via [DockerHub](https://hub.docker.com/u/enaccess).

For running a self-hosted version of MicroPowerManager multiple options exists as explained in the following.

> [!INFO]
> This page covers deployment related information for MicroPowerManager.
> An installation of MicroPowerManager can be customised using environment variables which is explained in detail [here](environment-variables.md).

## Docker Compose

A working environment running with production containers can be achieved by running:

```sh
docker compose -f docker-compose-prod.yml up
```

## Kubernetes

> [!NOTE]
> This section will be expanded in the future.

A working sample of Kubernetes manifest files that are used to run the [MPM Demo Version](https://demo.micropowermanager.io/#/login) can be found in the `k8s` directory of this repository.
