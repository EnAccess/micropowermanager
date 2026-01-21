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
# Database
################################################################################

# Database Host, Root Username, Root Password
output "db_host_public_ip" {
  value = google_sql_database_instance.mysql.public_ip_address
}

output "db_host_private_ip" {
  value = google_sql_database_instance.mysql.private_ip_address
}

output "db_root_user" {
  value = google_sql_user.db_root_user.name
}

output "db_root_password" {
  value     = random_password.db_root_password.result
  sensitive = true
}

output "db_name" {
  value = google_sql_database.database.name
}

################################################################################
# Network
################################################################################
output "network_external_loadbalancer_ip_address" {
  value = google_compute_global_address.http_loadbalancer_global_address.address
}

output "network_cloud_nat_static_ip_address" {
  value = length(google_compute_address.nat_external_ip) > 0 ? google_compute_address.nat_external_ip[0].address : ""
}

output "network_internal_loadbalancer_ip_address" {
  value = length(google_compute_address.internal_loadbalancer_address) > 0 ? google_compute_address.internal_loadbalancer_address[0].address : ""
}

output "network_internal_loadbalancer_tls_key" {
  value = length(tls_private_key.internal_loadbalancer_key) > 0 ? tls_private_key.internal_loadbalancer_key[0].private_key_pem : ""
}

output "network_internal_loadbalancer_tls_cert" {
  value = length(tls_self_signed_cert.internal_loadbalancer_cert) > 0 ? tls_self_signed_cert.internal_loadbalancer_cert[0].cert_pem : ""
}


output "mpm_backend_storage" {
  value = google_storage_bucket.mpm-backend-storage
}

output "mpm_service_account" {
  value = google_service_account.mpm_service_account
}
