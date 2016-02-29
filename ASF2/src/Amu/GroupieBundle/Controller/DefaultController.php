<?php

namespace Amu\GroupieBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/hello/{name}")
     * @Template()
     */
    public function indexAction($name)
    {
        $baseDN = $this->get('amu.ldap')->getBaseDN();
        echo "basedn : $baseDN";
        $resource = $this->get('amu.ldap')->connect();
        
        $result = $resource->search($baseDN, '(uid=pfernandezblanco)', array('uid', 'mail'));
        
        echo "Result ldapsearch : ".print_r($result, true);
        
/*    echo '<h3>requête de test de LDAP</h3>';

    $ds=ldap_connect("ldapmaitre-test.univ-amu.fr");  // doit être un serveur LDAP valide !
    echo 'Le résultat de connexion est ' . $ds . '<br />';

    ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

    if ($ds) { 

    $username = 'cn=grouper,ou=system,dc=univ-amu,dc=fr';
    $upasswd = 'Grouper2011Amu!';
    
    $r=ldap_bind($ds, $username, $upasswd);    
    
    echo 'Le résultat du bind est ' . $r . '<br />';
    
    $sr=ldap_search($ds,"ou=people,dc=univ-amu,dc=fr", "uid=pfernandezblanco");  
    echo 'Le résultat de la recherche est ' . $sr . '<br />';

    echo 'Le nombre d\'entrées retournées est ' . ldap_count_entries($ds,$sr) 
         . '<br />';

    echo 'Lecture des entrées ...<br />';
    $info = ldap_get_entries($ds, $sr);
    echo 'Données pour ' . $info["count"] . ' entrées:<br />';

    for ($i=0; $i<$info["count"]; $i++) {
        echo 'dn est : ' . $info[$i]["dn"] . '<br />';
        echo 'premiere entree cn : ' . $info[$i]["cn"][0] . '<br />';
        echo 'premier email : ' . $info[$i]["mail"][0] . '<br />';
    }

    echo 'Fermeture de la connexion';
    ldap_close($ds);

    } else {
    echo '<h4>Impossible de se connecter au serveur LDAP.</h4>';
    }*/
        return array('name' => $name);
    }
}
