Installation MPM
================

System Requirements
-------------------

PHP ^7.3

Node ^v14.3

Installation
------------

1. Clone or download the repository
2. Build the docker containers with ``docker-compose up``

Installing Dependencies
-----------------------

All dependencies will be automatically installed on the installation
step. However, if you need additional dependencies, install them in the
``laravel`` container. To Install additional php dependencies enter the
Docker-Container named ``laravel`` navigate to ``mpmanager`` & run
``php ../composer.phar install XXX``

Migrate the database
--------------------

-  Run ``docker exec -it laravel /bin/bash`` to jump into the laravel
   container
-  navigate to ``mpmanager`` directory with ``cd mpmanager``
-  Run ``php artisan migrate --seed`` to initialize the Database. The
   ``--seed`` option will create the default user to login.
-  The default user to login is ``admin@admin.com`` and
   ``basic-password``.

Installing Customer Registration App (Android)
----------------------------------------------

Please read the project documentation to get an idea of why we're using
a separate app to register customers via an Android-App. Follow the link
to get to the Customer Register App Project