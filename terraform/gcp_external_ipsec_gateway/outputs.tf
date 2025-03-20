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
# GKE Instance
################################################################################

# TBD

################################################################################
# Network
################################################################################
output "network_ipsec_gateway_external_ip" {
  value = google_compute_address.ipsec_gateway_external_ip.address
}
