GROUPIE V2
Gestionnaire de groupes
==========================================================================

Groupie est un logiciel de gestion de groupes.
Il se compose d'une interface web développée sous Symfony et de plusieurs scripts effectuant des opérations sur le LDAP.
Groupie permet de gérer 2 types de groupes
- Groupes institutionnels
  Ce sont les groupes créés par l'administrateur de Groupie. La gestion des membres s'effectue soit :
    par un ou plusieurs administrateurs qui peuvent ajouter/supprimer des membres ou administrateurs. Par exemple, un groupe pour les administrateurs d'une application.
    par alimentation automatique à partir d'un filtre LDAP. Par exemple, un groupe pour les membres d'un service.
    par alimentation automatique à partir d'une table d'une base de données. Par exemple, un groupe pour les utilisateurs d'une application (exemple Apogée).
  L'utilisateur de Groupie peut visualiser les groupes dont il est membre.
  Si l'utilisateur est administrateur de Groupie, il peut visualiser les groupes qu'il gère et accéder aux fonctions d'ajout/suppression de membres.
  Si l'utilisateur a un rôle DOSI, il peut visualiser tous les groupes.
- Groupes privés
  Ce sont des groupes créés et gérés par l'utilisateur. Ils sont préfixés par "amu:perso". Chaque utilisateur peut :
    créer un ou plusieurs groupes privés, dans la limite de 20 groupes maximum par utilisateur
    supprimer ses groupes privés
    ajouter des membres dans ses groupes
    supprimer des membres dans ses groupes

==========================================================================

Au niveau LDAP

- Création d'une branche ou=groups dans le LDAP
- Dans cette branche, création de ou=private pour gérer les groues privés
- Plusieurs attributs ont été ajoutés au niveau des groupes :
    amuGroupFilter : filtre LDAP si le groupe est alimenté automatiquement
    amuGroupAdmin : dn du ou des administrateurs du groupe
- Scripts d'alimentation qui tournent régulièrement sont sur la machine LDAP
    SyncAllGroups.pl : met à jour les groupes alimentés avec des filtres LDAP.
    SyncADGroups.pl : met à jour les groupes dans l'AD.
    bidule.pl : met à jour les groupes alimentés à partir d'une table d'une base de données.

NB: Le nommage peut être changé et est paramétrable dans l'application.

==========================================================================

Au niveau de l'interface
--------------------------------------------------------------------------
Les rôles
On identifie 5 rôles pour l'application :

    - ROLE_MEMBRE : C'est le rôle de base. L'utilisateur est seulement membre d'un ou de groupes. Il a seulement accès à la visualisation des groupes dont il fait partie.
    filtre LDAP : "(|(edupersonaffiliation=employee)(edupersonaffiliation=faculty)(edupersonaffiliation=researcher))"
    - ROLE_GESTIONNAIRE : l'utilisateur est administrateur d'un ou de groupes. Il a accès en visualisation aux groupes dont il fait partie, et il peut modifier les membres des groupes qu'il gère.
    filtre LDAP : "(memberof=cn=amu:app:grp:grouper:grouper-ent,ou=groups,dc=univ-amu,dc=fr)"
    - ROLE_DOSI : l'utilisateur est membre de la DOSI, il accède en visualisation à toutes les infos des groupes.
    filtre LDAP : "(memberof=cn=amu:svc:dosi:tous,ou=groups,dc=univ-amu,dc=fr)"
    - ROLE_ADMIN : l'utilisateur a tous les droits sur tous les groupes, ainsi que les droits de création/modification/suppression de groupes.
    filtre LDAP : "(memberof=cn=amu:adm:app:groupie,ou=groups,dc=univ-amu,dc=fr)"
    - ROLE_SUPER_ADMIN : partie développeur

NB: Le nom des groupes peut être changé et est paramétrable dans l'application.

--------------------------------------------------------------------------
Les vues
On dispose de 5 onglets et de plusieurs sous-menus :
    Groupes institutionnels :
        Dont je suis membre
        Dont je suis administrateur
        Voir tous les groupes

    Recherche :
        Rechercher un groupe
        Rechercher une personne

    Groupes privés :
        Dont je suis membre
        Dont je suis administrateur
        Tous les groupes privés

    Gestion des groupes
        Créer un groupe
        Supprimer un groupe

    Aide
        Aide groupes institutionnels
        Aide groupes privés

--------------------------------------------------------------------------
Le paramétrage
Le paramétrage s'effectue dans les fichiers de config dans ASF2\app\config
    - config.yml : La partie à configurer concerne le ldap et groupie
        amu_ldap:
            default_profil: default
            profils:
                default:
                    servers:
                        primary:
                            host: ldap.univ.fr                          Serveur LDAP
                            port: 389                                   Port de connexion
                    relative_dn: cn=adm,ou=system,dc=univ,dc=fr         Dn de l'utilisateur qui se connecte au LDAP
                    password: pwd                                       Mot de passe de l'utilisateur
                    network_timeout: 3                                  Paramètres LDAP
                    protocol_version: 3
                    base_dn: dc=univ,dc=fr                              Base

        amu_groupie:
            logs:
                facility: LOG_LOCAL0                                    Destination du syslog
            users:
                people_branch: ou=people                                Branche people du LDA
                uid: uid                                                Attributs LDAP
                name: sn
                givenname: givenname
                displayname: cn
                mail: mail
                tel: telephonenumber
                comp: amucomposante
                aff: supannentiteaffectation
                primaff: supannentiteaffectationprincipale
                campus: amucampus
                site: amusite
                filter: (&(!(edupersonprimaryaffiliation=student))(!(edupersonprimaryaffiliation=alum))(!(edupersonprimaryaffiliation=oldemployee))(amudatevalidation=*))
                                                                        Filtre pour récupérer les personnes ayant accès à Groupie (dans notre cas, les personnels ayant validé leur compte)
            groups:
                object_class: groupOfNames                              Object class pour les groupes du lDAP
                group_branch: ou=groups                                 Branche des groupes
                cn: cn                                                  Attributs LDAP des groupes
                desc: description
                member: member
                memberof: memberof
                groupfilter: amugroupfilter
                groupadmin: amugroupadmin
            private:
                private_branch: ou=private                              Branche pour les groupes privés
                prefix: amu:perso                                       Préfixe systématique des groupes privés


