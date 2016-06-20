<?php

namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends ContainerAware
{
    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        // ONGLET ACCUEIL visible pour tout le personnel
        if ((true === $this->container->get('security.authorization_checker')->isGranted('ROLE_MEMBRE'))) {
            $menu->addChild('Groupes institutionnels', array('route' => 'homepage'));
            // Sous-menus pour Accueil
            $menu['Groupes institutionnels']->addChild('Dont je suis membre', array('route' => 'memberships'));
            if ((true === $this->container->get('security.authorization_checker')->isGranted('ROLE_GESTIONNAIRE')) ||
                (true === $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
            ) {
                $menu['Groupes institutionnels']->addChild('Dont je suis administrateur', array('route' => 'my_groups'));
            }
            if ((true === $this->container->get('security.authorization_checker')->isGranted('ROLE_DOSI')) ||
                (true === $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
            ) {
                $menu['Groupes institutionnels']->addChild('Voir tous les groupes', array('route' => 'all_groups', 'roles' => ["ROLE_DOSI", "ROLE_ADMIN"]));
            }
        }

        // ONGLET RECHERCHE
        if ((true === $this->container->get('security.authorization_checker')->isGranted('ROLE_GESTIONNAIRE'))||
            (true === $this->container->get('security.authorization_checker')->isGranted('ROLE_DOSI'))||
            (true === $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))) {
            $menu->addChild('Recherche');
            // Sous-menus pour Recherche
            $menu['Recherche']->addChild('Rechercher un groupe', array('route' => 'group_search'));
            $menu['Recherche']->addChild('Rechercher une personne', array('route' => 'user_search'));
        }

        // ONGLET GROUPES PRIVES visible pour tout le personnel
        if ((true === $this->container->get('security.authorization_checker')->isGranted('ROLE_PRIVE'))) {
            $menu->addChild('Groupes privés');
            // Sous-menus pour Groupes privés
            $menu['Groupes privés']->addChild('Dont je suis membre', array('route' => 'private_memberships'));
            $menu['Groupes privés']->addChild('Dont je suis administrateur', array('route' => 'private_group'));
            if ((true === $this->container->get('security.authorization_checker')->isGranted('ROLE_DOSI'))) {
                $menu['Groupes privés']->addChild('Tous les groupes (DOSI)', array('route' => 'all_private_groups'));
            }
        }

        // ONGLET GESTION DES GROUPES pour les administrateurs
        if ((true === $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))) {
            $menu->addChild('Gestion des groupes');
            // Sous-menus pour Gestion des groupes
            $menu['Gestion des groupes']->addChild('Créer un groupe', array('route' => 'group_create'));
            $menu['Gestion des groupes']->addChild('Supprimer un groupe', array('route' => 'group_search_del'));
        }

        // ONGLET AIDE pour tout le personnel
        if ((true === $this->container->get('security.authorization_checker')->isGranted('ROLE_MEMBRE'))) {
            $menu->addChild('Aide');
            // Sous-menus pour Aide
            if ((true === $this->container->get('security.authorization_checker')->isGranted('ROLE_GESTIONNAIRE'))||
                (true === $this->container->get('security.authorization_checker')->isGranted('ROLE_DOSI'))||
                (true === $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))) {
                $menu['Aide']->addChild('Aide groupes institutionnels', array('route' => 'help'));
            }
            $menu['Aide']->addChild('Aide groupes privés', array('route' => 'private_help'));
        }

        return $menu;
    }
}
