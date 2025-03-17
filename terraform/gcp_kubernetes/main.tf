#
# Pre-amble
#
locals {
  k8s_cluster_name            = "${var.resoure_prefix}micropowermanager${var.resoure_suffix}"
  db_instance_name            = "${var.resoure_prefix}micropowermanager${var.resoure_suffix}"
  db_name                     = "micro_power_manager"
  network_global_address_name = "${var.resoure_prefix}loadbalancer-global-address${var.resoure_suffix}"
}

data "google_project" "gcp_project" {}

#
# Networking
#

# These are the default network and default region specific subnet
# which are created when the `compute.googleapis.com` API
# is enabled.
data "google_compute_network" "default" {
  project = var.gcp_project_id

  name = "default"
}

data "google_compute_subnetwork" "default" {
  project = var.gcp_project_id

  name   = "default"
  region = var.gcp_region
}

resource "google_compute_address" "nat_external_ip" {
  count = var.configure_gcp_project ? 1 : 0

  project = var.gcp_project_id

  name   = "nat-external-ip"
  region = var.gcp_region
}

resource "google_compute_router" "cloud_router" {
  count = var.configure_gcp_project ? 1 : 0

  project = var.gcp_project_id

  name    = "cloud-router"
  network = data.google_compute_network.default.self_link
  region  = var.gcp_region
}

resource "google_compute_router_nat" "cloud_nat" {
  count = var.configure_gcp_project ? 1 : 0

  project = var.gcp_project_id

  name                               = "cloud-nat"
  router                             = google_compute_router.cloud_router[0].name
  region                             = google_compute_router.cloud_router[0].region
  nat_ip_allocate_option             = "MANUAL_ONLY"
  nat_ips                            = [google_compute_address.nat_external_ip[0].self_link]
  source_subnetwork_ip_ranges_to_nat = "ALL_SUBNETWORKS_ALL_IP_RANGES"

  # Optional: Enable logging (useful for debugging)
  log_config {
    enable = true
    filter = "ERRORS_ONLY"
  }
}

# This is required to connect to our databases from other GCP services using VPC
# rather than public internet access.
resource "google_compute_global_address" "default_ip_range" {
  count = var.configure_gcp_project ? 1 : 0

  project = var.gcp_project_id

  name          = "default-ip-range"
  purpose       = "VPC_PEERING"
  address_type  = "INTERNAL"
  prefix_length = 20
  network       = data.google_compute_network.default.id
}

resource "google_service_networking_connection" "default" {
  count = var.configure_gcp_project ? 1 : 0

  network                 = data.google_compute_network.default.id
  service                 = "servicenetworking.googleapis.com"
  reserved_peering_ranges = [google_compute_global_address.default_ip_range[0].name]
}

# Static IP address to be used in Kubernetes Ingress
resource "google_compute_global_address" "http_loadbalancer_global_address" {
  name = local.network_global_address_name
}

#
# Kubernetes
#
resource "google_container_cluster" "k8s" {
  count = var.create_k8s_cluster ? 1 : 0

  project = var.gcp_project_id

  name     = local.k8s_cluster_name
  location = var.gcp_region

  enable_autopilot = true
}

#
# Database setup
#

## Database instance
resource "google_sql_database_instance" "mysql" {
  project = var.gcp_project_id

  name             = local.db_instance_name
  database_version = "MYSQL_8_4"
  region           = var.gcp_region

  settings {
    tier              = var.db_tier
    edition           = "ENTERPRISE"
    availability_type = "ZONAL"

    deletion_protection_enabled = true

    backup_configuration {
      enabled                        = true
      binary_log_enabled             = true
      transaction_log_retention_days = 7
    }

    ip_configuration {
      ipv4_enabled    = var.db_enabled_public_ip
      private_network = data.google_compute_network.default.id
      # FIXME: Use `ENCRYPTED_ONLY`
      ssl_mode = "ALLOW_UNENCRYPTED_AND_ENCRYPTED"

      dynamic "authorized_networks" {
        for_each = var.db_enabled_public_ip ? var.db_authorized_networks : []

        content {
          expiration_time = lookup(authorized_networks.value, "expiration_time", null)
          name            = lookup(authorized_networks.value, "name", null)
          value           = lookup(authorized_networks.value, "value", null)
        }
      }
    }

    insights_config {
      query_insights_enabled = true
      query_string_length    = 4500
    }

    password_validation_policy {
      min_length                  = 6
      reuse_interval              = 2
      complexity                  = "COMPLEXITY_DEFAULT"
      disallow_username_substring = true
      password_change_interval    = "30s"
      enable_password_policy      = true
    }
  }

  # The database can only be created after VPC peering has been configured, see
  # https://cloud.google.com/sql/docs/postgres/configure-private-services-access
  depends_on = [
    google_service_networking_connection.default
  ]
}

## Database setup (database, user, ...)
resource "random_password" "db_root_password" {
  length  = 16
  special = true
}

resource "google_sql_user" "db_root_user" {
  project = var.gcp_project_id

  name     = "mpm_root"
  instance = google_sql_database_instance.mysql.name
  // Required to allow connections using Cloud SQL Studio
  host     = "%"
  password = random_password.db_root_password.result
}

resource "google_sql_database" "database" {
  project = var.gcp_project_id

  name     = local.db_name
  instance = google_sql_database_instance.mysql.name
}

#
# Redis
#

# TBD
