<?php

/***********
 ATTENTION :
************

 1°) Ce fichier doit être placer dans la racine de votre bundle => "\Composer"
 2°) Le namespace "Amu\VotreBundle*\Composer"; doit être modifier selon votre namespace/bundle
 3°) un fichier d'exemple est proposé plus bas : installMonBundle()
 4°) pour utiliser l'installation paramétré il faut :

    a) définir une fonction d'installation en "public static function NomDeFonction(CommandEvent $event){...}"
        vous pouvez vous inspirer de la fonction plus bas "installMonBundle()...

            par notre exemple on dira que l'on a défini un fonction : installMonBundle()

    b) ajouter dans le "composer.json" dans la racine du PROJET PRINCIPAL de votre application symfony:
        (ATTENTION ce n'est pas le composer.json de votre bundle...)

        "scripts": {
            ...
            "post-install-cmd": [
                "Amu\\VotreBundle*\Composer\\AmuInstallHelper::installMonBundle",
                ...
            ],
            "post-update-cmd": [
                "Amu\\VotreBundle*\\Composer\\AmuInstallHelper::installMonBundle",
                ...
            ]
        },

        avec "Amu\\VotreBundle*" à changer selon le namespace et le nom de votre bundle bien sûr...
        avec "installMonBundle" le nom de votre fonction d'auto-installation personnalisé...

    c) quand vous lancerez un commande "composer update" sur le  projet PRINCIPAL de votre application:
        le script ce lancera automatiquement à chaque install/update selon votre configiguration en b)...


*/

namespace Amu\RoleBundle\Composer;

use Composer\Script\CommandEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Amu\RoleBundle\Composer\Utils\Installer;

/**
 *      Version secure du 20/10/2015 13:00
 *
 * InstallHelper est une classe d'aide à l'auto-installation/paramètrage d'un bundle
 *
 * Note : la Modification des fichiers ce fait pour le moment en mode chaîne de caractère.
 *
 *  À l'avenir il devrait utiliser Yaml::Parse() // Yaml::dump...() pour les fichiers yml
 * @see _addImportsV2
 *
 * @see Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator
 * @see Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator
 *
 * @author michel.ubeda@univ-amu.fr
 * @class InstallHelper
 *
 * @see https://github.com/wemakecustom/composer-script-utils/blob/master/README.md
 */
class ScriptHandler
{

    public static function install(CommandEvent $event)
    {
        /****************************************************
        *      paramètres à modifier selon vos besoins      *
        ****************************************************/
        $BundleName = "AmuRoleBundle";
        $bundleDeclaration = "new Amu\RoleBundle\AmuRoleBundle(),";
        $arNeedImports = array('roles.yml');
        $bckConfig = true;
        $verbose=true;
        $arConfigFiles = array("roles.yml");
        /***************************************************/

        $event->getIO()->write("\n\tDébut de l'installation de $BundleName...\n");

        $installer = new Installer();

        $installer->register($event, $BundleName, $bundleDeclaration);
        $installer->copyConfig($event,$arConfigFiles,$verbose);
        $installer->addImport($event,$arNeedImports,$verbose,$bckConfig);
        //
        $event->getIO()->write("\n\tInstallation de $BundleName terminé.\n");
    }

}
