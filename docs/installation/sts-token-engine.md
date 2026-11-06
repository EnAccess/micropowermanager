---
order: 6
---

# STS Token Engine (nxt-sts)

The **StsMeterVend** plugin generates STS prepayment tokens through [nxt-sts](https://github.com/nxtgrid/nxt-sts), a token engine you run next to MicroPowerManager.
This page explains how to deploy and connect it.
Once it is running, operators set up meters and keys as described in the [STS Meter Vending guide](../integrations-guide/sts-meter-vend-integration.md).

## What nxt-sts is

nxt-sts is a small, stateless service that turns a meter's decoder key and a credit amount into a 20-digit IEC 62055-41 token.
It stores nothing.
MicroPowerManager keeps the decoder keys and sends the key with every token request.
It is published as the container image `ghcr.io/nxtgrid/nxt-sts`, serves HTTP on port `8080`, and exposes a health check at `/actuator/health`.

> [!WARNING]
> nxt-sts has no authentication.
> Anyone who can reach it can generate tokens for any meter whose decoder key they know, and every request carries a decoder key.
> Never expose it to the internet or publish its port on a host.
> Only the MicroPowerManager backend and queue worker should be able to reach it.

## Deploy with Docker Compose

The repository's `docker-compose.yml` includes an `nxt-sts` service.
It has no host port, so it is reachable only from other services on the Compose network, at `http://nxt-sts:8080`.

```bash
docker compose up -d nxt-sts
```

## Deploy to Kubernetes

Both Kubernetes bases include an `nxt-sts` Deployment and a ClusterIP Service on port `8080`, so the engine is reachable inside the cluster at `http://nxt-sts:8080`.

Each base also includes a NetworkPolicy, `nxt-sts-ingress`, that only allows traffic to the engine from the backend pods, and on GKE also from the queue worker pods.

- **GKE Autopilot** enforces NetworkPolicies out of the box.
- **EKS** enforces them only when the VPC CNI add-on runs with network policies enabled (`enableNetworkPolicy: "true"`, VPC CNI 1.14 or later).
  Without it the policy is silently ignored and the engine is open to every pod in the cluster.

If another workload ever needs to call the engine, add its pod label to the NetworkPolicy, otherwise its requests time out.

## Connect MicroPowerManager

Set `STS_API_URL` to the engine's address for **both** the backend and the queue worker.
Payment tokens are issued by the queue worker, and tokens issued from the meter page by the backend.

```bash
STS_API_URL=http://nxt-sts:8080
```

Restart the backend and queue worker after changing it.
See [Environment Variables](environment-variables.md#stsmetervend) for the reference entry.

## Check the connection

1. Enable the **StsMeterVend** plugin in **Settings** → **Configuration** → **Plugins**.
2. Open **STS Meter Vending** → **Overview**.
   The **STS token service** card shows the engine's name and version when the backend can reach it.
   Otherwise it shows the error, for example `STS_API_URL is not configured.` or `STS service unreachable: …`.

The backend reports every engine failure to the user as a `502` error, with the engine's message.

## Decoder keys and APP_KEY

Decoder keys are stored encrypted with the application's `APP_KEY`.
If `APP_KEY` changes, stored keys can no longer be decrypted and STS meters stop vending until their keys are imported again.
Keep `APP_KEY` stable, or list the previous key in `APP_PREVIOUS_KEYS` when rotating it.

## Upgrading nxt-sts

The engine version is pinned by the image tag in `docker-compose.yml` and in the `nxt-sts` manifests of both Kubernetes bases.
To upgrade, change the tag in all three places and redeploy.

The current release only vends keys loaded with base date 2014, the STA algorithm and key expiry 255, and the plugin refuses to import keys with any other profile.
A release that supports more profiles also needs a matching MicroPowerManager update before those keys can be imported.
