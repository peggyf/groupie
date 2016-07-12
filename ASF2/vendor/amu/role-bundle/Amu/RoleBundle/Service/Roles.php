<?php

namespace Amu\RoleBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
/**
 * Permet de modifier les ROLES d'un utilisateur symfony par son firewall
 *
 *  Utilise un Token symfony de type "UsernamePasswordToken"
 *  Modifie le token stocké dans le container "security.token_storage"
 *
 * @author Michel UBÉDA <michel.ubeda@univ-amu.fr>
 */
class Roles
{
    private $container;
    //
    private $isDev;
    private $isLocal;
    private $isIntranet;
    private $isExtranet;
    //
    private $ip;
    private $uid;
    private $ldapServiceName = "";

    /**
     * constructeur Injection du Container et du nom de service Ldap à utiliser
     *
     * @param ContainerInterface $container
     * @param type $ldapServiceName
     */
    public function __construct(ContainerInterface $container, $ldapServiceName = "")
    {
        $this->container = $container;
        $this->ldapServiceName = $ldapServiceName;

        $this->initResaux();

        $uid = "";
        // détection et récupération de l'utilisateur/Token en cours d'utilisation
        if ($tokenStorage = $this->getSymfonyTokenStorage()) {
            if ($token = $tokenStorage->getToken()) {
                if ($token->getUser()) {
                    $uid = $token->getUser()->getUsername();
                }
            }
        }

        $this->initProfils($uid);
    }

    /**
     * Renvoi le bon service associé au token de sécurité en fonction de la version de Symfony
     * @return mixed
     *
     * @see https://github.com/FriendsOfSymfony/FOSUserBundle/blob/master/Controller/RegistrationController.php#L149
     */
    private function getSymfonyTokenStorage(){
        if (interface_exists('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')) {
            return $this->container->get('security.token_storage');
        } else {
            // Set the SecurityContext for Symfony <2.6
            return $this->container->get('security.context');
        }
    }

    /**
     * Renvoi le bon service associé à la gestion de la REQUEST  en fonction de la version de Symfony
     * @return mixed
     *
     * @see http://symfony.com/blog/new-in-symfony-2-4-the-request-stack
     */
    private function getSymfonyRequest(){
        //
        if (class_exists('Symfony\Component\HttpFoundation\RequestStack')) {
            return $this->container->get('request_stack')->getCurrentRequest();
            // getMasterRequest
        } else {
            // Set the SecurityContext for Symfony <2.4
            return $this->container->getRequest();
        }
    }

    /**
     * Initialisation des informations Réseaux :
     * Comme une adresse ip est fixe par client, on initialisera qu'une seule fois (contructeur)
     */
    private function initResaux()
    {
        $this->ip = $this->getSymfonyRequest()->getClientIp();
        // Comme une adresse ip est fixe par client : on initialise une seule fois les informations liées aux réseaux
        $this->isLocal = $this->getNetworks()->isLocal($this->ip);
        $this->isIntranet = $this->getNetworks()->isIntranet($this->ip);
        $this->isExtranet = false; // positionner plus tard à true si isIntranet==false et que login phpCAS => supposé isIntranet (ent)
    }

    /**
     * Initialisation des Profils (isDev) en fonction du login/uid
     * @param string $uid (null) modifier l'uid/login de référence
     */
    private function initProfils($uid = null)
    {
        if ($uid !== null) {
            $this->uid = $uid;
        }
        $this->isDev = ($this->getNetworks()->isDev($this->ip) || $this->getNetworks()->isDev2($this->uid));

    }

    /**
     * @return Session;
     */
    private function getSession()
    {
        // >=2.4 http://symfony.com/blog/new-in-symfony-2-4-the-request-stack
        //return $this->container->get('session'); // old <=2.3
        // En passant toujours par request puis Session on reste plus propre & générique...
        try{
            return $this->getSymfonyRequest()->getSession();
        }catch(\Exception $E){
            throw  new \Exception("Erreur d'accès à la SESSION...");
        }

    }

    /**
     * @return \Amu\LdapBundle\Ldap\Client;
     */
    private function getLdap($throwExeptionOnError=true)
    {
        try{
            return $this->container->get($this->ldapServiceName);
        }catch(\Exception $e){
            if($throwExeptionOnError==true) {

                $arRoles=$this->container->getParameter("amu.roles.custom");
                $arLdapRoles=array();

                foreach($arRoles as $key=> $aRole){
                    if(($aRole['type']=="ldap") ||($aRole['type']=="ldap") ){
                        $arLdapRoles[$key]=$aRole;
                    }
                }
                // cf http://symfony.com/blog/new-in-symfony-2-6-vardumper-component & http://symfony.com/doc/current/components/var_dumper/advanced
                if(class_exists("Symfony\Component\VarDumper\VarDumper")){
                    dump($arLdapRoles);
                }
                throw  new ServiceNotFoundException(
                    print_r($this->ldapServiceName,true).'"'."\n\n".
                    "Erreur d'initialisation du service d'accès au LDAP\n".
                    "Veuillez préciser le nom du service d'accès au LDAP ('amu.ldap' par défaut)\n".
                    "[ cf config originale => vendor/BundleDir/Resources/config/services.yml ]\n".
                    "\nCe service est requis pour la gestion des ROLES de type 'ldap' et/ou 'ldap2' que vous avez définis...".var_dump($arLdapRoles) ."\n"
                );
            }
            else{
                return null;
            }
        }
    }

    /**
     * @return \Amu\RoleBundle\Service\Networks;
     */
    private function getNetworks()
    {
        return $this->container->get("amu.networks");
    }

    /**
     * @@return \Symfony\Bridge\Monolog\Logger
     */
    private function getLogger()
    {
        return $this->container->get("logger");
    }

    /**
     *   Mise à jour des 'Roles' par défauts :
     *   Auto-détection "Authentificatin CAS" via session->get('phpCAS_user'), ROLE_DEVELOPER, ROLE_BETA...
     *
     * @param string $user identifiant de l'utilisateur symfony
     * @param Session $session varaibels d'accès à la session (détection php_cas => Authentification CAS OK)
     * @param array $arRoles Les roles déjà défini (précédements...)
     * @return array tableau des Roles
     */
    private function addDefaultsRoles($user, $session, $arRoles)
    {
        if (($user != "") && ($user != "anon.")) {
            foreach (array("ROLE_USER", "ROLE_AUTHENTIFIED") as $aRole) {
                if (!in_array($aRole, $arRoles)) {
                    $arRoles[] = $aRole;
                }
            }
        }

        if ($this->isDev) {
            foreach (array("ROLE_DEVELOPER", "ROLE_ALLOWED_TO_SWITCH") as $aRole) {
                if (!in_array($aRole, $arRoles)) {
                    $arRoles[] = $aRole;
                }
            }
        }

        if ($this->isIntranet) {
            $arRoles[] = "ROLE_INTRANET";
        }
        // detection Authentification CAS => O/N
        if ($session->get('phpCAS_user') != "") {
            foreach (array("ROLE_FULL_AUTHENTIFIED", "ROLE_CAS_AUTHENTIFIED", "IS_AUTHENTICATED_FULLY") as $aRole) {
                if (!in_array($aRole, $arRoles)) {
                    $arRoles[] = $aRole;
                }
            }
            if ((!$this->isIntranet) && (!$this->isLocal)) {
                if (!in_array("ROLE_EXTRANET", $arRoles)) {
                    $arRoles[] = "ROLE_EXTRANET";
                }
                $this->isExtranet = true;
            }
        }

        return $arRoles;
    }

    /**
     * retourne le tableau des Attributes en fonction de la configuration :
     * Parameter:
     *    'amu.roles.attributes'              ==> roles.yml "attributes:list"
     *    'amu.roles.attributes.into_session' ==> roles.yml "attributes:into_session"
     *    'amu.roles.attributes.session_prefix_vars' ==> roles.yml "attributes:session_prefix_vars"
     *
     * @param string $user
     * @return type
     */
    public function getAttributes($user)
    {
        $tokenStorage = $this->getSymfonyTokenStorage();
        $token = (($tokenStorage) ? $tokenStorage->getToken() : null);
        $arAttributes = array();
        $ldapAttrib = $this->container->getParameter("amu.roles.attributes");

        if ($token) {
            $arAttributes = $token->getAttributes();
            $user = $token->getUser()->getUsername();
        }

        if(is_array($ldapAttrib) and (count($ldapAttrib)>0)){
            $ldap=$this->getLdap(false);
            if($ldap==null){
                // SI le service LDAP n'as pas été correctement paramétré ou qu'il n'exite pas ALORS on ecrit un warning dans le fichier des logs et on continue

                $this->getLogger()->addWarning(__CLASS__.":".__FUNCTION__."=> Attributs LDAP non remontés, Erreur d'accès au service ldap\n Veuillez verifier le paramètrage du service 'amu.roles' avec sa configuration d'origine...");

            }else{
                $baseDN = $ldap->getBaseDN();
                $resource = $ldap->connect();
                $arAttributesLDAP = $resource->search($baseDN, "(uid=$user)", $ldapAttrib,true);
                $ldap->disconnect();
                if(count($arAttributesLDAP)>0){
                    $sessionPrefix = $this->container->getParameter("amu.roles.attributes.session_prefix_vars");
                    $session = $this->getSession();
                    foreach ($arAttributesLDAP as $key => $value) {
                        if ($this->container->getParameter("amu.roles.attributes.into_session")) {
                            $session->set($sessionPrefix . $key, print_r($value, true));
                        }
                        $arAttributes[$sessionPrefix . $key]= $value;
                    }
                }
            }
        }

        $arAttributes["isDev"] = ($this->isDev);
        $arAttributes["isIntranet"] = ($this->isIntranet);
        $arAttributes["isLocal"] = ($this->isLocal);
        $arAttributes["isExtranet"] = ($this->isExtranet);

        return $arAttributes;
    }

    /**
     *  retourne le tableau des ROLES_...
     *
     * @return array
     */
    public function getRoles($user = "", $debug = false)
    {
        $session = $this->getSession();
        $arRoles = array();

        if ($user == "") {
            $token = $this->getSymfonyTokenStorage()->getToken();
            if ($token) {
                $arRoles = $token->getRoles();
                $user = $token->getUser();
                if ($user instanceof User) {
                    $casRoles = $user->getRoles();
                    // $arRoles est un tableau d'objets "Symfony\Component\Security\Core\Role\Role"
                    $casRoles = $token->getRoles();
                    if (is_array($casRoles)) {
                        foreach ($casRoles as $aRole) {
                            //  $arRoles[] = ($user instanceof Role) ? $aRole->getRole() : $aRole ;
                            $arRoles[] = $aRole;
                        }
                    } elseif (is_string($casRoles)) {
                        $arRoles[] = $casRoles;
                    }
                }
            } else {
                $user = "anon.";
                $arRoles = array();
            }
        }

        if ($user != $this->uid) {
            $this->initProfils($user);
        }

        $arRoles = $this->addDefaultsRoles($user, $session, $arRoles);

        $roleManager = $this->container->getParameter("amu.roles.custom");

        if (is_array($roleManager)) {
            if (count($roleManager) > 0) {
                foreach ($roleManager as $oneRule) {
                    switch (strtolower($oneRule['type'])) {

                        case 'ldap': // Intérrogation ldap simple:
                            $ldap=$this->getLdap();
                            $baseDN = $ldap->getBaseDN();
                            $resource = $ldap->connect();

                            if ($resource) {

                                if ($oneRule['link'] == "isMember") {
                                    $groupName = $oneRule['values'];
                                    $isMemberFilter = "(&(objectclass=*)(memberof=CN=$groupName,OU=groups,DC=univ-amu,DC=fr)(uid=$user))";
                                    $baseDN = "dc=univ-amu,dc=fr";
                                    $results = $resource->search($baseDN, $isMemberFilter, array("uid"),false);
                                    if ($results["count"] == 1) {
                                        $arRoles[] = $oneRule['name'];
                                    }
                                } else {
                                    // SAMPLE   - { name:"ROLE_STUDENT", type:"ldap", link:"suppanAfflilliation", values: ["student","alum"] }
                                    if (isset($oneRule['values']) && ($oneRule['values'] != "")) {
                                        $arAttr = array();
                                        if (isset($oneRule['link']) && ($oneRule['link'] != "")) {
                                            $arAttr = array($oneRule['link']);
                                        } else {
                                            throw new \Exception("Erreur Roles (ldap) paramètres obligatoire non trouvé : 'link' \n" . print_r($oneRule, true));
                                        }

                                        $filter = "";
                                        $mustValues = $oneRule['values'];
                                        if (is_array($mustValues)) {
                                            if (count($mustValues) == 1) {
                                                $filter = "(" . $oneRule['link'] . "=" . $mustValues[0] . ")";
                                            } else {
                                                // cas multiple values
                                                $filter = "(|";
                                                foreach ($mustValues as $oneValue) {
                                                    $filter .= "(" . $oneRule['link'] . "=" . $oneValue . ")";
                                                }
                                                $filter .= ")";
                                            }
                                        } else {
                                            $filter = "(" . $oneRule['link'] . "=" . $mustValues . ")";
                                        }

                                        if (isset($oneRule['dn']) && ($oneRule['dn'] != "")) {
                                            $baseDN = $oneRule['dn'];
                                        }

                                        // Limitation remonté uniquement des donnée pour l'utilisateur connecté...
                                        $filter = "(&$filter(uid=$user))";

                                        $resultsBrut = $resource->search($baseDN, $filter, $arAttr, false);
                                        $results=$resultsBrut[0];

                                        if ($debug) {
                                            $this->getLogger()->addInfo(
                                                "Roles (ldap2) => $filter =>" .
                                                "\n baseDN: " . print_r($baseDN, true) .
                                                "\n Filtre: " . print_r($filter, true) .
                                                "\n Attributs: " . print_r($arAttr, true) .
                                                "\n Données brutes:" . print_r($results, true) .
                                                "\n oneRule:" . print_r($oneRule, true)
                                            );
                                            $this->getLogger()->addInfo(
                                                "addRuleLDAP=> results<pre>" . print_r($results, true) .
                                                "</pre> </br> oneRule => <pre>" . print_r($oneRule, true) . "</pre>"
                                            );
                                        }

                                        if (count($results) > 0) {
                                            $this->rulesLDAP($oneRule, $results, $arRoles);
                                        }
                                    } else {
                                        throw new \Exception("Erreur Roles (ldap) paramètres obligatoire non trouvé : 'values' \n" . print_r($oneRule, true));
                                    }
                                }
                            }
                            $ldap->disconnect();
                            break;

                        case 'ldap2': // Intérrogation ldap avancée:
                            // SAMPLE - { name: "ROLE_LUMINY",   type: "ldap2",  link: "",  values: "", filter: "(amuCampus=L)(|(eduPersonAffiliation=employee)(eduPersonAffiliation=staff)(eduPersonAffiliation=faculty)(eduPersonAffiliation=researcher))" }
                            $ldap=$this->getLdap();
                            $baseDN = $ldap->getBaseDN();
                            $resource = $ldap->connect();

                            if ($resource) {

                                if (isset($oneRule['filter']) && ($oneRule['filter'] != "")) {
                                    if (isset($oneRule['link']) && ($oneRule['link'] != "")) {
                                        $arAttr = array($oneRule['link']);
                                    } else {
                                        throw new \Exception("Erreur Roles (ldap2) paramètres obligatoire non trouvé : 'link' \n" . print_r($oneRule, true));
                                    }

                                    $arAttr = array();
                                    $needValue = "";
                                    $filter = trim($oneRule['filter']);

                                    if ($oneRule['link'] == "uid") {
                                        $filter = strtr($filter, array('$login' => $this->uid));
                                        $needValue = $this->uid;
                                    } elseif ($oneRule['link'] == "ip") {
                                        $filter = strtr($filter, array('$ip' => $this->ip));
                                        $needValue = $this->ip;
                                    } else {
                                        if ($oneRule['link'] != "") {
                                            $needValue = $oneRule['link'];
                                        }
                                    }

                                    if (isset($oneRule['dn']) && ($oneRule['dn'] != "")) {
                                        $baseDN = $oneRule['dn'];
                                    }
                                    $userLdapDatas = $resource->search($baseDN, $filter, $arAttr, true);
                                    if ($debug) {
                                        $this->getLogger()->addInfo(
                                            "Roles (ldap2) => $filter =>" .
                                            "\n baseDN: " . print_r($baseDN, true) .
                                            "\n Filtre: " . print_r($filter, true) .
                                            "\n Attributs: " . print_r($arAttr, true) .
                                            "\n Données brutes:" . print_r($results, true) .
                                            "\n oneRule:" . print_r($oneRule, true)
                                        );
                                    }
                                    if (count($userLdapDatas) > 0) {
                                        $found = false;
                                        if (isset($userLdapDatas[$oneRule['link']])) {
                                            if ((is_array($needValue))) {
                                                foreach ($needValue as $aNeedValue) {
                                                    if ($userLdapDatas[$oneRule['link']] == $aNeedValue) {
                                                        $found = true;
                                                        break;
                                                    }
                                                }
                                            } else {
                                                if ($userLdapDatas[$oneRule['link']] == $needValue) {
                                                    $found = true;
                                                }
                                            }
                                        }
                                        if ($found) {
                                            if (!in_array($oneRule['name'], $arRoles)) {
                                                $arRoles[] = $oneRule['name'];
                                            }
                                        }
                                    }
                                } else {
                                    throw new \Exception("Erreur Roles (ldap) paramètres obligatoire non trouvé : 'filter' \n" . print_r($oneRule, true));
                                }
                            }
                            $ldap->disconnect();
                            break;

                        case 'session': // SAMPLE   - { name:"ROLE_STUDENT_ADM", type:"session", link:"uid", values: ["123456","7891011"] }
                            if (isset($session)) {
                                $this->rulesSESSION($oneRule, $session, $arRoles);
                            }
                            break;

                        case 'bdd': // SAMPLE   - { name:"ROLE_FONC1", type:"bdd", link:"select fldFunction1 from TABLE where fldUID='session:uid'" }
                            /* PAR ENCORE IMPLEMENTÉE pour le moment... */
                            break;

                        case 'ip': // SAMPLE    - { name: "ROLE_IS_INTO_LABO_TEST_NETWORK", type:"ip", link: "ip",  values: ["192.1.3.12","192.1.3.13","192.1.1.0/24","192.1.2.0/24"] }
                            if ($this->ip) {
                                $this->rulesNETWORKS($oneRule, $this->ip, $arRoles);
                            }
                            break;
                    }
                }
            }
        }

        return $arRoles;
    }

    /**
     * rulesSession rajoute le role $oneRule['name'] dans le tableau $arRoles si la $oneRule est vrai sur les valeurs de SESSION $session
     * @param array $oneRule la règle à tester
     * @param session $session une variable d'accès aux données stockées en SESSION
     * @param array &$arRoles le tableau des Roles (modifier dans la function...)
     *
     */
    private function rulesSESSION($oneRule, $session, &$arRoles)
    {
        if (isset($session)) {
            $role = $oneRule['name'];
            $mustValues = $oneRule['values'];
            $varName = $oneRule['link'];
            $isMember = false;
            if (($role != "") && ($varName != "") && ($session->has($varName))) {
                $sessionValues = $session->get($varName);
                if (is_array($mustValues)) { // les valeurs possibles sont multiples
                    foreach ($mustValues as $oneValue) {
                        if (is_array($sessionValues)) { // valeur à tester est multiple
                            foreach ($sessionValues as $aValue) {
                                if ($aValue == $oneValue) {
                                    $isMember = true;
                                    break;
                                }
                            }
                        } else {// valeur à tester = unique
                            if ($sessionValues == $oneValue) {
                                $isMember = true;
                                break;
                            }
                        }
                        if ($isMember) {
                            break; // on sort dès qu'un des cas est vrai
                        }
                    }
                } else { // la valeur $mustValues possible est unique
                    if (is_array($sessionValues)) { // valeur de Référence est multiple
                        foreach ($sessionValues as $aValue) {
                            if ($aValue == $mustValues) {
                                $isMember = true;
                                break;
                            }
                        }
                    } else { // valeur $sessionValues à tester = unique
                        if ($sessionValues == $mustValues) {
                            $isMember = true;
                        }
                    }
                }
                if ($isMember == true) {
                    if (!in_array($role, $arRoles)) {
                        $arRoles[] = $role;
                    }
                }
            }
        }
        return $arRoles;
    }

    /**
     * rulesLDAP rajoute le role $oneRule['name'] dans le tableau $arRoles si la $oneRule est vrai sur les valeurs du LDAP $ldapDatas
     * @param array $oneRule la règle à tester
     * @param array $ldapDatas les donnée LDAP de l'utilisateur en cours... ($login)
     * @param array &$arRoles le tableau des Roles (modifier dans la function...)
     * @return array &$arRoles
     */
    private function rulesLDAP($oneRule, $ldapDatas, &$arRoles)
    {
        if (is_array($oneRule)) {
            if ($oneRule['type'] == 'ldap') {
                $mustValue = $oneRule['values'];
                $role = trim($oneRule['name']);
                $ldapValues=null;
                if (isset($ldapDatas[strtolower($oneRule['link'])])) {
                    $ldapValues = $ldapDatas[strtolower($oneRule['link'])];
                }
                $found = false;
                if ($role != "") {
                    if (isset($mustValue) && isset($ldapValues)) {
                        // Conditions + Valeurs OK
                        if (is_array($ldapValues)) { // traitement valeur LDAP MULTIPLE
                            foreach ($ldapValues as $oneLdapValue) {
                                if (is_array($mustValue)) { // Valeur conditionnel d'appartenance  = MULTIPE
                                    foreach ($mustValue as $oneValue) {
                                        if ($oneLdapValue == $oneValue) {
                                            $found = true;
                                            break;
                                        }
                                    }
                                } else { // Valeur conditionnel d'appartenance = UNIQUE
                                    if ($oneLdapValue == $mustValue) {
                                        $found = true;
                                    }
                                }
                            }
                        } else { // traitement valeur LDAP UNIQUE
                            if (is_array($mustValue)) { // Valeur conditionnel d'appartenance  = MULTIPE
                                foreach ($mustValue as $oneValue) {
                                    if ($ldapValues == $oneValue) {
                                        $found = true;
                                        break;
                                    }
                                }
                            } else { // Valeur conditionnel d'appartenance = UNIQUE
                                if ($ldapValues == $mustValue) {
                                    $found = true;
                                }
                            }
                        }
                    }
                }
                if ($found) {
                    if (!in_array($role, $arRoles)) {
                        $arRoles[] = $role;
                    }
                }
            }
        }
        return($arRoles);
    }

    /**
     * rulesNETWORKS rajoute le role $oneRule['name'] dans le tableau $arRoles si la l'adresse $ip du client
     * est contenu dans un tableau d'ip ou un des sous-réseau des plages ip défini dans $oneRule['value']
     * @param $oneRule le Role à ajouté dans le cas où une des conditions est remplie
     * @param $ip l'adresse IP du client à tester
     * @param &$arRoles le tableau des Roles (modifier dans la function...)
     * @return array &$arRoles
     */
    private function rulesNETWORKS($oneRule, $ip, &$arRoles){
        if (is_array($oneRule)) {
            if ($oneRule['type'] == 'ip') {
                $mustValue = $oneRule['values'];
                $role = trim($oneRule['name']);
                $found = false;
                if ($role != "") {
                    if (isset($mustValue)) {
                        if (is_array($mustValue)) { // Valeur conditionnel d'appartenance  = MULTIPE
                            foreach ($mustValue as $oneValue) {
                                $found = $this->getNetworks()->testIp($oneValue, $ip);
                                if ($found) {
                                    break;
                                }
                            }
                        } else { // Valeur conditionnel d'appartenance = UNIQUE
                            if ($this->getNetworks()->testIp($mustValue, $ip)) {
                                $found = true;
                            }
                        }
                    }
                }
                if ($found) {
                    if (!in_array($role, $arRoles)) {
                        $arRoles[] = $role;
                    }
                }
            }
        }
        return($arRoles);
    }
}
