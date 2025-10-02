#!/bin/bash

###################################################################
##    This Shell file clones Package-Development-Starter-Pack    ##
###################################################################
packageName="$1"
nameSpace="$2"

echo $packageName
cd /var/www/html/packages/inensus
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

cd ..

##    Step4: Replaces the route prefix in the package frontend routes   ##
cd resources/assets/js
sed -i "s/{{package-name}}/${packageName}/g" routes.js
cd ../../..

##    Step5: Updates the menu item with actual package namespace   ##
cd Services
sed -i "s/{{Package-Name}}/${nameSpace}/g" MenuItemService.php
cd ../..

##    Step6: Updates composer.json file with actuall package namespace   ##
sed -i "s/{{Package-Name}}/${nameSpace}/g" composer.json
sed -i "s/{{package-name}}/${packageName}/g" composer.json
