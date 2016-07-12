<?php

namespace VotreNamespace\VotreBundle*\Composer;

use Composer\Script\CommandEvent;
use VotreNamespace\VotreBundle*\Composer\Utils\Installer;

class ScriptHandler
{
    public static function install(CommandEvent $event)
    {
        /*************************************************************
         *      paramètres à modifier pour selon vos besoins          *
         **************************************************************/
        $BundleName = "AmuPdfModelesBundle";
        $bundleDeclaration = "new Amu\PdfModeles\AmuPdfModelesBundle(),";
        $arNeedImports = array("pdfModeles.yml");
        $bckConfig = true;
        $verbose=true;
        $arConfigFiles = array("pdfModeles.yml");
        $detectRoutesPresence="@AmuPdfModelesBundle/Controller/";
        $newRoutesDefs=<<<EOF

# AmuPdfModelesBundle
pdf_modeles:
    resource: "@AmuPdfModelesBundle/Controller/"
    type:     annotation
    prefix:   /

EOF;
        $arSourcesDestinationsFiles=array(
            "Resources/views/visuMacros.html.twig"=>"views/visuMacros.html.twig",
            "Resources/views/ihm_css.html.twig"=>"views/ihm_css.html.twig",
            "Resources/views/ihm_js.html.twig"=>"views/ihm_js.html.twig",
        );
        /**************************************************************/

        $installer = new Installer();

        $event->getIO()->write("\n\tDébut de l'installation de $BundleName...\n");

        $installer->register($event, $BundleName, $bundleDeclaration, $verbose);
        $installer->copyConfig($event, $arConfigFiles,$verbose);
        $installer->addImport($event, $arNeedImports, $verbose, $bckConfig);
        $installer->addRoutes($event, $detectRoutesPresence, "", $newRoutesDefs, $verbose );

        $installer->copyResources($event, $arSourcesDestinationsFiles,$verbose);
        $installer->installAssets($event);
        $installer->clearCache($event);

        $event->getIO()->write("\n\tInstallation de $BundleName terminé.\n");

    }

}
