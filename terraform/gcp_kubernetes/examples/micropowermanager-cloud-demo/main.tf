module "micropowermanager_cloud" {
  source = "../../"

  gcp_project_id = "my-gcp-project"
  gcp_region     = "europe-west10" # Berlin

  configure_gcp_project = true
  create_k8s_cluster    = true

  resoure_prefix = ""
  resoure_suffix = "-cloud"

  db_tier              = "db-custom-1-3840"
  db_enabled_public_ip = true
}

module "micropowermanager_demo" {
  source = "../../"

  gcp_project_id = "my-gcp-project"
  gcp_region     = "europe-west10" # Berlin

  configure_gcp_project = false
  create_k8s_cluster    = false

  resoure_prefix = ""
  resoure_suffix = "-demo"

  db_tier              = "db-f1-micro"
  db_enabled_public_ip = true
}
