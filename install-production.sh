#!/bin/bash

###################################################################
############### Function declarations #############################
###################################################################
function check_if_docker_compose_installed() {
  if ! [ -x "$(command -v docker-compose)" ]; then
    echo 'Error: docker-compose is not installed.' >&2
    exit 1
  fi
}
function setup_tls_parameters() {
  local data_path=$1
  if [ ! -e "$data_path/conf/options-ssl-nginx.conf" ] || [ ! -e "$data_path/conf/ssl-dhparams.pem" ]; then
    echo "### Downloading recommended TLS parameters ..."
    mkdir -p "$data_path/conf"
    curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf >"$data_path/conf/options-ssl-nginx.conf"
    curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem >"$data_path/conf/ssl-dhparams.pem"
    echo
  fi
}

function should_renew_certificate() {
  local data_path=$1
  local domains=$2

  # Check if certificates already exists
  if [ -d "$data_path/conf/live/${domains}" ]; then
    read -p "Existing data found for ${domains}. Continue and replace existing certificate? (y/N) " decision
      if [ "$decision" != "Y" ] && [ "$decision" != "y" ]; then
      echo 0
    else
      echo 1
      fi
  else
    echo 1
  fi


}
function make_dummy_certificate() {
  local data_path=$1
  local domains=("$2") # This will convert the string values into an array

  # In the case user wants to replace old certificate we will replace it with the new one.
  echo "### Creating dummy certificate for domains ${domains[*]} ..."
  path="/etc/letsencrypt/live/${domains[0]}"
  mkdir -p "$data_path/conf/live/${domains[0]}"
  docker-compose -f docker-compose-prod.yml run --rm --entrypoint " \
    openssl req -x509 -nodes -newkey rsa:1024 -days 1 \
      -keyout '$path/privkey.pem' \
      -out '$path/fullchain.pem' \
      -subj '/CN=localhost'" certbot
  echo
}

function start_nginx() {
  echo "### Starting nginx ..."
  docker-compose -f docker-compose-prod.yml up --build --force-recreate -d
  echo
}

function delete_dummy_certificate() {
  local dummy_certificate_domain=$1
  echo "### Deleting dummy certificate for $dummy_certificate_domain ..."
  docker-compose -f docker-compose-prod.yml run --rm --entrypoint " \
    rm -Rf /etc/letsencrypt/live/$dummy_certificate_domain && \
    rm -Rf /etc/letsencrypt/archive/$dummy_certificate_domain && \
    rm -Rf /etc/letsencrypt/renewal/$dummy_certificate_domain.conf" certbot
  echo
}
function request_new_certificate() {
  local domains=("$1")
  local email="$2"
  local rsa_key_size="$3"
  local staging="$4"

  echo "### Requesting Let's Encrypt certificate for ${domains[*]} ..."
  #Join $domains to -d args
  domain_args=""
  for domain in "${domains[@]}"; do
    domain_args="$domain_args -d $domain"
  done

  # Select appropriate email arg
  case "$email" in
  "") email_arg="--register-unsafely-without-email" ;;
  *) email_arg="--email $email" ;;
  esac

  # Enable staging mode if needed
  if [ "$staging" != "0" ]; then
    staging_arg="--staging"
  fi

  docker-compose -f docker-compose-prod.yml run --rm --entrypoint " \
    certbot certonly --webroot -w /var/www/certbot \
      $staging_arg \
      $email_arg \
      $domain_args \
      --rsa-key-size $rsa_key_size \
      --agree-tos \
      --force-renewal" certbot
  echo
}

function reload_nginx() {
  echo "### Reloading nginx ..."
  docker-compose -f docker-compose-prod.yml exec nginx nginx -s reload
}
function make_config_file() {
   touch ./NginxProxy/conf.p/app.conf
   local domain_name=$1

echo 'server {
     listen 80;
     server_tokens off;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }
    location / {
        return 301 https://$host$request_uri;
    }
}

server {
    listen 443 ssl;
    server_tokens off;

    ssl_certificate /etc/letsencrypt/live/'$domain_name'/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/'$domain_name'/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf /etc/nginx/mime.types;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
    root  /var/www/html/ui/dist;
    index index.php index.html index.htm;

    location / {
          try_files $uri $uri/ /index.php?$query_string /index.html;
          gzip_static on;
       }

    location /api/ {
            root  /var/www/html/htdocs/mpmanager/public;
            index index.php index.html index.htm;
            try_files $uri =404;
            fastcgi_split_path_info ^(.+\.php)(/.+)$;
            fastcgi_pass laravel:9000;
            fastcgi_index index.php;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param PATH_INFO $fastcgi_path_info;
    }

}' > ./NginxProxy/conf.p/app.conf
}
function email_validation(){
  regex="^[a-z0-9!#\$%&'*+/=?^_\`{|}~-]+(\.[a-z0-9!#$%&'*+/=?^_\`{|}~-]+)*@([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z0-9]([a-z0-9-]*[a-z0-9])?\$"
  local email=$1
  if [[ $email =~ $regex ]] ; then
    echo 1
else
    echo 0
fi
}
###################################################################
##################### Script start ################################
###################################################################
domains_list=()
rsa_key_size=4096
data_path="./certbot"
email=""  # Adding a valid address is strongly recommended
staging=0 # Set to 1 if you're testing your setup to avoid hitting request limits
ipDecision=""
check_if_docker_compose_installed
echo "###################################################################################"
echo "#                               IMPORTANT !!                                      #"
echo "###################################################################################"
echo "#  MicroPowerManager has got two types of installations for the Prod. mode,       #"
echo "#  those are IP based & domain based with SSL certificate.                        #"
echo "###################################################################################"
echo ""

   read -p "Which installation type do you prefer IP based or domain based? (i/d)" ipDecision
   while [ "$ipDecision" != "d" -a  "$ipDecision" != "D" -a "$ipDecision" != "i" -a "$ipDecision" != "I" ]; do
   read -p "Which installation type do you prefer IP based or domain based ? (i/d)" ipDecision
  done
    if [ "$ipDecision" == "D" ] || [ "$ipDecision" == "d" ]; then

echo "###################################################################################"
echo "# This script will setup SSL Certificates that are required for the Prod. mode    #"
echo "# If you already configured your Certificates, you can skip the first part and    #"
echo "# start the web services.                                                         #"
echo "###################################################################################"
echo ""

    read -p "MicroPowerManager starting with domain with SSL certificate mode. Continue set up certification? (n/Y) " decision

if [ "$decision" != "Y" ] && [ "$decision" != "y" ] && [ "$decision" != "" ]; then
  echo "Do you want to start only the web services? "

   read -p "(N/y)" webservice

  if [ "$webservice" == "Y" ] || [ "$webservice" == "y" ]; then
    echo " Starting web services please wait."
    echo $( docker-compose -f docker-compose-prod.yml up --build --force-recreate -d )
    echo "Web services started "
  fi

  exit
else
  domainsDone=0

  while [ $domainsDone == 0 ]; do
    read -p "Enter domain name (without www) and press [ENTER]:" domain
    if [ "$domain" == "" ]; then
        echo "Please enter a valid domain"
        continue
    fi
    domains_list+=($domain)
    domains_list+=("www.$domain")
    for key in ${!domains_list[*]}; do
      printf "%4d: %s\n" $key ${domains_list[$key]}
    done
    read -p "Are the inputs correct? (y/n) " newDomain
    if [ "$newDomain" != "N" ] && [ "$newDomain" != "n" ]; then

      domainsDone=1
      make_config_file "$domain"
      break
    else
        domains_list=()
    fi
  done

   read -p "Enter a valid email address and press [ENTER]:" email
    echo "Email address :" $email
   while [ $(email_validation "$email") == 0 ]; do
    read -p "Enter a valid email address and press [ENTER]:" email
    echo "Email address :" $email
    emailIsValid=$(email_validation "$email")
  done

  setup_tls_parameters $data_path

  # Create dummy certificates if needed.
  n_renewals=0
  dummy_certificate_domains=()
  for domains in "${domains_list[@]}"; do
    renew_certificate=$(should_renew_certificate "$data_path" "$domains")
    if [ "$renew_certificate" -eq 1 ]; then
      n_renewals=$((n_renewals + 1))
      make_dummy_certificate "$data_path" "$domains"
      dummy_certificate_domains+=("${domains[0]}")
    else
      dummy_certificate_domains+=("")
    fi
  done
  if [ "$n_renewals" -eq "0" ]; then
    echo "No new renewals, starting web services and quiting."
    echo $( docker-compose -f docker-compose-prod.yml up --build --force-recreate -d )
    echo "Web services started "
    exit
  fi

  start_nginx

  # For each domain renew certificate (if needed).
  n_domains="${#domains_list[@]}"
  for ((i = 0; i < "$n_domains"; i++)); do
    dummy_certificate_domain="${dummy_certificate_domains[$i]}"
    if [ -z "$dummy_certificate_domain" ]; then
      # if we did not create dummy certificate continue to the next domain.
      continue
    fi

    delete_dummy_certificate "$dummy_certificate_domain"

    domains="${domains_list[$i]}"

    request_new_certificate "$domains" "$email" "$rsa_key_size" "$staging"
  done

  # Reload nginx with new certificates.
  reload_nginx

fi
elif [ "$ipDecision" == "I" ] || [ "$ipDecision" == "i" ]; then

echo "###################################################################################"
echo "# This script will start MicroPowerManager with a IP-base set up                  #"
echo "# in the Prod. mode.                                                              #"
echo "###################################################################################"
echo ""

echo " MicroPowerManager is starting please wait."

 echo $( docker-compose -f docker-compose-prod-non-domain.yml up --build --force-recreate -d)
  echo "MicroPowerManager started. "

exit
else
   ipDecision=""
fi



