################################################################################
# Project
################################################################################
output "project_id" {
  description = "The Google Cloud Platform (GCP) project id."
  value       = data.google_project.gcp_project.project_id
}

output "project_number" {
  description = "The Google Cloud Platform (GCP) project number."
  value       = data.google_project.gcp_project.number
}

################################################################################
# GCE Instance
################################################################################

output "ipsec_gateway_gce_instance" {
  description = "The Google Cloud Engine (GCE) instance that implements the IPSec gateway."
  value       = google_compute_instance.ipsec_gateway.self_link
}

output "ipsec_gateway_gce_instance_zone" {
  description = "The Google Cloud Engine (GCE) instance's zone that implements the IPSec gateway."
  value       = google_compute_instance.ipsec_gateway.zone
}

################################################################################
# Network
################################################################################
output "network_ipsec_gateway_external_ip" {
  value = google_compute_address.ipsec_gateway_external_ip.address
}
