<?php

/*
 *      Script d'installation automatique du Bundle :
 *
 * Ce que fait le script :
 *  - registerBundle dans le appKernel.php
 *  - modif/ajout de routes dans "app/config/routing.yml"
 *  - modif/ajout d'imports dans "app/config/config.yml"
 *  - copies des fichiers de paramètrages modèles ("vendors/amu/cas-bundle/.../Resources/config/..." => "app/config/...")
 *
 *      Fortement inspiré de DistributionBundle/Composer/ScriptHandler.php
 *
 */

namespace Amu\CasBundle\Composer;

use Symfony\Component\Filesystem\Filesystem;
use Composer\Script\CommandEvent;
use Amu\CasBundle\Composer\Utils\Installer;

/**
 * @author Michel Ubéda <michel.ubeda@univ-amu.fr>
 *
 *    Fortement inspiré de DistributionBundle/Composer/ScriptHandler.php
 *
 * @see /vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Composer/ScriptHandler.php
 */
class ScriptHandler
{

    public static function install(CommandEvent $event)
    {

        /*************************************************************
         *      paramètres à modifier pour selon vos besoins          *
         **************************************************************/

        $BundleName = "AmuCasBundle";
        $bundleDeclaration = "new Amu\CasBundle\AmuCasBundle(),";
        $arNeedImports = array("cas.yml");
        $bckConfig = true;
        $verbose=true;
        $arConfigFiles = array(
            "cas.yml",
            "sample_security_cas_1.yml",
            "sample_security_cas_2.yml",
            "sample_security_cas_3.yml",
            "sample_security_cas_4.yml"
        );

        $configFile="config/config.yml";

        $tab=4; // (1 tab = 4 x espaces)

        $arModifs=array(
            0=>array(
                "comments"=> " - temps maximal des cookies de sessions (cookie_lifetime)",
                "after"=> str_repeat(" ", $tab)."session:",
                "var"=> str_repeat(" ", $tab*2)."cookie_lifetime: ",
                "value"=> 7200,
            ),
            1=>array(
                "comments"=> " - temps maximal des cookies de sessions (gc_maxlifetime)",
                "after"=> str_repeat(" ", $tab)."session:",
                "var"=> str_repeat(" ", $tab*2)."gc_maxlifetime: ",
                "value"=> 3600,
            ),
        );

        $detectRoutesPresence="@AmuCasBundle/Controller/";
        $newRoutesDefs=<<<EOF

# AmuCasBundle
cas:
    resource: "@AmuCasBundle/Controller/"
    type:     annotation
    prefix:   /

login:
    path:   /login_check

login_check:
    path:   /login_check
EOF;

        /**************************************************************/

        $event->getIO()->write("\n\tDébut de l'installation de $BundleName...\n");

        $installer = new Installer();

        $installer->register($event, $BundleName, $bundleDeclaration, $verbose);
        $installer->copyConfig($event, $arConfigFiles,$verbose);
        $installer->addImport($event, $arNeedImports, $verbose, $bckConfig);
        $installer->addConfigValues($event, $configFile, $arModifs, $tab, $verbose);
        $installer->addRoutes($event, $detectRoutesPresence, "", $newRoutesDefs, $verbose );

        $event->getIO()->write("\n\tInstallation de $BundleName terminé.\n");

        $event->getIO()->write("ATTENTION: Vous devez modifier manuellement security.yml en fonction de vos besoins.");
        $event->getIO()->write("Pour vous aider, vous disposez de 4 fichiers d'exemples 'sample_security_cas_1-4.yml'.\n");

    }

}
