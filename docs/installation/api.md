---
order: 11
---

# API

MicroPowerManager exposes a REST API for integrations, data import and export, and third-party payments.

## API reference

The full API reference is generated from the code as OpenAPI docs.

<!-- prettier-ignore -->
[OpenAPI docs](/openapi/index.html){target="_self"}

On a local development environment, the same docs render at `http://localhost:8000/docs/api/`.
Production containers do not expose them.

## Authentication

Every endpoint in the reference states which scheme it needs:

- **User token**: a JWT bearer token from the user login endpoint.
- **Agent token**: a JWT bearer token from the Agent App login endpoint.
- **API key**: a bearer token generated in the settings dashboard under **API Keys**.

## Stability and versioning

From v1.0.0, every endpoint in the OpenAPI docs is part of the stable public API and follows [semantic versioning](https://semver.org/).

- **Major releases** (`2.0.0`) may contain breaking changes.
- **Minor and patch releases** (`1.1.0`, `1.0.1`) stay backward compatible.

Breaking changes are listed under **Breaking changes** in the [release notes](https://github.com/EnAccess/micropowermanager/releases).
