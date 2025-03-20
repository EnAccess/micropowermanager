variable "gcp_project_id" {
  description = "Project ID of Google Cloud Platform (GCP) project. Required."
  type        = string
}

variable "gcp_region" {
  description = "Region of Google Cloud Platform (GCP) project. Required."
  type        = string
}

variable "resoure_prefix" {
  description = "Prefix used in resource creation. This can be useful to identify resources."
  type        = string
  default     = ""
}

variable "resoure_suffix" {
  description = "Suffix used in resource creation. This can be useful to distinguish between different environments like `development` and `production`."
  type        = string
  default     = ""
}

variable "configure_gcp_project" {
  description = "Determines whether to configure the Google Cloud Platform (GCP) project for usage with MicroPowerManager. For example the default network and enables API. If you are using multiple instances of this module, this should be enabled only once per GCP project."
  type        = bool
  default     = true
}

variable "create_k8s_cluster" {
  description = "Determines whether a Kubernetes cluster will be created (and managed) by this module."
  type        = bool
  default     = true
}

variable "create_internal_loadbalancer_address" {
  description = "Determines whether an internal IP address will be created (and managed) by this module. This is required in scenarios where IPSec tunnels are to be established."
  type        = bool
  default     = false
}

variable "db_tier" {
  description = "The machine type to use for the Cloud SQL database. See tiers for more details and supported versions."
  type        = string
  default     = "db-f1-micro"
}

variable "db_enabled_public_ip" {
  description = "Determines whether the database instance should be accessible via public IP. Note: Authorization is still required even when using public IP, for example using `db_authorized_networks`. See https://cloud.google.com/sql/docs/mysql/connect-overview"
  type        = bool
  default     = true
}

variable "db_authorized_networks" {
  description = "List of mapped public networks authorized to access the database instances."
  type        = list(map(string))
  # Default - short range of GCP health-checkers IPs
  default = [{
    name  = "sample-gcp-health-checkers-range"
    value = "130.211.0.0/28"
  }]
}
