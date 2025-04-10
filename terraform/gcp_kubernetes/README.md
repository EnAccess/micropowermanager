# MicroPowerManager on Google Cloud Platform

Basic setup on Google Cloud Platform (GCP).

- A Kubernetes cluster in [Autopilot](https://cloud.google.com/kubernetes-engine/docs/concepts/autopilot-overview) mode
- A dedicated MySQL database
- In-cluster Redis cluster
- Basic networking configuration using the GCP `default` network

This is intended to be run in a dedicated GCP Project which only runs MicroPowerManager.

## Pre-requisites

The main requisites to use the Terraform module is a dedicated GCP project which is provisioned.

A few manual steps have to be configured prior to using this Terraform module.

1. [Billing](https://cloud.google.com/billing/docs/concepts) has to be enabled and a billing account with valid payment details has to be linked to the project.

2. The folllwing API's have to be enabled on project level

   ```sh
   compute.googleapis.com
   servicenetworking.googleapis.com
   iap.googleapis.com
   ```

   Future

   ```sh
   redis.googleapis.com
   ```

3. Using GKE Autopilot required `Persistent Disk SSD (GB)` quota of at least 1000 GB.
   Depending on when the project was created the default quota may or may not suffice this criteria.
   Check the [quota](https://console.cloud.google.com/iam-admin/quotas) overview page to confirm or adjust if required.

## Usage

Load the module using

```hcl
source = "../gcp_kubernetes"
```

And populate module input.

For a full example, see `examples/` folder.
