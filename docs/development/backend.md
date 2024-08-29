---
order: 2
---

# Backend

The backend is built with Laravel. The backend is served under <http://localhost:8000/api>. You can find backend
files under `Website/htdocs/mpmanager`. After the first run with `docker-compose up`, dependencies will be installed
automatically. If you prefer to install dependencies manually or need to add additional packages, follow these steps:

1. Enter the Docker container named "laravel" by navigating to the "mpmanager" directory:

   ```bash
   docker exec -it laravel bash
   cd mpmanager
   ```

2. Run the following command to install dependencies, replacing {package-name} with the actual name of the package:

   ```bash
    ./composer.phar install {package-name}
   ```

These steps ensure that you can manage dependencies either automatically during the initial docker-compose up or
manually when needed.
Make sure to replace `{package-name}` with the actual name of the package you want to install.

We followed the laravel folder structure for the backend. If you want to learn more about the folder structure, you can
check the [Laravel documentation](https://laravel.com/docs/9.x/structure).
