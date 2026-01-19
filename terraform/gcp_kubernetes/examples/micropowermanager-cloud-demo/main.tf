module "micropowermanager_cloud" {
  source = "../../"

  gcp_project_id = "my-gcp-project"
  gcp_region     = "europe-west10" # Berlin

  configure_gcp_project = true
  create_k8s_cluster    = true

  create_internal_loadbalancer_address = true
  create_internal_loadbalancer_tls     = true

  resource_prefix = ""
  resource_suffix = "-cloud"

  db_tier              = "db-custom-1-3840"
  db_enabled_public_ip = true
}

module "micropowermanager_demo" {
  source = "../../"

  gcp_project_id = "my-gcp-project"
  gcp_region     = "europe-west10" # Berlin

  configure_gcp_project = false
  create_k8s_cluster    = false

  resource_prefix = ""
  resource_suffix = "-demo"

  db_tier              = "db-f1-micro"
  db_enabled_public_ip = true
}
