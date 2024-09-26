---
order: 5
---

# Plugins

Plugins are additional components developed as separate packages to enhance our product. This separation helps keep the
main codebase clean. Each plugin should reside in its own folder under the `src/frontend/src/plugins` directory.
Additionally, each plugin should have its own backend code, which will be explained in the backend section.

```sh
src/frontend
├── src
│   ├── plugins
│   │   ├── newPlugin
```

In the backend section, you'll find instructions on how to create a plugin.

## Install Plugins

We have a custom plugin creator command that generates a template. Use the following command to create a new plugin:

```bash
docker exec -it laravel-dev bash
php artisan micropowermanager:new-package {package-name}
```

This command creates a plugin template in the src/backend/packages/inensus folder. Upon creation, you can proceed with plugin development. You can check other plugins for reference.
Additionally, this command will create UI folders for the newly created plugin. Move the created UI folder to the src/frontend/src/plugins folder.
