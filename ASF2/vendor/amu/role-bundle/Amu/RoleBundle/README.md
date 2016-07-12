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
        "amu/role-bundle": "dev-master"
    },
    "repositories": [
      {
        "type": "git",
        "url": "ssh://gitadmin@rubis.pp.univ-amu.fr/RoleBundle.git"
      }
    ], 

### b) Parameters Symfony sandbox 

Please edit app/AppKernel.php file to update symfony Kernel

        $bundles = array(
             ...
            new Amu\RoleBundle\AmuRoleBundle(),
        );

### c) Parameters Symfony sandbox for use AmuPdfBundle

Please edit app/config/roles.yml and add the following line

à compléter...

 

