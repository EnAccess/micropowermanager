---
order: 6
---

# Building docker image for production

## Backend Prod

From the root directory run

```sh
docker build --platform linux/amd64 -t micropowermanager-laravel-prod -f docker/DockerfileBackendProd .
```

## Frontend Prod

```sh
docker build --platform linux/amd64 -t micropowermanager-ui-prod -f docker/DockerfileFrontendProd .
```

## Docker Compose

A working environment running with production containers can be achieved by running:

```sh
docker compose -f docker-compose-prod.yml up
```
