# MicroPowerManager on Google Cloud Platform (GCP)

Very basic setup on GCP.

- A Kubernetes cluster in Auto Pilot mode
- A dedicated Postgres
- Dedicated Redis cluster
- Basic networking configuration using the `default` network

This is intended to be run in a dedicated GCP Project which only runs MPM.

## Pre-requisites

A Google Cloud Project (GCP) with

- billing enabled
- folllwing API's enabled

```sh
compute.googleapis.com
servicenetworking.googleapis.com
iap.googleapis.com
```

Future

```sh
redis.googleapis.com
```

## Usage

Load the module using

```hcl
source = "../gcp_kubernetes"
```

And populate module input.

For a full example, see `examples/` folder.
