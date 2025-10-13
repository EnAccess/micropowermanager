#!/bin/bash

###################################################################
##    This Shell file clones Package-Development-Starter-Pack    ##
##    and automatically handles mandatory integration steps      ##
###################################################################
packageName="$1"
nameSpace="$2"
pluginDescription="$3"

# Validate required parameters
if [ -z "$packageName" ] || [ -z "$nameSpace" ]; then
    echo "Usage: $0 <package-name> <namespace> [plugin-description]"
    echo "Example: $0 my-awesome-plugin MyAwesomePlugin 'This plugin does awesome things'"
    exit 1
fi

# Set default description if not provided
if [ -z "$pluginDescription" ]; then
    pluginDescription="This plugin developed for ${nameSpace} functionality."
fi

echo "Creating package: $packageName with namespace: $nameSpace"
echo "Description: $pluginDescription"

# Get the next available plugin ID
# Determine the project root (handle both direct execution and Laravel command execution)
if [ -d "/var/www/html" ]; then
    PROJECT_ROOT="/var/www/html"
else
    # If running from Laravel command, we're already in the project root
    PROJECT_ROOT=$(pwd)
fi

cd "$PROJECT_ROOT"
currentMaxId=$(grep -r "public const.*=.*[0-9]" app/Models/MpmPlugin.php | grep -o '[0-9]\+' | sort -n | tail -1)
nextPluginId=$((currentMaxId + 1))

echo "Next available plugin ID: $nextPluginId"

# Create the package directory and clone starter pack
cd packages/inensus
mkdir $packageName
git clone https://github.com/inensus/Package-Development-Starter-Pack $packageName

##    Step1: Rename default config file to <package-name>.php  ##
cd $packageName/config
mv {{package-name}}-integration.php  "${packageName}.php"
cd ..

##    Step2: Personalize console command installer by replacing placeholders in InstallPackage.php   ##
cd src/Console/Commands
sed -i "s/{{Package-Name}}/${nameSpace}/g" InstallPackage.php
sed -i "s/{{package-name}}/${packageName}/g" InstallPackage.php
cd ../..

##    Step3: Updates provider classes with actual package namespace, rename service provider classes   ##
cd Providers
sed -i "s/{{Package-Name}}/${nameSpace}/g" EventServiceProvider.php
sed -i "s/{{Package-Name}}/${nameSpace}/g" ObserverServiceProvider.php
sed -i "s/{{Package-Name}}/${nameSpace}/g" RouteServiceProvider.php
mv {{Package-Name}}ServiceProvider.php  "${nameSpace}ServiceProvider.php"
sed -i "s/{{Package-Name}}/${nameSpace}/g" "${nameSpace}ServiceProvider.php"
sed -i "s/{{package-name}}/${packageName}/g" "${nameSpace}ServiceProvider.php"
# Add the namespace declaration after <?php and before the next line
sed -i "/^<?php$/a namespace Inensus\\\\${nameSpace}\\\\Providers;" "${nameSpace}ServiceProvider.php"

cd ..

##    Step4: Replaces the route prefix in the package frontend routes   ##
cd resources/assets/js
sed -i "s/{{package-name}}/${packageName}/g" routes.js
cd ../../..

##    Step5: Updates the menu item with actual package namespace   ##
cd Services
sed -i "s/{{Package-Name}}/${nameSpace}/g" MenuItemService.php
cd ../..

##    Step6: Updates composer.json file with actual package namespace   ##
sed -i "s/{{Package-Name}}/${nameSpace}/g" composer.json
sed -i "s/{{package-name}}/${packageName}/g" composer.json

cd "$PROJECT_ROOT"

##    Step7: Register ServiceProvider in bootstrap/providers.php   ##
echo "Registering ServiceProvider in bootstrap/providers.php..."
# Add the use statement after the last Inensus use statement
useStatement="use Inensus\\\\${nameSpace}\\\\Providers\\\\${nameSpace}ServiceProvider;"
lastLine=$(grep -n "^use Inensus.*Providers.*ServiceProvider;$" bootstrap/providers.php | tail -1 | cut -d: -f1)
sed -i "${lastLine}a ${useStatement}" bootstrap/providers.php

# Add to the providers array (insert before the closing bracket)
sed -i "/];$/i     ${nameSpace}ServiceProvider::class," bootstrap/providers.php

##    Step8: Add plugin to composer autoload   ##
echo "Adding plugin to composer autoload..."
# Add to autoload section (not autoload-dev) with proper JSON escaping
sed -i "/\"App\\\\\\\\\": \"app\/\"/a         \"Inensus\\\\\\\\${nameSpace}\\\\\\\\\": \"packages/inensus/${packageName}/src\"," composer.json

##    Step9: Add plugin constant to MpmPlugin model   ##
echo "Adding plugin constant to MpmPlugin model..."
# Generate constant name properly (convert PascalCase to UPPER_CASE)
constantName=$(echo "$nameSpace" | sed 's/\([A-Z]\)/_\1/g' | sed 's/^_//' | tr '[:lower:]' '[:upper:]')
# Find the last constant line and add after it
lastLine=$(grep -n "^    public const.*= [0-9]*;$" app/Models/MpmPlugin.php | tail -1 | cut -d: -f1)
sed -i "${lastLine}a     public const ${constantName} = ${nextPluginId};" app/Models/MpmPlugin.php

##    Step10: Generate database migration for plugin registration   ##
echo "Generating database migration for plugin registration..."
migrationName="add_${packageName}_to_mpm_plugin_table"

# Generate migration with a timestamp to avoid conflicts
timestamp=$(date +"%Y_%m_%d_%H%M%S")
migrationFileName="${timestamp}_${migrationName}.php"
migrationFile="database/migrations/${migrationFileName}"

# Create the migration file directly since make:migration is causing issues
echo "Creating migration file: $migrationFile"

# Create the migration content
cat > "$migrationFile" << EOF
<?php

use App\\Models\\MpmPlugin;
use Carbon\\Carbon;
use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::${constantName},
                'name' => '${nameSpace}',
                'description' => '${pluginDescription}',
                'tail_tag' => '${nameSpace}',
                'installation_command' => '${packageName}:install',
                'root_class' => '${nameSpace}',
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
            ->where('id', MpmPlugin::${constantName})
            ->delete();
    }
};
EOF

echo "Migration file created successfully!"

##    Step11: Run composer dump-autoload   ##
echo "Running composer dump-autoload..."
if command -v composer >/dev/null 2>&1; then
    composer dump-autoload
else
    php composer.phar dump-autoload
fi

echo ""
echo "=================================================="
echo "Package '$packageName' created successfully!"
echo "=================================================="
echo "Next steps:"
echo "1. Review the generated files in packages/inensus/$packageName"
echo "2. Move the UI folder to src/frontend/src/plugins/$packageName"
echo "3. Add frontend routes to src/frontend/src/ExportedRoutes.js"
echo "4. Run the migration: php artisan migrate"
echo "5. Install the plugin: php artisan $packageName:install"
echo ""
echo "Plugin Details:"
echo "- Package Name: $packageName"
echo "- Namespace: $nameSpace"
echo "- Plugin ID: $nextPluginId"
echo "- Constant Name: $constantName"
echo "- Installation Command: $packageName:install"
echo "=================================================="
