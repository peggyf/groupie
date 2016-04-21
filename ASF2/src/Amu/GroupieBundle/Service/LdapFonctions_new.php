<?php
/**
 * Created by PhpStorm.
 * User: peggy_fernandez
 * Date: 29/02/2016
 * Time: 16:25
 */

namespace Amu\GroupieBundle\Service;



use Amu\LdapBundle\Ldap\Client;

class LdapFonctions extends Client
{

    public function recherche($filtre, $restriction, $tri)
    {
        // Connexion au LDAP
        $baseDN = $this->getBaseDN();
        $resource = $this->connect();
        // Recherche avec les filtres et restrictions demandés
        if ($resource) {
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

                        $a .= strtolower($arData[$j - 1][$tri][0]);
                        $b .= strtolower($index[$tri][0]);
                        if (strlen($a) > strlen($b))
                            $b .= str_repeat(" ", (strlen($a) - strlen($b)));
                        if (strlen($b) > strlen($a))
                            $a .= str_repeat(" ", (strlen($b) - strlen($a)));

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
        else
            return false;
    }

    /**
     * Récupération des infos d'un user
     */
    public function getInfosUser($uid) {
        $filtre = "uid=" . $uid;
        $restriction = array("uid", "displayName", "mail", "telephonenumber", "sn");
        $result = $this->recherche($filtre, $restriction, "uid");
        return $result;
    }

    /**
     * Récupération des membres d'un groupe + infos des membres
     */
    public function getMembersGroup($groupName) {
        $filtre = "(&(objectclass=*)(memberOf=cn=" . $groupName . ", ou=groups, dc=univ-amu, dc=fr))";
        $restriction = array("uid", "displayName", "mail", "telephonenumber", "sn");
        $result = $this->recherche($filtre, $restriction, "uid");
        return $result;
    }

    /**
     * Récupération des admins d'un groupe + infos des membres
     */
    public function getAdminsGroup($groupName) {
        $filtre = "cn=". $groupName ;
        $restriction = array("amuGroupAdmin");
        $result = $this->recherche($filtre, $restriction, "uid");
        return $result;
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

        // Connexion au LDAP
        $baseDN = $this->getBaseDN();
        $resource = $this->connect();

        if ($resource) {
            $sr = ldap_mod_add($this->link, $dn_group, $groupinfo);

            if(ldap_error($this->link) == "Success")
                return true;
            else
                return false;
        }

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
        // Connexion au LDAP
        $baseDN = $this->ldap->getBaseDN();
        $resource = $this->ldap->connect();

        if ($resource) {
            $sr = ldap_mod_del($this->ldap->link, $dn_group, $groupinfo);
            //echo "<hr>DEBUG " . __CLASS__ . "::" . __FUNCTION__ . " Infos groupe <PRE>" . print_r($groupinfo, true) . "</PRE>";
            if(ldap_error($this->ldap->link) == "Success")
                return true;
            else
                return false;
        }

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
        // Connexion au LDAP
        $baseDN = $this->ldap->getBaseDN();
        $resource = $this->ldap->connect();
        if ($resource) {
            $sr = ldap_mod_add($this->ldap->link, $dn_group, $groupinfo);
            if(ldap_error($this->ldap->link) == "Success")
                return true;
            else
                return false;
        }

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
        // Connexion au LDAP
        $baseDN = $this->ldap->getBaseDN();
        $resource = $this->ldap->connect();
        if ($resource) {
            $sr = ldap_mod_del($this->ldap->link, $dn_group, $groupinfo);
            if(ldap_error($this->ldap->link) == "Success")
                return true;
            else
                return false;
        }

        return false;
    }

    /**
     * Supprimer le amugroupfilter d'un groupe
     * @return  \Amu\AppBundle\Service\Ldap
     */
    public function delAmuGroupFilter($dn_group, $filter) {

        $groupinfo['amuGroupFilter'] = $filter;

        // Connexion au LDAP
        $baseDN = $this->ldap->getBaseDN();
        $resource = $this->ldap->connect();
        if ($this->r) {
            $sr = ldap_mod_del($this->ldap->link, $dn_group, $groupinfo);
            if(ldap_error($this->ldap->link) == "Success")
                return true;
            else
                return false;
        }

        return false;
    }

    /**
     * Récupérer le amugroupfilter d'un groupe
     * @return  \Amu\AppBundle\Service\Ldap
     */
    public function getAmuGroupFilter($cn_group) {

        $filtre = "cn=" . $cn_group;
        $result = $this->recherche($filtre, array("amugroupfilter"), "cn");
        return $result;
    }

    public function createGroupeLdap($dn, $groupeinfo)
    {
        // Connexion au LDAP
        $baseDN = $this->ldap->getBaseDN();
        $resource = $this->ldap->connect();
        // Recherche avec les filtres et restrictions demandés
        if ($resource) {
           $res = $resource->add($dn, $groupeinfo);
           return($res);
        }

        return(false);

    }

    public function deleteGroupeLdap($cn)
    {
        $dn = "cn=".$cn.",ou=groups,dc=univ-amu,dc=fr";
        // Connexion au LDAP
        $baseDN = $this->ldap->getBaseDN();
        $resource = $this->ldap->connect();
        // Recherche avec les filtres et restrictions demandés
        if ($resource) {
            $res = $resource->delete($dn);
            return($res);
        }

        return(false);

    }

    public function getUidFromMail($mail, $restriction = array("uid", "displayName", "sn", "mail", "telephonenumber", "memberof")) {
        $filtre = "(mail=" . $mail . ")";
        $AllInfos = array();
        $AllInfos = $this->recherche($filtre, $restriction, "mail");

        return $AllInfos;
    }

    public function TestUid($uid, $restriction = array("uid", "sn", "displayName", "mail", "telephonenumber", "memberof")) {
        $filtre = "(uid=" . $uid . ")";
        $AllInfos = array();
        $AllInfos = $this->recherche($filtre, $restriction, "uid");

        return $AllInfos;
    }
}