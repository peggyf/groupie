<?php

namespace Amu\CliGrouperBundle\Service;

/**
 * Description of String
 *
 * @author Michel UBÉDA <michel.ubeda@univ-amu.fr>
 */
class String {

  /**
   * retourne une date formaté sous la forme suivante lundi 1ier janvier 2014
   * @param \DateTime $date
   * @return type
   */
  public function formatFullDateTexte(\DateTime $date){
    
    $arDays=array(0=>"dimanche",1=>"lundi",2=>"mardi",3=>"mecredi",4=>"jeudi",5=>"vendredi",6=>"samedi");
    $arMonth=array(1=>'janvier',2=>"février",3=>"mars",4=>"avril",5=>"mai",6=>"juin",7=>"juillet",8=>"aout",9=>"septembre",10=>"octobre",11=>"novembre",12=>"décembre");
    
    $dayName=$arDays[ $date->format('w')];
    $monthName=$arMonth[$date->format('n')];
    $day=$date->format("j");
    
    return ($dayName." ".$day.(($day==1)?"ier":"")." $monthName ".$date->format("Y"));
    
  }
  
  /**
   * Conversion de couleur au format Hexadécimal court(3) => format Hexadécimal normal(6) ("#123" => "#112233")
   * @param string $color
   * @return string
   */
  public function ColorHex3ToHex6($color) {
    if (strlen($color) == 4) {
      $arCol = str_split($color);
      $color = $arCol[0] . str_repeat($arCol[1], 2) . str_repeat($arCol[2], 2) . str_repeat($arCol[3], 2);
    }
    return($color);
  }

  /**
   * Remplace TOUS les caractères accentués par leur homologue sans accents, et
   * remplace par un [espace] les caractères spéciaux des noms composé tels que : ".-'"
   * @param string $string la chaine de carcatère à convertir
   * @param bool $utf8decode réaliser le décodage utf8 avant la convertion (si donnée issu de ORACLE...)
   * @return string
   */
  public function normEnleveAccents($string, $utf8decode = false, $utf8encode = false) {
    if ($utf8decode){
      $string = utf8_decode($string);
    }      
    if ($utf8encode){
      $string = utf8_encode($string);
    } 
    
    $arReplacer=array(

      "ä"=>"a", "à"=>"a", "à"=>"a",   
      "À"=>"A", "Á"=>"A", "Â"=>"A", "Ã"=>"A", "Ä"=>"A",

      "é"=>"e", "è"=>"e", "ê"=>"e", "ë"=>"e",
      "È"=>"E", "É"=>"E", "Ê"=>"E", "Ë"=>"E",

      "ï"=>"i", "î"=>"i",
      "Ì"=>"I", "Í"=>"I", "Î"=>"I", "Ï"=>"I",

      "ô"=>"o", "ö"=>"o", 
      "Ò"=>"O", "Ó"=>"O", "Ô"=>"O", "Ö"=>"O",

      "ù"=>"u", "û"=>"u", "ü"=>"u", 
      "Ù"=>"U", "Ú"=>"U", "Û"=>"U", "Ü"=>"U",

      "ç"=>"c",
      "Ç"=>"C",

      //" "=>"",
      "."=>"", "-"=>"", "'"=>"",

      );
    
    $string = strtr($string, $arReplacer);  
    
//    $string = strtr($string, "äâàéèêëïîîîôùûüç.-'", "aaaeeeeiiiiouuuc   "); NON FONCTIONNEL !!!
//    $string = strtr($string, "ÀÁÂÃÄÈÉÊËÌÍÎÏÒÓÔÖÙÚÛÜÇ", "AAAAAEEEEIIIIOOOOUUUUC"); NON FONCTIONNEL !!!
    
    return $string;
  }
  
  /**
   * normNameUpperNoSpace Normalise la chaine de carctrère en CAPITAL sans les carctères accentués, spéciaux, espaces...
   * @param string $source
   * @return string
   */
  public function normNameUpperNoSpace($source) {
    return preg_replace('/[^A-Z]/', '', strtoupper($this->normEnleveAccents($source)));
  }

  /**
   * Suggère un Login du type pname (initials du prénom + name) tous en minuscule
   * @param string $name
   * @param string $surname
   * @return string
   */
  public function suggestLogin($name,$surname) {
    $initialSurname="";
    if(strlen($surname)>0){
      $initialSurname= substr($surname,0,1);
      if( (strpos($surname,'-')!==false) || (strpos($surname,'-')!==false) ){
         $arSurname= preg_split("/[, -]/",$surname);
         $initialSurname="";
         foreach ($arSurname as $onePart) {
          $initialSurname.= substr($onePart,0,1);
         }
      }
    }
    //
    return preg_replace('/[^a-z]/', '', strtolower($this->normEnleveAccents( $initialSurname . $name)));
    //
  }
  
    /**
   * Suggère un mots de passe aléatoire en fonction du crypt (MD5 login)
   * @param string $login Login 
   * @param integer $len longueur du password à renvoyer
   * @return string
   */
  public function suggestPassword($login,$len=12) {
    $salt=md5($login);
    $newPass=crypt($login, $salt);
    return substr($newPass,0,($len-1));    
  }

  
  /**
   * Normalisation/ reformatage de numéro de télephone / Fax
   * @param string $num [OBLIGATOIRE] le numéro à formatter
   * @param bool $formatInternational format International +33 0.xx.xx.xx.xx ou 0x.xx.xx.xx.xx (par defaut: false)
   * @param striung $sep (séparateur de chiffre (par défaut = ".")
   * @return type
   */
  public function NormalizeTel($num, $formatInternational = false, $sep = ".") {
    $num = preg_replace('/\D/', '', $num);
    if ($formatInternational == true) {
      $prefix = preg_replace("/(\d{2})((\d{6}))/", "$1", $num);
      if (strpos($prefix, "0") !== false)
        $num = preg_replace("/(\d{1})(\d{2})(\d{2})(\d{2})/", '33$1' . $sep . '$2' . $sep . '$3' . $sep . '$4', $num); // 0x.xx.xx.xx.xx
      else
        preg_replace("/(\d{2}) (\d{1})(\d{2})(\d{2})(\d{2})/", '+$1' . $sep . '$2' . $sep . '$3' . $sep . '$4' . $sep . '$5', $num);  // +33 4.xx.xx.xx.xx
    }else {
      $prefix = preg_replace("/(\d{2})((\d{6}))/", "$1", $num);
      if (strpos($prefix, "33") !== false)
        $num = preg_replace("/(\d{2})(\d{1})(\d{2})(\d{2})(\d{2})/", '0$2' . $sep . '$3' . $sep . '$4' . $sep . '$5' . $sep . '$6', $num); // 1988-08-01
      else
        preg_replace("/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/", '$1' . $sep . '$2' . $sep . '$3' . $sep . '$4' . $sep . '$5', $num); // 1988-08-01
    }

    //$num = vsprintf("%02d.%02d.%02d.%02d.%02d", str_split($num,2)); // 1988-08-01
    return($num);
  }

  function normAddress($Address) {
    $Address = preg_replace("/[\t\n\r\f]/", "", $Address);
    //$Address=preg_replace("/((\s){1,})/"," ",$Address);
    $Address = str_replace("$", " ", $Address);
    //$Address=preg_replace("/\$\s/"," ",$Address);

    $Address = htmlentities($Address);
    $Address = str_replace('&nbsp;', ' ', $Address);

    $Address = trim($Address);
    if ($Address == "nc")
      $Address = "";

    if ($Address != "") {
      $Address = preg_replace("/campus /i", "</b><br>$0", $Address);
      $Address = str_replace(" - ", "<br> ", $Address);
      $Address = preg_replace("/(\d){5,10} /", "<br>$0", $Address);
      //$Address=preg_replace("/( (\d){1,3} (av|rue|boul|bld))/i","<br>$0",$Address);
      $Address = preg_replace("/([1-9].*)((av|rue|boul|bld|bd| - ).*)/i", "</b><br>$0", $Address);
      $Address = preg_replace("/ (\D){1,3} (av|rue|boul|bld| \- ).*/i", "</b><br>$0", $Address);
      if (strstr($Address, '</b>') != "")
        $Address = "<b>" . $Address;
      $Address = trim($Address);
      if (substr($Address, -3, 3) == "100") {
        $Address = substr($Address, 0, -3);
        $Address .="</br> FRANCE";
      }
    }
    return($Address);
  }

  function normNumPhoneFaxHTML($numOrig, $showOnlyFirst = false, $HTML_sep = "&#46;", $HTML_crlf = "<br>Autre :&nbsp;") {
    $numOk = $numOrig;

//	if($isDOSI)	$numOk=preg_replace('/^[0-9+]/','',$numOk);
    $isDOSI = ($_SESSION['phpCAS']['user'] == "u3.mubeda");

    $numOk = preg_replace('/[^0-9+]/', '', $numOk);
    $numOk = str_replace('+33', "0", $numOk);
    $numOk = trim($numOk);

    $numOk = preg_replace('/(\d{2})/', "$0" . $HTML_sep, $numOk);
    $lenMaxOneTel = 10 + 4 * strlen($HTML_sep);

    if ($showOnlyFirst) {
      $numMod = $numOk;
      if ($numMod != "")
        $numOk = substr($numMod, 0, $lenMaxOneTel);
      if (strlen($numMod) > ($lenMaxOneTel + strlen($HTML_sep)))
        $numOk.="...";
    }
    else {
      if (strlen($numOk) > $lenMaxOneTel) {
        $finalNumOK = "";
        while (strlen($numOk) > $lenMaxOneTel) {
          $finalNumOK .=substr($numOk, 0, $lenMaxOneTel) . $HTML_crlf;
          $numOk = substr($numOk, $lenMaxOneTel + strlen($HTML_sep));
        }
        $numOk = $finalNumOK;
        $numOk = substr($numOk, 0, strlen($numOk) - strlen($HTML_crlf));
      }
    }

    return($numOk);
  }

  function normNumPhoneFax($numOrig, $showOnlyFirst = false, $HTML_sep = ".", $HTML_crlf = " - ") {
    $numOk = $numOrig;

//	if($isDOSI)	$numOk=preg_replace('/^[0-9+]/','',$numOk);
    $isDOSI = ($_SESSION['phpCAS']['user'] == "u3.mubeda");

    $numOk = preg_replace('/[^0-9+]/', '', $numOk);
    $numOk = str_replace('+33', "0", $numOk);
    $numOk = trim($numOk);

    $numOk = preg_replace('/(\d{2})/', "$0" . $HTML_sep, $numOk);
    $lenMaxOneTel = 10 + 4 * strlen($HTML_sep);

    if ($showOnlyFirst) {
      $numMod = $numOk;
      if ($numMod != "")
        $numOk = substr($numMod, 0, $lenMaxOneTel);
      if (strlen($numMod) > ($lenMaxOneTel + strlen($HTML_sep)))
        $numOk.="...";
    }
    else {
      if (strlen($numOk) > $lenMaxOneTel) {
        $finalNumOK = "";
        while (strlen($numOk) > $lenMaxOneTel) {
          $finalNumOK .=substr($numOk, 0, $lenMaxOneTel) . $HTML_crlf;
          $numOk = substr($numOk, $lenMaxOneTel + strlen($HTML_sep));
        }
        $numOk = $finalNumOK;
        $numOk = substr($numOk, 0, strlen($numOk) - strlen($HTML_crlf));
      }
    }

    return($numOk);
  }

  function normSurname($Prenom, $utf8decode = true) {
    if ($utf8decode)
      $Prenom = utf8_decode($Prenom);
    $Prenom = ucfirst(mb_strtolower($Prenom));
//	$Prenom=preg_replace("/([a-z]+)/e", "ucwords(strtolower('$1'))", $Prenom);
    $Prenom = htmlentities($Prenom);
    return ($Prenom);
  }

  function normName($name) {
    $nameOK = ucfirst(mb_strtolower(utf8_decode($name)));
    //$Prenom=preg_replace("/(\w+)/e", "ucwords(strtolower('$1'))", $Prenom);
    $nameOK = preg_replace("/([a-zA-Zäëéèêïç]+)/e", "ucwords(strtolower('$1'))", $nameOK);
    $nameOK = htmlentities($nameOK);
    return($nameOK);
  }

  function normORA($data) {
    // Normalisation ORACLE '=> ''
    $data = str_ireplace("'", "''", $data);

    return($data);
  }

  /**
   * Conversion de DATE du format YYYYMMDD vers le format DD$sepMM$sepYYYY
   * @param string $date Date à convertir
   * @param string $sep Le séparateur de champs jour,mois, année à utiliser (défaut = "/")
   * @param bool $showOrig Afficher la date originale entre parenthèse (true:false defaut = false)
   * */
  function convBirthDate($date, $sep = "/", $showOrig = false) {
    if (strlen($date) >= 6) {
      $dateOK = preg_replace("/(\d{4})(\d{2})(\d{2})/", "$3" . $sep . "$2" . $sep . "$1" . (($showOrig == true) ? "  ('" . $date . "')" : ""), $date);
    }
    return($dateOK);
  }

 /**
   * Conversion de DATE du format 01/12/2013 22:10:59 vers un timestamp
   * @param string $date la date à convertir
   * @return int timestamp
   */
  function cnvDateTime2TimeStamp($date, $default = false) {
    // 01/12/2013 22:10:59 => timestamp
    $time = $default;
    $date_ar = preg_split("/[\/ :]/", $date);
    if (count($date_ar) < 3) { // cas $date="01/12/2013"
      $date_ar[3] = 0; //H
      $date_ar[4] = 0; //M
      $date_ar[5] = 0; //s
    }
    if (count($date_ar) == 5)
      $date_ar[5] = 0; //s

    if (count($date_ar) > 3) {
      $time = mktime($date_ar[3], $date_ar[4], $date_ar[5], $date_ar[1], $date_ar[0], $date_ar[2]);
    }

    return ($time);
  }
  
/**
 * Conversion de DATE du format 01/12/2013 22:10:59 vers un timestamp
 * @param string $date la date à convertir
 * @return int timestamp
 */
function cnvDateTime2TimeStamp2($date) {
  // 01/12/2013 22:10:59 => timestamp
  $date_ar = preg_split("[\/ :]", $date);
  if (count($date_ar) <= 3) { // cas $date="01/12/2013"
    $date_ar[3] = 0; //H
    $date_ar[4] = 0; //M
    $date_ar[5] = 0; //s
  }

  return mktime($date_ar[3], $date_ar[4], $date_ar[5], $date_ar[1], $date_ar[0], $date_ar[2]);
}

}
?>
