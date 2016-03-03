<?php

namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends ContainerAware
{
    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('Accueil');
        $menu->addChild('Recherche', array('roles' => ["ROLE_GESTIONNAIRE", "ROLE_DOSI", "ROLE_ADMIN"]));
        $menu->addChild('Groupes privés', array('roles' => ["ROLE_MEMBRE"]));
        $menu->addChild('Gestion des groupes', array('roles' => ["ROLE_ADMIN"]));
        $menu->addChild('Aide', array('roles' => ["ROLE_GESTIONNAIRE", "ROLE_DOSI", "ROLE_ADMIN"]));
    
        // Sous-menus pour Accueil
        $menu['Accueil']->addChild('Voir mes appartenances', array('route' => 'mes_appartenances', 'roles' => ["ROLE_MEMBRE", "ROLE_GESTIONNAIRE", "ROLE_ADMIN"]));
        $menu['Accueil']->addChild('Gérer mes groupes', array('route' => 'mes_groupes', 'roles' => ["ROLE_GESTIONNAIRE", "ROLE_ADMIN"]));
        $menu['Accueil']->addChild('Voir tous les groupes', array('route' => 'tous_les_groupes', 'roles' => ["ROLE_DOSI", "ROLE_ADMIN"]));
                
        // Sous-menus pour Recherche
        $menu['Recherche']->addChild('Rechercher un groupe', array('route' => 'group_search', 'roles' => ["ROLE_GESTIONNAIRE", "ROLE_DOSI", "ROLE_ADMIN"]));
        $menu['Recherche']->addChild('Rechercher une personne', array('route' => 'homepage', 'roles' => ["ROLE_GESTIONNAIRE", "ROLE_DOSI", "ROLE_ADMIN"]));
        
        // Sous-menus pour Groupes privés
        $menu['Groupes privés']->addChild('Voir mes appartenances', array('route' => 'homepage', 'roles' => ["ROLE_MEMBRE"]));
        $menu['Groupes privés']->addChild('Gérer mes groupes', array('route' => 'homepage', 'roles' => ["ROLE_MEMBRE"]));
        $menu['Groupes privés']->addChild('Tous les groupes (DOSI)', array('route' => 'tous_les_groupes_prives', 'roles' => ["ROLE_DOSI"]));

        // Sous-menus pour Gestion des groupes
        $menu['Gestion des groupes']->addChild('Créer un groupe', array('route' => 'homepage', 'roles' => ["ROLE_ADMIN"]));
        $menu['Gestion des groupes']->addChild('Supprimer un groupe', array('route' => 'homepage', 'roles' => ["ROLE_ADMIN"]));
        
        // Sous-menus pour Aide
        $menu['Aide']->addChild('Aide groupes institutionnels', array('route' => 'homepage', 'roles' => ["ROLE_GESTIONNAIRE", "ROLE_DOSI", "ROLE_ADMIN"]));
        $menu['Aide']->addChild('Aide groupes privés', array('route' => 'homepage', 'roles' => ["ROLE_MEMBRE", "ROLE_GESTIONNAIRE", "ROLE_DOSI", "ROLE_ADMIN"]));

        return $menu;
    }
}
