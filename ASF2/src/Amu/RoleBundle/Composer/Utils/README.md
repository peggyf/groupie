README FILE
===========

1) Introduction
---------------
Cette classe vous permet de rendre votre bundle auto-installable

2) Installation
---------------


Les opérations suivantes sont à réaliser dans la RACINE DE VOTRE BUNDLE:

### a) Placer le fichier Installer.php dans le répertoire "\Composer\Util\Installer.php"

### b) Modifier le namespace du fichier Composer\Util\Installer.php pour qu'il corresponde au votre ('VotreNamespace\VotreBundle*\Composer\Util')

### c) Définir une classe "ScriptHandler" dans le répertoire "Composer"

### d) Définir une fonction d'installation du style "public static function nomDeMaFonction(CommandEvent $event){...}"
        (vous pouvez vous inspirer du fichier "sample.md" => install() )


L'opération suivante est à réaliser dans la racine du PROJET PRINCIPAL de votre application symfony:

### e) Ajouter dans le "composer.json"
(ATTENTION ce n'est pas le composer.json de votre bundle, mais celui du projet qui l'utilisera...)

        "scripts": {
            ...
            "post-install-cmd": [
                "VotreNamespace\\VotreBundle*\Composer\\ScriptHandler::install**",
                ...
            ],
            "post-update-cmd": [
                "VotreNamespace\\VotreBundle*\\Composer\\ScriptHandler::install**",
                ...
            ]
        },

        avec "VotreNamespace\\VotreBundle*" à changer selon le namespace et le nom de votre bundle bien sûr...
        avec "install**" le nom de votre fonction d'auto-installation personnalisée...


### f) Quand vous lancerez une commande "composer update" sur le  projet PRINCIPAL de votre application:

la fonction ScriptHandler:install ce lancera automatiquement à chaque install/update selon votre configiguration...
