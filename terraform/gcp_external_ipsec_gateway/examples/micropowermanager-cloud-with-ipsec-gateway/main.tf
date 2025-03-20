module "micropowermanager_cloud" {
  source = "../../../gcp_kubernetes"

  gcp_project_id = "my-gcp-project"
  gcp_region     = "europe-west10" # Berlin

  configure_gcp_project = true
  create_k8s_cluster    = true

  resoure_prefix = ""
  resoure_suffix = "-cloud"

  db_tier              = "db-custom-1-3840"
  db_enabled_public_ip = true
}

module "micropowermanager_cloud_vodacom_mz" {
  source = "../../"

  gcp_project_id = "my-gcp-project"
  gcp_region     = "europe-west10" # Berlin

  resoure_prefix = "vodacom-mz-"
  resoure_suffix = "-cloud"
}
