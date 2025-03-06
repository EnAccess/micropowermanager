---
order: 2
---

# Deploy for Production

> [!INFO]
> This page covers **deployment** related information for MicroPowerManager.
> An installation of MicroPowerManager can be customised using environment variables which is explained in detail [here](environment-variables.md).

The MicroPowerManager is distributed as pre-compile Docker images via [DockerHub](https://hub.docker.com/u/enaccess).

## Choose a deployment scenario

For running a self-hosted version of MicroPowerManager multiple options exists.
We "officially" support two deployment options for MicroPowerManager

1. Cloud-hosted [Kubernetes](https://kubernetes.io/) with dedicated databases.
2. Monolithic [Docker Compose](https://docs.docker.com/compose/) on stand-alone server with Compose-managed databases.

which are further explained in the sections below.

### Kubernetes

This section describes the deployment scenario of a cloud-hosted [Kubernetes](https://kubernetes.io/) cluster with dedicated databases.

As an example we provide manifests for [Google Cloud Platform (GCP)](https://cloud.google.com/) with [Google Kubernetes Engine (GKE)](https://cloud.google.com/kubernetes-engine).
Other cloud providers might require adjustments to the manifest files.

1. Create the GCP infrastructure (for example using [Terraform](https://www.terraform.io/))
2. Create a `kustomize.yaml` and configure your DNS names

   ::: code-group

   ```yaml [kustomize.yaml]
   apiVersion: kustomize.config.k8s.io/v1beta1
   kind: Kustomization

   namespace: micropowermanager

   resources:
   - namespace.yaml
   - ../../base/gcp_gke/

   images:
   - name: enaccess/micropowermanager-backend:latest
       newTag: 0.0.12
   - name: enaccess/micropowermanager-frontend:latest
       newTag: 0.0.12

   patches:
   - patch: |-
       apiVersion: networking.gke.io/v1
       kind: ManagedCertificate
       metadata:
           name: mpm-managed-cert
       spec:
           domains:
           - api.demo.micropowermanager.io
           - demo.micropowermanager.io

   replacements:
   - source:
       kind: ManagedCertificate
       name: mpm-managed-cert
       fieldPath: spec.domains.0
       targets:
       - select:
           kind: Ingress
           name: mpm-ingress
           fieldPaths:
           - spec.rules.0.host
   - source:
       kind: ManagedCertificate
       name: mpm-managed-cert
       fieldPath: spec.domains.1
       targets:
       - select:
           kind: Ingress
           name: mpm-ingress
           fieldPaths:
           - spec.rules.1.host
   ```

   The `kustomize.yaml` is meant as a starting point and might require further adjustment.
   A good reference is the working sample `kustomize.yaml` Kubernetes manifest file that is used to run the [MPM Demo Version](https://demo.micropowermanager.io/#/login).
   It can be found in the `k8s` directory of this repository.

3. (Optional) Create a static IP address in GCP and populate the `kubernetes.io/ingress.global-static-ip-name` annotation in `Ingress` by using a Kustomize `patch`
4. (Optional) Adjust `ConfigMap` entries by using a Kustomize `patch`
5. Create a `secrets.yaml` by copying `secrets.yaml.example` and populating the values.
   Note: If you choose to run MicroPowerManager in a non-default namespace make sure the Kubernetes `Secret` gets deployed into the same namespace.
6. Run `kubectl -k overlays/gcp_gke`
7. Run `kubectl -f apply secrets.yaml`
8. Retrieve the loadbalancer IP address using

   ```sh
   kubectl describe ingress mpm-ingress
   ```

   Create DNS records for the backend and frontend URLs.
   Note: It might take a while for the newly created DNS records to propagate.

9. Proceed to the [Next Steps](#next-steps) section

### Stand-alone server using Docker Compose

> [!INFO]
> If you choose to run MicroPowerManager on a stand-alone server, additional configuration steps are required.
> These include installing a web server like [Nginx](https://nginx.org/), managing TLS certificates with [Let's Encrypt](https://letsencrypt.org/), and handling general Linux server maintenance such as system updates, [security patches](https://ubuntu.com/security/esm), and performance monitoring.
>
> There are plenty of great resources available online that cover these topics in detail.

1. A working "all-in one" environment running with production containers fetched from DockerHub can be achieved by running:

   ```sh
   docker compose -f docker-compose-dockerhub.yml up
   ```

   This exposes

   - Port `8443`, `8000`: The backend of MicroPowerManager
   - Port `8001`: The frontend of MicroPowerManager

2. Configure WebServer, networking, TLS, certificates and DNS.
3. Proceed to the [Next Steps](#next-steps) section

## Next Steps

After the installation an empty instance of MicroPowerManager should be accessible at

- [https://<your-url.com>](https://demo.micropowermanager.io)

This instance is fully functional just yet.
For example, you cannot log in, as further configuration is required.

Please proceed to [Configuration for Production](configuration-production.md)

## Troubleshooting
