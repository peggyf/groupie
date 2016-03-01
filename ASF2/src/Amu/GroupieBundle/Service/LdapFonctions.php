<?php
/**
 * Created by PhpStorm.
 * User: peggy_fernandez
 * Date: 29/02/2016
 * Time: 16:25
 */

namespace Amu\GroupieBundle\Service;



class LdapFonctions
{
    private $ldap;

    public function setLdap($ldap)
    {
        $this->ldap = $ldap;
    }

    public function recherche($filtre, $restriction)
    {
        // Connexion au LDAP
        $baseDN = $this->ldap->getBaseDN();
        $resource = $this->ldap->connect();
        // Recherche avec les filtres et restrictions demandés
        $arData = $resource->search($baseDN, $filtre, $restriction);

        // Tri des résultats
        if ($arData['count'] > 1) {
            for ($i = 0; $i < $arData['count']; $i++) {
                $index = $arData[$i];
                $j = $i;
                $is_greater = true;
                while ($j > 0 && $is_greater) {
                    //create comparison variables from attributes:
                    $a = $b = null;

                    $a .= strtolower($arData[$j - 1]["cn"][0]);
                    $b .= strtolower($index["cn"][0]);
                    if (strlen($a) > strlen($b))
                        $b .=str_repeat(" ", (strlen($a) - strlen($b)));
                    if (strlen($b) > strlen($a))
                        $a .=str_repeat(" ", (strlen($b) - strlen($a)));

                    // do the comparison
                    if ($a > $b) {
                        $is_greater = true;
                        $arData[$j] = $arData[$j - 1];
                        $j = $j - 1;
                    } else {
                        $is_greater = false;
                    }
                }

                $arData[$j] = $index;
            }
        }
        return $arData;
    }
}