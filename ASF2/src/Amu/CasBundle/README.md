README FILE
===========

1) Introduction
---------------
This file will help you to install and configure the project.

2) Installation
---------------

### a) Add the bundle to your composer.json file at the project root

    "require": {
        ...
        "amu/cas-bundle": "master"
    },
    "repositories": [
      {
        "type": "git",
        "url": "ssh://gitadmin@rubis.pp.univ-amu.fr/CasBundle.git"
      }
    ], 

### b) Parameters Symfony sandbox 

Please edit app/AppKernel.php file to update symfony Kernel

        $bundles = array(
             ...
            new Amu\CasBundle\AmuCasBundle(),
        );

### c) Parameters Symfony routing.yml

Please edit app/config/rooting.yml file and add 3 routes :

    login:
        path:   /login_check

    login_check:
        path:   /login_check

    logout:
        path:   /logout

### d) Parameters Symfony sandbox for use AmuCasBundle

Please edit app/config/config.yml and add the following line
       
    amu_cas:

      timeout:
          idle: 3600
          # idle = durée d'inactivitée maximale

          # Attention le timout de session est défini par symfony dans :
          # "app/config/config.yml"
          #   => framework:  session:  cookie_lifetime: 7200
          #   => framework:  session:  gc_maxlifetime: 3600

      force_logout:
          on_session_timeout: false
          on_idle_timeout: false

### e) Parameters Symfony sandbox for use AMU CAS Authentication

Please edit app/config/security.yml file to update symfony security policy

        # ...
        # If you want you can use a custom user provider
        # ...
        providers:
            amu_cas_user:
                id: cas.user_provider
	# ...
	    firewalls:
	        dev:
	            pattern: ^/(_(profiler|wdt)|css|images|js)/
	            security: false
	
	        secured_area:
	            pattern: ^/demo/secured/
              provider: amu_cas_user
              anonymous: false # ou true              
              cas:
                  cas_server: ident.univ-amu.fr
                  cas_port: 443
                  cas_path: /cas/
                  ca_cert_path: ~
                  cas_protocol: "2.0" #S1
                  cas_mapping_attribute: username
                  check_path: /login_check       
                  cas_logout: /logout       

