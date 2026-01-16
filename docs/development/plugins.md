---
order: 5
---

# Plugin Development Guide

This guide will walk you through the process of creating and
integrating a plugin for MicroPowerManager (MPM).

## Overview

Plugins are modular components that extend MPM's functionality.
Each plugin consists of:

- Backend package (`src/backend/app/Plugins/{plugin-name}`)
- Frontend module (`src/frontend/src/plugins/{plugin-name}`)

## Quick Start

1. **Prerequisites**
   - Running [advanced development environment](development-environment.md#advanced-development-environment) with local PHP installation
   - Basic knowledge of Laravel and Vue.js

2. **Create Plugin Template**

   From the root directory of MicroPowerManager in your local development environment, run the following command:

   ```bash
   cd src/backend
   php artisan micropowermanager:new-plugin {plugin-name}
   ```

   Replace `{plugin-name}` with the desired name of your plugin.

   This command:
   - Creates plugin backend code `src/backend/app/Plugins/{plugin-name}`
   - Creates plugin frontend code `src/frontend/src/plugins/{plugin-name}`
   - Automatically generates and registers a new plugin id
   - Automatically performs basic plugin registration (for example in frontend routes and backend plugins table)

3. **Post-Creation Setup**
   - Review generated code structure
   - Follow integration steps below

4. **Example Plugins**
   Check existing plugins for reference:
   - Payment providers (e.g., Calin, Vodacom)
   - Meter manufacturers (e.g., Stima, Spark)
   - Feature plugins (e.g., Asset Management)

## Integration Steps

The following steps describe how your plugin is integrated with MPM.
Most of these steps are automatically executed when you run the `artisan micropowermanager:new-plugin` command.

### Step 1: Backend Integration

The backend integration process involves several key steps to
make your plugin discoverable and functional within MPM.

#### 1.1 Register Service Provider

Add your plugin's service provider to the application's service providers definition:

```php
// src/backend/bootstrap/providers

return [
    /*
    * Application Service Providers...
    */
    App\Providers\AppServiceProvider::class,

    // ...other providers
    Inensus\YourPlugin\Providers\YourPluginServiceProvider::class,
];

```

#### 1.2 Register API Routes (Optional)

If your plugin needs to handle API requests (e.g., webhooks, custom endpoints),
register your route resolver:

```php
// src/backend/app/Services/ApiResolvers/Data/ApiResolvers

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

#### 1.3 Register Plugin in Core

Add your plugin to the MPM plugins model to make it discoverable by the system:

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

#### 1.4 Database Setup

Your plugin will need several database migrations to integrate with MPM:

##### a. Create Plugin Tables

If your plugin needs to create database tables the migrations should be created core `database/migrations`
folder:

```bash
src
└── backend
    └── database
        └── migrations
            └── tenant
                └── create_custom_plugin_table.php

```

##### b. Register Plugin in System

Finally, add your plugin to MPM's plugin registry:

Create a migration to add your plugin to the `mpm_plugins` table:

```php
<?php

use App\Models\MpmPlugin;
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
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::YOUR_PLUGIN,
                'name' => 'YourPlugin',
                'description' => 'This plugin developed for
                [describe your plugin functionality].',
                'tail_tag' => 'Your Plugin',
                'installation_command' => 'your-plugin:install',
                'root_class' => 'YourPlugin',
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
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::YOUR_PLUGIN)
            ->delete();
    }
};
```

#### 1.5 Create Installation Command

Create a command that will handle your plugin's installation process.
This command should:

- Publish plugin assets (migrations, configs, views) using vendor:publish
- Run necessary migrations
- Set up initial configuration
- Register required services

The installation command typically runs after publishing assets:

```php
<?php

namespace App\Plugins\YourPlugin\Console\Commands;

use Illuminate\Console\Command;
use App\Plugins\YourPlugin\Services\YourPluginService;

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

#### 1.6 Configure Autoloading

Add your plugin's namespace to composer's autoload configuration:

```json
"autoload": {
  "psr-4": {
    "Inensus\\YourPlugin\\": "app/Plugins/your-plugin",
  }
}
```

After adding the autoload entry, run:

```bash
composer dump-autoload
```

### Step 2: Frontend Integration

The frontend integration makes your plugin visible and accessible in the MPM interface.

#### 2.1 Add Plugin Routes

Add your plugin's routes to the exported routes:

```js
// src/frontend/src/ExportedRoutes.js

import YourPluginOverview from "./plugins/your-plugin/modules/Overview/Overview"

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

#### 2.2 Register Components

Register your plugin's Vue components in the main app:

```js
// src/frontend/src/main.js

import YourPlugin from "@/plugins/your-plugin/modules/Overview/Component"

Vue.component("Your-Plugin", YourPlugin)
```

## Advanced configurations

> [!WARNING]
> This section needs expansion.

- Add plugin pages to Role-based access control (RBAC)
- Creating external endpoints and using access keys

## Testing Your Plugin

1. Install the plugin:

   ```bash
   php artisan your-plugin:install
   ```

   or enable the plugin via the Settings UI.

2. Verify database setup:
   - Check migrations ran successfully
   - Verify tables were created
   - Ensure plugin is registered in `mpm_plugins`

3. Check frontend integration:
   - Plugin appears in sidebar
   - Routes are accessible
   - Components render correctly

## Best Practices

1. **Code Organization**
   - Keep your plugin self-contained as much as possible
   - Follow the established directory structure for consistency
   - Use the MPM core services when appropriate to maintain integration

2. **Testing**
   - Test all features thoroughly
   - Verify database operations
   - Check frontend functionality

3. Document your plugin's features and configuration requirements

4. Use proper versioning for your plugin package

Remember to check existing plugins for reference implementations
that might help guide your development.
