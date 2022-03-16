#!/bin/bash
echo "Creating npm installer"
echo "npm install" > /home/node/app/start.sh

read_var() {
     VAR=$(grep -w $1 $2 | xargs)
     IFS="=" read -ra VAR <<< "$VAR"
     echo ${VAR[1]}
}

NPM_MODE=$(read_var APP_ENV .env)

if [ "$NPM_MODE" == "development" ]; then
  echo "Building  project in development mode"
  echo "npm run watch" >> /home/node/app/start.sh
else
  echo "Building  project in production mode"
  echo "npm run production" >> /home/node/app/start.sh
fi


chmod +x /home/node/app/start.sh
echo "start installing and building"
cat /home/node/app/start.sh
/bin/bash /home/node/app/start.sh
cat /home/node/app.start.sh
echo "delete copy"
rm /home/node/app/start.sh
echo "NPM INSTALLED and BUILD SUCCESSFULLY"