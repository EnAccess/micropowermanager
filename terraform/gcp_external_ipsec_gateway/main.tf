locals {
  gateway_external_ip_name = "${var.resoure_prefix}ipsec-gateway-external-ip${var.resoure_suffix}"
  internal_ip_name         = "${var.resoure_prefix}ipsec-internal-ip${var.resoure_suffix}"
  gke_instance_name        = "${var.resoure_prefix}ipsec-gateway${var.resoure_suffix}"
}

data "google_project" "gcp_project" {}


#
# Networking
#
resource "google_compute_address" "ipsec_gateway_external_ip" {
  project = var.gcp_project_id

  name   = local.gateway_external_ip_name
  region = var.gcp_region
}

#
# VPN Gateway instance
#
data "google_compute_image" "ubuntu" {
  family  = "ubuntu-2404-lts-amd64"
  project = "ubuntu-os-cloud"
}

# We currently deploy the IPSec gateway in the first available zone in our region.
# TODO: Probably there is a more clever way to do this.
data "google_compute_zones" "available" {
  region = var.gcp_region
}

resource "google_compute_instance" "ipsec_gateway" {
  project = var.gcp_project_id

  name         = local.gke_instance_name
  machine_type = var.gke_machine_type
  zone         = data.google_compute_zones.available.names[0]

  # This is required to use the instance as a VPN Gateway
  can_ip_forward = true

  boot_disk {
    initialize_params {
      image = data.google_compute_image.ubuntu.self_link
    }
  }

  network_interface {
    network = "default"
    access_config {
      nat_ip = google_compute_address.ipsec_gateway_external_ip.address
    }
  }
}
