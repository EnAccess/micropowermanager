---
order: 2
---

# Deploy for Production

> [!INFO]
> This page covers **deployment** related information for MicroPowerManager.
> An installation of MicroPowerManager can be customised using environment variables which is explained in detail in the [environment variables section](environment-variables.md).

The MicroPowerManager is distributed as pre-compile Docker images via [DockerHub](https://hub.docker.com/u/enaccess).

## Choose a deployment scenario

For running a self-hosted version of MicroPowerManager multiple options exists.
We "officially" support two deployment options for MicroPowerManager

1. Cloud-hosted [Kubernetes](https://kubernetes.io/) with dedicated databases.
2. Monolithic deployment with [Docker Compose](https://docs.docker.com/compose/).

which are further explained in the sections below.

### Kubernetes (base setup)

This section describes the deployment scenario of a cloud-hosted [Kubernetes](https://kubernetes.io/) cluster with dedicated databases.

As an example we provide manifests for [Google Cloud Platform (GCP)](https://cloud.google.com/) with [Google Kubernetes Engine (GKE)](https://cloud.google.com/kubernetes-engine).
Other cloud providers might require adjustments to the manifest files.

1. Create the GCP infrastructure using [Terraform](https://www.terraform.io/) (a ready-to-use Terraform module can be found in [`terraform/gcp_kubernetes/`](https://github.com/EnAccess/micropowermanager/blob/main/terraform/gcp_kubernetes/) folder.)
2. Create a `kustomize.yaml` and configure your DNS names

   ::: code-group

   ```yaml [kustomize.yaml]
   apiVersion: kustomize.config.k8s.io/v1beta1
   kind: Kustomization

   namespace: micropowermanager

   resources:
     - namespace.yaml
     - ../../base/gcp_gke/

   patches:
     - patch: |-
         apiVersion: networking.gke.io/v1
         kind: ManagedCertificate
         metadata:
           name: mpm-managed-cert
         spec:
           domains:
             - api.demo.micropowermanager.io # [!code highlight]
             - demo.micropowermanager.io # [!code highlight]

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

3. (Optional, but recommended) Pin the version of MicroPowerManager Docker images used in the deployment

   ::: code-group

   ```yaml [kustomize.yaml]
   apiVersion: kustomize.config.k8s.io/v1beta1
   kind: Kustomization

   namespace: micropowermanager

   resources:
     - namespace.yaml
     - ../../base/gcp_gke/

   [...]

   images: # [!code ++]
     - name: enaccess/micropowermanager-backend:latest # [!code ++]
       newTag: 0.0.20 # [!code ++]
     - name: enaccess/micropowermanager-frontend:latest # [!code ++]
       newTag: 0.0.20 # [!code ++]

   [...]
   ```

4. (Optional) Create a static IP address in GCP and populate the `kubernetes.io/ingress.global-static-ip-name` annotation in `Ingress` by using a Kustomize `patch`

   ::: code-group

   ```yaml [kustomize.yaml]
   apiVersion: kustomize.config.k8s.io/v1beta1
   kind: Kustomization

   namespace: micropowermanager

   resources:
     - namespace.yaml
     - ../../base/gcp_gke/

   [...]

   patches:
     [...]
     - patch: |-  # [!code ++]
         apiVersion: networking.k8s.io/v1  # [!code ++]
         kind: Ingress  # [!code ++]
       metadata:  # [!code ++]
         name: mpm-ingress  # [!code ++]
         annotations:  # [!code ++]
           kubernetes.io/ingress.regional-static-ip-name: loadbalancer-global-address  # [!code ++]

   [...]
   ```

5. (Optional) Adjust `ConfigMap` entries by using a Kustomize `patch`

6. Create a `secrets.yaml` by copying `secrets.yaml.example` and populating the values.

   > [!NOTE]
   > If you choose to run MicroPowerManager in a non-default namespace make sure the Kubernetes `Secret` gets deployed into the same namespace.

7. Run `kubectl -k overlays/gcp_gke`
8. Run `kubectl -f apply secrets.yaml`
9. Retrieve the loadbalancer IP address using

   ```sh
   kubectl describe ingress mpm-ingress
   ```

   Create DNS records for the backend and frontend URLs.
   It might take a while for the newly created DNS records to propagate.

10. Proceed to the [Next Steps](#next-steps) section

### Kubernetes (advanced setup with IPSec tunnels to external systems)

Some payment provider require the establishment of a VPN Tunnel between MicroPowerManager and the corporate network.

As prerequisite for a VPN Tunnel we need to add an Internal Ingress to the Kubernetes setup.

1. Finish the [Kubernets (base setup)](#kubernetes-base-setup) from above
2. Deploy an internal IP address reservation by setting `create_internal_loadbalancer_address = true` in Terraform
3. Adapt `kustomize.yaml` to add the `internal_ingress` component and configure the reserved IP address

   ::: code-group

   ```yaml [kustomize.yaml]
   apiVersion: kustomize.config.k8s.io/v1beta1
   kind: Kustomization

   namespace: micropowermanager

   resources:
     - namespace.yaml
     - ../../base/gcp_gke/

   components: # [!code ++]
     - ../../components/internal_ingress # [!code ++]

   [...]

   patches:
     [...]
     - patch: |- # [!code ++]
       apiVersion: networking.k8s.io/v1 # [!code ++]
       kind: Ingress # [!code ++]
       metadata: # [!code ++]
         name: mpm-ingress-internal # [!code ++]
         annotations: # [!code ++]
           kubernetes.io/ingress.regional-static-ip-name: internal-loadbalancer-address # [!code ++]
   ```

Deploy a VPN IPSec Gateway

1. Using Terraform deploy the module [`terraform/gcp_external_ipsec_gateway/`](https://github.com/EnAccess/micropowermanager/blob/main/terraform/gcp_external_ipsec_gateway/)
2. Using `ssh` configure the IPSec Gateway to install `haproxy` and `strongswan`.
3. Configure according to provider request.

## Deployment with Docker Compose

> [!INFO]
> If you choose to run MicroPowerManager on a stand-alone server, additional configuration steps are required.
> These include installing a web server like [Nginx](https://nginx.org/), managing TLS certificates with [Let's Encrypt](https://letsencrypt.org/), and handling general Linux server maintenance such as system updates, [security patches](https://ubuntu.com/security/esm), and performance monitoring.
>
> There are plenty of great resources available online that cover these topics in detail.

### Prerequisites

- **Docker**: Version 20.10 or higher
- **Docker Compose**: Version 2.0 or higher
- **System Resources**: Minimum 4GB RAM, 20GB free disk space, 2 CPU cores

### Environment Configuration

1. **Backend Configuration** - Update `dev/.env.micropowermanager-backend`:

   ```env
   APP_ENV=production
   APP_KEY=<generate_secure_key_for_production>
   APP_DEBUG=false
   APP_URL=https://your-domain.com

   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=micro_power_manager
   DB_USERNAME=root
   DB_PASSWORD=<secure_database_password>

   CACHE_DRIVER=redis
   REDIS_HOST=redis
   REDIS_PORT=6379

   MPM_LOAD_DEMO_DATA=false
   MPM_ENV=production
   ```

2. **Frontend Configuration** - Update `dev/.env.micropowermanager-frontend`:

   ```env
   MPM_ENV=production
   MPM_BACKEND_URL=https://api.your-domain.com
   ```

3. **Database Configuration** - Update `dev/.env.mysql`:

   ```env
   MYSQL_ROOT_PASSWORD=<secure_database_password>
   MYSQL_DATABASE=micro_power_manager
   MYSQL_USER=mpm_user
   MYSQL_PASSWORD=<secure_user_password>
   ```

### Deployment

#### Option 1: DockerHub Images (Recommended)

```sh
# Start all services
docker compose -f docker-compose-dockerhub.yml up -d
```

#### Option 2: Build Locally

```sh
# Start all services
docker compose -f docker-compose-prod.yml up -d
```

### Service Ports

- **Backend**: 8000 (HTTP), 8443 (HTTPS)
- **Frontend**: 8001
- **MySQL**: 3306
- **Redis**: 6379

### Health Check

```sh
# Check service status
docker compose -f docker-compose-dockerhub.yml ps

# Test backend health
curl http://localhost:8000/up
```

### Configure WebServer, networking, TLS, certificates and DNS

For production deployment, you'll need to configure:

- **Web Server**: Install and configure Nginx or Apache as a reverse proxy
- **TLS/SSL**: Set up SSL certificates (Let's Encrypt recommended)
- **DNS**: Point your domain to the server's IP address
- **Firewall**: Configure firewall rules to allow HTTP/HTTPS traffic
- **Domain Configuration**: Update environment variables with your actual domain names

## Next Steps

After the installation an empty instance of MicroPowerManager should be accessible at

- [https://<your-url.com>](https://demo.micropowermanager.io)

This instance is fully functional just yet.
For example, you cannot log in, as further configuration is required.

Please proceed to [Configuration for Production](configuration-production.md)

## Troubleshooting
