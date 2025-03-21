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

variable "gke_machine_type" {
  description = "Machine type of the Google Compute Engine (GKE) instance. See https://cloud.google.com/compute/docs/general-purpose-machines"
  type        = string
  default     = "e2-small"
}
