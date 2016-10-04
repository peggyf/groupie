<?php


namespace Amu\CasBundle\Composer\Utils;

use Composer\Script\CommandEvent;
use Symfony\Component\Filesystem\Filesystem;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;


/**
 *          Installer est une classe d'aide à l'auto-installation/paramètrage de votre bundle
 *
 * Note :
 *
 * La modification des fichiers ce fait en mode chaîne de caractère (tout comme le bundle DistributionBundle de symfony...)
 * À l'avenir on pourrait peut être utiliser Yaml::Parse() // Yaml::dump...() pour les fichiers yml
 * @see _addImportsV2
 *
 * @see Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator
 * @see Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator
 * @see /vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Composer/ScriptHandler.php
 *
 * @author michel.ubeda@univ-amu.fr
 * @version Secure du 21/10/2015 16:00
 *
 * @class Installer
 *
 */
class Installer
{
    /**
     * Composer variables are declared static so that an event could update
     * a composer.json and set new options, making them immediately available
     * to forthcoming listeners.
     */
    protected static $options = array(
        'symfony-app-dir' => 'app',
        'symfony-web-dir' => 'web',
        'symfony-assets-install' => 'hard',
        'symfony-cache-warmup' => false,
    );

    protected static function getOptions(CommandEvent $event)
    {
        $options = array_merge(static::$options, $event->getComposer()->getPackage()->getExtra());

        $options['symfony-assets-install'] = getenv('SYMFONY_ASSETS_INSTALL') ?: $options['symfony-assets-install'];

        $options['process-timeout'] = $event->getComposer()->getConfig()->get('process-timeout');

        return $options;
    }

    /**
     *    Permet d'enregistrer automatiquement votre bundle dans app/AppKernel.php
     *
     * @param CommandEvent $event gestionnaire d'événement pour l'écriture console
     * @param string $BundleName nom du bundle
     * @param string $bundleDeclaration ligne de déclaration du bundle FINI PAR UNE VIRGULE
     * @param bool $verbose Optionnel (defaut=true) activer le mode verbose
     * @return boolean
     * @example InstallHelper::registerBundle($event,"AmuMonBundle","new Amu\MonBundle\AmuMonBundle(),");
     */
    public static function register(CommandEvent $event, $BundleName, $bundleDeclaration, $verbose = true)
    {
        $options = static::getOptions($event);
        $appDir = $options['symfony-app-dir'];
        $kernelFile = $appDir . '/AppKernel.php';
        $ref = 'new AppBundle\AppBundle(),';
        $content = "";
        $result = false;

        try {
            $content = file_get_contents($kernelFile);
        } catch (\Exception $ex) {
            $event->getIO()->write("ERREUR: impossible d'ouvrir le fichier $kernelFile !\n" . $ex->getMessage());
        }

        if ($content != "") {
            if (false === strpos($content, $bundleDeclaration)) {
                $autoRec=true;
                if ($verbose) {
                    $autoRec=($event->getIO()->askConfirmation("\nVoulez-vous enregistrer \"$BundleName\" dans le Kernel ? [Y/n]", true));
                }
                if ($autoRec) {
                    $updatedContent = str_replace($ref, $bundleDeclaration . "\n            " . $ref, $content);
                    file_put_contents($kernelFile, $updatedContent);
                    $result = true;

                    if ($verbose) {
                        $event->getIO()->write("Enregistrement dans le Kernel => OK");
                    }
                }

            } else {
                if ($verbose) {
                    $event->getIO()->write("Enregistrement dans le Kernel => IGNORÉ (déjà enregistré)");
                    $result = false;
                }
            }
        }
        else{
            $event->getIO()->write("ERREUR: fichier $kernelFile vide !\n" . $ex->getMessage());
        }

        return $result;
    }

    /**
     *    Copie des fichiers de Ressources globales dans "app/Resources/..."
     *
     * version sécure avec détection auto des fichiers déjà présents..
     *  1)demande avant écrasement si présent
     *  2)demande copie des nouveaux fichiers (avec l'extention .new => "FILENAME.new")
     *  3) demande si procéder de la même manière pour les autres fichiers...
     *
     * @param CommandEvent $event gestionnaire d'événement pour l'écriture console
     * @param array $arConfigFiles|array() un tableau des fichiers à copier dans app/Resources array( "sourcePath"=>"destinationPath")
     * @param boolean $verbose|true
     */
    public static function copyResources(CommandEvent $event, $arSourcesDestinationsFiles = array(),$verbose=true)
    {
        $nbFile=count($arSourcesDestinationsFiles);
        if ($nbFile > 0) {
            if ($verbose) {
                $event->getIO()->write("\nCopie des fichiers de Resources...");
            }
            $fs = new Filesystem();
            $options = static::getOptions($event);
            $appDir = $options['symfony-app-dir'];
            $askOnce=false; $sameWayAllFile=false; $copyNew=false;
            foreach ($arSourcesDestinationsFiles as $oneSrc=>$oneDest) {
                $isPresent=($fs->exists($appDir . '/Resources/' . $oneDest));
                if($verbose){
                    if($sameWayAllFile==false){
                        $toCopy=false;
                        $copyNew=false;
                        if($isPresent){
                            if ($event->getIO()->askConfirmation("Le fichier $oneDest existe déjà : voulez-vous l'ÉCRASER ? [y/N]", false)) {
                                $toCopy=true;
                            }else{
                                $copyNew=($event->getIO()->askConfirmation("Voulez-vous copier le NOUVEAU fichier => $oneDest.new ? [Y/n]", true));
                            }
                            if ( ($nbFile > 1) && ($askOnce==false) ) {
                                $askOnce=true;
                                $sameWayAllFile=($event->getIO()->askConfirmation("Voulez-vous procédez de la même manière pour les AUTRES fichiers ($nbFile) ? [Y/n]", true));
                            }
                        }
                    }
                }
                else{ // en mode silent
                    $toCopy=false; // on n'écrase pas le fichier de configuration
                    $copyNew=true; // on copie le nouveau fichier en "NomFichierConfig.yml.new"
                }
                //
                if($isPresent){
                    if($toCopy){
                        // on descent de /../../ car on est dans "Composer/Utils"
                        $fs->copy(__DIR__ . '/../CasBundle/' . $oneSrc, $appDir . '/Resources/' . $oneDest, true);
                        $event->getIO()->write(" => app/Resources/$oneDest => OK (écrasé)");
                    }else{
                        if($copyNew){
                            $fs->copy(__DIR__ . '/../CasBundle/' . $oneSrc, $appDir . '/config/' . $oneDest.".new", true);
                            $event->getIO()->write(" => app/Resources/$oneDest.new => OK");

                        }
                        else{
                            $event->getIO()->write(" => app/Resources/$oneDest => IGNORÉ");
                        }
                    }
                }
                else{
                    // on descent de "/../../" car on est dans "Composer/Utils"
                    $fs->copy(__DIR__ . '/../CasBundle/' . $oneSrc, $appDir . '/Resources/' . $oneDest, true);
                    $event->getIO()->write(" => app/Resources/$oneDest => OK");
                }
            }
        }
    }

    /**
     *    Copie des fichiers de configurations 'modèles' dans "app/config/..."
     *
     * version sécure avec détection auto des fichiers déjà présents..
     *  1)demande avant écrasement si présent
     *  2)demande copie des nouveaux fichiers (avec l'extention .new => "FILENAME.new")
     *  3) demande si procéder de la même manière pour les autres fichiers...
     *
     * @param CommandEvent $event gestionnaire d'événement pour l'écriture console
     * @param Filesystem $fs une classe d'accès/modification aux fichiers
     * @param string $appDir le répertoire "app" de base de symfony
     * @param array $arConfigFiles|array() un tableau des fichier de configuration à copier
     * @param boolean $verbose|true
     */
    public static function copyConfig(CommandEvent $event, $arConfigFiles = array(),$verbose=true)
    {
        $nbFile=count($arConfigFiles);
        if ($nbFile > 0) {
            if ($verbose) {
                $event->getIO()->write("\nCopie des fichiers de Configurations...");
            }
            $fs = new Filesystem();
            $options = static::getOptions($event);
            $appDir = $options['symfony-app-dir'];
            $askOnce=false; $sameWayAllFile=false; $copyNew=false;
            foreach ($arConfigFiles as $oneFile) {
                $isPresent=($fs->exists($appDir . '/config/' . $oneFile));
                if($verbose){
                    if($sameWayAllFile==false){
                        $toCopy=false;
                        $copyNew=false;
                        if($isPresent){
                            if ($event->getIO()->askConfirmation("Le fichier $oneFile existe déjà : voulez-vous l'ÉCRASER ? [y/N]", false)) {
                                $toCopy=true;
                            }else{
                                $copyNew=($event->getIO()->askConfirmation("Voulez-vous copier le NOUVEAU fichier => $oneFile.new ? [Y/n]", true));
                            }
                            if ( ($nbFile > 1) && ($askOnce==false) ) {
                                $askOnce=true;
                                $sameWayAllFile=($event->getIO()->askConfirmation("Voulez-vous procédez de la même manière pour les AUTRES fichiers ($nbFile) ? [Y/n]", true));
                            }
                        }
                    }
                }
                else{ // en mode silent
                    $toCopy=false; // on n'écrase pas le fichier de configuration
                    $copyNew=true; // on copie le nouveau fichier en "NomFichierConfig.yml.new"
                }
                //
                if($isPresent){
                    if($toCopy){
                        // on descent de /../../ car on est dans "Composer/Utils"
                        $fs->copy(__DIR__ . '/../../Resources/config/' . $oneFile, $appDir . '/config/' . $oneFile, true);
                        $event->getIO()->write(" => app/config/$oneFile => OK (écrasé)");
                    }else{
                        if($copyNew){
                            $fs->copy(__DIR__ . '/../../Resources/config/' . $oneFile, $appDir . '/config/' . $oneFile.".new", true);
                            $event->getIO()->write(" => app/config/$oneFile.new => OK");

                        }
                        else{
                            $event->getIO()->write(" => app/config/$oneFile => IGNORÉ");
                        }
                    }
                }
                else{
                    $fs->copy(__DIR__ . '/../../Resources/config/' . $oneFile, $appDir . '/config/' . $oneFile, true);
                    $event->getIO()->write(" => app/config/$oneFile => OK");
                }
            }
        }
    }

    /**
     *    Permet d'ajouter dans le fichier de configuration app/config/routing.yml de nouvelles définition de routes $newRoutesDefs,
     *    Ssi $detectPresent n'est pas trouvé dans le fichier...
     *
     * @param CommandEvent $event gestionnaire d'événement pour l'écriture console
     * @param string $detectPresent chaîne de caractères de réference permettant de detecté si la route a déjà été ajouter ou pas
     * @param string $comments ajoute un commentaire qui précédera les nouvelles routes
     * @param string $newRoutesDefs chaîne de caractères contenant les nouvelles définitions de routes à ajouter
     * @param bool $verbose Optionnel (defaut=true) activer le mode verbose
     * @return boolean résultat de l'ajout
     * @example InstallHelper::addRouting($event,"@AmuMonBundle/Controller/","Routes inserer AmuMonBundle","amu_mon_bundle:\n\tresource: "@AmuMonBundle/Controller/"\n\ttype: annotation\n");
     */
    public static function addRoutes(CommandEvent $event, $detectPresence, $comments, $newRoutesDefs, $verbose = true)
    {
        if ($verbose) {
            $event->getIO()->write("\nMise à jour des routes (routing.yml)...");
        }
        $options = static::getOptions($event);
        $appDir = $options['symfony-app-dir'];
        $result = false;
        $routingFile = $appDir . '/config/routing.yml';
        $routingData = file_get_contents($routingFile);
        if ($routingData != "") {
            if (strpos($routingData, $detectPresence) == false) {
                $routingData .= (($comments != "") ? "\n # $comments" : "\n ") . $newRoutesDefs;
                file_put_contents($routingFile, $routingData);
                $result = true;
                $event->getIO()->write(" => OK");

            } else {
                if ($verbose) {
                    $event->getIO()->write(" => IGNORÉES (routes déjà présentes)");
                }
            }
        }

        return $result;
    }

    /**
     *    Ajout une ou plusieures ligne(s) d'import(s) dans le fichier de configuration principale "app/config/config.yml"
     *
     * @param CommandEvent $event gestionnaire d'événement pour l'écriture console
     * @param array $arNewImports un tableau des noms de fichier de configuration à ajouter aux "imports: "
     * @param boolean $verbose Optionnel (defaut=true) activer le mode verbose
     * @param boolean $bckConfig Optionnel (defaut=true) activer la copie de sauvegarde => "app/config/config.yml.orig"
     * @return boolean|integer nombre de ligne d'imports inscrit dans le fichier app/config/config.yml
     */
    public static function addImport(CommandEvent $event, $arNewImports, $verbose = true, $bckConfig = true)
    {
        $options = static::getOptions($event);
        $appDir = $options['symfony-app-dir'];
        $result = 0;
        $configFile = $appDir . '/config/config.yml';
        if ($verbose) {
            $event->getIO()->write("\nMise à jour des importations (config.yml)...");
        }

        $contents = file_get_contents($configFile);
        if ($contents != "") {
            foreach ($arNewImports as $needValue) {
                $needImport = "    - { resource: $needValue }";
                if (false === strpos($contents, $needImport)) {
                    if ($verbose) {
                        $event->getIO()->write(" $needValue => OK");
                    }
                    $ref = "    - { resource: parameters.yml }";
                    $contents = str_replace($ref, $ref . "\n" . $needImport, $contents);
                    $result++;
                } else {
                    if ($verbose) {
                        $event->getIO()->write(" $needValue => IGNORÉE (importation déjà faite)");
                    }
                }
            }
            if ($result>0) {
                if ($bckConfig) {
                    system("cp $configFile $configFile.orig");
                }
            }
            $result = file_put_contents($configFile, $contents);
        } else {
            $event->getIO()->write("ERREUR le fichier $configFile n'a pas pu être ouvert...");
        }

        return $result;
    }

    /**
     *    Ajout d'un paramètre dans le fichier de configuration principale "app/config/config.yml"
     *
     * @param CommandEvent $event gestionnaire d'événement pour l'écriture console
     * @param string $configFile le nom du fichier de configuration à modifier
     * @param array $arModifs le tableau de configuration des couples variables/valeurs/emplacements à ajouter cf example ci-dessous...
     *
     * @example valeur de $arModifs
     * <code>
     *  $arModifs = array(
     *
     *   1=>array(
     *      "comments"=>"...",
     *      "after"=>"chaine de caractère ABC..." ou "before "=>"chaine de caractère CDE..",
     *      "var"=> "la chaine de description de la variable..."
     *      "value"=> "la chaine de description de sa valeur..."
     *      ),
     *   2=>array(
     *      ...
     *      ),
     *  );
     * </code>
     * @param integer/4 $tab le nombres d'espaces pour 1 tabulation (1 tab = 4 x espaces)
     * @param boolean/true $verbose pour activer le mode verbose
     * @return integer le nombre de valeurs ajoutées
     */
    public static function addConfigValues(CommandEvent $event, $configFile, $arModifs, $tab=4 ,$verbose = true)
    {
        $nbAdded=0;

        $event->getIO()->write("\nMise à jour de $configFile...");

        if(count($arModifs)==0){
            $event->getIO()->write("Erreur: Aucun élément de configuration paramétré !!!");
        }else{

            $options = static::getOptions($event);
            $appDir = $options['symfony-app-dir'];
            $result = 0;
            $configFile = $appDir . '/'.$configFile;

            $contents = file_get_contents($configFile);

            if ($contents!="") {

                foreach($arModifs as $key=>$aModif){

                    $varName=$aModif["var"];
                    $majComment="Mise à jour de l'élément de configuration n°$key: $varName";
                    $after=(isset($aModif["after"])?$aModif["after"]:"");
                    $before=(isset($aModif["before"])?$aModif["before"]:"");
                    $value=$aModif["value"];
                    $comments=(isset($aModif["comments"])?$aModif["comments"]:"$majComment");

                    if(($varName=="") || ($value=="") || (($after=="") && ($before=="")) ){
                        $event->getIO()->write("Erreur sur l'élément de configuration n°$key :".print_r($aModif,true)."\t=> mise à jour IGNORÉE !!!");
                    }else{

                        $added=false;
                        if (false === strpos($contents, $varName)) {
                            if($before!=""){
                                $contents= str_replace($before, $varName.$value. "\n" .$before , $contents);
                                $added=true;
                                $nbAdded++;
                            }elseif($after!=""){
                                $contents= str_replace($after, $after . "\n" .$varName.$value, $contents);
                                $added=true;
                                $nbAdded++;
                            }
                        }
                        if($verbose){
                            $event->getIO()->write("$comments => ".($added?"OK":"déjà présent"));
                        }

                    }
                }

                file_put_contents($configFile, $contents);

            } else {
                $event->getIO()->write("ERREUR le fichier $configFile n'a pas pu être ouvert...");
            }

        }

        return ($nbAdded);

    }

    /**
     *    Modification d'un paramètre dans le fichier de configuration principale "app/config/config.yml"
     *
     * @param CommandEvent $event gestionnaire d'événement pour l'écriture console
     * @param string $name nom de la varaible à ajouter
     * @param string/integer $value valeur de la varaible à ajouter
     * @param boolean $verbose Optionnel (defaut=true) activer le mode verbose
     * @return boolean résultat de l'ajout
     */
    public static function _modifValue(CommandEvent $event, $name, $value, $verbose = true)
    {
        return false;
    }

    /**
     * @param string $needValue valeur de la ressource à rajouter dans les imports: - { ressources: "$needValue" }
     * @param bool $backupOriginaConfig
     * @return type
     */
    private function _addImportsV2($arNeedImports = array(), $backupOriginaConfig = true)
    {
        $addImports = 0;

        if (count($arNeedImports) > 0) {
            $appDir = preg_replace('/vendor.*/', "app", __DIR__);
            $configName = $appDir . "/config/config.yml";
            $contents = file_get_contents($configName);
            $parsed = Yaml::parse($contents);

            foreach ($arNeedImports as $needValue) {
                $found = false;
                if (isset($parsed["imports"])) {
                    foreach ($parsed["imports"] as $key => $oneValue) {
                        if (($key == "resource") && ($oneValue == "$needValue")) {
                            $found = true;
                            break;
                        }
                    }
                }
                if (!$found) {
                    $parsed["imports"][] = array("resource" => '"' . $needValue . '"');
                    $addImports++;
                }
            }

            if ($backupOriginaConfig) {
                system("cp $configName $configName.orig");
            }

            file_put_contents($configName, Yaml::dump($parsed));
        }

        return $addImports;
    }


    /* DÉBUT récupération de code issue de /vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Composer/ScriptHandler.php */


    protected static function hasDirectory(CommandEvent $event, $configName, $path, $actionName)
    {
        if (!is_dir($path)) {
            $event->getIO()->write(sprintf('The %s (%s) specified in composer.json was not found in %s, can not %s.', $configName, $path, getcwd(), $actionName));

            return false;
        }

        return true;
    }

    /**
     * Returns true if the new directory structure is used.
     *
     * @param array $options Composer options
     *
     * @return bool
     */
    protected static function useNewDirectoryStructure(array $options)
    {
        return isset($options['symfony-var-dir']) && is_dir($options['symfony-var-dir']);
    }

    /**
     * Returns a relative path to the directory that contains the `console` command.
     *
     * @param CommandEvent $event      The command event.
     * @param string       $actionName The name of the action
     *
     * @return string|null The path to the console directory, null if not found.
     */
    protected static function getConsoleDir(CommandEvent $event, $actionName)
    {
        $options = static::getOptions($event);

        if (static::useNewDirectoryStructure($options)) {
            if (!static::hasDirectory($event, 'symfony-bin-dir', $options['symfony-bin-dir'], $actionName)) {
                return;
            }

            return $options['symfony-bin-dir'];
        }

        if (!static::hasDirectory($event, 'symfony-app-dir', $options['symfony-app-dir'], 'execute command')) {
            return;
        }

        return $options['symfony-app-dir'];
    }

    protected static function getPhpArguments()
    {
        $arguments = array();

        $phpFinder = new PhpExecutableFinder();
        if (method_exists($phpFinder, 'findArguments')) {
            $arguments = $phpFinder->findArguments();
        }

        if (false !== $ini = php_ini_loaded_file()) {
            $arguments[] = '--php-ini='.$ini;
        }

        return $arguments;
    }

    protected static function getPhp()
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find()) {
            throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }

        return $phpPath;
    }

    protected static function executeCommand(CommandEvent $event, $consoleDir, $cmd, $timeout = 300)
    {
        $php = escapeshellarg(static::getPhp(false));
        $phpArgs = implode(' ', array_map('escapeshellarg', static::getPhpArguments()));
        $console = escapeshellarg($consoleDir.'/console');
        if ($event->getIO()->isDecorated()) {
            $console .= ' --ansi';
        }

        $process = new Process($php.($phpArgs ? ' '.$phpArgs : '').' '.$console.' '.$cmd, null, null, null, $timeout);
        $process->run(function ($type, $buffer) use ($event) { $event->getIO()->write($buffer, false); });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred when executing the "%s" command.', escapeshellarg($cmd)));
        }
    }

    /**
     * Installs the assets under the web root directory.
     *
     * For better interoperability, assets are copied instead of symlinked by default.
     *
     * Even if symlinks work on Windows, this is only true on Windows Vista and later,
     * but then, only when running the console with admin rights or when disabling the
     * strict user permission checks (which can be done on Windows 7 but not on Windows
     * Vista).
     *
     * @param $event CommandEvent A instance
     */
    public static function installAssets(CommandEvent $event)
    {
        $options = static::getOptions($event);
        $consoleDir = static::getConsoleDir($event, 'install assets');

        if (null === $consoleDir) {
            return;
        }

        $webDir = $options['symfony-web-dir'];

        $symlink = '';
        if ($options['symfony-assets-install'] == 'symlink') {
            $symlink = '--symlink ';
        } elseif ($options['symfony-assets-install'] == 'relative') {
            $symlink = '--symlink --relative ';
        }

        if (!static::hasDirectory($event, 'symfony-web-dir', $webDir, 'install assets')) {
            return;
        }

        static::executeCommand($event, $consoleDir, 'assets:install '.$symlink.escapeshellarg($webDir), $options['process-timeout']);
    }

    /**
     * Clears the Symfony cache.
     *
     * @param $event CommandEvent A instance
     */
    public static function clearCache(CommandEvent $event)
    {
        $options = static::getOptions($event);
        $consoleDir = static::getConsoleDir($event, 'clear the cache');

        if (null === $consoleDir) {
            return;
        }

        $warmup = '';
        if (!$options['symfony-cache-warmup']) {
            $warmup = ' --no-warmup';
        }

        static::executeCommand($event, $consoleDir, 'cache:clear'.$warmup, $options['process-timeout']);
    }

    /* FIN récupération de code issue de /vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Composer/ScriptHandler.php */

}
