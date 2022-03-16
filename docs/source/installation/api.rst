Generate API Documentation
--------------------------

To generate the API documentation, jump in the ``laravel`` container and
type ``php artisan apidoc:generate`` in the **mpmanager** directory.
That will create a new **docs** folder under **public** folder. The API
documentation should be available under
``http://mpmanager.local/docs/``. The whole API documentation will be
migrated to third-party tools like Postman or Swagger.