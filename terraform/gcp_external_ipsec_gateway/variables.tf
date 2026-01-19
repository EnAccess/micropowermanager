variable "gcp_project_id" {
  description = "Project ID of Google Cloud Platform (GCP) project. Required."
  type        = string
}

variable "gcp_region" {
  description = "Region of Google Cloud Platform (GCP) project. Required."
  type        = string
}

variable "resource_prefix" {
  description = "Prefix used in resource creation. This can be useful to identify resources."
  type        = string
  default     = ""
}

variable "resource_suffix" {
  description = "Suffix used in resource creation. This can be useful to distinguish between different environments like `development` and `production`."
  type        = string
  default     = ""
}

variable "gke_machine_type" {
  description = "Machine type of the Google Compute Engine (GKE) instance. See https://cloud.google.com/compute/docs/general-purpose-machines"
  type        = string
  default     = "e2-small"
}

variable "compute_routes_to_right_side" {
  description = "List of destination IP ranges (CIDR blocks) in the right subnets. For each entry, a Google Compute Route will be provisioned."
  type        = list(string)
  default     = []

  validation {
    condition = alltrue([
      for cidr in var.compute_routes_to_right_side :
      can(cidrnetmask(cidr))
    ])
    error_message = "All entries in compute_routes_to_right_side must be valid CIDR blocks (e.g. 10.0.0.0/16)."
  }
}
