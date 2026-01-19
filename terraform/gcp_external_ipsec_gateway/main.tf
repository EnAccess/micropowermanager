locals {
  gateway_external_ip_name      = "${var.resource_prefix}ipsec-gateway-external-ip${var.resource_suffix}"
  internal_ip_name              = "${var.resource_prefix}ipsec-internal-ip${var.resource_suffix}"
  gke_instance_name             = "${var.resource_prefix}ipsec-gateway${var.resource_suffix}"
  compute_route_to_right_subnet = "${var.resource_prefix}to-right${var.resource_suffix}"
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
  # Note: We still need to configure IP forwarding on OS level.
  can_ip_forward = true

  boot_disk {
    initialize_params {
      image = data.google_compute_image.ubuntu.self_link
    }
  }

  lifecycle {
    ignore_changes = [
      boot_disk[0].initialize_params[0].image
    ]
  }

  network_interface {
    network = "default"
    access_config {
      nat_ip = google_compute_address.ipsec_gateway_external_ip.address
    }
  }

  metadata = {
    startup-script = <<-EOT
      #!/bin/bash
      set -e

      echo "Updating packages..."
      apt update -y

      echo "Installing HAProxy and StrongSwan..."
      apt install -y haproxy strongswan

      echo "Enabling and starting services..."
      systemctl enable haproxy strongswan
      systemctl start haproxy strongswan

      echo "Setup complete."
    EOT
  }
}

#
# Routing
#
resource "google_compute_route" "compute_routes_to_right_side" {
  for_each = toset(var.compute_routes_to_right_side)

  project = var.gcp_project_id

  name = substr(
    "${local.compute_route_to_right_subnet}-${replace(replace(each.value, "/", "--"), ".", "-")}",
    0,
    63
  )
  network    = "default"
  dest_range = each.value

  next_hop_instance      = google_compute_instance.ipsec_gateway.self_link
  next_hop_instance_zone = google_compute_instance.ipsec_gateway.zone

  priority = 999
}

#
# Misc
#
resource "ansible_host" "ipsec_gateway" {
  name = "vodacom-mz-ipsec-gateway-cloud"

  variables = {
    ansible_host = google_compute_address.ipsec_gateway_external_ip.address
    # TODO: Can we get the user dynamically from Google Auth somehow
    # ansible_user                = "me"
    # TODO: Can we get this to work fully dynamically?
    # ansible_ssh_common_args     = "'-o ProxyCommand=\"gcloud compute ssh vodacom-mz-ipsec-gateway-cloud --zone=europe-west10-a --ssh-flag=-W --quiet\"'"
  }
}
