<?php

namespace Amu\CliGrouperBundle\Service;

use Symfony\Bridge\Monolog\Logger;
/*
define("WS_NO_DATA", "technical.data.nullretrieve");

define("WS_OFFLINE", "technical.data.transaction.connexion");
define("WS_OFFLINE2", "java.lang.reflect.InvocationTargetException");

define("WS_NO_USER", "technical.security.invaliduser.user");
define("WS_NO_ETU", "technical.parameter.noncoherentinput.codEtu ");

define("WS_NO_HARPID", "HARPEGE_WS_INDIVIDU_NON_TROUVEE");

define("ERROR_MSG_OFFLINE", "Web Service hors ligne !!!");
*/
/**
 * Classe regroupant tous les outils standards d'intérrogation WS...
 * @author Michel UBÉDA <michel.ubeda@univ-amu.fr>
 */
class WSTools extends String {

  public $DebugLogger;
    
  function __construct(ContainerInterface $container,Logger $debug) {
    $this->DebugLogger=$debug;
  }

  /**
   * Fonction générique d'intéroggation de WEB SERVICE (retour class->$wsReturn)
   * @param type $WSDL <b>OBLIGATOIRE</b> l'URL du fichier de définition wsdl du WS à intérroger
   * @param type $function <b>OBLIGATOIRE</b> la FONCTION ws à intérroger
   * @param type $params les PARAMÈTRES DU ws à intérroger (defaut: array() )
   * @param type $wsReturn l'ELEMENT de l'OBJET à traiter (defaut: "" => correspondra à  $function . "Return"; )
   * @param type $localFunc NOM de la FONCTION appellante (defaut: __FUNCTION__ == "_genericWSRequestToArray")
   * @param type $localFile NOM du FICHIER appellant (defaut: __FILE__ == "WSTools.php")
   * @param type $ExitOnError FORCER la SORTIE en cas d'erreurs (true/false  defaut:false)
   * @param type $debug Activer le premier niveau de DEBUG (true/false  defaut:false)
   * @param boolean $debug2 Activer le second niveau de DEBUG (true/false  defaut:false)
   * @return class->$wsReturn
   */
  protected function _genericWSRequest($WSDL = "", $function = "", $params = array(), $wsReturn = "", $localFunc = __FUNCTION__, $localFile = __FILE__, $ExitOnError = false, $debug = false) {
    if ($wsReturn == "")
      $wsReturn = $function . "Return";
    if ($WSDL != "")
      if ($function != "") {
        try {
          $soapClient = new \SoapClient($WSDL);
          $result = $soapClient->__soapCall($function, $params);
          if ($debug)
            $this->DebugLogger->addInfo("DANS $localFile ".__CLASS__."::$localFunc => $function : donn&eacute;es brutes:</br><pre>".  print_r($result,true)."</pre>");

          unset($soapClient);
          if ($debug)
            $this->showDebugResultWS($result, $params, $localFunc, $localFile, $wsReturn);
        } catch (\SoapFault $fault) {
          $result = array();
          if ((strpos($fault->getMessage(), "technical.parameter.nullretrieve") !== false) or
                  (strpos($fault->getMessage(), "technical.data.nullretrieve") !== false)) {
            if ($debug) {
              $this->showDebugInfos("Aucun r&eacute;sultat pour cette requ&ecirc;tes" .
                      "</br>fonction: $localFunc" .
                      "</br>params<pre>" . print_r($params, true) . "</pre>", "Int&eacute;rrogation WS $localFunc ($function) - <font color=red>PAS DE DONN&Eacute;ES EN RETOUR...</font>", $localFile, true);
//                        $this->showDebugResultWS($fault[0], $params, $localFunc, $localFile, $wsReturn);
            }
          }
          else
            $this->showSOAP_Error($fault, $localFunc, $localFile, $ExitOnError);
          unset($fault);
          if ($debug)
            $this->showDebugInfos($result, $localFunc . " <font color='gray'>[<i> " . __FUNCTION__ . " </i>]</font> => <font color='green'>R&Eacute;SULTAT FINAL</font>", __FILE__, false);
          return($result);
        }
        if ($wsReturn != "") {
          if (property_exists($result, $wsReturn)) { // or isset($result->$wsReturn)
            if ($debug)
              $this->showDebugInfos($result->$wsReturn, $localFunc . " <font color='gray'>[<i> " . __FUNCTION__ . " </i>]</font> => <font color='green'>R&Eacute;SULTAT FINAL</font>", __FILE__, false);
            return($result->$wsReturn);
          }
          else {
            $this->showDebugInfos($result, "WS - variable de retour '$wsReturn' in&eacute;xistante !!!", $localFile, false, true);
            return($result);
          }
        } else {
          if ($debug)
            $this->showDebugInfos($result, $localFunc . " <font color=gray>[<i> " . __FUNCTION__ . " </i>]</font> => <font color=green>R&Eacute;SULTAT FINAL</font>", __FILE__, false);
        }
        return($result);
      }
  }

  /**
   * Fonction générique d'intéroggation de WEB SERVICE (retour Array indexé avec $arKey/$arVal)
   * @param type $WSDL l'URL du fichier de définition wsdl du WS à intérroger [OBLIGATOIRE]
   * @param type $function la FONCTION ws à intérroger [OBLIGATOIRE]
   * @param type $params les PARAMÈTRES DU ws à intérroger (defaut: array() )
   * @param type $wsReturn l'ELEMENT de l'OBJET à traiter (defaut: "" => correspondra à  $function . "Return"; )
   * @param type $arKey la valeur de l'objet qui fera office de clef dans le Tableau retourné (defaut: "")
   * @param type $arVal la valeur de l'objet qui fera office de valeur dans le Tableau retourné (defaut: "")
   * @param type $localFunc NOM de la FONCTION appellante (defaut: __FUNCTION__ == "_genericWSRequestToArray")
   * @param type $localFile NOM du FICHIER appellant (defaut: __FILE__ == "WSTools.php")
   * @param type $ExitOnError FORCER la SORTIE en cas d'erreurs (true/false  defaut:false)
   * @param type $debug Activer le premier niveau de DEBUG (true/false  defaut:false)
   * @param boolean $debug2 Activer le second niveau de DEBUG (true/false  defaut:false)
   * @return array
   */
  protected function _genericWSRequestToArray($WSDL = "", $function = "", $params = array(), $wsReturn = "", $arKey = "", $arVal = "", $localFunc = __FUNCTION__, $localFile = __FILE__, $ExitOnError = false, $debug = false) {
    $result = $this->_genericWSRequest($WSDL, $function, $params, $wsReturn, $localFunc . " <font color=gray>[<i> " . __FUNCTION__ . "( $arKey, " . print_r($arVal, true) . " ) </i>]</font>", $localFile, $ExitOnError, $debug);

    $this->deleteSpecialCasesValues($function, $result);
    if ($debug)
      $this->showDebugInfos($result, $localFunc . " <font color=navy> APR&Egrave;S filtrage => [<i>deleteSpecialCasesValues() </i>]</font>", __FILE__, false);

    if ($arKey != "")
      if ($arVal != "") {
        if (is_array($result)) {
          $arValues = $this->makeArray($result, $arKey, $arVal, true, $localFunc . " => " . __FUNCTION__, $debug);
        } else {
          try {
            $arValues[$result->$arKey] = $this->addMultipleValues($result, $arVal, false);
          } catch (\Exception $e) {
            $this->showDebugInfos($result, " <font color=red>Variable '$arKey' et/ou '$arVal' NON trouv&eacute; !!! </font></br><font color=gray>[<a target=_blank href=$WSDL>$WSDL</a>=>$function]</font>", __FILE__, false, true, true);
            unset($e);
          }
        }
        if ($debug)
        //$this->showDebugInfos($arValues, $localFunc . " <font color=gray>[<i> " . __FUNCTION__ . " </i>]</font> => R&eacute;sultats", __FILE__, false);
          $this->showDebugInfos($arValues, $localFunc . " <font color=gray>[<i> " . __FUNCTION__ . " </i>]</font> => <font color=green>R&Eacute;SULTAT FINAL</font>", __FILE__, false);
      }
      else
        $arValues = $result;
    return($arValues);
  }

  /**
   * Fonction générique d'intéroggation de WEB SERVICE (retour Array indexé avec $arKey/$arVal)
   * @param type $WSDL l'URL du fichier de définition wsdl du WS à intérroger [OBLIGATOIRE]
   * @param type $function la FONCTION ws à intérroger [OBLIGATOIRE]
   * @param type $params les PARAMÈTRES DU ws à intérroger (defaut: array() )
   * @param type $wsReturn l'ELEMENT de l'OBJET à traiter (defaut: "" => correspondra à  $function . "Return"; )
   * @param type $arKey la valeur de l'objet qui fera office de clef dans le Tableau retourné (defaut: "")
   * @param type $arVal la valeur de l'objet qui fera office de valeur dans le Tableau retourné (defaut: "")
   * @param type $localFunc NOM de la FONCTION appellante (defaut: __FUNCTION__ == "_genericWSRequestToArray")
   * @param type $localFile NOM du FICHIER appellant (defaut: __FILE__ == "WSTools.php")
   * @param type $ExitOnError FORCER la SORTIE en cas d'erreurs (true/false  defaut:false)
   * @param type $debug Activer le premier niveau de DEBUG (true/false  defaut:false)
   * @param boolean $debug2 Activer le second niveau de DEBUG (true/false  defaut:false)
   * @return array
   */
  protected function _genericWSRequestOneValue($WSDL = "", $function = "", $params = array(), $wsReturn = "", $onlyOneValue = "", $localFunc = __FUNCTION__, $localFile = __FILE__, $ExitOnError = false, $debug = false) {
    $resultOneValue = "";
    $result = $this->_genericWSRequest($WSDL, $function, $params, $wsReturn, $localFunc . " <font color=gray>[<i> " . __FUNCTION__ . " </i>]</font>", $localFile, $ExitOnError, $debug);

    $this->deleteSpecialCasesValues($function, $result);

    if ($onlyOneValue != "") {
      if (is_array($result)) {
        //$arValues = $this->makeArray($result, $arKey, $arVal, true, $localFunc . " => " . __FUNCTION__, $debug);
        $this->showDebugInfos($result, "<div style='background-color:#FEF1EC;border: 3px solid red;padding:25px;'><font color=red>Top de Valeurs de retour - La Variable '$onlyOneValue' NON trouv&eacute; !!! </font></br><font color=gray>[<a target=_blank href=$WSDL>$WSDL</a>=>$function]</font></div>", __FILE__, false, true, true);
      } else {
        try {
          $resultOneValue = $result->$onlyOneValue;
        } catch (\Exception $e) {
          $this->showDebugInfos($result, "<div style='background-color:#FEF1EC;border: 3px solid red;padding:25px;'><br><font color=red>FONCTION : $localFunc() => Variable '$onlyOneValue' NON trouv&eacute; !!! </font></br></br><font color=gray>[<a target=_blank href=$WSDL>$WSDL</a>=>$function]</font></div>", $localFile . " => " . __FILE__, false, true, true);
          unset($e);
        }
      }
      if ($debug)
      //$this->showDebugInfos($arValues, $localFunc . " <font color=gray>[<i> " . __FUNCTION__ . " </i>]</font> => R&eacute;sultats", __FILE__, false);
        $this->showDebugInfos($resultOneValue, $localFunc . " <font color=gray>[<i> " . __FUNCTION__ . " </i>]</font> => R&Eacute;SULTAT FINAL (onlyOneValue)", __FILE__, false);
    }
    else
      $resultOneValue = $result;
    return($resultOneValue);
  }

  /**
   * Fonction de suppression de CAS PARATICULIERS, Valeurs indésirables
   * @param type $result
   * @return type
   */
  private function deleteSpecialCasesValues($function, &$result) {
    // CAS PARTICULIER pour WS 
    if (is_array($result))
      switch ($function) {
        case "recupererQuotiteTravWS":
          if (isset($result[3]))
            unset($result[3]);
          break;
        /*
          [3] => stdClass Object
          (
          [codeQuotite] => 0
          [libQtr] => Sans objet
          [libQuotite] => Sans objet
          [temEnSveQtr] => O
          [temMinQtr] => O
          )
         */
        case "recupererPays":
          if (isset($result[63]))
            unset($result[63]);
          break;
        /* 2 Doublons "INCONNUE" 999 + 990
          on supprime
          [63] => stdClass Object
          (
          [codePay] => 999
          [libNat] => INCONNUE
          [libPay] => ETRANGER
          [temEnSvePay] => O
          )
          on conserve celle-ci :
          [13] => stdClass Object
          (
          [codePay] => 990
          [libNat] => INCONNUE
          [libPay] => AUTRES PAYS
          [temEnSvePay] => O
          )
         */

        case "recupererProfilEtudiantWS":
          if (isset($result[22]))
            unset($result[22]);
          if (isset($result[23]))
            unset($result[23]);
          break;
        /* GeographieMetier.wsdl=>recupererProfilEtudiantWSReturn
         * On enlève les 22 et 23ième valeurs...
          [22] => stdClass Object
          (
          [code] => RD
          [libPru] => NE PLUS UTILISER
          [libelle] => NE PLUS UTILISER
          [temEnSvePru] => O
          [temMinPru] => N
          )

          [23] => stdClass Object
          (
          [code] => RN
          [libPru] => NE PLUS UTILISER
          [libelle] => NE PLUS UTILISER
          [temEnSvePru] => O
          [temMinPru] => N
          )
         */

        case "recupererDepartement":
          if (isset($result[0]))
            unset($result[0]);
          break;
        /* GeographieMetier.wsdl=>recupererDepartement
         * On enlève la première valeur...
          [0] => stdClass Object
          (
          [codeAca] => 34
          [codeDept] => 000
          [libAca] => INDETERMINE
          [libDept] => ARMEES
          [temEnSveDept] => O
          )
         */
      }
  }

  private function addMultipleValues($item, $value, $debug = false) {
    $itemVal = "";
    if ($debug) {
      $this->showDebugInfos($item, __FUNCTION__, __FILE__);
      $this->showDebugInfos($value, __FUNCTION__, __FILE__);
    }
    if (is_array($value)) {
      $itemVal = "";
      $nb = 0;
      foreach ($value as $OneValue) {
        if ($nb == 1)
          $itemVal .=" [";
        if ($nb > 1)
          $itemVal .= (($itemVal != "") ? " - " : "");
        $itemVal .= $item->$OneValue;
        $nb++;
      }
      $itemVal .="]";
    }
    else
      $itemVal = $item->$value;

    if ($debug)
      $this->showDebugInfos($itemVal, __FUNCTION__ . " RETOUR", __FILE__);

    return($itemVal);
  }

  /**
   * Pacours un Objet donné, et remplit un tableau de résultat indexé avec $key & $value (deux éléménts de l'objet parcourus)
   * <br>[<i>Gestion d'erreur si $key et/ou $value n'existe pas...</i>]
   * @param obj $Object l'objet àparcourir
   * @param string $key le nom de la varaible qui sera l'index du tableau  (doit faire partie de l'objet $Object)
   * @param string $value le nom de la varaible qui sera la valeur contenu du tableau  (doit faire partie de l'objet $Object)
   * @param bool $sort appliquer un classement alphanumérique (true/false  defaut:false)
   * @param string $localFunc le NOM de LA FONCTION appellante
   * @param bool $debug Afficher le mode dédug (true/false  defaut:false)
   * @return array
   */
  protected function makeArray($Object, $key, $value, $sort = false, $localFunc = "", $debug = false) {
    $arDatas = array();
    if ($localFunc != "")
      $localFunc = "<font color=gray>[<i> " . $localFunc . " </i>]</font> ";
    try {
      foreach ($Object as $item) {
        $arDatas[$item->$key] = $this->addMultipleValues($item, $value, false);
      }
      if ($sort)
        asort($arDatas);
      if ($debug)
        $this->showDebugInfos($arDatas, $localFunc . " <font color=navy> APR&Egrave;S  => " . __FUNCTION__ . "</font>");
      return($arDatas);
    } catch (\Exception $e) {
      $this->showDebugInfos($Object, "<div style='background-color:#FEF1EC;border: 3px solid red;padding:25px;'>makeArray Variable $key et/ou $value NON trouv&eacute; !!! </div>", __FILE__, false, true, true);
    }
  }

  protected function _no_used_my_array_multisort(&$entries, $attribs, $startIDX = 0) {
    $i = $startIDX;
    for ($i; $i < count($entries); $i++) {
      $index = $entries[$i];
      $j = $i;
      do {
        //create comparison variables from attributes:
        $a = $b = null;
        foreach ($attribs as $attrib) {
//            if($j - 1>$startIDX)
          //try {
          $a .= strtolower($entries[$j - 1][$attrib][0]);
          $b .= strtolower($index[$attrib][0]);
          if (strlen($a) > strlen($b))
            $b .=str_repeat(" ", (strlen($a) - strlen($b)));
          if (strlen($b) > strlen($a))
            $a .=str_repeat(" ", (strlen($b) - strlen($a)));
        }
//            Catch(\Exception $e)
//            {
//              unset($e);
//            }
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

  protected function walkSearchArray($array, $keyToFind, $keyValue = "", $debug = false) {
    $arArray = array();
    $i;
    static $enter;
    $enter++;
    $bFound = false;
    if ($debug)
      echo "<hr><p><b>walkSearchArray(array,'$keyToFind','$keyValue','" . ($debug ? 'true' : 'false') . "') #$enter</b>";

    //if(is_object($array))
    foreach ($array as $key => $value) {
      //if(strcmp($key,$keyToFind)==0)
      if ($key == $keyToFind) {
        if ($debug)
          echo "<br><font color=red><b>VALEUR TROUVÉ !!!!</b></font>";

        if ($keyValue == "")
          $arArray[$i++] = utf8_decode($value); //		




























          
//else  $arArray[$i++]=$value[$keyValue];			
        //if($debug) echo "<br><b>arArray</b>values =".$array[$key]." <PRE>".print_r($arArray,true)."</PRE>";
      }

      if (is_object($value)) {
        if ($debug)
          echo "<br><b>key $key obj :</b>value = <PRE>" . print_r($value, true) . "</PRE>";


        $arArray2 = walkSearchArray($value, $keyToFind, $keyValue, $debug);

        if ($debug)
          echo "<hr>arArray2<PRE>" . print_r($arArray2, true) . "</PRE>";

        //if($arArray2!="") $arArray[$i++]=$arArray2;

        foreach ($arArray2 as $key2 => $value2)
          if ($key2 === $keyToFind)
            $arArray[$i++] = utf8_decode($value2);
      }
    }
    if (count($arArray) == 1)
      return($arArray[0]);
    else
    if (($keyValue != "") && ($arArray[$keyValue] != ""))
      return(utf8_decode($arArray[$keyValue]));
    else {
      if (count($arArray) == 0)
        return("");
      else
        return($arArray);
    }
  }

  protected function walkSearch_array2($array, $keyToFind, $keyValue = "", $debug = false) {
    $arArray = array();
    $i = 0;
    if ($debug)
      echo "<br><b>keyToFind</b>=$keyToFind";

    foreach ($array as $key => $value) { //if($debug) echo "<br><b>key</b>=$key";
      if ($key === $keyToFind) {
        $arArray[$i++] = utf8_decode($value);
        if ($debug)
          echo "<br><font color=red><b>VALEUR TROUVÉ !!!!</b></font>=$key=>$value";
      }
      $arArray2 = $this->walkSearch_array2($value, $keyToFind, $keyValue, $debug);
      if ($debug)
        echo "<hr>arArray2<PRE>" . print_r($arArray2, true) . "</PRE>";

      if (is_array($arArray2)) {
        foreach ($arArray2 as $key2 => $value2)
          $arArray[$i++] = $value2;
      } else if ($arArray2 != "")
        $arArray[$i++] = $arArray2;
    }
    if (count($arArray) == 1)
      return($arArray[0]);
    else
    if (($keyValue != "") && ($arArray[$keyValue] != ""))
      return(utf8_decode($arArray[$keyValue]));
    else {
      if (count($arArray) == 0)
        return("");
      else
        return($arArray);
    }
  }

  protected function walkSearch_array3($array, $keyToFind, $keyValue = "", $debug = false) {
    $arArray = array();
    $i = 0;
    static $a;
    $a++;

    if ($debug)
      echo "<br><b>enter</b>=" . $a;

    //if(is_object($array))
    foreach ($array as $key => $value) {
      if ($key === $keyToFind) {
        $arArray[$i++] = utf8_decode($value);
//				if($debug) echo "<br><font color=red><b>VALEUR TROUVÉ !!!!</b></font>=$key=>$value";
      }

      if (is_object($value)) {
        if ($debug)
          echo "<br><b>key $key obj :</b>value = <PRE>" . print_r($value, true) . "</PRE>";

        $arArray2 = $this->walkSearch_array3($value, $keyToFind, $keyValue, $debug);
        if ($debug)
          echo "<hr>arArray2<PRE>" . print_r($arArray2, true) . "</PRE>";

        //if($arArray2!="") $arArray[$i++]=$arArray2;

        foreach ($arArray2 as $key2 => $value2)
          if ($key2 === $keyToFind)
            $arArray[$i++] = utf8_decode($value2);
      }
    }
    if (count($arArray) == 1)
      return($arArray[0]);
    else
    if (($keyValue != "") && ($arArray[$keyValue] != ""))
      return(utf8_decode($arArray[$keyValue]));
    else {
      if (count($arArray) == 0)
        return("");
      else
        return($arArray);
    }
  }

  /**
   * Affiche des informations de debug formaté
   * @param obj $infos variable ou informations formaté à afficher
   * @param string $titre Le titre de la rubrique affiché (defaut: "")
   * @param string $file le Fichier où l'erreur c'est déclaré (defaut: __FILE__)
   * @param bool $FormatedInfos (true:false defaut=false) Affecter TRUE Si $infos est une information déjà formaté (donc inhibé l'encadrement <pre>".print_r(...,true)."</pre>
   * @param bool $error Formatté la fenêtre d'informatoion comme une ERREUR  (true:false defaut=false) 
   * @param bool $forceExit FORCER la SORTIE du PROGRAMME  (true:false defaut=false) 
   */
  protected function showDebugInfos($infos, $titre = "", $file = __FILE__, $FormatedInfos = false, $error = false, $forceExit = false) {
    $id = "_dbginfos_" . rand();
    $style = "'color:orange;'";
    $tilte = "DEBUG INFOS";
    if ($error) {
      $style = "'color:red;background-color:yellow;'";
      $tilte = "ERREUR CRITIQUE";
    }
    
    $NewInfos= "";
    //clear:both;clip: auto;
    $NewInfos .= "<div class='dbginfos' style='padding-left:25px;padding-right:25px;display:block;' id='$id'>";
    $NewInfos .= "<div class=accordion_debug >"; //style='padding:25px;'
      $NewInfos .= "<h3 style='padding-left:25px;padding-right:25px;' ><a href='#'><b><span style=$style>$tilte</span> - </b><font color=blue>$titre</font></a></h3>";
        $NewInfos .= "<div style='display:block;'>";
        $NewInfos .= "<span class='ui-button ui-icon ui-icon-trash ui-widget-content' title='Effacer ce cadre de debug' onclick=$('#" . $id . "').remove();></span>";
        $NewInfos .= "<br>FICHIER : <font color=blue>" . $file . "</font>";
        $NewInfos .= "<br>CLASSE : <font color=blue>" . get_called_class() . "</font>";
        $NewInfos .= "<hr>";
        $NewInfos .= (($FormatedInfos) ? "$infos" : "<pre>" . print_r($infos, true) . "</pre>");
        $NewInfos .= "</div>";
      $NewInfos .= "</div>";
    $NewInfos .= "</div>";
    $this->DebugLogger->addInfo($NewInfos);
    //echo $NewInfos;
    //$this->DebugLogger->addInfo( print_r($infos, true));
    if ($forceExit)
      exit();
  }

  protected function showDebugResultWS($result, $params = "", $fctName = "?", $file = __FILE__, $wsFunction = "") {
    $id = "_dbgWSinfos_" . rand();
        
    $NewInfos="";
    $NewInfos .= "<div class='dbginfos' style='padding-left:25px;padding-right:25px;' id='$id'>";
    $NewInfos .= "<div class='accordion_debug' >"; //  style='padding:25px;'  ui-state-error ui-corner-all
    $NewInfos .= "<h3 style='padding-left:25px;padding-right:25px;' ><a href='#'><b><font color=red>WS DEBUG</font></b> <font color=blue>$fctName</font> <font color=red>(<i>donn&eacute;es brutes...</i>)</font></a></h3>";
    $NewInfos .= "<div>";
    $NewInfos .= "<span class='ui-button ui-icon ui-icon-trash ui-widget-content' title='Effacer ce cadre de debug' onclick=$('#" . $id . "').remove();></span>";
    $NewInfos .= "</br>FICHIER : <font color=blue>" . $file . "</font>";
    $NewInfos .= "</br>CLASSE : <font color=blue>" . get_called_class() . "</font>";
    $NewInfos .= "</br>fonction : <font color=blue>" . $fctName . '</font>';
    $NewInfos .= "</br>index de retour: <font color=blue>" . $wsFunction . '</font>';
    $NewInfos .= "<hr>";
    $NewInfos .= "</br><b>Param&egrave;tre d'entr&eacute;es: </b><pre><font color=red>" . print_r($params, true) . "</pre></font>";
    $NewInfos .= "<hr><b>Donn&eacute;es brutes de retour: </b><pre style=color:blue;>" . print_r($result, true) . "</pre><br><hr>";
    if ($wsFunction != "") {
      if (property_exists($result, $wsFunction) !== false)
        $NewInfos .= "<hr><b>Donn&eacute;es de retour: </b><pre style=color:green;>" . print_r($result->$wsFunction, true) . "</pre><br><hr>";
      else
        $this->showDebugInfos($result, "WS - variable de retour '$wsFunction' in&eacute;xistante !!!", $file, false, true);
    }
    $NewInfos .= "</div>";
    $NewInfos .= "</div>";
    $NewInfos .= "</div>";
    //echo $NewInfos;
    
    $this->DebugLogger->addInfo($NewInfos);

  }

  private function showSOAP_Error(\SoapFault $fault, $fromFUNC = "", $fromFILE = "", $forceExit = false) {
    $offline = false;
    $arTraces = $fault->getTrace();
    $NewInfos ="";   
    $NewInfos .=   "<div style='padding:25px;' >";
    $NewInfos .=   "<div style='" . (($forceExit) ? "background-color:#FEF1EC;" : "") . "padding:25px;' class='ui-corner-all ui-state-error' >";
    $NewInfos .=   "</br><font color=red><b>ERREUR/EXCEPTION</b></font>";
    if ($forceExit)
      $NewInfos .=   "</br>MODE DE SORTIE : <font color=red><b>FORC&Eacute;</b></font><font color=gray> (forceExit=" . $forceExit . ")</font>";
    $NewInfos .=   "</br>FICHIER : <font color=blue>" . $fromFILE . "</font>";
    $NewInfos .=   "</br>CLASSE : <font color=blue>" . get_called_class() . "</font>";
    $NewInfos .=   "</br>fonction : <font color=blue>" . $fromFUNC . '()</font>';
    $error = $fault->getMessage();
    unset($fault);
    $Msg = "";
    switch ($error) {
      case WS_OFFLINE:
        $Msg = ERROR_MSG_OFFLINE; //"Web Service non disponible";
        $offline = true;
        break;
      case WS_NO_USER: // HARPEGE 
        $Msg = "Utilisateur non existant";
      case WS_NO_DATA:
        $Msg = "Pas de données existantes";
        break;
      case WS_NO_HARPID:
        $Msg = "Numéro d'individu non trouvé !!!";
        break;
      case WS_NO_ETU:  // APOGEE
        $Msg = "Etudiant non existant";
        break;
    }
    $NewInfos .=   "</br>Message d'erreur : <font color=red><b>" . $error . '</b></font>';
    $NewInfos .=   "</br>Message traduit : <font color=orange><b>" . htmlentities($Msg) . '</b></font>';
    foreach (array('Dernier éventement avant l\'erreur' => '0', 'Avant dernier éventement avant l\'erreur' => '1') as $lbl => $iTrace)
      $NewInfos .=   "</br><font color=red><hr><b>Pile d'appel (trace) </b> </br>" . htmlentities($lbl) . "<pre>" . print_r($arTraces[$iTrace], true) . "</pre></font>";
    $NewInfos .=   "<hr>";
//    if (strpos($fault->faultstring, WS_NO_DATA) === FALSE) { // on ignore les retours sans données
//      if ($fault->detail->fault->code == WS_NO_HARPID) { // on ignore les intérogation avec harpid invalide // affiche msg err ssi mode Debug (debug=true)
////        if ($_GET['debug'] == 'true') {
////          echo "<values>Numéro d'individu non trouvé !!!</values>";
////          exit();
////        }
//      } else {
////        if (($fault->message == "technical.data.transaction.connexion") || (stristr($fault->faultstring, "Cannot open connection") != ""))
////          $offline = true;
////        else {
////          echo $fault->faultcode;
////          echo "<hr>";
////          echo "<font color=red><B>ERROR: <I> " . $fault->faultcode . "-" . $fault->faultstring . "</I></B><br><pre>" . print_r($fault, true) . "</pre></font>";
////          echo "<hr>";
//////          echo "<b>Liste des Fonctions membre définies dans le WSDL :</b><font color=green><pre>" . print_r($client->__getFunctions(), true) . "</pre></font>";
//////          echo "<hr>";
//////          echo "<br><b>Liste des Types définis dans le WSDL :</b><p><font color=orange>";
//////          foreach ($client->__getTypes() as $TypeDef)
//////            echo "><pre>" . print_r($TypeDef, true) . "</pre>";
////          echo "<p></font>";
////          echo "<hr>";
////        }
//      }
//      if ($offline) {
//        echo $this->ERROR_MSG_OFFLINE;
//      }
//    }
    $NewInfos .=   "</div>";
    $NewInfos .=   "</div>";
    
    $this->DebugLogger->addInfo($NewInfos);

    if ($forceExit)    
      exit();
  }

  protected function WS_getInfos($WSDL, $method, $params, $debug = false) {
    $soapClient = new \SoapClient($WSDL);
    try {
      $result = $soapClient->__soapCall($method, $params);
      if ($debug)
        $this->showDebugResult($return, $params);

      //	echo "<B>Result :</B><PRE>".utf8_decode(print_r($result,true))."</PRE>";
    } catch (SoapFault $fault) {

      $this->showSOAP_Error($soapClient, $fault);
      $NewInfos =  "";
      $NewInfos .=   "<br><B>Liste des Fonctions membre défini dans le WSDL :<font color=green></B>";
      foreach ($soapClient->__getFunctions() as $oneFonction)
        $NewInfos .=   "<br><PRE>" . print_r($oneFonction, true) . "</PRE></font>";
      
      $this->DebugLogger->addInfo($NewInfos);

    }
    unset($soapClient);
  }

  /**
   *  Fonction de normalisation des numéros de téléphones, fax...
   *  enlève les espace, tiret et point
   * en gardant le + (internationalisation)
   * @param string $numTel
   * @return string
   */
  protected function wsNormalizeTel($numTel) {
    return(preg_replace('/[^0-9+]/', '', $numTel));
  }

  /**
   * Convertis une date issue des WebServices en date d/m/Y ou d/m/Y H:i:s (si $hms=true)
   * @param string $date date originale issue du WS
   * @param bool $hms OPTION : ajouter l'affichage des " Heures:minutes:secondes (true/false defaut=false)
   * @param bool $hms OPTION : affichage HTML " Heures:minutes:secondes (true/false defaut=false)
   * @param bool $debug Afficher le mode Débug.. (true/false defaut=false)
   * @return string chaine date reformaté
   */
  public function wsDateToVisuDate($date, $hms = false, $html = false, $debug = false) {
    $cnvDate = $date;
    if ($date != '') {
      $date_ar = preg_split('/[-T:\.Z]/', $date);
      if ($hms)
        $cnvDate = date("d/m/Y H:i:s", mktime($date_ar[3], $date_ar[4], $date_ar[5], $date_ar[1], $date_ar[2], $date_ar[0]));
      else
        $cnvDate = date("d/m/Y", mktime(0, 0, 0, $date_ar[1], $date_ar[2], $date_ar[0]));
      if ($debug) {
        $debugInfos = "";
        $debugInfos .= "<hr><B>wsDateToVisuDate::Debug =></B><br>";
        $debugInfos .= "<br>Valeur de la date en entrée : " . $date;
        $debugInfos .= "<br>Tableau décortique : <PRE>" . print_r($date_ar, true) . "</PRE>";
        $debugInfos .= "<br>Valeur de la date en sortie : " . $cnvDate;
        $debugInfos .= "<hr>";

        $this->showDebugInfos($debugInfos, "wsDateToVisuDate($date,$hms,$html,$debug)", "", true);
      }
    }

    if ($html)
      return( str_replace("/", "&#47;", str_replace("%", "&#37;", $cnvDate)));
    else
      return($cnvDate);
  }

  protected function enleveaccents($string)
  {
    $string=strtr($string,"äâàéèêëïîîîôùûüç","aaaeeeeiiiiouuuc");
    $string=strtr($string,"ÀÁÂÃÄÈÉÊËÌÍÎÏÒÓÔÖÙÚÛÜÇ","AAAAAEEEEIIIIOOOOUUUUC");
    return $string;
  }	
  
    /**
   * Convertion de format de date issue du ldap YYYYMMDDHMMSSZ (20120622000000Z ) vers le format date/heure d/m/Y ou d/m/Y H:i:s (si $hms=true)
   * @param string $date [OBLIGATOIRE] la date originale "d/m/Y" ou "d/m/Y H:i:s"
   * @param bool $hms [OPTION] : ajouter l'affichage des " Heures:minutes:secondes (true/false defaut=false)
   * @param bool $debug Afficher le mode Débug.. (true/false defaut=false)
   * @return string chaine date reformatée pour l'intérrogations de Web Services 
   */
   public function ldapDateToVisuDate($date = "", $hms = false, $debug = false) {
    if ($date == "")
      $date = date("YmdHis");
    $cnvDate = $date;

    if ($hms)
      $cnvDate = preg_replace('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})Z/', '$3/$2/$1 $4:$5:$6', $date);
    else
      $cnvDate = preg_replace('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})Z/', '$3/$2/$1', $date);

    if ($debug) {
      $debugInfos = "";
      $debugInfos .= "<hr><B>".__FUNCTION__."::Debug =></B><br>";
      $debugInfos .= "<br>Valeur de la date en entr&eacute;e : " . $date;
      $debugInfos .= "<br>Valeur de la date en sortie : " . $cnvDate;
      $debugInfos .= "<hr>";
      $this->showDebugInfos($debugInfos, "ldapDateToVisuDate($date,$hms,$debug)", "", true);
    }
    return($cnvDate);
  }
  
  /**
   * Convertion de format de date d'un timestamp (1224505210) vers le format date/heure d/m/Y ou d/m/Y H:i:s (si $hms=true)
   * @param string $date [OBLIGATOIRE] la date originale "d/m/Y" ou "d/m/Y H:i:s"
   * @param bool $hms [OPTION] : ajouter l'affichage des " Heures:minutes:secondes (true/false defaut=false)
   * @param bool $debug Afficher le mode Débug.. (true/false defaut=false)
   * @return string chaine date reformatée pour l'intérrogations de Web Services 
   */
  public function timestampToVisuDate($timestamp = "", $hms = false, $debug = false) {
   if($timestamp=="")
     $timestamp=time();
   
    if ($date == "") {
      if ($hms)
        $date = date("d/m/Y H:i:s",$timestamp);
      else
        $date = date("d/m/Y",$timestamp);
    }
    
    if ($debug) {
      $debugInfos = "";
      $debugInfos .= "<hr><B>".__FUNCTION__."::Debug =></B><br>";
      $debugInfos .= "<br>Valeur de la date en entr&eacute;e : " . $date;
      $debugInfos .= "<br>Valeur de la date en sortie : " . $cnvDate;
      $debugInfos .= "<hr>";
      $this->showDebugInfos($debugInfos, "ldapDateToVisuDate($date,$hms,$debug)", "", true);
    }
    return($cnvDate);
  }

  /**
   * Convertion de format de date vers le format date/heure des Web Services 
   *  d/m/Y ou d/m/Y H:i:s (si $hms=true) =>  XML Date/Time "YYYY-MMDDThh:mm:ss.nnnZ"
   * @param string $date [OBLIGATOIRE] la date originale "d/m/Y" ou "d/m/Y H:i:s"
   * @param bool $hms [OPTION] : ajouter l'affichage des " Heures:minutes:secondes (true/false defaut=false)
   * @param bool $debug Afficher le mode Débug.. (true/false defaut=false)
   * @return string chaine date reformatée pour l'intérrogations de Web Services 
   */
  public function wsDateToWSDate($date = "", $hms = false, $debug = false) {
    if ($date == "") {
      if ($hms)
        $date = date("d/m/Y H:i:s");
      else
        $date = date("d/m/Y");
    }
    $cnvDate = $date;
    if ($date != '') {
      $date_ar = preg_split('/[\/:\.]/', $date);
      if ($hms) {
        $cnvDate = date("c", mktime($date_ar[3], $date_ar[4], $date_ar[5], $date_ar[1], $date_ar[0], $date_ar[2]));
      } else {
        $cnvDate = date("c", mktime(0, 0, 0, $date_ar[1], $date_ar[0], $date_ar[2]));
      }
      if ($debug) {
        $debugInfos = "";
        $debugInfos .= "<hr><B>".__FUNCTION__."::Debug =></B><br>";
        $debugInfos .= "<br>Valeur de la date en entr&eacute;e : " . $date;
        $debugInfos .= "<br>Tableau d&eacute;cortique : <PRE>" . print_r($date_ar, true) . "</PRE>";
        $debugInfos .= "<br>Valeur de la date en sortie : " . $cnvDate;
        $debugInfos .= "<hr>";
        $this->showDebugInfos($debugInfos, "wsDateToWSDate($date,$hms,$debug)", "", true);
      }
    }
    return($cnvDate);
  }

}

?>
