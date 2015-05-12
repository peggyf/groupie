<?php

namespace Amu\CliGrouperBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Monolog\Logger;

/** Class d'interrogation d'un serveur LDAP
 * ========================================
 * @category classe regroupant l'ensembles des fcts d'intérogation LDAP
 * @author Michel UBÉDA <michel.ubeda@univ-amu.fr>
 * @version 2.0 du 20/09/2012 (spécialisation Service Symfony)
 * 1.1 12/01/2010 : ajout LDAP_ListAttributs() 
 * last modif: 13/03/2012 add LDAP_getJSON_InfosWhere_cnLike +++values
 * @since 15/09/2009
 * @uses ParamsLDAP : paramètrage d'accès au LDAP.
 */
/* * * Classe d'intérrogation LDAP 
 * @param $rootCnx boolean seconnecter en mode ldapadmin true/false (true par défaut = connexion 'anonyme')
 * @param $ModeDebug boolean (false par défaut) afficher/masquer le mode débug
 * @param $servers le serveur / ou la liste des 3 serveurs séparé par une virgule
 * @param $port le numéro de port
 * @param $userDN (optionel)
 * @param $userPW (optionel)
 * @param $racine (optionel)
 * @param $racineUser (optionel)  */
class Ldap extends WSTools {

  private $ds, $r;
  public $Debug = false;
  public $Anonymous = false;

  const Version = "2.1 du 31/10/2012";

  private $profilsLDAP = array();
  // 
  // Paramétrage du/des serveur(s) LDAP
  //
  // utile pour faire une recherche sur les 2 ldap si l'un des 2 est en panne
  private $LDAP_serveur2 = '';
  private $LDAP_serveur3 = '';
  private $LDAP_racine = '';
  private $LDAP_racineUser = '';
  // LDAP AMU
  private $LDAP_admdn = "";
  private $LDAP_admpw = '';
//  
//  private $LDAP_serveur1 = 'ldaps://ldap1.univ-amu.fr ldaps://ldap2.univ-amu.fr';
//  private $LDAP_serveur2 = 'ldaps://ldap2.univ-amu.fr ldaps://ldap1.univ-amu.fr';
//  private $LDAP_serveur3 = 'ldaps://ldap.univ-amu.fr';
//  private $LDAP_racine = 'dc=univ-amu,dc=fr';
//  private $LDAP_racineUser = 'ou=people,dc=univ-amu,dc=fr';
//  // LDAP AMU
//  private $LDAP_admdn = "cn=annuaire,ou=system,dc=univ-amu,dc=fr";
//  private $LDAP_admpw = '$annuaire-2011!';
  private $arLibPatrimoines = array(
      "A01" => "SITE UNIVERSITAIRE SCHUMAN",
      "A02" => "AIXCENTRE",
      "A04" => "EUROPÔLE DE L'ARBOIS",
      "A05" => "SITE GASTON BERGER",
      "A08" => "SITE JULES FERRY",
      "A11" => "CENTRE SCHUMAN",
      "A12" => "MMSH JAS DE BOUFFAN",
      "A18" => "SITE DE LA CIOTAT",
      "A19" => "SITE UNIVERSITAIRE SCHUMAN",
      "A20" => "AUBAGNE",
      "A21" => "SITE DE GAP",
      "A22" => "IUT SALON",
      "A23" => "ESPACE VAN GOGH (ARLES)",
      "A24" => "PUYRICARD",
      "A25" => "LAMBESC",
      "A26" => "IUT ARLES",
      "A27" => "IUT DIGNE",
      "A28" => "IUFM AIX-EN-PROVENCE",
      "A29" => "IUFM DIGNE",
      "E01" => "CAMPUS SAINT JÉRÔME",
      "E02" => "IUT SAINT JÉRÔME",
      "E03" => "CHATEAU GOMBERT",
      "IEP" => "INSTITUT D'ÉTUDES POLITIQUES",
      "L01" => "SITE DE LUMINY",
      "L02" => "SITE ENDOUME",
      "M01" => "MARSEILLE CENTRE",
      "M02" => "CENTRE SAINT CHARLES",
      "M02" => "MARSEILLE CENTRE",
      "M04" => "IUFM CANEBIÈRE",
      "M05" => "IUFM EUGENECAS",
      "M07" => "SITE PIERRE PUGET",
      "P01" => "JARDIN DU PHARO",
      "T01" => "SITE CENTRE",
      "T02" => "SITE TIMONE",
      "T03" => "SITE NORD"
  );

  public function getProfilsLDAP($name) {
    if (isset($this->profilsLDAP[$name]))
      return $this->profilsLDAP[$name];
    else
      return null;
  }

//    private function initDefault(){
//     $this->LDAP_serveur1 = "ldaps://ldap1.univ-amu.fr ldaps://ldap2.univ-amu.fr";
//    $this->LDAP_serveur2 = "ldaps://ldap2.univ-amu.fr ldaps://ldap1.univ-amu.fr";
//    $this->LDAP_serveur3 = "ldaps://ldap.univ-amu.fr";
//    $this->LDAP_racine = "dc=univ-amu,dc=fr";
//    $this->LDAP_racineUser = "ou=people,dc=univ-amu,dc=fr";
//    $this->LDAP_admdn = "cn=annuaire,ou=system,dc=univ-amu,dc=fr";
//    $this->LDAP_admpw = '$annuaire-2011!';
//  }
//  
  private function razConfig() {
    $this->LDAP_serveur1 = "";
    $this->LDAP_serveur2 = "";
    $this->LDAP_serveur3 = "";
    $this->LDAP_racine = "";
    $this->LDAP_racineUser = "";
    $this->LDAP_admdn = "";
    $this->LDAP_admpw = "";
  }

  /**
   * Fonction de paramètrage 
   * @param type $server url unique ou liste des urls serveurs LDAP à intérrogé (défaut = ($this->LDAP_serveur1,$this->LDAP_serveur2,$this->LDAP_serveur3)
   * @param type $userDN 
   * @param type $userPW
   * @param type $racine
   * @param type $racineUser
   * @param type $externe
   */
  public function setConfig($server = "", $userDN = "", $userPW = "", $racine = "", $racineUser = "", $externe = false, $debug = false) {
    $this->razConfig();

    if ($server != "") {
      if (strpos($server, ',') !== false)
        list($this->LDAP_serveur1, $this->LDAP_serveur2, $this->LDAP_serveur3) = explode(',', $server);
      else
        $this->LDAP_serveur1 = $server;
      $this->LDAP_serveur2 = "";
      $this->LDAP_serveur3 = "";
    }
    if (!$externe) {
      if ($userDN != "")
        $this->LDAP_admdn = $userDN;
      if ($userPW != "")
        $this->LDAP_admpw = $userPW;
    }
    else {
      $this->LDAP_admdn = $userDN;
      $this->LDAP_admpw = $userPW;
    }
    if ($racine != "")
      $this->LDAP_racine = $racine;
    if ($racineUser != "")
      $this->LDAP_racineUser = $racineUser;

    if ($debug) {
      $this->traceInfos($this, "setConfig");
    }
  }

  function __construct(ContainerInterface $container, Logger $logger) {
    $this->DebugLogger = $logger;

    $profilsLDAP = $container->getParameter("ldap.profils");
    $this->profilsLDAP = array();
    foreach ($profilsLDAP as $oneProfil) {
      $this->profilsLDAP[$oneProfil["name"]] = $oneProfil["filtre"];
    }

    $server = $container->getParameter("ldap.server");
    $racine = $container->getParameter("ldap.root");
    $racineUser = $container->getParameter("ldap.user_root");
    $userDN = $container->getParameter("ldap.dn");
    $userPW = $container->getParameter("ldap.pw");
    $externe = $container->getParameter("ldap.external");

    if ($server != "") {
      if (strpos($server, ',') !== false)
        list($this->LDAP_serveur1, $this->LDAP_serveur2, $this->LDAP_serveur3) = explode(',', $server);
      else
        $this->LDAP_serveur1 = $server;
    }
    if (!$externe) {
      if ($userDN != "")
        $this->LDAP_admdn = $userDN;
      if ($userPW != "")
        $this->LDAP_admpw = $userPW;
    }
    else {
      $this->LDAP_admdn = $userDN;
      $this->LDAP_admpw = $userPW;
    }
    if ($racine != "")
      $this->LDAP_racine = $racine;
    if ($racineUser != "")
      $this->LDAP_racineUser = $racineUser;
  }

  private function traceInfos($Infos, $Titre) {
    $this->showDebugInfos($Infos, $Titre, __FILE__);
  }

  private function traceDebug($Infos, $Titre, $fctName) {
    $this->showDebugResult($Infos, $Titre, $fctName, __FILE__);
  }

  /**
   * Affiche des information de debug pré formattées()
   * @param string $infos les données à afficher
   * @param string $titre le titre du cadre de debug
   * @param bool $FormatedInfos traitement des $infos entre balise <pre> </pre> (true/false : defaut=false)
   */
//  private function showDebugInfos($infos, $titre = "",$FormatedInfos=false) {
//    echo "<div style='padding:25px;' >";
//    //echo "<div class='ui-state-highlight ui-corner-all' style='padding:25px;' >";
//    echo "<div class='ui-state-error ui-corner-all' style='padding:25px;' >";
//    echo "<b><font color=red>Informations de DEBUG :</font></b></br><font color=blue>$titre</font>";
//    echo "</br>FICHIER : <font color=blue>" . __FILE__. "</font>";
//    echo "</br>CLASSE : <font color=blue>" . __CLASS__ . "</font>";
//    echo "<hr>";
//    echo (($FormatedInfos)?"$infos":"<pre>" . print_r($infos, true) . "<pre>");
//    echo "</div>";
//    echo "</div>";
//  }


  private function connect() {
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
    if (!function_exists('ldap_connect')) {
      throw new \Exception("module php-ldap isn't install");
    }
    $this->ds = ldap_connect($this->LDAP_serveur1);
    ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($this->ds, LDAP_OPT_NETWORK_TIMEOUT, 5);

    if ($this->Anonymous) {
      $this->r = ldap_bind($this->ds);
      if ($this->Debug)
        $this->traceInfos($this->LDAP_serveur1, "Connexion en Anonymous sur le serveur : ", __FUNCTION__);
    }
    else {
      $this->r = ldap_bind($this->ds, $this->LDAP_admdn, $this->LDAP_admpw);
      if ($this->Debug)
        $this->traceInfos($this->LDAP_serveur1, "Connexion NON Anonymous sur  le serveur ", __FUNCTION__);
    };
    //connexion sur le serveur 2 si le 1 ne répond pas
    if (!$this->r) {
      $this->traceInfos(" La connexion au serveur LDAP ' . $this->LDAP_serveur1 . ' a échoué...", "Connexion", __FUNCTION__);
      exit();
    }
  }

  private function connectModif() {
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
    $this->ds = ldap_connect($this->LDAP_serveur3);
    ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, 3);
    if ($this->Anonymous)
      $this->r = ldap_bind($this->ds);
    else
      $this->r = ldap_bind($this->ds, $this->LDAP_admdn, $this->LDAP_admpw);

    //connexion sur le serveur 2 si le 1 ne répond pas
    if (!$this->r) {
      $this->traceInfos(" La connexion au serveur LDAP ' . $this->LDAP_serveur3 . ' a échoué...", "Connexion", __FUNCTION__);

      exit();
    }
  }

  private function disconnect() {
    if ($this->ds)
      ldap_close($this->ds);
  }

  /** Fonction privée de classement multi-paramètre des éléménts retournés d'une intérrogation LDAP
   * @param array $entries
   * @param array $attribs
   * @desc Sort LDAP result entries by multiple attributes.
   * Inspiré de commons/tools/fonctions.php
   * AJOUTS: 
   *  1) pas sensible à la casse
   *  2) prend en compte les longeurs =/= des chaines ( ex PAPA & PAPATEST...... => PAPA en premier PAPATEST en second)
   * 
   */
  private function ldap_multi_sort(&$entries, $attribs) {
    if ($entries['count'] > 1)
      for ($i = 0; $i < $entries['count']; $i++) {
        $index = $entries[$i];
        $j = $i;
        do {
          //create comparison variables from attributes:
          $a = $b = null;
          foreach ($attribs as $attrib) {
            $a .= strtolower($entries[$j - 1][$attrib][0]);
            $b .= strtolower($index[$attrib][0]);
            if (strlen($a) > strlen($b))
              $b .=str_repeat(" ", (strlen($a) - strlen($b)));
            if (strlen($b) > strlen($a))
              $a .=str_repeat(" ", (strlen($b) - strlen($a)));
          }
          // do the comparison
          if ($a > $b) {
            $is_greater = true;
            $entries[$j] = $entries[$j - 1];
            $j = $j - 1;
          } else {
            $is_greater = false;
          }
        } while ($j > 0 && $is_greater);

        $entries[$j] = $index;
      }
    return $entries;
  }

  /**
   * Fonction privée de normalisation : enlève tout les accents d'un chaine donnée
   * @param string $string  la chaine d'origine
   * @param boolean $utf8
   * @return string la chaine normalisée 
   */
  private function RemoveAccents($string, $utf8 = false) {
    if ($utf8)
      $string = utf8_decode($string);
    $string = strtr($string, "äâàéèêëïîîîôùûüç", "aaaeeeeiiiiouuuc");
    $string = strtr($string, "ÀÁÂÃÄÈÉÊËÌÍÎÏÒÓÔÖÙÚÛÜÇ", "AAAAAEEEEIIIIOOOOUUUUC");
    if ($utf8)
      $string = utf8_encode($string);
    return($string);
  }

  /**
   * Fonction privé d'extraction d'une chaine entre des BALISE A et B données
   * @param string $chaine    la chaine d'origine
   * @param string $A         la balise de début
   * @param string $B         la balise de fin
   * @param boolean $includeAB include les balise de début et fin (true/false)
   * @param boolean $CaseSensitive option sensible à la casse (true/false)
   * @return type 
   */
  private function StrBetweenAB($chaine, $A, $B, $includeAB = false, $CaseSensitive = true) {
    $return = "";
    $posDeb = ($CaseSensitive == false) ? stripos($chaine, $A) : strpos($chaine, $A);
    if ($posDeb !== false) {// trouvé !
      if (!$includeAB)
        $posDeb = $posDeb + strlen($A); // add len pour pas récup A
      $posFin = ($CaseSensitive == false) ? stripos($chaine, $B, $posDeb) : stripos($chaine, $B, $posDeb);
      if ($includeAB)
        $posFin = $posFin + strlen($B);  // add len pour récup B

      if ($posFin !== false)// trouvé !
        if ($posFin > $posDeb)
          $return = substr($chaine, $posDeb, ($posFin - $posDeb));
    }
    return($return);
  }

  /**  Fct généric d'intérrogation avec retours multiples
   * --------------------------------
   * @param string $filtre
   * @return string[] tableau indexé de toutes les valeurs LDAP trouvés :
   * "sn", "givenname", "uid","supannaliaslogin","facsimiletelephonenumber","telephonenumber","supanncivilite","idu3","uid","empIdU3","supannListeRouge","mail","coAbonnement","supannEmpId"
   */
  public function LDAP_getIdxTabInfosMultiple($filtre, $restriction = array("sn", "givenname", "uid", "supannaliaslogin", "facsimiletelephonenumber", "telephonenumber", "supanncivilite", "idu3", "uid", "empIdU3", "supannListeRouge", "mail", "coAbonnement", "supannEmpId"), $cnvBalise = null, $needAttr = null) {
    if ($this->Debug)
      echo '<br><b>' . __FUNCTION__ . '()</b><br><B><FONT color=blue> CLASS LDAP_Infos :</FONT> <FONT color=red>MODE DEBUG ACTIF</FONT></B><br><br>' . "\n";
    $this->connect();
    if ($this->r) {
      $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0); // ,25 pour limiter les résultats à 25 items
      $AllInfosBrutes = ldap_get_entries($this->ds, $sr);
      $AllInfos = $this->ldap_multi_sort($AllInfosBrutes, array("sn", "givenname"));

      if ($this->Debug)
        echo "<b>" . __FUNCTION__ . "()</b><br>" . str_repeat("#", 50) . "<br> DEBUT DEBUG INFOS <br>" .
        "<br><B>Filtre</B>     => " . $filtre .
        "<br><B>this->ds</B>     => " . $this->ds .
        "<br><B>this->LDAP_racine</B>     => " . $this->LDAP_racine .
        "<br><B>Restriction</B>  => " . print_r($restriction, true) .
        "<br><B>Infos brut</B>=><FONT color =green><PRE>" . print_r($AllInfosBrutes, true) . "</PRE></FONT></FONT>";
      "<br><B>Infos ldap_multi_sort</B>=><FONT color =orange><PRE>" . print_r($AllInfos, true) . "</PRE></FONT>";

      for ($num = 0; $num < $AllInfos['count']; $num++) {
        if ($needAttr == null || ($AllInfos[$num][$needAttr] != ""))
          foreach ($AllInfos[$num] as $balise) {
            if (in_array($balise, $restriction)) {
              $baliseRetour = $balise;
              if (is_array($cnvBalise))
                if ($cnvBalise[$balise] != "")
                  $baliseRetour = $cnvBalise[$balise];
              if ($AllInfos[$num][$balise]['count'] == 1)
                $result[$num][$baliseRetour] = $this->RemoveAccents(($AllInfos[$num][$balise][0]), true);
              else {
                $arFld = Array();
                for ($i = 0; $i < ($AllInfos[$num][$balise]['count']); $i++)
                  $arFld[] = $this->RemoveAccents(($AllInfos[$num][$balise][$i]), true);
                $result[$num][$baliseRetour] = $arFld;
              }
            }
          }
      }
      if ($this->Debug)
        echo "<b>" . __FUNCTION__ . "()</b><br>" .
        "<br><B>Infos après filtre</B>=><PRE>" . print_r($result, true) . "</PRE></FONT>" .
        "<br>" . str_repeat("#", 50) . "<br> FIN DEBUG INFOS <br>";
      return $result;
    }
  }

  /**  Fct généric d'intérrogation
   * --------------------------------
   * @param string $filtre
   * @return string[] tableau indexé de toutes les valeurs LDAP prédefinies :
   * array( "supannCivilite","cn","sn", "givenname","amuMail","mail", "supannEmpId","supannOrganisme",
    "idu3", "uid", "facsimileTelephoneNumber",  "supannAutreTelephone", "supannListeRouge",
    "telephoneNumber", "postalAddress", "amuOldMail","schacDateOfBirth",
    "coabonnement", "supannetuid", "mobile",
    "annuaireadmin", "edupersonprimaryaffiliation",
    // AMU add
    "eduPersonPrimaryAffiliation","amuPerimetre","amuCampus"	,"amuComposante","amuSite","supannEntiteAffectation","supannEntiteAffectationPrincipale", "supannEtablissement"
    // ETU add
    ,	"supannAffectation","supannAliasLogin","supannEtuAnneeInscription","supannEtuDiplome","supannEtuEtape" );
   */
  private function LDAP_getIdxTabInfos($filtre) {
    $result = array();

    $this->connect();
    if ($this->r) {
      $sortAttributes = array('sn', 'givenName');
      //$restriction = array( "sn", "givenName", "uid","supannaliaslogin","facsimiletelephonenumber","telephonenumber","supanncivilite","idu3","uid","empIdU3","supannListeRouge","mail","coAbonnement","supannEmpId");
      $restriction = array("supannCivilite", "cn", "sn", "givenName", "amuMail", "mail", "supannEmpId", "supannOrganisme",
          "idu3", "uid", "facsimileTelephoneNumber", "supannAutreTelephone", "supannListeRouge",
          "telephoneNumber", "postalAddress", "amuOldMail", "schacDateOfBirth",
          "coabonnement", "supannetuid", "mobile",
          "annuaireadmin", "edupersonprimaryaffiliation",
          // AMU add
          "eduPersonPrimaryAffiliation", "amuPerimetre", "amuCampus", "amuComposante", "amuSite", "supannEntiteAffectation", "supannEntiteAffectationPrincipale", "supannEtablissement"
          // ETU add
          , "supannAffectation", "supannAliasLogin", "supannEtuAnneeInscription", "supannEtuDiplome", "supannEtuEtape");
      $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
      $infoB = ldap_get_entries($this->ds, $sr);
      $info = $this->ldap_multi_sort($infoB, $sortAttributes);

      $infoF = print_r($info, true);

      $infoF = str_replace(")", "", $infoF);
      $infoF = str_replace("=>", "", $infoF);
      $infoF = str_replace("(", "", $infoF);
      $infoF = str_replace("[count]", "", $infoF);
      $infoF = str_replace("Array", "", $infoF);
      $infoF = str_replace("[0]", "", $infoF);

      if ($this->Debug) {
        $this->showDebugInfos($filtre, __FUNCTION__ . " <b><font color=blue>filtre</font></b>");
        $this->showDebugInfos($restriction, __FUNCTION__ . " <b><font color=blue>restriction</font></b>");
        $this->showDebugInfos($info, __FUNCTION__ . " <b><font color=blue>infos brut en retour</font></b>");
      }


      $nom = utf8_decode($info[0]["sn"][0]);
      if (!empty($nom)) {
        $civ = $info[0]["supanncivilite"][0];
        $civ = trim($civ);
        $civ = strtoupper($civ);
        if (($civ == "MLLE") || ($civ == "MME"))
          $info[0]["supanncivilite"][0] = "Mme";

        $result[CIV] = utf8_decode($info[0]["supanncivilite"][0]);
        $result[SN] = utf8_decode($info[0]["sn"][0]);
        $result[NAME] = utf8_decode($info[0]["givenname"][0]);
        $result[PHONE] = utf8_decode($info[0]["telephonenumber"][0]);
        $result[UID] = utf8_decode($info[0]["uid"][0]);
        $result[IDU3] = utf8_decode($info[0]["idu3"][0]);
        $result[supannEmpId] = utf8_decode($info[0]["supannempid"][0]);
        $result[empIdU3] = utf8_decode($info[0]["empIdU3"][0]);
        $result[supannListeRouge] = utf8_decode($info[0]["supannlisterouge"][0]);
        $result[mail] = utf8_decode($info[0]["mail"][0]);
        $result[mail_u3] = utf8_decode($info[0]["mail"][0]);
        $result[CO] = utf8_decode($info[0]["coabonnement"][0]);


        $result[supannAliasLogin] = utf8_decode($info[0]["supannaliaslogin"][0]);
        $result[supanncivilite] = utf8_decode($info[0]["supanncivilite"][0]);
        $result[sn] = utf8_decode($info[0]["sn"][0]);
        $result[givenname] = utf8_decode($info[0]["givenname"][0]);
        $result[telephonenumber] = utf8_decode($info[0]["telephonenumber"][0]);
        $result[uid] = utf8_decode($info[0]["uid"][0]);
        $result[idu3] = utf8_decode($info[0]["idu3"][0]);
        $result[supannempid] = utf8_decode($info[0]["supannempid"][0]);
        $result[empidu3] = utf8_decode($info[0]["empidu3"][0]);
        $result[supannlisterouge] = utf8_decode($info[0]["supannlisterouge"][0]);
        $result[mail_u3] = utf8_decode($info[0]["mail"][0]);
        $result[coabonnement] = utf8_decode($info[0]["coabonnement"][0]);

        $perimetre = "";
        $PerimetreCode = "";
        $cleanSupannOrg = str_replace(' ', '', $info[0]["supannorganisme"][0]);
        switch ($cleanSupannOrg) {
          case "{EES}0131842G": $Ico = $IcoAMU;
            $PerimetreCode = "P1";
            $perimetre = "<font color='#5C9CCC'>P1</font>";
            break; // Univ-Provence
          case "{EES}0131843H": $Ico = $IcoAMU;
            $PerimetreCode = "P2";
            $perimetre = "<font color='#5C9CCC'>P2</font>";
            break; // univ-Med
          case "{EES}0132364Z": $Ico = $IcoAMU;
            $PerimetreCode = "P3";
            $perimetre = "<font color='#5C9CCC'>P3</font>";
            break;
          case "{EES}0130221V": $Ico = $IcoIEP;
            $PerimetreCode = "IEP";
            $perimetre = "<font color='#E20018'>I</font>";
            break;
          case "{MinDefense}": $PerimetreCode = "DEF";
            break;

          default:
            if ((strstr($cleanSupannOrg, "{CNRS}") != "") || ($cleanSupannOrg == "{CNRS}")) {
              $Ico = $IcoCNRS;
              $PerimetreCode = "CNRS";
              $perimetre = "<font color='#004C70'>C</font>";
            }
            if ((strstr($cleanSupannOrg, "{INSERM}") != "") || ($cleanSupannOrg == "{INSERM}")) {
              $Ico = $IcoINSERM;
              $PerimetreCode = "INSERM";
            }
            // elseif(strstr( $info[$i]["supannorganisme"][0],"{EES}")!="") $Ico=$IcoAMU; //AMU U1,2,3

            break;
        }

        $result[Perimetre] = $PerimetreCode;

        foreach ($restriction as $oneParam) {
          switch (strtolower($oneParam)) {

            case "postalAddress":
              $valNUm = $info[0][strtolower($oneParam)][0];
              $result[$oneParam] = $this->normAddress($valNUm);
              break;

            case "telephonenumber":
            case "mobile":
            case "facsimiletelephonenumber":
            case "supannautretelephone":
              $valNUm = $info[0][strtolower($oneParam)][0];
              $result[$oneParam] = $this->normNumPhoneFax($valNUm);
              break;
            case "sn" : 
              $result['sn'] = $info[0][strtolower($oneParam)][0];
              $result['sn_array'] = $info[0][strtolower($oneParam)];  // recup All SN
              //break;
            //	
            default: $result[$oneParam] = utf8_decode($info[0][strtolower($oneParam)][0]);
              break;
          }
        }
      }
    }

    if ($this->Debug)
      $this->showDebugInfos($result, "<b>retour de la fonction<b> " . __FUNCTION__);

    return $result;
  }

  /**
   * Renvoi un tableau personnaliser de données LDAP
   * @param type $uid identifiant de la personne
   * @param array $restriction tableau des éléments LDAP à retourner
   * @return array
   */
  public function arUserInfos($uid = "", $restriction = array(), $debug = false) {
    $result = array();
    if ($uid != "")
      if (count($restriction) > 0) {

        $filtre = "uid=" . $uid;
        $this->connect();
        if ($this->r) {
          $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
          $info = ldap_get_entries($this->ds, $sr);
//
//            $infoF = print_r($info, true);
//            $infoF = str_replace(")", "", $infoF);
//            $infoF = str_replace("=>", "", $infoF);
//            $infoF = str_replace("(", "", $infoF);
//            $infoF = str_replace("[count]", "", $infoF);
//            $infoF = str_replace("Array", "", $infoF);
//            $infoF = str_replace("[0]", "", $infoF);

          if ($debug) {
            $this->showDebugInfos($filtre, __FUNCTION__ . " <b><font color=blue>filtre</font></b>");
            $this->showDebugInfos($restriction, __FUNCTION__ . " <b><font color=blue>restriction</font></b>");
            $this->showDebugInfos($info, __FUNCTION__ . " <b><font color=blue>infos brut en retour</font></b>");
          }

          $civ = $info[0]["supanncivilite"][0];
          $civ = trim($civ);
          $civ = strtoupper($civ);
          if (($civ == "MLLE") || ($civ == "MME"))
            $info[0]["supanncivilite"][0] = "Mme";

          $nom = $info[0]["sn"][0];
          if (!empty($nom)) {

            $perimetre = "";
            $PerimetreCode = "";
            $cleanSupannOrg = str_replace(' ', '', $info[0]["supannorganisme"][0]);
            switch ($cleanSupannOrg) {
              case "{EES}0131842G": $Ico = "AMU_ico.png";
                $PerimetreCode = "P1";
                $perimetre = "<font color='#5C9CCC'>P1</font>";
                break; // Univ-Provence
              case "{EES}0131843H": $Ico = "AMU_ico.png";
                $PerimetreCode = "P2";
                $perimetre = "<font color='#5C9CCC'>P2</font>";
                break; // univ-Med
              case "{EES}0132364Z": $Ico = "AMU_ico.png";
                $PerimetreCode = "P3";
                $perimetre = "<font color='#5C9CCC'>P3</font>";
                break;
              case "{EES}0130221V": $Ico = "IEP_ico.png";
                $PerimetreCode = "IEP";
                $perimetre = "<font color='#E20018'>I</font>";
                break;
              case "{MinDefense}": $PerimetreCode = "DEF";
                break;

              default:
                if ((strstr($cleanSupannOrg, "{CNRS}") != "") || ($cleanSupannOrg == "{CNRS}")) {
                  $Ico = "CNRS_ico.png";
                  $PerimetreCode = "CNRS";
                  $perimetre = "<font color='#004C70'>C</font>";
                }
                if ((strstr($cleanSupannOrg, "{INSERM}") != "") || ($cleanSupannOrg == "{INSERM}")) {
                  $Ico = "ICO_INSERM.png";
                  $PerimetreCode = "INSERM";
                }
                // elseif(strstr( $info[$i]["supannorganisme"][0],"{EES}")!="") $Ico=$IcoAMU; //AMU U1,2,3

                break;
            }

            $result['amuSiteTraduc'] = $info[0]["amusite"][0];
            if (isset($this->arLibPatrimoines[$info[0]["amusite"][0]]))
              $result['amuSiteTraduc'] = $this->arLibPatrimoines[$info[0]["amusite"][0]];

            $result['PerimetreCode'] = $PerimetreCode;
            $result['PerimetreHTMLInfos'] = $perimetre;
            $result['PerimetreIco'] = $Ico;


            foreach ($restriction as $oneParam) {

              $curValue = $info[0][strtolower($oneParam)][0];

              switch (strtolower($oneParam)) {

                case "amudatevalidation": // YYYYMMDDhhmmssZ (20120622000000Z) => date
                  $result['_' . $oneParam] = $curValue; // valeur originale préfixé avec _
                  $result[$oneParam] = $this->ldapDateToVisuDate($curValue, true);
                  break;
                case "schacdateofbirth": // YYYYMMDD => date
                  $result['_' . $oneParam] = $curValue; // valeur originale préfixé avec _
                  $result[$oneParam] = $this->convBirthDate($curValue);
                  break;

                case "sambapwdlastset" : // timestamp (1224505210) => date
                  $result['_' . $oneParam] = $curValue; // valeur originale préfixé avec _
                  $result[$oneParam] = $this->timestampToVisuDate($curValue, false);
                  break;

                case "postaladdress":
                  $result[$oneParam] = $curValue; // valeur originale préfixé avec _
                  $result[$oneParam . "HTML"] = $this->normAddress($curValue);
                  break;
                case "telephonenumber":
                case "mobile":
                case "facsimiletelephonenumber":
                case "supannautretelephone":
                case "fax":
                case "phone":
                  $result['_' . $oneParam] = $curValue; // valeur originale préfixé avec _
                  $result[$oneParam] = $this->NormalizeTel($curValue);
                  break;
                // valeurs mutlivalue
                case "sn" :
                  $result['sn_first'] = $curValue;  // recup All SN
                case "supannEntiteaffectation":
                case "supannaffectation":
                case "edupersonaffiliation":
                  $result[$oneParam] = $info[0][strtolower($oneParam)];  // recup All SN
                  break;
                //	
                default: $result[$oneParam] = $curValue;
                  break;
              }
            }
          }
        }
      }

    if ($debug)
      $this->showDebugInfos($result, "<b>retour de la fonction<b> " . __FUNCTION__);

    return $result;
  }
  
   /**
   * Renvoi un tableau personnaliser de données LDAP
   * @param type $uid identifiant de la personne
   * @param array $restriction tableau des éléments LDAP à retourner
   * @return array
   */
  public function arDatasFilter($filtre = "", $restriction = array(), $debug = false) {
    $result = array();
    if ($filtre != "")
      if (count($restriction) > 0) {

        $this->connect();
        if ($this->r) {
          $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
          ldap_sort($this->ds, $sr, "cn");
          $resultb = ldap_get_entries($this->ds, $sr);
          $result = $this->ldap_multi_sort($resultb, array("cn"));
        }
      }
      
      return($result);
  }
  
  /**
   * Renvoi un tableau personnaliser de données LDAP
   * @param type $uid identifiant de la personne
   * @param array $restriction tableau des éléments LDAP à retourner
   * @return array
   */
  public function arUserInfosFilter($filtre = "", $restriction = array(), $debug = false) {
    $result = array();
    if ($filtre != "")
      if (count($restriction) > 0) {

        $this->connect();
        if ($this->r) {
          $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
          $info = ldap_get_entries($this->ds, $sr);
//
//            $infoF = print_r($info, true);
//            $infoF = str_replace(")", "", $infoF);
//            $infoF = str_replace("=>", "", $infoF);
//            $infoF = str_replace("(", "", $infoF);
//            $infoF = str_replace("[count]", "", $infoF);
//            $infoF = str_replace("Array", "", $infoF);
//            $infoF = str_replace("[0]", "", $infoF);

          if ($debug) {
            $this->showDebugInfos($filtre, __FUNCTION__ . " <b><font color=blue>filtre</font></b>");
            $this->showDebugInfos($restriction, __FUNCTION__ . " <b><font color=blue>restriction</font></b>");
            $this->showDebugInfos($info, __FUNCTION__ . " <b><font color=blue>infos brut en retour</font></b>");
          }

          $civ = $info[0]["supanncivilite"][0];
          $civ = trim($civ);
          $civ = strtoupper($civ);
          if (($civ == "MLLE") || ($civ == "MME"))
            $info[0]["supanncivilite"][0] = "Mme";

          $nom = $info[0]["sn"][0];
          if (!empty($nom)) {

            $perimetre = "";
            $PerimetreCode = "";
            $cleanSupannOrg = str_replace(' ', '', $info[0]["supannorganisme"][0]);
            switch ($cleanSupannOrg) {
              case "{EES}0131842G": $Ico = "AMU_ico.png";
                $PerimetreCode = "P1";
                $perimetre = "<font color='#5C9CCC'>P1</font>";
                break; // Univ-Provence
              case "{EES}0131843H": $Ico = "AMU_ico.png";
                $PerimetreCode = "P2";
                $perimetre = "<font color='#5C9CCC'>P2</font>";
                break; // univ-Med
              case "{EES}0132364Z": $Ico = "AMU_ico.png";
                $PerimetreCode = "P3";
                $perimetre = "<font color='#5C9CCC'>P3</font>";
                break;
              case "{EES}0130221V": $Ico = "IEP_ico.png";
                $PerimetreCode = "IEP";
                $perimetre = "<font color='#E20018'>I</font>";
                break;
              case "{MinDefense}": $PerimetreCode = "DEF";
                break;

              default:
                if ((strstr($cleanSupannOrg, "{CNRS}") != "") || ($cleanSupannOrg == "{CNRS}")) {
                  $Ico = "CNRS_ico.png";
                  $PerimetreCode = "CNRS";
                  $perimetre = "<font color='#004C70'>C</font>";
                }
                if ((strstr($cleanSupannOrg, "{INSERM}") != "") || ($cleanSupannOrg == "{INSERM}")) {
                  $Ico = "ICO_INSERM.png";
                  $PerimetreCode = "INSERM";
                }
                // elseif(strstr( $info[$i]["supannorganisme"][0],"{EES}")!="") $Ico=$IcoAMU; //AMU U1,2,3

                break;
            }

            $result['amuSiteTraduc'] = $info[0]["amusite"][0];
            if (isset($this->arLibPatrimoines[$info[0]["amusite"][0]]))
              $result['amuSiteTraduc'] = $this->arLibPatrimoines[$info[0]["amusite"][0]];

            $result['PerimetreCode'] = $PerimetreCode;
            $result['PerimetreHTMLInfos'] = $perimetre;
            $result['PerimetreIco'] = $Ico;


            foreach ($restriction as $oneParam) {

              $curValue = $info[0][strtolower($oneParam)][0];

              switch (strtolower($oneParam)) {

                case "amudatevalidation": // YYYYMMDDhhmmssZ (20120622000000Z) => date
                  $result['_' . $oneParam] = $curValue; // valeur originale préfixé avec _
                  $result[$oneParam] = $this->ldapDateToVisuDate($curValue, true);
                  break;
                case "schacdateofbirth": // YYYYMMDD => date
                  $result['_' . $oneParam] = $curValue; // valeur originale préfixé avec _
                  $result[$oneParam] = $this->convBirthDate($curValue);
                  break;

                case "sambapwdlastset" : // timestamp (1224505210) => date
                  $result['_' . $oneParam] = $curValue; // valeur originale préfixé avec _
                  $result[$oneParam] = $this->timestampToVisuDate($curValue, false);
                  break;

                case "postaladdress":
                  $result[$oneParam] = $curValue; // valeur originale préfixé avec _
                  $result[$oneParam . "HTML"] = $this->normAddress($curValue);
                  break;
                case "telephonenumber":
                case "mobile":
                case "facsimiletelephonenumber":
                case "supannautretelephone":
                case "fax":
                case "phone":
                  $result['_' . $oneParam] = $curValue; // valeur originale préfixé avec _
                  $result[$oneParam] = $this->NormalizeTel($curValue);
                  break;
                // valeurs mutlivalue
                case "sn" :
                  $result['sn_first'] = $curValue;  // recup All SN
                case "supannEntiteaffectation":
                case "supannaffectation":
                case "edupersonaffiliation":
                  $result[$oneParam] = $info[0][strtolower($oneParam)];  // recup All SN
                  break;
                //	
                default: $result[$oneParam] = $curValue;
                  break;
              }
            }
          }
        }
      }

    if ($debug)
      $this->showDebugInfos($result, "<b>retour de la fonction<b> " . __FUNCTION__);

    return $result;
  }

  /**  Fct spécifique d'intérrogation LDAP : renvoi le mail en fct(idu3)
   * --------------------------------
   * @param string $idu3 IDU3 de la personne.
   * @return string email enregistré dans le LDAP pour la pers.
   */
  public function MailFromIDU3($idu3) {
    $mail = "";
    $this->connect();
    if ($this->r) {
      $filtre = "idu3=" . $idu3 . "*";
      $restriction = array("sn", "mail");
      $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
      $info = ldap_get_entries($this->ds, $sr);
      //$nom=utf8_decode($info[0]["sn"][0]);
      //if (!empty($nom))
      $mail = utf8_decode($info[0]["mail"][0]);
    }
    return $mail;
  }

  /**  Fct spécifique d'intérrogation LDAP : renvoi le mail en fct(idu3)
   * --------------------------------
   * @param string $idu3 IDU3 de la personne.
   * @return string email enregistré dans le LDAP pour la pers.
   */
  public function MailFromHarpid($id) {
    $mail = "";
    $this->connect();
    if ($this->r) {
      $filtre = "supannempid=" . $id . "*";
      $restriction = array("sn", "mail");
      $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
      $info = ldap_get_entries($this->ds, $sr);
      //$nom=utf8_decode($info[0]["sn"][0]);
      //if (!empty($nom))
      $mail = utf8_decode($info[0]["mail"][0]);
    }
    return $mail;
  }

  /**  Fct spécifique d'intérrogation LDAP : renvoi le Numéro Harpège en fct(idu3)
   * @param string $idu3
   * @return int/string Numéro Harpège de la personne concerné
   */
  public function HarpIdFromIDU3($idu3) {
    $id = "";
    if ($this->Debug)
      echo '<br><B><FONT color=blue> CLASS LDAP_Infos :</FONT> <FONT color=red>MODE DEBUG ACTIF</FONT></B><br><br>' . "\n";
    $this->connect();
    if ($this->r) {
      if ($this->Debug)
        if ($this->Anonymous)
          echo '<br><B><FONT color=blue> CLASS LDAP_Infos :</FONT> <FONT color=red>Connexion en Anonymous...</FONT></B><br><br>' . "\n";
      $filtre = "idu3=" . $idu3 . "*";
      $restriction = array("supannempid");
      $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
      $info = ldap_get_entries($this->ds, $sr);
      $id = utf8_decode($info[0]["supannempid"][0]);
    }
    return($id);
  }

  /**
   * Renvoi le N° Harpege (supannempid) d'une personne en fonction de son MAIL
   * @param type $mail
   * @return type 
   */
  public function HarpIdFromMail($mail) {
    $id = "";
    if ($this->Debug)
      echo '<br><B><FONT color=blue> CLASS LDAP_Infos :</FONT> <FONT color=red>MODE DEBUG ACTIF</FONT></B><br><br>' . "\n";
    $this->connect();
    if ($this->r) {
      if ($this->Debug)
        if ($this->Anonymous)
          echo '<br><B><FONT color=blue> CLASS LDAP_Infos :</FONT> <FONT color=red>Connexion en Anonymous...</FONT></B><br><br>' . "\n";
      $filtre = "mail=" . $mail . "*";
      $restriction = array("supannempid");
      $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
      $info = ldap_get_entries($this->ds, $sr);
      $id = utf8_decode($info[0]["supannempid"][0]);
    }
    return($id);
  }

  /** Fct spécifique d'intérrogation LDAP : teste si l'adresse email existe dans le LDAP
   * @param string $mail
   * @return boolean  */
  function ExistMail($mail) {
    $result = false;
    if ($mail != "") {
      $this->connect();
      if ($this->r) {
        $filtre = "mail=" . $mail . "*";
        $restriction = array("sn", "mail", "supannempid");
        $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
        $info = ldap_get_entries($this->ds, $sr);
        $nom = utf8_decode($info[0]["sn"][0]);
        if (!empty($nom)) {
          $mail2 = utf8_decode($info[0]["mail"][0]);
          $result = (trim($mail2) == trim($mail));
        }
      }
    }
    return($result);
  }

  /**
   * Renvoi le mail d'un personne en fonction de son NOM/PRENOM
   * @param type $nom
   * @param type $prenom
   * @return type 
   */
  function HarpMail($nom, $prenom) {
    $result = false;
    if ($mail != "") {
      $this->connect();
      if ($this->r) {
        $filtre = "sn=" . $nom . "*,givenName=" . $prenom . "*";
        $restriction = array("sn", "mail", "supannempid");
        $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
        $info = ldap_get_entries($this->ds, $sr);
        $nom = utf8_decode($info[0]["sn"][0]);
        if (!empty($nom)) {
          $mail2 = utf8_decode($info[0]["mail"][0]);
          $result = (trim($mail2) == trim($mail));
        }
      }
    }
    return($result);
  }

  /**
   * Récupère dans un tableau, l'ensemble des mails disponibles pour un ÉTUDIANT en fonction de son "supannEtuId" 
   * @param integer $supannEtuId le supannEtuId de l'étudiant
   * @return array ["cn", "supannEtuId", "uid", "mail", "amuMail", "amuOldMail"]
   */
  function EtudiantMails($supannEtuId) {
    $arMails = array();
    if ($supannEtuId != "") { //$this->connectMaintenance(); //connect();
      $this->connect();
      if ($this->r) {
        $filtre = "supannetuid=" . $supannEtuId;
        $restriction = array("cn", "supannEtuId", "uid", "mail", "amuMail", "amuOldMail");
        $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
        $arMails = ldap_get_entries($this->ds, $sr);
      }
    }
    return($arMails);
  }

  /**
   * Vérifie si un compte ÉTUDIANT est toujours actif P3 (!suppU3)
   * @param type $uid
   * @return type 
   */
  function EtudiantExist($uid) {
    $result = false;
    if ($uid != "") { //$this->connectMaintenance(); //connect();
      $this->connect();
      if ($this->r) {
        $filtre = "uid=" . $uid;
        $restriction = array("sn", "mail", "uid", "suppU3");
        $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
        $info = ldap_get_entries($this->ds, $sr);
        $nom = utf8_decode($info[0]["sn"][0]);
        $result = (!empty($nom));
        //$result=($info[0]["suppU3"][0]!='');
      }
    }
    return($result);
  }

  /**
   * Vérifier si le mail passé en paréamètre est disponible (pas encore défini..)
   * @param $mail
   */
  function IsMailDispo($mail) {
    $result = false;
    if ($mail != "") {
      $this->connect();
      if ($this->r) {
        $filtre = "mail=" . $mail;
        $restriction = array("sn", "mail", "uid", "suppU3");
        $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
        $info = ldap_get_entries($this->ds, $sr);
        $nom = utf8_decode($info[0]["sn"][0]);
        if (!empty($nom))
          $result = ($info[0]["suppU3"][0] != '');
        else
          $result = true;
      }
    }
    return($result);
  }

  /** Function de modification de la variable ListeRouge dans le LDAP pour un identifiant donnée
   * @param $login identifiant de la personne
   * @param $newvalue nouvelle valeur à affecté (true/false)
   * @param $debug si=true Activer le mode DEBUG
   */
  function modifListeRouge($login, $newvalue, $debug = false) {
    $AttributInfos = "";
    $oldState = $this->Anonymous;
    $this->Anonymous = false;
    $this->connectModif();
    if ($this->ds) {
      $dn = "ou=people,dc=univ-amu,dc=fr";
      $filtre = "uid=" . $login;
      $restriction = array('supannEmpId', 'supannListeRouge', 'sn');
      $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction, 0);
      $entry = ldap_get_entries($this->ds, $sr);

      if ($debug == true)
        echo "<B>DEBUG: " . __CLASS__ . "::" . __FUNCTION__ . " =><br>entry</B> = <BR><PRE>" . print_r($entry, true) . "</PRE><BR>";
      $listerouge = $entry[0]['supannlisterouge'][0];
      $empid = $entry[0]['supannempid'][0];
      //$sn=$entry[0][sn][0];
      if ($debug == true)
        echo "<B>supannempid</B>=" . $empid . " <BR><B>supannlisterouge</B>=" . $listerouge . "<BR>";

      if ($debug == true)
        echo "<B>Nouveau supannlisterouge</B>=" . $newvalue . "<BR>";
      $newentry['supannlisterouge'] = ((strtolower($newvalue) == "true") ? "TRUE" : "FALSE");

      $result = ldap_mod_replace($this->ds, $filtre . "," . $dn, $newentry);
      if (!$result)
        echo ldap_error($this->ds);
      if ($debug == true)
        echo "<B>Résultat d'écriture des Nouveaux codes </B> = > " . (($result) ? "OK" : "ERREUR") . "<BR>";
//			
      unset($entry);
    }
    $this->disconnect();
    $this->Anonymous = $oldState;
    return $result;
  }

  /** Fct généric d'intérrogation (GLOBAL) des informations en fct(uid d'une personne)
   * @param string $uid
   * @return string[] tableau indéxé des données LDAP disponibles pour cette personne */
  public function arInfosFrom_UID($uid, $debug = false) {
    $this->Debug = $debug;
    return $this->LDAP_getIdxTabInfos("uid=" . $uid);
  }

//  /** Fct généric d'intérrogation (GLOBAL) des informations en fct(IDU3 d'une personne)
//   * @param string $idu3 l'identifiant U3 (IDU3) NOM_Patronymique+Prénom+Date de Naissance (sans espace, accents ou car spécial)
//   * @return string[] tableau indéxé des données LDAP disponibles pour cette personne */
//  public function LDAP_getIdxTabInfosFrom_IDU3($idu3) {
//    $this->Debug=$debug;
//    return $this->LDAP_getIdxTabInfos("idu3=" . $idu3);
//  }

  /** Fct généric d'intérrogation (GLOBAL) des informations en fct(login d'une personne)
   * @param string $login identifiant de connexion au LDAP
   * @return string[] tableau indéxé des données LDAP disponibles pour cette personne */
  public function arInfosFrom_login($login, $debug = false) {
    $this->Debug = $debug;
    return $this->LDAP_getIdxTabInfos("uid=" . $login);
  }

  /** Fct généric d'intérrogation (GLOBAL) des informations en fct(mail d'une personne)
   * @param string $mail email
   * @return string[] tableau indéxé des données LDAP disponibles pour cette personne */
  public function arInfosFrom_mail($mail, $debug = false) {
    $this->Debug = $debug;
    return $this->LDAP_getIdxTabInfos("mail=" . $mail);
  }

  /** Fct généric d'intérrogation (GLOBAL) des informations en fct(Numéro Harpège d'une personne)
   * @param string $id Numéro Harpège
   * @return string[] tableau indéxé des données LDAP disponibles pour cette personne */
  public function arInfosFrom_HarpId($id, $debug = false) {
    $this->Debug = $debug;
    return $this->LDAP_getIdxTabInfos("supannempid=" . $id);
  }

  /**
   * Renvoi des informations en fonction du numéro HARPEGE (décortique $id + auto redirection suramuperimetre fonction du préfixe P1,P2, P3)
   * Exemple 3.6201 => (&(supannempid=6201)(amuperimetre=3))"); 
   * @param type $id
   * @return type 
   */
  public function arInfosFrom_HarpId123($id, $debug = false) {
    $this->Debug = $debug;
    $arID = explode('.', $id);
    if ($debug)
      echo "<hr># arID= <PRE>" . print_r($arID, true) . "</PRE>";
    if (count($arID) == 2) {
      if ($arID[0] != '')
        if ($arID[1] != '') {
          if ($debug)
            echo "<hr># this->LDAP_getIdxTabInfos('(&(supannempid='$arID[1]')(amuperimetre='$arID[0]'))'); ";
          return $this->LDAP_getIdxTabInfos("(&(supannempid=" . $arID[1] . ")(amuperimetre=" . $arID[0] . "))");
        }
    }
    else
      return(array());
  }

  /**
   * Renvoi un tableau des membres d'un groupe (GROUPER)
   * @param type $groupName
   * @param type $restriction
   * @param type $debug
   * @return type 
   */
  public function getMembersOfGroup($groupName, $restriction = array("cn", "member", "memberof", "memberOf", "uid"), $debug = false) {
    $filtre = "(&(objectclass=*)(cn=" . $groupName . "))";
    $Infos = $this->LDAP_getIdxTabInfosMultiple($filtre, $restriction, null, "cn");

    $arUsers = array();
    foreach ($Infos[0]['member'] as $key => $oneMember) {
      // Modif PA 
//        if($key!="count") 
//        {
      $arUsers[] = preg_replace("/(uid=)(([a-z0-9.]{1,}))(,ou=.*)/", "$3", $oneMember);
//        }
    }

    if ($debug)
      echo "<hr>DEBUG " . __CLASS__ . "::" . __FUNCTION__ . " arUsers <PRE>" . print_r($arUsers, true) . "</PRE>";

    return $arUsers;
  }

  /**
   * Renvoi un tableau des GROUPES (grouper) auxquels appartient la personne dont l'uid est spécifié
   * @param type $uid l'uid/login de la personne 
   * @param type $debug ==true => Afficher les informations de débug
   * @return type 
   */
  public function getMemberOf($uid, $debug = false) {
    $filtre = "(&(objectclass=*)(member=uid=$uid,ou=people,dc=univ-amu,dc=fr))";
    $Infos = $this->LDAP_getIdxTabInfosMultiple($filtre, array("cn"), null, "cn");
    $arGroups = array();
    foreach ($Infos as $oneGroup)
      $arGroups[] = $oneGroup['cn'];

    if ($debug)
      echo "<hr>DEBUG " . __CLASS__ . "::" . __FUNCTION__ . " arGroups <PRE>" . print_r($arGroups, true) . "</PRE>";

    return $arGroups;
  }

  /**
   * renvoi un tableau des GROUPES disponible issue des données du LDAP (formaté en JSON pour alimenté une compleition liste)
   * @param type $DebNom        [OBLIGATOIRE  > 2] chaine de caractère du début du nom
   * @param type $restriction   [OPTION] Tableau de paramètres restriction/filtrage des éléments retournés ("cn","description","member" par défaut) 
   * @param type $cnvLdapAttr   [OPTION] Option de conveersion des Attribut ("null" par défaut)
   * @param type $needAttr      [OPTION] rendre obligatoire un paramètre de retour : ligne non renvoyé si l'élémént spécifié n'est pas initialisé (cn par défaut)
   * @param type $ValueNameVar  [OPTION] Élément représentant la "Valeur" (value) à retourner pour chaque éléménts ("cn" par défaut)
   * @param type $debug         [OPTION] Si = true active le mode DEBUG ("false" par défaut)
   * @return type 
   */
  protected function JSON_InfosGroupWhere_descriptionLike($DebNom, $restriction = array("cn", "description", "member"), $cnvLdapAttr = null, $needAttr = 'cn', $ValueNameVar = 'cn', $debug = false) {
    $JSON_datas = "";
    if (strlen($DebNom) >= 2) {
      $srType = "";
      $sr = "description=*" . str_ireplace(" ", "", $DebNom) . "*";
    }

    if ($this->Debug)
      echo "<hr>#DEBUG <b>" . __FUNCTION__ . "()</b> filtre ldap=" . $sr;
    $Infos = $this->LDAP_getIdxTabInfosMultiple($sr, $restriction, $cnvLdapAttr, $needAttr);
    //if($this->Debug) echo "<hr>#DEBUG <b>".__FUNCTION__."() LDAP_getIdxTabInfosMultiple</b><font color=red><PRE>".print_r($Infos,true)."</PRE></font>";
    //asort($Infos);
    foreach ($Infos as $key => $oneInfo) {
      $newAr = Array();
      foreach ($oneInfo as $key2 => $oneInfo2)
        $newAr[$key2] = $oneInfo2;

      if (is_array($ValueNameVar)) {
        $completeValueName = "";
        foreach ($ValueNameVar as $oneValueNameVar) {
          $completeValueName .= (($completeValueName != "") ? "." : "") . $oneInfo[$oneValueNameVar];
        }
        $newAr['value'] = $completeValueName;
      }else
        $newAr['value'] = $oneInfo[$ValueNameVar];

      $newAr['label'] = $oneInfo['description'];

      foreach ($restriction as $oneLdpaVar) {
        $newAr[$oneLdpaVar] = $oneInfo[$oneLdpaVar];
      }

      $JSON_datas .=(($JSON_datas != "") ? "," : "") . json_encode($newAr);
    }

    $JSON_datas = "[" . $JSON_datas . "]";
    return($JSON_datas);
  }

  /**
   * renvoi un tableau de toutes les informations disponibles (formaté en JSON pour alimenté une compleition liste)
   * @param type $DebNom chaine de caractère du début du nom ( OBLIGATOIRE & strlen > 2 )
   * OPTIONS
   * =======
   * @param array $restriction   Tableau de paramètres restriction/filtrage des éléments retournés 
   * <br> par défaut :<i> array("sn", "givenname", "uid", "supannaliaslogin", "facsimiletelephonenumber", "telephonenumber", "supanncivilite", "idu3", "uid", "empIdU3", "supannListeRouge", "mail", "coAbonnement", "supannEmpId", "amuPerimetre") </i>
   * @param string $cnvLdapAttr   Option de conversion des Attributs ("null" par défaut)
   * @param string $needAttr      Option rendre obligatoire un paramètre de retour : <i> lignes non renvoyées si l'élémént spécifié n'est pas initialisé (supannempid par défaut)</i>
   * @param string $ValueNameVar  Élément reprrésentant la "Valeur" (value) à retourner pour chaque éléménts ("supannempid" par défaut)
   * @param csv $onlyAffect       Option de filtrage : ne renvoi que les éléménts/personnes qui sont affecté à ce code HARPEGE $onlyAffect (supannentiteaffectation) (defaut: false)
   * @param bool $noStudent       Option de filtrage : ne renvoi pas les personnes de type étdudiants (edupersonprimaryaffiliation != student ou alum ou oldemployee) (defaut: false)
   * @param bool $noPersonals     Option de filtrage : ne renvoi pas les personnels de l'université (edupersonprimaryaffiliation=student) et edupersonprimaryaffiliation!=alum) (defaut: false)
   * @param bool $needPerimetre   Option de filtrage : ne renvoi que les personnels dont le périmètre est initialisé (amuPerimetre) (defaut: false)
   * @param bool $debug           Si = true active le mode DEBUG (defaut: false)
   * @return JSON array( $ValueNameVar => array($restriction) ) ; soit par defaut <br><i> array( 'supannempid'=>array("sn", "givenname", "uid", "supannaliaslogin", "facsimiletelephonenumber", "telephonenumber", "supanncivilite", "idu3", "uid", "empIdU3", "supannListeRouge", "mail", "coAbonnement", "supannEmpId", "amuPerimetre") )</i>
   */
  protected function JSON_InfosWhere_cnLike($DebNom, $restriction = array("sn", "givenname", "uid", "supannaliaslogin", "facsimiletelephonenumber", "telephonenumber", "supanncivilite", "idu3", "uid", "empIdU3", "supannListeRouge", "mail", "coAbonnement", "supannEmpId", "amuPerimetre"), $cnvLdapAttr = null, $needAttr = 'supannempid', $ValueNameVar = 'supannempid', $onlyAffect = "", $noStudent = false, $noPersonals = false, $needPerimetre = false, $codEtape = "", $codDiplome = "", $debug = false) {
    $JSON_datas = "";
    $srEtp = "";
    $srDip = "";
    if (strlen($DebNom) >= 2) {

      //$DebNom=str_ireplace(" ","",$DebNom);
      //$filtreNom="(sn=".$DebNom."*)";
      $filtreNom = "(|(sn=" . $this->enleveaccents(utf8_decode($DebNom)) . "*)(sn=" . $DebNom . "*))";
      //old (sn=".str_ireplace(" ","",$DebNom)."*)


      $srType = "";
      if ($noStudent)
        $srType = "(&(!(edupersonprimaryaffiliation=student)))(&(!(edupersonprimaryaffiliation=alum)))(&(!(edupersonprimaryaffiliation=oldemployee)))";
      // (&(!(edupersonprimaryaffiliation=oldemployee)))
      if ($noPersonals) {
        $srType = "(&(edupersonprimaryaffiliation=student)))(&(!(edupersonprimaryaffiliation=alum)))";
        if ($codEtape != "")
          $srEtp = "(&(supannetuetape=$codEtape))";
        if ($codDiplome != "")
          $srDip = "(&(supannetudiplome=$codDiplome))";
      }


      if ($onlyAffect != "") {
        if (strstr($onlyAffect, ",") == "")
          $sr = "(&" . $filtreNom . "(supannentiteaffectation=" . $onlyAffect . "*)$srType)";
        else {
          $arAffects = explode(',', $onlyAffect);
          $sr = "(|";
          foreach ($arAffects as $oneAffect)
            if ($oneAffect != "")
              $sr .= "(&" . $filtreNom . "(supannentiteaffectation=" . $oneAffect . "*)$srType)";
          $sr .=")";
        }
      }
      else {
        if ($noStudent)
          $sr = "(&" . $filtreNom . "$srType)";
        else
          $sr = $filtreNom;
      }
//
//      if ($debug){
//        echo "</br><hr>#DEBUG <b>" . __FUNCTION__ . "()</b> filtre sr= " . $sr;
//        echo "</br><hr>#DEBUG <b>" . __FUNCTION__ . "()</b> filtre2 srEtp= $srEtp";
//        echo "</br><hr>#DEBUG <b>" . __FUNCTION__ . "()</b> filtre2 srDip= $srDip";
//      }

      $Infos = $this->LDAP_getIdxTabInfosMultiple($sr, $restriction, $cnvLdapAttr, $needAttr);


      //if($this->Debug) echo "<hr>#DEBUG <b>".__FUNCTION__."() LDAP_getIdxTabInfosMultiple</b><font color=red><PRE>".print_r($Infos,true)."</PRE></font>";
      //asort($Infos);
      foreach ($Infos as $key => $oneInfo) {
        $newAr = Array();
        foreach ($oneInfo as $key2 => $oneInfo2)
          $newAr[$key2] = $oneInfo2;

        if (is_array($ValueNameVar)) {
          $completeValueName = "";
          foreach ($ValueNameVar as $oneValueNameVar) {
            $completeValueName .= (($completeValueName != "") ? "." : "") . $oneInfo[$oneValueNameVar];
          }
          $newAr['value'] = $completeValueName;
        }else
          $newAr['value'] = $oneInfo[$ValueNameVar];

        $infoNoms = $oneInfo['sn'];
        if (is_array($oneInfo['sn'])) {
          foreach ($oneInfo['sn'] as $oneNom) {
            $infoNoms = ( ($infoNoms != "") ? "," : "" ) . $oneNom;
          }
        }

        $newAr['label'] = $infoNoms;

        foreach ($restriction as $oneLdpaVar) {
          $newAr[$oneLdpaVar] = $oneInfo[$oneLdpaVar];
        }

        $names = $oneInfo['sn'];
        $surname = $oneInfo['givenname'];
        if (is_array($names)) {
          $newAr['label'] = "";
          foreach ($names as $kn => $oneName)
            $newAr['label'] .= (($newAr['label'] != '') ? ", " : "") . $oneName;
        }
        //else  $newAr['label']=$oneInfo['sn'];
        $newAr['label'] .= " " . ucfirst(strtolower($surname));

        if ($needPerimetre) {
          if (trim($newAr['amuperimetre']) != "")
            $JSON_datas .=(($JSON_datas != "") ? "," : "") . json_encode($newAr);
        }
        else
          $JSON_datas .=(($JSON_datas != "") ? "," : "") . json_encode($newAr);
      }
    }
    $JSON_datas = "[" . $JSON_datas . "]";
    return($JSON_datas);
  }

  /**
   * Renvoi une liste JSON issue des données du LDAP des étudiants dont le nom ("sn") commence par $term 
   * <br> (et en option dont le "supannentiteaffectation" est égale à $onlyCompo)</font>
   * <br><br><b>REMARQUE IMPORTANTE :</b><i> la recherche ne débutera qu'à partir du moment ou $term contient plus de 2 caractères</i>
   * @param type $term        [OBLIGATIORE] le début du nom (sn) de l'étudiant
   * @param string $onlyCompo [OPTION] liste des composantes (csv séparateur virgule) 
   * <br><i>=> Filtrer par Affectation : n'afficher QUE les étudiants dont le nom commence par $term<br>ET dont l'affectation fait partie de la liste $onlyCompo</i>
   * @param type $debug       Si = true active le mode DEBUG (défaut: false)
   * @return JSON (formaté pour alimenter une "liste auto-complétion")<br>  array( 'supannetuid' => array('codeetape','supannentiteaffectationerincipale','supannentiteaffectation','supannetuid','sn','givenname','supannaliaslogin','uid') )
   */
  public function JSON_ListeEtudiants($term = "", $onlyCompo = "", $codEtp = "", $codDip = "", $debug = false) {
    return $this->JSON_InfosWhere_cnLike(strtolower($term), array('supannetuetape', 'codeetape', 'supannentiteaffectationerincipale', 'supannentiteaffectation', 'supannetuid', 'sn', 'givenname', 'supannaliaslogin', 'uid', 'supannetuetape', 'supannetudiplome'), null, 'supannetuid', 'supannetuid', $onlyCompo, false, true, false, $codEtp, $codDip, $debug);
  }

  /**
   * Renvoi une liste JSON issue des données du LDAP des personnels dont le nom ("sn") commence par $term 
   * <br> (et en option dont le "supannentiteaffectation" est égale à $onlyCompo)</font>
   * <br><br><b>REMARQUE IMPORTANTE :</b><i> la recherche ne débutera qu'à partir du moment ou $term contient plus de 2 caractères</i>
   * @param string $term      [OBLIGATIORE] le début du nom (sn) de l'étudiant
   * @param string $onlyCompo [OPTION] liste des composantes (csv séparateur virgule) 
   * <br><i>=> Filtrer par Affectation : n'afficher QUE les étudiants dont le nom commence par $term<br>ET dont l'affectation fait partie de la liste $onlyCompo</i>
   * @param type $debug       Si = true active le mode DEBUG (défaut: false)
   * * @return JSON (formaté pour alimenter une liste "auto-complétion")<br>  array( 'uid' => array('sn','givenname','supannempid','supannaliaslogin','uid') )
   */
  public function JSON_ListePersonnels($term = "", $onlyCompo = "", $debug = false) {
    return $this->JSON_InfosWhere_cnLike(strtolower($term), array('sn', 'givenname', 'supannempid', 'supannaliaslogin', 'uid'), null, "uid", 'uid', $onlyCompo, true, false, false, "", "", $debug);
  }

  /**
   * Renvoi une liste JSON issue des données du LDAP des personnes (agents, étudiants...) dont le nom ("sn") commence par $term 
   * <br> (et en option dont le "supannentiteaffectation" est égale à $onlyCompo)</font>
   * <br><br><b>REMARQUE IMPORTANTE :</b><i> la recherche ne débutera qu'à partir du moment ou $term contient plus de 2 caractères</i>
   * @param string $term      [OBLIGATIORE] le début du nom (sn) de l'étudiant
   * @param string $onlyCompo [OPTION] liste des composantes (csv séparateur virgule) 
   * <br><i>=> Filtrer par Affectation : n'afficher QUE les étudiants dont le nom commence par $term<br>ET dont l'affectation fait partie de la liste $onlyCompo</i>
   * @param type $debug       Si = true active le mode DEBUG (défaut: false)
   * @return JSON (formaté pour alimenter une liste "auto-complétion")<br> array( 'uid' => array('sn','givenname','supannempid','supannaliaslogin','codeetape','supannentiteaffectationerincipale','supannentiteaffectation','supannetuid','uid') )
   */
  public function JSON_ListePersonnes($term = "", $onlyCompo = "", $debug = false) {
    $arRestriction = array(
        'uid', 'supanncivilite', 'cn', 'sn', 'givenname', 'amuMail', 'mail', 'amuOldMail', // basic
        'postalAddress', 'telephoneNumber', 'facsimileTelephoneNumber', 'supannAutreTelephone', // contact
        'eduPersonAffiliation', 'eduPersonPrimaryAffiliation',
        // PERSONNEL
        'supannEmpId', 'supannOrganisme', "supannEtablissement", // personnel
        'supannAffectation', 'eduPersonPrimaryAffiliation', 'supannEntiteAffectation', 'supannEntiteAffectationPrincipale', //affectations
        "amuPerimetre", "amuCampus", "amuComposante", "amuSite",
        'supannAliasLogin', 'idu3', // P3
        'supannListeRouge', 'mobile', 'schacDateOfBirth', 'coabonnement',
        // ÉTUDIANT
        'supannEtuId', 'supannEtuEtape', 'supannEtuDiplome', 'supannAnneeInscription', // Etu
        'supannAffectation', 'eduPersonPrimaryAffiliation', 'supannEntiteAffectation', 'supannEntiteAffectationPrincipale', //affectations
        'codeEtape', 'supannAliasLogin', // old P3
        // INFOS SUP
        'schacDateOfBirth',
    );
    foreach ($arRestriction as $key => $value) {
      $arRestriction[$key] = strtolower($value);
    }
    //$defRestriction=array('edupersonprimaryaffiliation','supannempid','supannetuid','supannetuetape','supannetudiplome','supannanneeInscription', 'sn','givenname','supannempid','supannaliaslogin','supannetuetape','codeetape','supannentiteaffectationerincipale','supannentiteaffectation','supannetuid', 'uid');
    return $this->JSON_InfosWhere_cnLike(strtolower($term), $arRestriction, null, "uid", 'uid', $onlyCompo, false, false, false, "", "", $debug);
  }

  /**
   * renvoi un tableau des GROUPES disponible issue des données du LDAP (formaté en JSON pour alimenté une liste à auto-complétion)
   * @param string $term        [OBLIGATOIRE > 2] chaine de caractère du début du nom
   * @param array $restriction  [OPTION] Tableau de paramètres restriction/filtrage des éléments retournés ("cn","description","member" par défaut) 
   * @param string $cnvLdapAttr [OPTION] Option de conveersion des Attribut ("null" par défaut)
   * @param bool $needAttr      [OPTION] rendre obligatoire un paramètre de retour : ligne non renvoyé si l'élémént spécifié n'est pas initialisé (cn par défaut)
   * @param bool $ValueNameVar  [OPTION] Élément représentant la "Valeur" (value) à retourner pour chaque éléménts ("cn" par défaut)
   * @param bool $debug         [OPTION] Si = true active le mode DEBUG ("false" par défaut)
   * @param type $debug       Si = true active le mode DEBUG (défaut: false)
   * @return JSON (formaté pour alimenté une liste à auto-complétion)<br> array( 'cn' => array("cn","description","member") )
   */
  public function JSON_ListeGroupes($term = "", $debug = false) {
    return $this->JSON_InfosGroupWhere_descriptionLike($term, array("cn", "description", "member"), null, 'cn', 'cn', $debug);
  }

  /** Fct généric d'intérrogation (GLOBAL) des informations en fct(Nom, Prénom, Date de naissance d'une personne)
   * @param string $nom Nom de la personne
   * @param string $prenom Prénom de la personne
   * @param string $datenaiss Date de naissance de la personne
   * @return string[] tableau indéxé des données LDAP disponibles pour cette personne */
  public function arInfosFrom_IDU3bis($nom, $prenom, $datenaiss) {
    return $this->LDAP_getIdxTabInfos("idu3=" . str_ireplace(" ", "", $nom . $prenom . str_ireplace("/", "", $datenaiss)) . "*");
  }

  /**
 * CrÃ©ation d'un groupe dans le LDAP
 * @return  \Amu\AppBundle\Service\Ldap
 */
 public function createGroupeLdap($dn, $groupeinfo) 
 {
   
    $this->connect();
    if ($this->r) {

        ldap_add($this->ds,$dn,$groupeinfo);
        $e = ldap_error($this->ds);
        
        if(ldap_error($this->ds) == "Success")
            return true;
        else
            return false;
    }
    
    return(false);
    
 }
 
 /**
 * Suppression d'un groupe dans le LDAP
 * @return  \Amu\AppBundle\Service\Ldap
 */
 public function deleteGroupeLdap($cn) 
 {
    $dn = "cn=".$cn.", ou=groups, dc=univ-amu, dc=fr";
    $this->connect();
    if ($this->r) {

        ldap_delete($this->ds,$dn);
    
        if(ldap_error($this->ds) == "Success")
            return true;
        else
            return false;
    }
    
    return(false);
    
 }
 
 /**
 * Modification d'un groupe dans le LDAP
 * @return  \Amu\AppBundle\Service\Ldap
 */
 public function modifyGroupeLdap($dn, $groupeinfo) 
 {
   
    $this->connect();
    if ($this->r) {

        $cn = preg_replace("/(cn=)(([A-Za-z0-9:._-]{1,}))(,ou=.*)/", "$3", $dn);
        if ($cn != $groupeinfo['cn'])
        {
            // Renommage de l'entrée LDAP
            $newRdn = "cn=".$groupeinfo['cn'];
        
            // $newparent IS the full DN to the NEW parent DN that you want to move/rename to
            $newParent = "ou=groups, dc=univ-amu, dc=fr";
            
            ldap_rename($this->ds, $dn, $newRdn, $newParent, true);
            $dn = $newRdn . ", ou=groups, dc=univ-amu, dc=fr";
        }
        
        
        ldap_modify($this->ds,$dn,$groupeinfo);
     
        $r = ldap_error($this->ds);
    
        if(ldap_error($this->ds) == "Success")
            return true;
        else
            return false;
    }
    
    return(false);
    
 }
 /**
 * RÃ©cupÃ©ration des membres d'un groupe + infos des membres
 * @return  \Amu\AppBundle\Service\Ldap
 */
 public function getMembersGroup($groupName, $restriction = array("uid", "displayName", "mail", "telephoneNumber", "sn"), $debug = false) {
    $filtre = "(&(objectclass=*)(memberOf=cn=" . $groupName . ", ou=groups, dc=univ-amu, dc=fr))";
    $AllInfos = array();
    $AllInfosBrutes = array();
    $this->connect();
    if ($this->r) {
      $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction); // ,25 pour limiter les rÃ©sultats Ã  25 items
      $AllInfosBrutes = ldap_get_entries($this->ds, $sr);
      $AllInfos = $this->ldap_multi_sort($AllInfosBrutes, array("sn"));
   
    }
       
    if ($debug)
      echo "<hr>DEBUG " . __CLASS__ . "::" . __FUNCTION__ . " arUsers <PRE>" . print_r($AllInfos, true) . "</PRE>";

    return $AllInfos;
  }
  
   /**
 * RÃ©cupÃ©ration des admins d'un groupe + infos des membres
 * @return  \Amu\AppBundle\Service\Ldap
 */
 public function getAdminsGroup($groupName, $restriction = array("amuGroupAdmin"), $debug = false) {
    $filtre = "cn=". $groupName ;
    $AllInfos = array();
    $this->connect();
    if ($this->r) { 
      $sr = ldap_search($this->ds, $this->LDAP_racine, $filtre, $restriction); // ,25 pour limiter les rÃ©sultats Ã  25 items
      $AllInfos = ldap_get_entries($this->ds, $sr);
    }

    if ($debug)
      echo "<hr>DEBUG " . __CLASS__ . "::" . __FUNCTION__ . " arUsers <PRE>" . print_r($AllInfos, true) . "</PRE>";

    return $AllInfos;
  }
  
  /**
 * Ajouter un membre dans un groupe
 * @return  \Amu\AppBundle\Service\Ldap
 */
 public function addMemberGroup($dn_group, $arUserUid) {
       
    foreach ($arUserUid as $uid)
    {
        $groupinfo['member'][] = "uid=".$uid.",ou=people,dc=univ-amu,dc=fr";
    }
    $this->connect();
    if ($this->r) {
        $sr = ldap_mod_add($this->ds, $dn_group, $groupinfo);      
         if(ldap_error($this->ds) == "Success")
            return true;
        else
            return false;
    }
       
    if ($debug)
      echo "<hr>DEBUG " . __CLASS__ . "::" . __FUNCTION__ . " arUserUid <PRE>" . print_r($groupinfo, true) . "</PRE>";

    return false;
  }
  
  /**
 * Supprimer un membre d'un groupe
 * @return  \Amu\AppBundle\Service\Ldap
 */
 public function delMemberGroup($dn_group, $arUserUid) {
       
    foreach ($arUserUid as $uid)
    {
        $groupinfo['member'][] = "uid=".$uid.",ou=people,dc=univ-amu,dc=fr";
    }
    $this->connect();
    if ($this->r) {
        $sr = ldap_mod_del($this->ds, $dn_group, $groupinfo);     
        //echo "<hr>DEBUG " . __CLASS__ . "::" . __FUNCTION__ . " Infos groupe <PRE>" . print_r($groupinfo, true) . "</PRE>";
         if(ldap_error($this->ds) == "Success")
            return true;
        else
            return false;
    }
       
    if ($debug)
      echo "<hr>DEBUG " . __CLASS__ . "::" . __FUNCTION__ . " arUserUid <PRE>" . print_r($groupinfo, true) . "</PRE>";

    return false;
  }
  
 /**
 * Ajouter un administrateur dans un groupe
 * @return  \Amu\AppBundle\Service\Ldap
 */
 public function addAdminGroup($dn_group, $arUserUid) {
       
    foreach ($arUserUid as $uid)
    {
        $groupinfo['amuGroupAdmin'][] = "uid=".$uid.",ou=people,dc=univ-amu,dc=fr";
    }
    $this->connect();
    if ($this->r) {
        $sr = ldap_mod_add($this->ds, $dn_group, $groupinfo);      
        if(ldap_error($this->ds) == "Success")
            return true;
        else
            return false;
    }
       
    if ($debug)
      echo "<hr>DEBUG " . __CLASS__ . "::" . __FUNCTION__ . " arUserUid <PRE>" . print_r($groupinfo, true) . "</PRE>";

    return false;
  }
  
   /**
 * Supprimer un membre d'un groupe
 * @return  \Amu\AppBundle\Service\Ldap
 */
 public function delAdminGroup($dn_group, $arUserUid) {
       
    foreach ($arUserUid as $uid)
    {
        $groupinfo['amuGroupAdmin'][] = "uid=".$uid.",ou=people,dc=univ-amu,dc=fr";
    }
    $this->connect();
    if ($this->r) {
        $sr = ldap_mod_del($this->ds, $dn_group, $groupinfo);     
        if(ldap_error($this->ds) == "Success")
            return true;
        else
            return false;
    }
       
    if ($debug)
      echo "<hr>DEBUG " . __CLASS__ . "::" . __FUNCTION__ . " arUserUid <PRE>" . print_r($groupinfo, true) . "</PRE>";

    return false;
  }
  
  /**
 * Supprimer le amugroupfilter d'un groupe
 * @return  \Amu\AppBundle\Service\Ldap
 */
 public function delAmuGroupFilter($dn_group, $filter) {
       
    $groupinfo['amuGroupFilter'] = $filter;
   
    $this->connect();
    if ($this->r) {
        $sr = ldap_mod_del($this->ds, $dn_group, $groupinfo);      
         if(ldap_error($this->ds) == "Success")
            return true;
        else
            return false;
    }
       
    if ($debug)
      echo "<hr>DEBUG " . __CLASS__ . "::" . __FUNCTION__ . " arUserUid <PRE>" . print_r($groupinfo, true) . "</PRE>";

    return false;
  }  
 
 }
?>
