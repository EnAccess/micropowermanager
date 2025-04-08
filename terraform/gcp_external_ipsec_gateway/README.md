# MicroPowerManager external VPN Gateway

Here we create a simple Google Cloud Compute instance which can act as a VPN Gateway to external, corporate Networks.

This is commonly used in Mobile Money and Payment Provider integrations.

It creates a Google Compute Engine with the `CanForwardIP` flag.

Using

- StrongSwan
- HAProxy

When using this, should the Kubernetes cluster should be setup with an internal Ingress.

Additionally, this module creates a Ansible inventory which can be used with

```yaml
---
plugin: cloud.terraform.terraform_provider
```
