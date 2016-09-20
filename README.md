Gestionnaire de groupes GROUPIE 
===============================
Groupie est un logiciel de gestion de groupes.

Il se compose d'une interface web développée sous Symfony et de plusieurs scripts effectuant des opérations sur le LDAP.


Groupie permet de gérer 2 types de groupes :

- Groupes institutionnels
Ce sont les groupes créés par l'administrateur de Groupie. La gestion des membres s'effectue soit :
    - par un ou plusieurs administrateurs qui peuvent ajouter/supprimer des membres ou administrateurs. Par exemple, un groupe pour les administrateurs d'une application.
    - par alimentation automatique à partir d'un filtre LDAP. Par exemple, un groupe pour les membres d'un service.
    - par alimentation automatique à partir d'une table d'une base de données. Par exemple, un groupe pour les utilisateurs d'une application (exemple Apogée).
    
L'utilisateur de Groupie peut visualiser les groupes dont il est membre.

Si l'utilisateur est administrateur de Groupie, il peut visualiser les groupes qu'il gère et accéder aux fonctions d'ajout/suppression de membres.

Si l'utilisateur a un rôle DOSI, il peut visualiser tous les groupes.

- Groupes privés
Ce sont des groupes créés et gérés par l'utilisateur. Ils sont préfixés par "amu:perso". Chaque utilisateur peut :
    - créer un ou plusieurs groupes privés, dans la limite de 20 groupes maximum par utilisateur
    - supprimer ses groupes privés
    - ajouter des membres dans ses groupes
    - supprimer des membres dans ses groupes

Au niveau LDAP
==========================================================================
- Création d'une branche ou=groups dans le LDAP
- Dans cette branche, création de ou=private pour gérer les groupes privés
- Plusieurs attributs ont été ajoutés au niveau des groupes :
    - amuGroupFilter : filtre LDAP si le groupe est alimenté automatiquement
    - amuGroupAdmin : dn du ou des administrateurs du groupe
- Scripts d'alimentation qui tournent régulièrement sont sur la machine LDAP
    - SyncAllGroups.pl : met à jour les groupes alimentés par des filtres LDAP ou par une table d'une base Oracle.
    - SyncADGroups.pl : met à jour les groupes dans l'AD.

NB: Le nommage dans le LDAP peut être changé et est paramétrable dans l'application.

Au niveau de l'interface
==========================================================================
Les rôles
--------------------------------------------------------------------------
On identifie 6 rôles dans l'application :

- ROLE_MEMBRE : C'est le rôle de base. L'utilisateur est seulement membre d'un ou de groupes. Il a seulement accès à la visualisation des groupes dont il fait partie.
Appartenance au groupe LDAP : "amu:glob:ldap:personnel"
- ROLE_GESTIONNAIRE : l'utilisateur est administrateur d'un ou de groupes. Il a accès en visualisation aux groupes dont il fait partie, et il peut modifier les membres des groupes qu'il gère.
Appartenance au groupe LDAP : "amu:app:grp:grouper:grouper-ent"
- ROLE_DOSI : l'utilisateur est membre de la DOSI, il accède en visualisation à toutes les infos des groupes.
Appartenance au groupe LDAP : "amu:svc:dosi:tous"
- ROLE_PRIVE : l'utilisateur peut accéder à la partie "groupes privés". 
Appartenance au groupe LDAP : "amu:svc:dosi:tous"
- ROLE_ADMIN : l'utilisateur a tous les droits sur tous les groupes, ainsi que les droits de création/modification/suppression de groupes.
Appartenance au groupe LDAP : "amu:adm:app:groupie"
- ROLE_SUPER_ADMIN : partie développeur

NB: Les groupes sont paramétrables

Les vues
--------------------------------------------------------------------------
On dispose de 5 onglets et de plusieurs sous-menus :

* Groupes institutionnels :
    * Dont je suis membre
    * Dont je suis administrateur
    * Voir tous les groupes
* Recherche :
    * Rechercher un groupe
    * Rechercher une personne
* Groupes privés :
    * Dont je suis membre
    * Dont je suis administrateur
    * Tous les groupes privés
* Gestion des groupes
    * Créer un groupe
    * Supprimer un groupe
* Aide
    * Aide groupes institutionnels
    * Aide groupes privés

Le paramétrage
--------------------------------------------------------------------------
Le paramétrage s'effectue dans les fichiers de config dans ASF2\app\config

* config.yml : La partie à configurer concerne le ldap et groupie
    
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

* security_cas.yml : paramètrage du CAS et hiérarchie des rôles

        firewalls:
            dev:
                pattern:  ^/(_(profiler|wdt)|css|images|js)/
                security: false     secured_area:
            pattern:  /*
            anonymous: false
            provider: amu_users
            cas:
                cas_allow_anonymous: false
                cas_server: cas.univ.fr                                 URL du CAS
                cas_port: 443
                cas_path: /cas/
                ca_cert_path: ~
                cas_protocol: "2.0" #S1
                cas_mapping_attribute: uid
                check_path: /login_check
                cas_logout: /logout       
                login_path: /login_check
        role_hierarchy:
            ROLE_ADMIN: [ROLE_MEMBRE, ROLE_GESTIONNAIRE, ROLE_DOSI, ROLE_PRIVE]
            ROLE_DEVELOPER: [ROLE_USER,ROLE_ALLOWED_TO_SWITCH]

* roles.yml : Il faut configurer les groupes qui auront les différents droits dans l'application

        roles:
            - { name: "ROLE_MEMBRE",         type: "ldap",    link: "isMember",  values: "amu:glob:ldap:personnel" }
            - { name: "ROLE_GESTIONNAIRE",   type: "ldap",    link: "isMember",  values: "amu:app:grp:grouper:grouper-ent" } 
            - { name: "ROLE_DOSI",           type: "ldap",    link: "isMember",  values: "amu:svc:dosi:tous" }
            - { name: "ROLE_PRIVE",          type: "ldap",    link: "isMember",  values: "amu:svc:dosi:tous" }
            - { name: "ROLE_ADMIN",          type: "ldap",    link: "isMember",  values: "amu:adm:app:groupie" }
            - { name: "ROLE_SUPER_ADMIN",    type: "session",  link: "_isDevelopper",  values: "1" }

* charteConfig.yml : La partie à configurer concerne l'affichage de l'application

    parameters:
    
        amu_chartegraphique_title: "Groupie"
        amu_chartegraphique_auteur: "DOSI"
        amu_chartegraphique_apptitle: "Groupie"
        amu_chartegraphique_appslogan: "Gestion des groupes"
        amu_chartegraphique_appcontact: "AMU - DOSI Pôle Environnement Numérique"
        amu_chartegraphique_url_intranet: "http://intramu.univ-amu.fr"      Intranet de l'université
        amu_chartegraphique_nom_intranet: "intrAMU"
        amu_chartegraphique_url_ent: "http://ent.univ-amu.fr"               ENT
        amu_chartegraphique_url_annuaire: "http://annuaire.univ-amu.fr"     Annuaire
        amu_chartegraphique_url_site: "http://www.univ-amu.fr"              Site de l'université
        amu_chartegraphique_accueil: "Accueil Aix-Marseille Université"

Scripts PERL LDAP pour peupler des groupes basés sur des filtres (plus efficace que dynlist)
--------------------------------------------------------------------------------------------
Chez nous les scripts et autres définitions sont sous /var/ldap dans les répertoires etc cron et lib
Ils doivent s'exécuter sur un LDAP Maitre (lecture du slapd.conf de OpenLDAP et du password root (en clair))

* Dans etc:
	fichier hosts contient des définitions
* Dans lib:
	utils2.pm librairie pour lire /etc/openldap/slapd.conf (rootdn rootpw suffix..)
* Dans cron (modifier les quelques variables si besoin)  
	SyncAllGroups.pl synchronise les membres des groupes qui ont un attribut contenant un filtre de type LDAP ou SQL
	exemples de filtres dans l'attribut amuGroupfilter:
	* SQL: dbi:mysql:host=apogee.univ.fr;port=3306;database=fwa2|user|pass|SELECT * from V_USERS_APOGEE
	* LDAP: (&(amudatevalidation=*)(amuComposante=odontologie)(eduPersonAffiliation=faculty))
	
	SyncADGroups.pl synchronise la branche ou=groups LDAP avec une branche ou=groups Active Directory

Schéma LDAP
----------------------------------------------------------------------------------

		objectclass   ( 1.3.6.1.4.1.7135.1.1.2.2.7 NAME 'AMUGroup' SUP top AUXILIARY
			 DESC 'Groupes spécifiques AMU '
			 MAY ( amuGroupFilter $ amuGroupAdmin $ amuGroupAD $ amuGroupMember ))

		attributetype (  1.3.6.1.4.1.7135.1.3.131.3.40 NAME 'amuGroupAdmin'
			DESC 'RFC2256: admin of a group'
			SUP distinguishedName )

		attributetype ( 1.3.6.1.4.1.7135.1.3.131.3.41 NAME 'amuGroupFilter'
			DESC 'Filtre LDAP pour les groupes'
			 EQUALITY caseIgnoreMatch
			 SUBSTR caseIgnoreSubstringsMatch
			 SYNTAX 1.3.6.1.4.1.1466.115.121.1.15{256} )

		attributetype (  1.3.6.1.4.1.7135.1.3.131.3.45 NAME 'amuGroupMember'
			DESC 'manual member of a group in a group by filter using ldapadd'
			SUP distinguishedName )
			
		attributetype ( 1.3.6.1.4.1.7135.1.3.131.3.42 NAME 'amuGroupAD'
           DESC 'Export AD '
           EQUALITY booleanMatch
           SINGLE-VALUE
          SYNTAX 1.3.6.1.4.1.1466.115.121.1.7 )	

