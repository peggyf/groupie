<?php

// La séquence de base avec LDAP est
// connexion, liaison, recherche, interprétation du résultat
// déconnexion

echo '<h3>requête de test de LDAP</h3>';
echo 'Connexion ...';
$ds=ldap_connect("ldap1.univ-amu.fr", 389);  // doit être un serveur LDAP valide !
echo 'Le résultat de connexion est ' . $ds . '<br />';

if ($ds) {
    echo 'Liaison ...';
    $r=ldap_bind($ds, "cn=ipro,ou=system,dc=univ-amu,dc=fr", "c1pro2stag$");     // connexion anonyme, typique
    echo 'Le résultat de connexion est ' . $r . '<br />';

    // Recherche b007
    $sr=ldap_search($ds, "dc=univ-amu,dc=fr", "uid=b007");
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