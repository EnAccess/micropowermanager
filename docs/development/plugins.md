---
order: 5
---

# Plugins

Plugins are additional components developed as separate packages to enhance our product.
This separation helps keep the main codebase clean.
Each plugin should reside in its own folder under the `src/frontend/src/plugins` directory.
Additionally, each plugin should have its own backend code, which will be explained in the backend section.

```sh
src/frontend
├── src
│   ├── plugins
│   │   ├── newPlugin
```

In the backend section, you'll find instructions on how to create a plugin.

## Install Plugins

We have a custom plugin creator command that generates a template.
Use the following command to create a new plugin:

```bash
docker exec -it backend-dev bash
php artisan micropowermanager:new-package {package-name}
```

This command creates a plugin template in the `src/backend/packages/inensus` folder.
Upon creation, you can proceed with plugin development.
You can check other plugins for reference.
Additionally, this command will create UI folders for the newly created plugin.
Move the created UI folder to the `src/frontend/src/plugins` folder.

## Plugin Integration Process

### Backend Integration

To make your plugin discoverable and properly integrated with MPM, follow these steps:

#### 1. Register Your Plugin's Installation Command

Add your plugin's installation command class to the core application's console kernel:

```php
// src/backend/app/Console/Kernel.php

use Inensus\YourPlugin\Console\Commands\InstallYourPluginPackage;

class Kernel extends ConsoleKernel {
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\AddMeterAddress::class,
        InstallYourPluginPackage::class,
        // ...other commands
    ];
    // ...
}
```

#### 2. Register Your Plugin's Service Provider

Add your plugin's service provider to the core application's configuration:

```php
// src/backend/config/app.php

<?php

return [
    // rest of app configuration
    // ...
    /*
    * Laravel Framework Service Providers...
    */
    'providers' => [
        // ...other providers
        Inensus\YourPlugin\Providers\YourPluginServiceProvider::class,
    ]
];
```

#### 3. Register Custom HTTP Route Resolver

If your plugin requires API endpoints, register your custom route resolver:

```php
// src/backend/app/modules/Sharding/ApiResolvers/Data/ApiResolvers

class ApiResolverMap {
    // ...other API constants
    public const YOUR_PLUGIN_API = 'api/your-plugin/callback';

    public const RESOLVABLE_APIS = [
        // ...other resolvable APIs
        self::YOUR_PLUGIN_API,
    ];

    private const API_RESOLVER = [
        // ...other API resolvers
        self::YOUR_PLUGIN_API => YourPluginApiResolver::class,
    ];

    public function getResolvableApis(): array {
        return self::RESOLVABLE_APIS;
    }

    public function getApiResolver(string $api): string {
        return self::API_RESOLVER[$api];
    }
}
```

#### 4. Register Plugin in MPM Plugins Model

Add your plugin to the MPM plugins model:

```php
// src/backend/app/Models/MpmPlugin.php

<?php

namespace App\Models;

use App\Models\Base\BaseModelCore;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MpmPlugin extends BaseModelCore {
    use HasFactory;
    // ...other plugin constants
    public const YOUR_PLUGIN = 19; // Add your plugin ID (increment sequentially)

    protected $table = 'mpm_plugins';

    public function plugins() {
        return $this->hasMany(Plugins::class);
    }
}
```

#### 5. Create Required Migrations

##### a. Protected Page Migration

Create a migration to register your plugin's overview page:

```php
<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::table('protected_pages')->insert([
            [
                'name' => '/your-plugin/your-plugin-overview',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        DB::table('protected_pages')->where('name', '/your-plugin/your-plugin-overview')->delete();
    }
};
```

##### b. Plugin Models Migration

Create tenant migrations for your plugin's models using:

```bash
php artisan make:migration-tenant
```

Make sure to create stub files for these migrations in your plugin's `database/migrations/` directory.

##### c. Register Plugin in MPM Plugins Table

Create a migration to add your plugin to the `mpm_plugins` table:

```php
<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::YOUR_PLUGIN,
                'name' => 'YourPlugin',
                'description' => 'This plugin developed for [describe your plugin functionality].',
                'tail_tag' => 'Your Plugin',
                'installation_command' => 'your-plugin:install',
                'root_class' => 'YourPlugin',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down() {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::YOUR_PLUGIN)
            ->delete();
    }
};
```

#### 6. Implement Installation Command

Create an installation command for your plugin:

```php
<?php

namespace Inensus\YourPlugin\Console\Commands;

use Illuminate\Console\Command;
use Inensus\YourPlugin\Services\YourPluginService;

class InstallPackage extends Command {
    protected $signature = 'your-plugin:install';
    protected $description = 'Install YourPlugin Package';

    public function __construct(
        private YourPluginService $pluginService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing YourPlugin Integration Package\n');
        $this->pluginService->initialize();
        $this->info('Package installed successfully..');
    }
}
```

#### 7. Register Plugin in Composer.json

Add your plugin to the `autoload-dev` section in composer.json:

```json
"autoload-dev": {
  "psr-4": {
    "Inensus\\YourPlugin\\": "packages/inensus/your-plugin/src",
  }
}
```

### Frontend Integration

#### 1. Register Plugin Routes

Add your plugin's routes to the exported routes:

```js
// src/frontend/src/ExportedRoutes.js

import YourPluginOverview from "./plugins/your-plugin/js/modules/Overview/Overview"

export const exportedRoutes = [
  // ...other routes
  {
    path: "/your-plugin",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin_id: 19, // Your plugin ID from MpmPlugin
        name: "Your Plugin",
        icon: "plugin-icon", // Choose an appropriate icon
      },
    },
    children: [
      {
        path: "your-plugin-overview",
        component: YourPluginOverview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
      // Add more routes as needed
    ],
  },
]
```

#### 2. Mount Plugin Components

Register your Vue components in the main app:

```js
// src/frontend/src/main.js

import YourPlugin from "@/plugins/your-plugin/js/modules/Overview/Component"

Vue.component("Your-Plugin", YourPlugin)

// ...

/*eslint-disable */
const app = new Vue({
  el: "#app",
  // ...
})
```

## Plugin Development Best Practices

1. Keep your plugin self-contained as much as possible
2. Follow the established directory structure for consistency
3. Use the MPM core services when appropriate to maintain integration
4. Thoroughly test your plugin's functionality before integration
5. Document your plugin's features and configuration requirements
6. Use proper versioning for your plugin package

Remember to check existing plugins for reference implementations that might help guide your development.
