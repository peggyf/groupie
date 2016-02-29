<?php
// La séquence de base avec LDAP est 
// connexion, liaison, recherche, interprétation du résultat
// déconnexion

echo '<h3>requête de test de LDAP</h3>';

$ds=ldap_connect("ldap1.univ-amu.fr");  // doit être un serveur LDAP valide !
echo 'Le résultat de connexion est ' . $ds . '<br />';

ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

if ($ds) { 

    $username = 'cn=annuaire,ou=system,dc=univ-amu,dc=fr';
    $upasswd = '$annuaire-2011!';
    
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
}
?>

