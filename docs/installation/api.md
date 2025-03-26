---
order: 11
---

# Generate API Documentation

> [!WARNING]
> This sites describes a potentially deprecated feature of MicroPowerManager

To generate the API documentation, jump in the `laravel` container and type `php artisan apidoc:generate`.
That will create a new **docs** folder under **public** folder.
The API documentation should be available under `http://localhost:8000/docs/`.
The whole API documentation will be migrated to third-party tools like Postman or Swagger.
